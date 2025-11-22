@extends('layouts.admin')

@section('content')
    <div class="mx-auto space-y-6" id="admin-dashboard" data-latest-contact-count="{{ $latestContacts->count() }}"">
        <section class="grid gap-4 md:grid-cols-2">
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">リアルタイム通知</h2>
                <p class="text-sm text-gray-600">
                    新しいお問い合わせが届くとここに表示されます。ブラウザを開いたままにしておくとリアルタイムで更新されます。
                </p>
                <div class="mt-4" id="contact-alert-banner" hidden>
                    <div class="p-4 bg-blue-100 border border-blue-300 rounded text-blue-800 text-sm">
                        新しいお問い合わせが届きました。
                        <button type="button" class="ml-2 underline hover:no-underline" data-action="scroll-to-list">
                            確認する
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">概要</h2>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li>最新お問い合わせ件数： <span>{{ $latestContacts->count() }}件</span></li>
                    <li>リアルタイム通知： <span class="font-semibold text-green-600">準備完了</span></li>
                </ul>
            </div>
        </section>

        <section class="bg-white rounded shadow">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2>最新のお問い合わせ</h2>
                <a href="{{ route('admin.contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    すべてを見る
                </a>
            </div>

            <div class="p-4">
                <ul id="contact-stream" class="divide-y divide-gray-200" data-hydrated="false">
                    @forelse ($latestContacts as $contact)
                        <li class="py-3 flex items-center justify-between gap-4" data-contact-id="{{ $contact->id }}">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $contact->name }}
                                    <span
                                        class="ml-4 text-xs text-gray-500">{{ $contact->created_at->format('Y/m/d H:i') }}</span>
                                </p>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ Str::limit($contact->body, 120) }}</p>
                            </div>
                            <a class="text-sm text-blue-600 hover:text-blue-800 whitespace-nowrap"
                                href="{{ route('admin.contacts.show', $contact) }}">詳細</a>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-gray-500">お問い合わせはまだありません。</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
@endsection
