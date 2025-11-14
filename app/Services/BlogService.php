<?php

namespace App\Services;

use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BlogService
{
    public function store(array $validatedData, UploadedFile $image, User $user): Blog
    {
        // Blogテーブル登録処理(データ整合性を担保するためトランザクション)
        return DB::transaction(function () use ($validatedData, $image, $user) {
            // 画像を保存
            $imagePath = $image->store('blogs', 'public');

            // Blog登録処理
            $blog = $user->blogs()->create(array_merge(Arr::except($validatedData, ['cats']), ['image' => $imagePath, 'user_id' => $user->id]));

            // cats 関連を保存
            $blog->cats()->sync($validatedData['cats'] ?? []);

            return $blog;
        });
    }

    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        return DB::transaction(function () use ($request, $blog) {
            $updateData = $request->validated();
            // 画像を変更する場合
            if ($request->hasFile('image')) {
                // 変更前の画像を削除
                Storage::disk('public')->delete($blog->image);
                // 変更後の画像をアップロード、保存パスを更新対象データにセット
                $updateData['image'] = $request->file('image')->store('blogs', 'public');
            }
            $blog->update(Arr::except($updateData, ['cats']));
            $blog->cats()->sync($updateData['cats'] ?? []);
        });
    }
}
