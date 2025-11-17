<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlogRequest;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    private const BLOGS_PER_PAGE = 12;
    /**
     * （一般用）ブログ一覧画面を表示する。
     */
    public function index(BlogRequest $request)
    {
        // ブログ記事のクエリを構築
        $query = Blog::with(['category', 'user', 'cats'])
            ->orderBy('updated_at', 'desc');

        // カテゴリでフィルタリング(クエリパラメータが存在する場合)
        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category_id', $request->category);
        });

        // ページネーション実行
        $blogs = $query->paginate(self::BLOGS_PER_PAGE)->withQueryString();

        // すべてのカテゴリを取得（フィルター用）
        $categories = Category::get(['id', 'name']);

        $currentCategory = $request->category;
        return view('blogs.index', compact('blogs', 'categories', 'currentCategory'));
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
        // ブログ記事の詳細情報を取得（リレーションも含む）
        $blog->load(['category', 'user', 'cats']);

        // 同じ著者の他の記事を3件取得（現在の記事を除く）
        $otherBlogs = Blog::with(['category', 'cats'])
            ->where('user_id', $blog->user_id)
            ->where('id', '!=', $blog->id)
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        return view('blogs.show', compact('blog', 'otherBlogs'));
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
