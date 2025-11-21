<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => route('admin.login'));
        $middleware->redirectUsersTo(fn() => route('admin.blogs.index'));

        // SAML ACSエンドポイントをCSRF保護から除外
        $middleware->validateCsrfTokens(except: [
            'saml2/keycloak/acs',
        ]);

        // 本番環境: ロードバランサー/リバースプロキシの信頼設定
        // ロードバランサー（ALB、Nginx、Cloudflare等）を使用する場合、
        // X-Forwarded-* ヘッダーを信頼する必要があります
        if (env('APP_ENV') === 'production') {
            // 信頼するプロキシのIPアドレス
            // '*' = すべてのプロキシを信頼（本番環境では特定のIPを指定推奨）
            $middleware->trustProxies(
                at: env('TRUSTED_PROXIES'), // .env.prod で信頼するプロキシのIPを必ず指定してください (例: '192.168.1.0/24')
                headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
