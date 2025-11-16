<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;

class AdminContactController extends Controller
{
    /**
     * お問い合わせ一覧を表示
     */
    public function index()
    {
        $contacts = Contact::orderBy('created_at', 'desc')->paginate(10);

        return view('admin.contact.index', compact('contacts'));
    }

    /**
     * お問い合わせ詳細を表示
     */
    public function show(Contact $contact)
    {
        return view('admin.contact.detail', compact('contact'));
    }

    /**
     * お問い合わせを対応済みに更新
     */
    public function update(Contact $contact)
    {
        $contact->update([
            'is_read' => true,
        ]);

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'お問い合わせを対応済みにしました');
    }

    /**
     * お問い合わせを削除
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'お問い合わせを削除しました。');
    }
}
