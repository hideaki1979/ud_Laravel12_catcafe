<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

// API用：著者のブログを追加で取得する
Route::get('/blogs/{blog}/author-blogs', [BlogController::class, 'loadMoreAuthorBlogs'])->name('api.blogs.author-blogs');
