@extends('layouts.admin')

@section('content')
    <div class="mx-auto">
        <main class="py-4 px-6">
            <!-- ▼▼▼▼ページ毎の個別内容▼▼▼▼　-->
            <section class="py-8">
                <div class="container px-4 mx-auto">
                    <div class="py-4 bg-white rounded shadow">
                        <div class="px-6 pb-4 border-b">
                            <h3 class="text-2xl font-semibold">お問い合わせ内容</h3>
                        </div>

                        <div class="pt-4 px-6">
                            <div class="mb-6">
                                <label for="name" class="block text-sm mb-2">お名前</label>
                                <input class="block w-96 max-w-full px-4 py-3 mb-2 text-sm text-gray-500 border rounded"
                                    type="text" id="name" disabled value="{{ $contact->name }}">
                            </div>
                            <div class="mb-6">
                                <label for="name_kana" class="block text-sm mb-2">お名前（フリガナ）</label>
                                <input class="block w-96 max-w-full px-4 py-3 mb-2 text-sm text-gray-500 border rounded"
                                    type="text" id="name_kana" disabled value="{{ $contact->name_kana }}">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm mb-2" for="phone">電話番号</label>
                                <input class="block w-96 max-w-full px-4 py-3 mb-2 text-sm text-gray-500 border rounded"
                                    type="text" id="phone" disabled value="{{ $contact->phone }}">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm mb-2" for="email">メールアドレス</label>
                                <input class="block w-96 max-w-full px-4 py-3 mb-2 text-sm text-gray-500 border rounded"
                                    type="email" id="email" disabled value="{{ $contact->email }}">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm mb-2" for="body">本文</label>
                                <textarea class="block w-96 max-w-full px-4 py-3 mb-2 text-sm text-gray-500 border rounded" id="body"
                                    rows="5" disabled>{{ $contact->body }}</textarea>
                            </div>
                            <div class="mt-6 pt-6 border-t">
                                <a href="{{ route('admin.contacts.index') }}"
                                    class="inline-block px-6 py-3 text-sm text-white font-semibold bg-blue-500 hover:bg-blue-700 rounded transition duration-300">
                                    一覧に戻る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection
