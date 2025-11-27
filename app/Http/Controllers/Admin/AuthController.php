<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        // バリデーション（フォームリクエストに書き換え可）
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ログイン情報が正しいか
        // Auth::attemptメソッドでログイン情報が正しいか検証
        if (Auth::attempt($credentials)) {
            // セッションを再生成する処理（セキュリティ対策）
            $request->session()->regenerate();

            // ミドルウェアに対応したリダイレクト
            return redirect()->intended('/admin/blogs');
        }

        // ログイン情報が正しくない場合のみ実行される処理（returnすると以降の処理は実行されないため）
        // 一つ前のページ（ログイン画面）にリダイレクト
        // その際にwithErrorを使ってエラーメッセージで手動で指定する
        // リダイレクト後のビュー内でold関数によって、直前の入力内容を取得出来る項目をonlyInputで指定する
        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません',
        ])->onlyInput('email');
    }

    /**
     * ログアウト処理
     *
     * SAMLでログインしたユーザー（saml_idが設定されている）の場合は
     * SamlAuthController::logout() にリダイレクトしてSLO（Single Logout）を実行。
     * 通常のフォームログインの場合はローカルセッションのみクリア。
     *
     * 参考:
     * - https://github.com/aacotroneo/laravel-saml2
     * - https://www.keycloak.org/docs/latest/server_admin/index.html#_saml_logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // SAMLでログインしたユーザーの場合はSAML SLOルートにリダイレクト
        // SamlAuthController::logout() が KeycloakへのLogoutRequestを送信
        if ($user && !empty($user->saml_id)) {
            // ローカルセッションをクリア
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            // 'keycloak' をルートパラメータとして渡し、SAMLパッケージに正しいIdP設定をロードさせる
            // 参考: https://github.com/aacotroneo/laravel-saml2#multi-tenant--idp
            return redirect()->route('saml2_logout', 'keycloak');
        }

        // 通常のフォームログインの場合は従来通り
        // ログアウト処理
        Auth::logout();
        // 現在使っているセキュリティを無効化（セキュリティ対策のため）
        $request->session()->invalidate();
        // セッションを無効化を再生成
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
