@extends('layouts.default')
@section('title', 'トップページ')

@section('content')
    <section class="bg-gray-100">
        <div class="container mx-auto py-32 relative">
            <h1 class="mt-2 text-4xl font-bold text-center">ねこに癒やしてもらいませんか？</h1>
            <p class="text-center pt-8">ねこの可愛い仕草、自由気ままな雰囲気を見ることはもちろん、<br>
                ただ同じ空間にいるだけでも癒やされます<br>
                当店自慢のフワッフワのねこに触れ合うことで「幸せホルモン」が補給しませんか？
            </p>
            <div class="absolute right-0 bottom-0 transform translate-y-20">
                <a href="/"><img src="/images/index/video.jpg" alt="" class="shadow-lg"></a>
            </div>
        </div>
    </section>

    <section class="mt-24">
        <div class="container mx-auto">
            <p class="text-center text-2xl">特徴</p>
            <h2 class="mt-4 font-bold text-center text-3xl">安心して楽しんで頂くための3つの徹底ポイント</h2>
        </div>

        <div class="flex flex-wrap -m-3 container mx-auto pt-8">
            <div class="w-full lg:w-1/3 p-3">
                <div class="bg-gray-100 rounded shadow">
                    <div class="flex py-10 px-6">
                        <img class="w-20 h-20 object-cover rounded-full" src="/images/index/foliage-plant.jpg"
                            alt="">
                        <div class="ml-4 w-56">
                            <h3 class="mb-1 text-2xl text-left text-blue-500">清潔な環境</h3>
                            <p class="text-left text-gray-500 text-sm">2022年2月にリニューアル!! <br>24時間常に換気<br> 最新型空気清浄機を設置</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3 p-3">
                <div class="bg-gray-100 rounded shadow">
                    <div class="flex py-10 px-6">
                        <img class="w-20 h-20 object-cover rounded-full" src="/images/index/foliage-plant.jpg"
                            alt="">
                        <div class="ml-4 w-56">
                            <h3 class="mb-1 text-2xl text-left text-blue-500">衛生管理</h3>
                            <p class="text-left text-gray-500 text-sm">店員・ゲストの検温<br> マスクの常時着用<br> 1時間毎に全ての設備を消毒</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3 p-3">
                <div class="bg-gray-100 rounded shadow">
                    <div class="flex py-10 px-6">
                        <img class="w-20 h-20 object-cover rounded-full" src="/images/index/foliage-plant.jpg"
                            alt="">
                        <div class="ml-4 w-56">
                            <h3 class="mb-1 text-2xl text-left text-blue-500">ねこの体調管理</h3>
                            <p class="text-left text-gray-500 text-sm">毎日の検温<br> ワクチン接種済み<br> 定期的な検診</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-16 container mx-auto">
        <p class="text-center text-2xl">お知らせ</p>
        <h2 class="mt-4 font-bold text-center text-3xl">営業時間や新メンバーなど様々な情報をお届け</h2>

        <ul class="mt-8">
            <li class="flex py-4 border-t border-b">
                <p class="font-bold w-40">2022. 3. 4（水）</p>
                <p class="bg-gray-100 text-gray-400 px-3 py-1">カテゴリ</p><a href="#"
                    class="ml-4 text-blue-500">キャットフードなどの販売をはじめました</a>
            </li>
            <li class="flex py-4 border-b">
                <p class="font-bold w-40">2022. 2. 14（水）</p>
                <p class="bg-gray-100 text-gray-400 px-3 py-1">カテゴリ</p><a href="#"
                    class="ml-4 text-blue-500">『ノア』ちゃん新しく加入しました！</a>
            </li>
            <li class="flex py-4 border-b">
                <p class="font-bold w-40">2022. 3. 4（水）</p>
                <p class="bg-gray-100 text-gray-400 px-3 py-1">カテゴリ</p><a href="#"
                    class="ml-4 text-blue-500">貸し切りご予約のため3/20は12:00~の営業となります</a>
            </li>
            <li class="flex py-4 border-b">
                <p class="font-bold w-40">2022. 3. 4（水）</p>
                <p class="bg-gray-100 text-gray-400 px-3 py-1">カテゴリ</p><a href="#"
                    class="ml-4 text-blue-500">『●●●●』に、当店が紹介されました</a>
            </li>
            <li class="flex py-4 border-b">
                <p class="font-bold w-40">2022. 3. 4（水）</p>
                <p class="bg-gray-100 text-gray-400 px-3 py-1">カテゴリ</p><a href="#"
                    class="ml-4 text-blue-500">サイト開設しました！！</a>
            </li>
        </ul>

        <div class="mt-8">
            <a href="#"
                class="inline-block text-sm text-blue-500 font-semibold leading-none rounded py-3 border-solid bg-white border-blue-500 border-2 px-6 hover:text-white hover:bg-blue-500">もっと見る</a>
        </div>
    </section>

    <section class="mt-16">
        <div class="container mx-auto">
            <p class="text-center text-2xl">ブログ</p>
            <h2 class="mt-4 font-bold text-center text-3xl">ほぼ毎日お店でねこの様子をお届け！！</h2>

            <div class="my-8 pb-4 border-b">
                <p class="text-lg">カテゴリ / ねこちゃん</p>
                <ul class="flex pt-2">
                    <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3">カテゴリー</li>
                    <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3"><a href="#">カテゴリー</a></li>
                    <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3"><a href="#">カテゴリー</a></li>
                    <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3"><a href="#">#ねこちゃん</a></li>
                    <li class="bg-gray-100 text-gray-400 py-1 px-3 mr-3"><a href="#">#ねこちゃん</a></li>
                    <li class="bg-gray-100 text-gray-400 py-1 px-3"><a href="#">#ねこちゃん</a></li>
                </ul>
            </div>

            <div class="flex flex-wrap -mx-3">
                <article class="w-full md:w-1/2 lg:w-1/3 p-3">
                    <div class="border rounded-lg overflow-hidden shadow">
                        <div class="relative h-48">
                            <span
                                class="absolute text-xs text-gray-400 border border-white bg-gray-100 uppercase py-2 px-8 left-0 bottom-0">カテゴリー</span>
                            <a href="#"><img class="w-full h-full object-cover"
                                    src="/images/placeholders/blogs/lunch.jpeg" alt=""></a>
                            <time class="text-xs text-gray-500 text-right pr-2 pt-2">2022.3.12</time>
                        </div>
                        <div class="p-6 pb-4 px-4">
                            <a href="#">
                                <h1 class="mb-2 text-2xl font-semibold">ランチの様子</h1>
                                <p class="mb-4 text-gray-500 leading-relaxed truncate">12:00のランチタイムはド迫力！！写真映えもするこの...</p>
                            </a>
                            <div class="flex justify-between">
                                <ul class="flex">
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs py-1 px-2">＃ねこちゃん</li>
                                </ul>
                                <p class="font-semibold">店長</p>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="w-full md:w-1/2 lg:w-1/3 p-3">
                    <div class="border rounded-lg overflow-hidden shadow">
                        <div class="relative h-48">
                            <span
                                class="absolute text-xs text-gray-400 border border-white bg-gray-100 uppercase py-2 px-8 left-0 bottom-0">カテゴリー</span>
                            <a href="#"><img class="w-full h-full object-cover"
                                    src="/images/placeholders/blogs/lunch.jpeg" alt=""></a>
                            <time class="text-xs text-gray-500 text-right pr-2 pt-2">2022.3.11</time>
                        </div>
                        <div class="p-6 pb-4 px-4">
                            <a href="#">
                                <h1 class="mb-2 text-2xl font-semibold">リモートワークにも！</h1>
                                <p class="mb-4 text-gray-500 leading-relaxed truncate">実はねこカフェはリモートワークやコワーキングとしても大活躍....
                                </p>
                            </a>
                            <div class="flex justify-between">
                                <ul class="flex">
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs py-1 px-2">＃ねこちゃん</li>
                                </ul>
                                <p class="font-semibold">店長</p>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="w-full md:w-1/2 lg:w-1/3 p-3">
                    <div class="border rounded-lg overflow-hidden shadow">
                        <div class="relative h-48">
                            <span
                                class="absolute text-xs text-gray-400 border border-white bg-gray-100 uppercase py-2 px-8 left-0 bottom-0">カテゴリー</span>
                            <a href="#"><img class="w-full h-full object-cover"
                                    src="/images/placeholders/blogs/lunch.jpeg" alt=""></a>
                            <time class="text-xs text-gray-500 text-right pr-2 pt-2">2022.3.10</time>
                        </div>
                        <div class="p-6 pb-4 px-4">
                            <a href="#">
                                <h1 class="mb-2 text-2xl font-semibold">トイレの教え方</h1>
                                <p class="mb-4 text-gray-500 leading-relaxed truncate">このカフェに来て3日のルキアがもうトイレを覚えてくれました…</p>
                            </a>
                            <div class="flex justify-between">
                                <ul class="flex">
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs mr-2 py-1 px-2">＃ねこちゃん</li>
                                    <li class="bg-gray-100 text-gray-400 text-xs py-1 px-2">＃ねこちゃん</li>
                                </ul>
                                <p class="font-semibold">高橋</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-8 text-center">
                <a href="#"
                    class="inline-block text-blue-500 font-semibold leading-none bg-white border-blue-500 border-2 border-solid rounded py-4 px-8 hover:text-white hover:bg-blue-500">もっと見る</a>
            </div>
        </div>
    </section>

    <section id="access" class="mt-20 pb-20 bg-gray-100">
        <div class="container mx-auto">
            <p class="text-center pt-8 text-2xl">アクセス</p>
            <h2 class="mt-4 font-bold text-center text-3xl h-16">ご来店をお待ちしております！</h2>
            <div class="md:flex justify-center gap-x-4">
                <div>
                    <img class="rounded mx-auto" src="/images/map.jpg" alt="">
                </div>
                <div>
                    <p class="font-bold text-3xl pb-4">ねこカフェららべる</p>
                    <dl>
                        <dt class="text-xl">営業時間</dt>
                        <dd class="pl-4"><span class="mr-4">平日</span>11:00〜20:00<span>（L.O.
                                19:15）</span></dd>
                        <dd class="pl-4"><span class="mr-1">土日祝</span>11:00〜18:00<span>L.O. 17:15）</span></dd>
                        <dt class="mt-4 text-xl">住所</dt>
                        <dd class="text-lg pl-4">〒123-4567<br>東京都墨田区押上1-2-3 ●●ビル▲F</dd>
                        <dt class="mt-4 text-xl">最寄り駅から</dt>
                        <dd class="pl-4 text-lg">とうきょうスカイツリー駅<span class="ml-2">徒歩5分</span></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="flex mx-auto bg-white py-4 mt-8 md:w-3xl">
            <div class="mx-auto">
                <p class="text-lg">なにかあればお気軽にお問い合わせください！</p>
                <p><a href="#" class="text-blue-600 hover:underline">よくあるご質問</a>もご用意しています</p>
            </div>
            <div class="mx-auto">
                <a href="/contact"
                    class="inline-block text-sm text-blue-700 font-semibold leading-none rounded border-solid bg-white border-blue-700 border-2 px-8 py-4">お問い合わせ</a>
            </div>
        </div>
    </section>
@endsection
