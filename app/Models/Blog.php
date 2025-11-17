<?php

namespace App\Models;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'category_id', 'user_id', 'image', 'body'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cats()
    {
        return $this->belongsToMany(Cat::class)->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getExcerptAttribute()
    {
        // HTML Purifierで本文をサニタイズ
        $cleanBody = app(HTMLPurifier::class)->purify($this->body);

        // HTMLタグを除去してプレーンテキストにする
        $plainText = strip_tags($cleanBody);

        // プレーンテキストから抜粋を生成
        return Str::limit($plainText, 50);
    }
}
