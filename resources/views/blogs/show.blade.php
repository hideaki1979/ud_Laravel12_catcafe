@extends('layouts.default')

@section('title', $blog->title . ' - ねこカフェららべる')

@section('content')
    <section class="bg-gray-100 py-2">
        <div class="container mx-auto">
            <p class="px-4 py-2 text-gray-400">
                <a href="/" class="text-blue-600 hover:underline">ホーム</a>
                <span class="px-2">&gt;</span>
                <a href="{{ route('blogs.index') }}">ブログ</a>
                <span class="px-2">&gt;</span>
                {{ $blog->title }}
            </p>
        </div>
    </section>

    <section class="py-10">
        <div class="container mx-auto px-4">
            <article class="max-w-4xl mx-auto">
                {{-- タイトル --}}
                <h1 class="text-3xl md:text-4xl font-bold text-center mb-8">
                    {{ $blog->title }}
                </h1>

                {{-- カテゴリ / ねこちゃん --}}
                <div class="mb-8 pb-4 border-b">
                    <p class="text-lg mb-2 font-semibold">カテゴリ / ねこちゃん</p>
                    <div class="flex flex-wrap gap-2">
                        @if ($blog->category)
                            <span class="bg-gray-100 text-gray-400 text-sm py-2 px-3">
                                {{ $blog->category->name }}
                            </span>
                        @endif
                        @foreach ($blog->cats as $cat)
                            <span class="bg-gray-100 text-gray-400 text-sm py-2 px-3">
                                #{{ $cat->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- メイン画像 --}}
                @if ($blog->image)
                    <div class="mb-8 rounded-lg overflow-hidden">
                        <img class="w-80 h-80 object-cover mx-auto" src="{{ asset('storage/' . $blog->image) }}"
                            alt="{{ $blog->title }}">
                    </div>
                @endif

                {{-- 本文 --}}
                <div>
                    {!! nl2br(e($blog->body)) !!}
                </div>

                {{-- この記事を書いた店員 --}}
                @if ($blog->user)
                    <div class="prose max-w-none mb-12 text-gray-700 leading-relaxed">
                        <h2 class="text-xl font-semibold mb-4">この記事を書いた店員</h2>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                @if ($blog->user->image)
                                    <img class="w-24 h-24 rounded-full object-cover"
                                        src="{{ asset('storage/' . $blog->user->image) }}" alt="{{ $blog->user->name }}">
                                @else
                                    <div class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span>{{ mb_substr($blog->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold mb-2">
                                    {{ $blog->user->name }}
                                </h3>
                                <p class="text-gray-600 text-sm leading-relaxed">
                                    {{ $blog->user->introduction ?? '説明テキストが入ります説明テキストが入ります説明テキストが入ります。' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- この店員が書いた他の記事 --}}
                @if ($otherBlogs->isNotEmpty())
                    <div class="mb-12">
                        <h2 class="text-2xl font-semibold text-center mb-8">この店員が書いた他の記事</h2>
                        <div id="author-blogs-container" class="flex flex-wrap">
                            @foreach ($otherBlogs as $otherBlog)
                                @include('blogs._card', ['blog' => $otherBlog])
                            @endforeach
                        </div>

                        {{-- もっと見るボタン --}}
                        @if ($blog->user && $blog->user->blogs_count > 4)
                            <div id="load-more-container" class="text-center mt-8">
                                <button id="load-more-btn" data-blog-id="{{ $blog->id }}" data-offset="3"
                                    class="inline-block border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-12 py-3 rounded-md transition-colors">
                                    もっと見る
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ブログ一覧に戻る --}}
                <div class="text-center">
                    <a href="{{ route('blogs.index') }}"
                        class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 px-8 py-3 rounded-md transition-colors">
                        ← ブログ一覧に戻る
                    </a>
                </div>
            </article>
        </div>
    </section>
    @if ($otherBlogs->isNotEmpty() && $blog->user && $blog->user->blogs_count > 4)
        @push('scripts')
            <script src="{{ asset('js/blogs-show.js') }}"></script>
        @endpush
    @endif
@endsection
