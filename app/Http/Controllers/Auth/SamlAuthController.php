<?php

namespace App\Http\Controllers\Auth;

use Aacotroneo\Saml2\Saml2Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SamlAuthController extends Controller
{
    /**
     * Keycloak SAML ログインページへリダイレクト
     */
    public function login()
    {
        $saml2Auth = app(Saml2Auth::class);

        // 認証成功後のリダイレクト先を指定
        $returnTo = route('admin.dashboard');

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

            // メールアドレスの取得
            $email = $this->getEmailFromAttributes($attributes);

            // メールアドレスが取得できない場合の処理
            if (empty($email)) {
                // 本番環境ではメールアドレスを必須とする（推奨）
                if (config('saml2.require_email', env('APP_ENV') === 'production')) {
                    Log::error('SAML認証失敗: メールアドレスが必須です', [
                        'attributes' => $attributes,
                        'nameId' => $nameId,
                        'samlId' => $samlId,
                        'environment' => env('APP_ENV'),
                    ]);

                    return redirect()->route('admin.login')
                        ->with('error', 'ユーザー情報の取得に失敗しました。メールアドレスが見つかりません。管理者に連絡してください。');
                }

                // 開発環境のみ: ダミーメールアドレスを生成
                // 注意: 本番環境では使用しないでください
                $email = $this->generateDummyEmail($samlId);

                Log::warning('SAML認証: メールアドレスが取得できなかったため、ダミーメールを生成しました（開発環境のみ）', [
                    'attributes' => $attributes,
                    'nameId' => $nameId,
                    'samlId' => $samlId,
                    'generated_email' => $email,
                    'environment' => env('APP_ENV'),
                    'warning' => '本番環境では Keycloak 側でメールアドレスを必須に設定してください',
                ]);
            }

            // 名前の取得
            $name = $this->getNameFromAttributes($attributes);

            // ユーザーの作成または更新
            $user = $this->findOrCreateUser($samlId, $email, $name, $attributes);

            // ログイン処理
            Auth::login($user);

            // セッション固定攻撃対策(セッションID再生成)
            request()->session()->regenerate();

            Log::info('SAML認証成功', [
                'user_id' => $user->id,
                'email' => $user->email,
                'saml_id' => $samlId
            ]);

            // ログイン後のリダイレクト先
            $redirectUrl = $samlUser->getIntendedUrl() ?? route('admin.dashboard');

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
     *
     * 重要: aacotroneo/laravel-saml2 パッケージ（OneLogin PHP SAML）は
     * HTTP_REDIRECT Binding（$_GET）のみをネイティブサポートしています。
     * しかし、KeycloakはFront channel logout = ON でも HTTP_POST Binding で
     * SAMLRequestを送信する場合があります。
     *
     * このメソッドでは、POSTリクエストのBodyからSAMLRequest/SAMLResponseを取得し、
     * $_GETに設定することで、OneLoginライブラリが正しく処理できるようにしています。
     *
     *
     * 参考: https://github.com/aacotroneo/laravel-saml2
     *
     * @param Saml2Auth $saml2Auth
     * @param string $idpName IdP名（ルートパラメータから自動注入）
     */
    public function sls(Saml2Auth $saml2Auth, string $idpName = 'keycloak'): RedirectResponse
    {
        try {

            // OneLogin PHP SAMLライブラリは $_GET からSAMLRequest/SAMLResponseを取得する
            // KeycloakがPOSTでリクエストを送信する場合、$_GETに値を設定する必要がある
            if (request()->isMethod('POST')) {
                // POSTリクエストの場合、BodyからSAMLパラメータを取得して$_GETに設定
                if (request()->has('SAMLRequest') && !isset($_GET['SAMLRequest'])) {
                    $_GET['SAMLRequest'] = request()->input('SAMLRequest');
                }

                if (request()->has('SAMLResponse') && !isset($_GET['SAMLResponse'])) {
                    $_GET['SAMLResponse'] = request()->input('SAMLResponse');
                }

                if (request()->has('RelayState') && !isset($_GET['RelayState'])) {
                    $_GET['RelayState'] = request()->input('RelayState');
                }
            }

            $retrieveParametersFromServer = config('saml2_settings.retrieveParametersFromServer');
            // パッケージの sls() メソッドを呼び出し
            // 内部で Saml2LogoutEvent が発火され、AppServiceProvider のリスナーで
            // Auth::logout() と Session::save() が実行される
            $errors = $saml2Auth->sls($idpName, $retrieveParametersFromServer);

            if (!empty($errors)) {
                Log::error('SAML SLS エラー', ['errors' => $errors]);
                // エラーがあっても、セッションはクリアしてリダイレクト
                // （ログアウトフローを妨げない）
                $this->clearLocalSession();
            }

            // ログアウト後のリダイレクト先
            return redirect(config('saml2_settings.logoutRoute'));
        } catch (\Exception $e) {
            Log::error('SAML SLS 例外', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // 例外発生時もローカルセッションをクリアしてリダイレクト
            $this->clearLocalSession();
            return redirect(config('saml2_settings.logoutRoute'))
                ->with('error', 'ログアウト処理中にエラーが発生しました。');
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
     *
     * @param string $samlId SAML ID（一意識別子）
     * @param string $email メールアドレス
     * @param string $name 名前
     * @param array $attributes SAML属性（将来の拡張用: ロール、グループ、カスタム属性など）
     * @return User
     */
    protected function findOrCreateUser(string $samlId, string $email, string $name, array $attributes): User
    {
        return DB::transaction(function () use ($samlId, $email, $name, $attributes) {
            // 1. SAML ID でユーザーを検索（最優先）
            $user = User::where('saml_id', $samlId)->lockForUpdate()->first();

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
            $user = User::where('email', $email)->lockForUpdate()->first();

            if ($user) {
                // 既にSAML IDが設定されており、かつ今回のSAML IDと異なる場合はエラー
                if (!empty($user->saml_id) && $user->saml_id !== $samlId) {
                    Log::warning('SAML認証: メールアドレスが既に他のSAMLアカウントに紐付いています。', [
                        'email' => $email,
                        'existing_saml_id' => $user->saml_id,
                        'new_saml_id' => $samlId,
                    ]);

                    throw new \Exception('指定されたメールアドレスは、既に他のアカウントで使用されています。');
                }
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
                'password' => Hash::make(Str::random(32)),
                'image' => '', // デフォルト画像なし
                'introduction' => '', // デフォルト自己紹介なし
            ]);

            // 将来の拡張: SAML属性からロールやグループ情報を取得
            // 例: $roles = $attributes['Role'] ?? [];
            // 例: $user->syncRoles($roles);

            Log::info('SAML認証: 新規ユーザーを作成', [
                'user_id' => $user->id,
                'saml_id' => $samlId,
                'email' => $email,
                'attributes' => $attributes, // SAML属性をログに記録（デバッグ用）
            ]);

            return $user;
        });
    }

    /**
     * ダミーメールアドレスを生成（開発環境のみ）
     *
     * ⚠️ 本番環境では使用しないでください
     * 本番環境では Keycloak 側でメールアドレスを必須に設定してください
     *
     * @param string $samlId SAML ID
     * @return string ダミーメールアドレス
     */
    protected function generateDummyEmail(string $samlId): string
    {
        // 環境に応じたドメインを使用
        $domain = config('saml2.dummy_email_domain', env('SAML2_DUMMY_EMAIL_DOMAIN', 'lanekocafe.local'));

        // NameID をベースにした一意のダミーメールアドレスを生成
        // SHA256ハッシュを使用して安全性を確保
        $hash = hash('sha256', $samlId);

        return "saml_{$hash}@{$domain}";
    }

    private function clearLocalSession(): void
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
