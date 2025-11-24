<?php

namespace App\Http\Controllers;

use App\Events\ContactReceived;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactAdminMail;
use App\Models\Contact;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        try {
            // これ以降の行は入力エラーがなかった場合のみ実行されます
            // お問い合わせ登録処理
            DB::transaction(function () use ($validated) {
                $contact = Contact::create($validated);
                Mail::to(config('mail.to.address'))->send(new ContactAdminMail($validated));

                // 管理者へのリアルタイム通知イベント発火
                ContactReceived::dispatch($contact);
            });

            return to_route('contact.complete');
        } catch (Exception $e) {
            // エラーログに記録
            Log::error('お問い合わせ送信エラー：', ['error' => $e->getMessage(), 'exception' => $e,]);

            // ユーザーにエラーメッセージを表示
            return back()->withInput()->with('error', 'お問い合わせの送信に失敗しました。');
        }
    }

    public function complete()
    {
        return view('contact.complete');
    }
}
