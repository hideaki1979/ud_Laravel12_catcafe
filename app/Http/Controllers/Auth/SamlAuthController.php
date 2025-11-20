<?php

namespace App\Http\Controllers\Auth;

use Aacotroneo\Saml2\Saml2Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Laravel\Prompts\warning;

class SamlAuthController extends Controller
{
    /**
     * Keycloak SAML ログインページへリダイレクト
     */
    public function login()
    {
        $saml2Auth = app(Saml2Auth::class);

        // 認証成功後のリダイレクト先を指定
        $returnTo = route('admin.blogs.index');

        return $saml2Auth->login($returnTo);
    }

    /**
     * Assertion Consumer Service (ACS) - SAML レスポンスを受信
     * Keycloak からの認証結果を処理してユーザーを作成/更新
     */
    public function acs(Saml2Auth $saml2Auth): RedirectResponse
    {
        try {
            // SAML レスポンスを処理
            $errors = $saml2Auth->acs();

            if (!empty($errors)) {
                Log::error('SAML ACS エラー', ['error' => $errors]);
                return redirect()->route('admin.login')
                    ->with('error', 'SAML認証に失敗しました。');
            }

            // SAML ユーザー情報を取得
            $samlUser = $saml2Auth->getSaml2User();

            // ユーザー属性を取得
            $attributes = $samlUser->getAttributes();
            $nameId = $samlUser->getNameId();

            // SAML ID（Keycloak のユニーク ID）- persistent format
            $samlId = $nameId;  // persistent format の場合、NameID が一意の識別子

            // メールアドレスの取得（オプション）
            $email = $this->getEmailFromAttributes($attributes);

            // メールアドレスが取得できない場合は、NameID からダミーメールを生成
            if (empty($email)) {
                // NameID をベースにしたダミーメールアドレスを生成
                // ユニークで安全な形式にする
                $email = 'saml_' . md5($samlId) . '@lanekocafe.local';
                Log:
                warning('SAML認証: メールアドレスが取得できなかったため、ダミーメールを生成しました', [
                    'attributes' => $attributes,
                    'nameId' => $nameId,
                    'samlId' => $samlId,
                    'generated_email' => $email,
                ]);

                // return redirect()->route('admin.login')
                //     ->with('error', 'ユーザー情報の取得に失敗しました。メールアドレスが見つかりません。');
            }

            // 名前の取得
            $name = $this->getNameFromAttributes($attributes);

            // ユーザーの作成または更新
            $user = $this->findOrCreateUser($samlId, $email, $name, $attributes);

            // ログイン処理
            Auth::login($user);

            Log::info('SAML認証成功', [
                'user_id' => $user->id,
                'email' => $user->email,
                'saml_id' => $samlId
            ]);

            // ログイン後のリダイレクト先
            $redirectUrl = $samlUser->getIntendedUrl() ?? route('admin.blogs.index');

            return redirect($redirectUrl)
                ->with('success', 'Keycloakでログインしました。');
        } catch (\Exception $e) {
            Log::error('SAML ACS 例外', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.login')
                ->with('error', 'SAML認証処理中にエラーが発生しました。');
        }
    }

    /**
     * シングルログアウト (SLO)
     */
    public function logout()
    {
        $saml2Auth = app(Saml2Auth::class);

        // ローカルセッションからログアウト
        Auth::logout();

        // Keycloak からもログアウト
        $returnTo = route('admin.login');

        return $saml2Auth->logout($returnTo);
    }

    /**
     * SAML Single Logout Service (SLS) - IdP からのログアウトリクエストを処理
     */
    public function sls(Saml2Auth $saml2Auth): RedirectResponse
    {
        try {
            $retrieveParametersFromServer = config('saml2_settings.retrieveParametersFromServer');
            $errors = $saml2Auth->sls($retrieveParametersFromServer);

            if (!empty($errors)) {
                Log::error('SAML SLS エラー', ['errors' => $errors]);
            }

            // ローカルセッションからログアウト
            Auth::logout();

            return redirect()->route('admin.login')
                ->with('success', 'ログアウトしました。');
        } catch (\Exception $e) {
            Log::error('SAML SLS 例外', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.login');
        }
    }

    /**
     * SAML メタデータを返す
     * IdP（Keycloak）がSPの情報を取得するために使用
     */
    public function metadata(Saml2Auth $saml2Auth)
    {
        $metadata = $saml2Auth->getMetadata();

        return response($metadata, 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * SAML 属性からメールアドレスを取得
     * Keycloak の標準的な属性名に対応
     */
    protected function getEmailFromAttributes(array $attributes): ?string
    {
        // Keycloak の標準的な属性名（優先順位順）
        $emailKeys = [
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
            'email',
            'mail',
            'emailAddress',
        ];

        foreach ($emailKeys as $key) {
            if (isset($attributes[$key]) && !empty($attributes[$key])) {
                $value = is_array($attributes[$key]) ? $attributes[$key][0] : $attributes[$key];

                // メールアドレス形式の検証
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * SAML 属性から名前を取得
     * Keycloak の標準的な属性名に対応
     */
    protected function getNameFromAttributes(array $attributes): string
    {
        // 表示名の取得を試みる
        $displayNameKeys = [
            'http://schemas.microsoft.com/identity/claims/displayname',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
            'displayName',
            'name',
        ];

        foreach ($displayNameKeys as $key) {
            if (isset($attributes[$key]) && !empty($attributes[$key])) {
                $value = is_array($attributes[$key]) ? $attributes[$key][0] : $attributes[$key];

                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $value;
                }
            }
        }

        // 名と姓から組み立てる
        $givenNameKeys = [
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
            'givenName',
            'firstName',
        ];

        $surnameKeys = [
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
            'surname',
            'lastName',
        ];

        $givenName = '';
        $surname = '';

        foreach ($givenNameKeys as $key) {
            if (isset($attributes[$key]) && !empty($attributes[$key])) {
                $givenName = is_array($attributes[$key]) ? $attributes[$key][0] : $attributes[$key];
                break;
            }
        }

        foreach ($surnameKeys as $key) {
            if (isset($attributes[$key]) && !empty($attributes[$key])) {
                $surname = is_array($attributes[$key]) ? $attributes[$key][0] : $attributes[$key];
                break;
            }
        }

        if (!empty($givenName) || !empty($surname)) {
            return trim($surname . ' ' . $givenName);
        }

        // デフォルト名
        return 'Keycloak User';
    }

    /**
     * ユーザーを検索または作成
     * ベストプラクティス: SAML ID による一意の識別を優先
     */
    protected function findOrCreateUser(string $samlId, string $email, string $name, array $attributes): User
    {
        // 1. SAML ID でユーザーを検索（最優先）
        $user = User::where('saml_id', $samlId)->first();

        if ($user) {
            // 既存ユーザーの情報を更新（メールアドレスや名前が変更されている可能性）
            $user->update([
                'name' => $name,
                'email' => $email,
            ]);

            Log::info('SAML認証：既存ユーザーを更新', [
                'user_id' => $user->id,
                'saml_id' => $samlId,
            ]);

            return $user;
        }

        // 2. メールアドレスでユーザーを検索（既存ユーザーとの紐付け）
        $user = User::where('email', $email)->first();

        if ($user) {
            // 既存ユーザーに SAML ID を追加
            $user->update([
                'saml_id' => $samlId,
                'name' => $name,
            ]);

            Log::info('SAML認証: 既存ユーザーにSAML IDを追加', [
                'user_id' => $user->id,
                'saml_id' => $samlId
            ]);

            return $user;
        }

        // 3. 新規ユーザーを作成
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'saml_id' => $samlId,
            'password' => bcrypt(Str::random(32)),
            'image' => '', // デフォルト画像なし
            'introduction' => '', // デフォルト自己紹介なし
        ]);

        Log::info('SAML認証: 新規ユーザーを作成', [
            'user_id' => $user->id,
            'saml_id' => $samlId,
            'email' => $email,
        ]);

        return $user;
    }
}
