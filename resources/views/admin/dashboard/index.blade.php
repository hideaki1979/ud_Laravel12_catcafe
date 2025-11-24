@extends('layouts.admin')

@section('content')
    <div class="mx-auto space-y-6" id="admin-dashboard" data-latest-contact-count="{{ $latestContacts->count() }}">
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
                        <li class="py-6 text-center text-sm text-gray-500" id="no-contacts-message">お問い合わせはまだありません。</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    <script type="module">
        // HTML エスケープ関数
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, (m) => map[m]);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const banner = document.getElementById('contact-alert-banner');
            const stream = document.getElementById('contact-stream');
            const noContactsMsg = document.getElementById('no-contacts-message');

            // スクロールボタンの動作
            document.querySelector('[data-action="scroll-to-list"]')?.addEventListener('click', () => {
                stream.scrollIntoView({behavior: 'smooth', block: 'start'});
                banner.hidden = true;
            });

            // Reverb (Echo) リスナー
            if (window.Echo) {
                window.Echo.private('admin.notifications')
                    .listen('.ContactReceived', (e) => {
                        // 通知バナーを表示
                        banner.hidden = false;

                        // リストの先頭に新しいアイテムを追加
                        const contact = e.contact;
                        const detailUrl = `/admin/contacts/${contact.id}`;

                        // 日時フォーマット (簡易版: YYYY/MM/DD HH:mm)
                        const date = new Date(contact.created_at);
                        const formattedDate = `${date.getFullYear()}/${(date.getMonth()+1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;

                        // 本文の抜粋（120文字）
                        let bodyPreview = contact.body;
                        if (bodyPreview.length > 120) {
                            bodyPreview = bodyPreview.substring(0, 120) + '...';
                        }

                        const li = document.createElement('li');
                        li.className = "py-3 flex items-center justify-between gap-4 bg-blue-50 transition-colors duration-1000"; // 新着ハイライト用に背景色付与
                        li.dataset.contactid = contact.id;
                        li.innerHTML = `
                            <div>
                                <p class="text-sm font-semibold text-gray-800">${escapeHtml(contact.name)}
                                    <span class="ml-4 text-xs text-gray-500">${formattedDate}</span>
                                </p>
                                <p class="text-sm text-gray-600 line-clamp-2">${escapeHtml(bodyPreview)}</p>
                            </div>
                            <a class="text-sm text-blue-600 hover:text-blue-800 whitespace-nowrap"
                                href="${detailUrl}">詳細</a>
                        `;

                        // "お問い合わせはまだありません" があれば削除
                        if (noContactsMsg) {
                            noContactsMsg.remove();
                        }

                        stream.insertBefore(li, stream.firstChild);

                        // ハイライトをフェードアウト
                        setTimeout(() => {
                            li.classList.remove('bg-blue-50');
                        }, 3000);
                    });
            }
        });
    </script>
@endsection
