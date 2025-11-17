<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * （一般用）ブログ一覧画面を表示する。
     */
    public function index()
    {
        // カテゴリ、ユーザー、猫ちゃんの情報を含めて取得（N+1問題を回避）
        $blogs = Blog::with(['category', 'user', 'cats'])
            ->latest('updated_at')
            ->paginate(12);

        // すべてのカテゴリを取得（フィルター用）
        $categories = Category::all();

        return view('blogs.index', compact('blogs', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        //
    }
}
