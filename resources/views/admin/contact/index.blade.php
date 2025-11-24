@extends('layouts.admin')

@section('content')
    <div class="mx-auto">
        <main class="py-4 px-6">
            <section>
                <div class="mb-6 py-4 bg-white rounded shadow" id="admin-contact-list"
                    data-initial-count="{{ $contacts->count() }}">
                    <div class="px-6 pb-4 border-b">
                        <h2 class="text-2xl font-semibold">お問い合わせ一覧</h2>
                        <div id="contact-live-indicator" class="flex items-center text-sm text-gray-500 gap-2 mt-2">
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 border border-gray-200">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse hidden"></span>
                                リアルタイム監視中
                            </span>
                            <span id="new-contact-counter"
                                class="hidden px-2 py-1 bg-blue-600 text-white text-xs rounded-full"></span>
                        </div>
                    </div>
                    <div class="pt-4 px-4 overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-sm text-gray-600">
                                    <th class="px-4 py-3">名前</th>
                                    <th class="px-4 py-3">お名前（フリガナ）</th>
                                    <th class="px-4 py-3">メールアドレス</th>
                                    <th class="px-4 py-3">お問い合わせ日時</th>
                                    <th class="px-4 py-3">操作</th>
                                </tr>
                            </thead>
                            <tbody id="contact-table-body">
                                @forelse ($contacts as $contact)
                                    <tr class="@if ($contact->is_read) bg-gray-100 @else bg-white @endif text-sm border-b hover:bg-gray-200 cursor-pointer transition-colors"
                                        role="link" tabindex="0" onkeydown="if(event.key === 'Enter') this.click();"
                                        onclick="window.location = '{{ route('admin.contacts.show', $contact) }}'">
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->name_kana }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->email }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">
                                            {{ $contact->created_at->format('Y.m.d H:i:s') }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">
                                            <div class="flex items-center gap-3">
                                                @if (!$contact->is_read)
                                                    <form action="{{ route('admin.contacts.update', $contact) }}"
                                                        method="POST" onclick="event.stopPropagation();">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-500 hover:text-green-700"
                                                            title="対応済み">
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M20 6L9 17L4 12" stroke="currentColor"
                                                                    stroke-width="2" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('admin.contacts.destroy', $contact) }}"
                                                    method="POST" onsubmit="return confirm('本当に削除しますか？')"
                                                    onclick="event.stopPropagation();">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700"
                                                        title="削除">
                                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M3 6h18" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M10 11v6" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M14 11v6" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <rect x="4" y="6" width="16" height="18" rx="2"
                                                                stroke="currentColor" stroke-width="2" fill="none" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-contacts-row">
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">お問い合わせはありません。
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $contacts->links() }}
                </div>
            </section>
        </main>
    </div>

    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('contact-table-body');
            const newCounter = document.getElementById('new-contact-counter');
            const noContactsRow = document.getElementById('no-contacts-row');

            if (window.Echo) {
                window.Echo.private('admin.notifications')
                    .listen('.ContactReceived', (e) => {
                        console.log('お問い合わせ受信:', e.contact);
                        handleNewContact(e.contact);
                    });

                // 共通処理関数
                const handleNewContact = (contact) => {
                    // 新着バッジ表示
                    newCounter.classList.remove('hidden');
                    newCounter.textContent = '新着';
                    setTimeout(() => newCounter.classList.add('hidden'), 5000);

                    const detailUrl = `/admin/contacts/${contact.id}`;
                    const date = new Date(contact.created_at);
                    // YYYY.MM.DD HH:mm:ss
                    const formattedDate = `${date.getFullYear()}.${(date.getMonth()+1).toString().padStart(2, '0')}.${date.getDate().toString().padStart(2, '0')} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}:${date.getSeconds().toString().padStart(2, '0')}`;

                    const tr = document.createElement('tr');
                    // 新規行のクラス設定（未読なのでbg-white）
                    tr.className = "bg-blue-50 text-sm border-b hover:bg-gray-200 cursor-pointer transition-colors duration-1000";
                    tr.role = "link";
                    tr.tabIndex = 0;
                    tr.onclick = () => window.location = detailUrl;
                    tr.onkeydown = (event) => { if (event.key === 'Enter') window.location = detailUrl; };

                    tr.innerHTML = `
                        <td class="px-4 py-4 text-sm text-gray-700">${escapeHtml(contact.name)}</td>
                        <td class="px-4 py-4 text-sm text-gray-700">${escapeHtml(contact.name_kana)}</td>
                        <td class="px-4 py-4 text-sm text-gray-700">${escapeHtml(contact.email)}</td>
                        <td class="px-4 py-4 text-sm text-gray-700">${formattedDate}</td>
                        <td class="px-4 py-4 text-sm text-gray-700">
                            <div class="flex items-center gap-3">
                                    <!-- 簡易表示: アクションを行うには詳細ページへ遷移推奨 -->
                                    <a href="${detailUrl}" class="text-blue-600 hover:underline">詳細</a>
                            </div>
                        </td>
                    `;

                    if (noContactsRow) {
                        noContactsRow.remove();
                    }

                    // テーブルの先頭に追加
                    tableBody.insertBefore(tr, tableBody.firstChild);

                    // ハイライト解除
                    setTimeout(() => {
                        tr.classList.remove('bg-blue-50');
                        tr.classList.add('bg-white');
                    }, 3000);
                };
            }

        });
    </script>
@endsection
