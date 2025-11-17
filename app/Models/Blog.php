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
        // HTML Purifierの設定を作成
        $config = HTMLPurifier_Config::createDefault();

        // 必要に応じて設定をカスタマイズできます。
        // 例: 許可するHTMLタグや属性を指定する場合
        // $config->set('HTML.Allowed', 'p,a[href],b,i,strong,em');
        // デフォルトでは多くの安全なタグが許可されます。
        $purifier = new HTMLPurifier($config);
        $cleanBody = $purifier->purify($this->body);

        // サニタイズされた本文から抜粋を生成
        return Str::limit($cleanBody, 50);
    }
}
