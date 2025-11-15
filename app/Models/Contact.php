<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'name_kana',
        'phone',
        'email',
        'body',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
