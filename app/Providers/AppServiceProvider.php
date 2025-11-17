<?php

namespace App\Providers;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Pagination\Paginator;
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
    }
}
