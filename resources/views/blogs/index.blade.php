@extends('layouts.default')

@section('title', 'ブログ - ねこカフェららべる')

@section('content')
    <section class="bg-gray-100 pt-2">
        <div class="container mx-auto">
            <p class="px-4 pt-2 text-gray-400">
                <a href="/" class="text-blue-600 hover:underline">ホーム</a>
                <span class="px-2">&gt;</span>ブログ
            </p>
            <p class="text-center pt-10 text-2xl">ブログ</p>
            <h1 class="mt-2 text-3xl font-bold font-heading text-center h-24">ほぼ毎日お店でねこの様子をお届け！！</h1>
        </div>
    </section>

    <section class="pb-24">
        <div class="container px-4 mx-auto">
            <div class="my-8 pb-4 border-b">
                <p class="text-lg">カテゴリ / ねこちゃん</p>
                <ul class="flex text-center pt-2 flex-wrap">
                    <li class="bg-gray-200 text-gray-500 py-1 px-3 mr-3 mb-2 hover:bg-gray-300 cursor-pointer">カテゴリ</li>
                    @foreach ($categories as $category)
                        <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3 mb-2 hover:bg-gray-200 cursor-pointer">
                            <a href="#">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($blogs->count() > 0)
                <div class="flex flex-wrap">
                    @foreach ($blogs as $blog)
                        <article class="w-full md:w-1/2 lg:w-1/3 p-3">
                            <div class="botder rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow">
                                <div class="relative h-56">
                                    <span
                                        class="py-2 px-10 mt-56 absolute left-0 bottom-0 text-xs text-gray-400 border border-white bg-gray-100 uppercase">
                                        {{ $blog->category ? $blog->category->name : 'カテゴリ' }}
                                    </span>
                                    <a href="#">
                                        <img class="w-full h-56 object-cover"
                                            src="{{ $blog->image ? asset('storage/' . $blog->image) : asset('storage/app/public/dummy.jpg') }}"
                                            alt="{{ $blog->title }}">
                                    </a>
                                    <time class="block text-xs text-gray-500 text-right pt-2 pr-2">
                                        {{ $blog->updated_at->format('Y.n.j') }}
                                    </time>
                                </div>
                                <div class="pt-8 pb-4 px-4">
                                    <a href="#">
                                        <h1 class="mb-2 text-xl font-semibold font-heading">{{ $blog->title }}</h1>
                                        <p class="mb-6 text-gray-500 leading-relaxed truncate">
                                            {{ Str::limit(strip_tags($blog->body), 50) }}
                                        </p>
                                    </a>
                                    <div class="flex justify-between items-center">
                                        <ul class="flex flex-wrap gap-x-2">
                                            @foreach ($blog->cats->take(3) as $cat)
                                                <li class="bg-gray-100 text-gray-400 text-xs py-1 px-2">
                                                    #{{ $cat->name }}
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p class="font-semibold text-sm">
                                            {{ $blog->user ? $blog->user->name : '店長' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- ページネーション --}}
                @if ($blogs->hasPages())
                    <div class="mt-10">
                        <div class="flex justify-center items-center">
                            {{-- 前へボタン --}}
                            @if ($blogs->onFirstPage())
                                <span
                                    class="flex items-center justify-center h-10 w-10 mr-3 bg-gray-100 text-gray-400 rounded-xl cursor-not-allowed">
                                    <svg width="8" height="12" viewbox="0 0 8 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M7.10807 11.4444C7.55252 10.9999 7.55252 10.3333 7.10807 9.88883L3.21918 5.99994L7.10807 2.11106C7.55252 1.66661 7.55252 0.999946 7.10807 0.555501C6.66363 0.111057 5.99696 0.111057 5.55252 0.555501L0.88585 5.22217C0.663627 5.44439 0.552516 5.66661 0.552516 5.99994C0.552516 6.33328 0.663627 6.5555 0.88585 6.77772L5.55252 11.4444C5.99696 11.8888 6.66363 11.8888 7.10807 11.4444Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </span>
                            @else
                                <a href="{{ $blogs->previousPageUrl() }}"
                                    class="flex items-center justify-center h-10 w-10 mr-3 bg-gray-100 hover:bg-gray-200 rounded-xl">
                                    <svg width="8" height="12" viewbox="0 0 8 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M7.10807 11.4444C7.55252 10.9999 7.55252 10.3333 7.10807 9.88883L3.21918 5.99994L7.10807 2.11106C7.55252 1.66661 7.55252 0.999946 7.10807 0.555501C6.66363 0.111057 5.99696 0.111057 5.55252 0.555501L0.88585 5.22217C0.663627 5.44439 0.552516 5.66661 0.552516 5.99994C0.552516 6.33328 0.663627 6.5555 0.88585 6.77772L5.55252 11.4444C5.99696 11.8888 6.66363 11.8888 7.10807 11.4444Z"
                                            fill="#697073"></path>
                                    </svg>
                                </a>
                            @endif

                            {{-- ページ番号 --}}
                            @foreach ($blogs->getUrlRange(1, $blogs->lastPage()) as $page => $url)
                                @if ($page == $blogs->currentPage())
                                    <span
                                        class="flex items-center justify-center h-10 w-10 mr-3 text-xl font-semibold text-white rounded-xl bg-gray-600">
                                        {{ $page }}
                                    </span>
                                @elseif ($page == 1 || $page == $blogs->lastPage() || abs($page - $blogs->currentPage()) <= 2)
                                    <a class="flex items-center justify-center h-10 w-10 mr-3 text-xl font-semibold text-gray-400 rounded-xl hover:bg-gray-100"
                                        href="{{ $url }}">{{ $page }}</a>
                                @elseif (abs($page - $blogs->currentPage()) === 3)
                                    <span
                                        class="flex items-center justify-center h-10 w-10 mr-3 text-xl font-semibold text-gray-400">
                                        ...
                                    </span>
                                @endif
                            @endforeach

                            {{-- 次へボタン --}}
                            @if ($blogs->hasMorePages())
                                <a class="flex items-center justify-center h-10 w-10 mr-3 text-xl font-semibold rounded-xl bg-gray-100 hover:bg-gray-200"
                                    href="{{ $blogs->nextPageUrl() }}">
                                    <svg width="8" height="12" viewbox="0 0 8 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.552084 11.4444C0.107639 10.9999 0.107639 10.3333 0.552084 9.88883L4.44097 5.99994L0.552083 2.11106C0.107639 1.66661 0.107639 0.999946 0.552083 0.555501C0.996528 0.111057 1.6632 0.111057 2.10764 0.555501L6.77431 5.22217C6.99653 5.44439 7.10764 5.66661 7.10764 5.99994C7.10764 6.33328 6.99653 6.5555 6.77431 6.77772L2.10764 11.4444C1.6632 11.8888 0.996528 11.8888 0.552084 11.4444Z"
                                            fill="#697073"></path>
                                    </svg>
                                </a>
                            @else
                                <span
                                    class="flex items-center justify-center h-10 w-10 text-gray-400 rounded-xl hover:bg-gray-100 cursor-not-allowed">
                                    <svg width="8" height="12" viewbox="0 0 8 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.552084 11.4444C0.107639 10.9999 0.107639 10.3333 0.552084 9.88883L4.44097 5.99994L0.552083 2.11106C0.107639 1.66661 0.107639 0.999946 0.552083 0.555501C0.996528 0.111057 1.6632 0.111057 2.10764 0.555501L6.77431 5.22217C6.99653 5.44439 7.10764 5.66661 7.10764 5.99994C7.10764 6.33328 6.99653 6.5555 6.77431 6.77772L2.10764 11.4444C1.6632 11.8888 0.996528 11.8888 0.552084 11.4444Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-24">
                    <p class="text-gray-500 text-lg">ブログ記事がまだありません。</p>
                </div>
            @endif
        </div>
    </section>
@endsection
