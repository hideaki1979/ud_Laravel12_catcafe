<?php

namespace App\Providers;

use Aacotroneo\Saml2\Events\Saml2LogoutEvent;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HTMLPurifier::class, function () {
            $config = HTMLPurifier_Config::createDefault();
            // 必要に応じて設定をカスタマイズ
            // $config->set('HTML.Allowed', 'p,a[href]');
            return new HTMLPurifier($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        /**
         * SAML Single Logout (SLO) イベントリスナー
         *
         * Keycloakや他のSP（SPA Backend等）からログアウトが開始された場合、
         * KeycloakがこのLaravelアプリケーションにLogoutRequestを送信します。
         * aacotroneo/laravel-saml2パッケージがそのリクエストを処理し、
         * このSaml2LogoutEventを発火します。
         *
         * 参考: https://github.com/aacotroneo/laravel-saml2
         *
         * Back-Channel Logout（推奨）:
         * - KeycloakがサーバーサイドでHTTP POSTでLogoutRequestを送信
         * - このイベントでセッションをクリアし、Session::save()で即座に保存
         * - ブラウザリダイレクトはログアウト開始元SPのみ
         */
        Event::listen(Saml2LogoutEvent::class, function (Saml2LogoutEvent $event) {
            Auth::logout();

            // 重要: OneLoginライブラリがIdPにリダイレクトする前にセッションを保存
            // ミドルウェアによるセッション保存が間に合わない場合があるため
            Session::save();
        });
    }
}
