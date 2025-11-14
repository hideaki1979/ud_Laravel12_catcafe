<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'max:255'],
            'image' => [
                'nullable',
                'file',
                'image',
                'max:2048',
                'mimes:jpeg,jpg,png,webp',
                'dimensions:min_width=300,min_height=300,max_width=2048,max_height=2048', // 画像の解像度が300px * 300px ~ 1200px * 1200px
            ],
            'body' => ['required', 'max:20000'],
            'cats' => ['nullable', 'array'],
            'cats.*' => ['distinct', 'exists:cats,id'],
        ];
    }

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
