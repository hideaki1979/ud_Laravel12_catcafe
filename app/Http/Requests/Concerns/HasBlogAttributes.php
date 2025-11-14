<?php

namespace App\Http\Requests\Concerns;

trait HasBlogAttributes
{
    public function attributes()
    {
        return [
            'title' => 'タイトル',
            'image' => '画像',
            'category_id' => 'カテゴリー',
            'body' => '本文',
            'cats' => '登場するねこ',
        ];
    }
}
