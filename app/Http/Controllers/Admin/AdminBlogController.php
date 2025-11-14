<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\Cat;
use App\Models\Category;
use App\Models\User;
use App\Services\BlogService;
use Illuminate\Support\Facades\Storage;

class AdminBlogController extends Controller
{
    protected $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    /**
     * ブログ一覧画面表示
     */
    public function index()
    {
        // category と user をまとめて取得して N+1 を防ぐ
        $blogs = Blog::with(['category', 'user'])->latest('updated_at')->paginate(10);
        return view('admin.blogs.index', ['blogs' => $blogs]);
    }

    /**
     * ブログ投稿画面表示
     */
    public function create()
    {
        $categories = Category::all(['id', 'name']);
        $cats = Cat::all(['id', 'name']);
        return view('admin.blogs.create', [
            'categories' => $categories,
            'cats' => $cats,
        ]);
    }

    /**
     * ブログ投稿処理
     */
    public function store(StoreBlogRequest $request)
    {
        // Blogテーブル登録処理(データ整合性を担保するためトランザクション)
        $this->blogService->store($request->validated(), $request->file('image'), $request->user());

        // ブログ一覧に遷移
        return to_route('admin.blogs.index')->with('success', 'ブログを投稿しました。');
    }

    /**
     *
     */
    public function show(string $id) {}

    /**
     * 指定したIDのブログ編集画面
     */
    public function edit(Blog $blog)
    {
        $categories = Category::all(['id', 'name']);
        $cats = Cat::all(['id', 'name']);
        return view('admin.blogs.edit', [
            'blog' => $blog,
            'categories' => $categories,
            'cats' => $cats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        $this->blogService->update($request, $blog);

        return to_route('admin.blogs.index')->with('success', 'ブログを更新しました！');
    }

    /**
     * 指定したIDのブログ情報を削除する
     */
    public function destroy(string $id)
    {
        //
        $blog = Blog::findOrFail($id);
        $blog->delete();
        Storage::disk('public')->delete($blog->image);

        return to_route('admin.blogs.index')->with('success', 'ブログを削除しました');
    }
}
