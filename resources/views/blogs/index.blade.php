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
                    <li class="bg-gray-200 text-gray-500 py-1 px-3 mr-3 mb-2 hover:bg-gray-300 cursor-pointer">
                        <a href="{{ route('blogs.index') }}">全カテゴリ</a>

                    </li>
                    @foreach ($categories as $category)
                        <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3 mb-2 hover:bg-gray-200 cursor-pointer">
                            <a href="{{ route('blogs.index', ['category' => $category->id]) }}">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($blogs->isNotEmpty())
                <div class="flex flex-wrap">
                    @foreach ($blogs as $blog)
                        <article class="w-full md:w-1/2 lg:w-1/3 p-3">
                            <div class="border rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow">
                                <div class="relative h-56">
                                    <span
                                        class="py-2 px-10 mt-56 absolute left-0 bottom-0 text-xs text-gray-400 border border-white bg-gray-100 uppercase">
                                        {{ $blog->category ? $blog->category->name : 'カテゴリ' }}
                                    </span>
                                    <a href="#">
                                        <img class="w-full h-56 object-cover"
                                            src="{{ $blog->image ? asset('storage/' . $blog->image) : asset('storage/dummy.jpg') }}"
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
                        {{ $blogs->links() }}
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
