<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\Cat;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminBlogController extends Controller
{
    /**
     * ブログ一覧画面表示
     */
    public function index()
    {
        $blogs = Blog::latest('updated_at')->paginate(10);
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
        DB::transaction(function () use ($request) {

            // バリデート済みデータ取得
            $validated = $request->validated();
            // 画像保存
            $savedImagePath = $request->file('image')->store('blogs', 'public');

            // Blog登録処理
            $blog = Blog::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'category_id' => $validated['category_id'],
                'image' => $savedImagePath,
            ]);

            // cats 関連を保存
            $blog->cats()->sync($data['cats'] ?? []);
        });

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
        $categories = Category::all();
        $cats = Cat::all();
        return view('admin.blogs.edit', [
            'blog' => $blog,
            'categories' => $categories,
            'cats' => $cats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlogRequest $request, string $id)
    {
        DB::transaction(function () use ($request, $id) {
            $blog = Blog::findOrFail($id);
            $updateData = $request->validated();

            // 画像を変更する場合
            if ($request->has('image')) {
                // 変更前の画像を削除
                Storage::disk('public')->delete($blog->image);
                // 変更後の画像をアップロード、保存パスを更新対象データにセット
                $updateData['image'] = $request->file('image')->store('blogs', 'public');
            }
            $blog->category()->associate($updateData['category_id']);
            $blog->update($updateData);
            $blog->cats()->sync($updateData['cats'] ?? []);
        });

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
