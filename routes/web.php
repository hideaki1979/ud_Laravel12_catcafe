<?php

use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminContactController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::view('/', 'index');

// （一般向け）ブログ関連
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{blog}', [BlogController::class, 'show'])->name('blogs.show');

// お問い合わせフォーム
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'sendMail'])->name('contact.send');
Route::get('/contact/complete', [ContactController::class, 'complete'])->name('contact.complete');

// 管理者向けページ
Route::prefix('/admin')
    ->name('admin.')
    ->group(function () {
        // ログイン時にアクセス可能
        Route::middleware('auth')
            ->group(function () {
                // ブログ関連
                Route::resource('/blogs', AdminBlogController::class)->except('show');

                // ユーザー登録・ログイン
                Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
                Route::post('/users', [UserController::class, 'store'])->name('users.store');

                // お問い合わせ管理
                Route::resource('contacts', AdminContactController::class)->only('index', 'show', 'update', 'destroy');

                // ログアウト
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            });

        // 未ログイン時のにアクセス可能
        Route::middleware('guest')
            ->group(function () {
                // ログイン画面・処理
                Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
                Route::post('/login', [AuthController::class, 'login']);
            });
    });

Route::get('/health', function () {
    try {
        // データベース接続確認
        DB::connection()->getPdo();

        // Redis接続確認（使用している場合）
        Cache::store('redis')->get('health_check');

        return response()->json(['status' => 'healthy'], 200);
    } catch (\Throwable $e) {
        Log::error('Health check failed', ['exception' => $e]);
        return response()->json(['status' => 'unhealthy'], 503);
    }
});
