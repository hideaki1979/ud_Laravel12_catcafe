<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'image' => [
                'required',
                'file', // ファイルがアップロードされている
                'image',    // 画像ファイルである
                'max:2000',  // ファイル容量が2000KB（2MB）以下である
                'mimes:jpeg,jpg,png',    // 形式がjpeg,jpg,pngである
                'dimensions:min_width=300,min_height=300,max_width=2048,max_height=2048', // 画像の解像度が300px * 300px ~ 2048px * 2048px
            ],
            'introduction' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'introduction' => '自己紹介文'
        ];
    }
}
