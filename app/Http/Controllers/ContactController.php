<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactAdminMail;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
    }

    public function sendMail(ContactRequest $request)
    {
        $validated = $request->validated();

        // これ以降の行は入力エラーがなかった場合のみ実行されます
        // お問い合わせ登録処理
        Contact::create($validated);
        Mail::to('syumeikyo@outlook.jp')->send(new ContactAdminMail($validated));
        return to_route('contact.complete');
    }

    public function complete()
    {
        return view('contact.complete');
    }
}
