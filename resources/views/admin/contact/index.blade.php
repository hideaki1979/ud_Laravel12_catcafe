@extends('layouts.admin')

@section('content')
    <div class="mx-auto">
        <main class="py-4 px-6">
            <section>
                <div class="mb-6 py-4 bg-white rounded shadow">
                    <div class="px-6 pb-4 border-b">
                        <h2 class="text-2xl font-semibold">お問い合わせ一覧</h2>
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
                            <tbody>
                                @forelse ($contacts as $contact)
                                    <tr class="@if ($loop->even) bg-gray-100 @else bg-white @endif text-sm border-b hover:bg-gray-200 cursor-pointer transition-colors"
                                        onclick="window.location = '{{ route('admin.contacts.show', $contact) }}'">
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->name_kana }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $contact->email }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">
                                            {{ $contact->created_at->format('Y.m.d H:i:s') }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">
                                            <div class="flex items-center gap-3">
                                                <form action="{{ route('admin.contacts.update', $contact) }}" method="POST"
                                                    onclick="event.stopPropagation();">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-green-500 hover:text-green-700"
                                                        title="対応済み">
                                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
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
                                    <tr>
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
@endsection
