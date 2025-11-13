<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'ねこカフェららべる')</title>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="/css/tailwind/tailwind.min.css">

    <link rel="icon" type="image/png" sizes="16x16" href="/favicon.png">
    <script src="/js/main.js"></script>
</head>

<body class="antialiased bg-body text-body font-body">
    <!-- ▼▼▼▼共通ヘッダー▼▼▼▼　-->
    <header>
        <div class="container px-4 mx-auto">
            <nav class="flex items-center justify-between py-6">
                <a href="/" class="text-3xl font-semibold leading-none">ねこカフェららべる</a>
                <ul class="hidden lg:flex ml-12 mr-auto space-x-6">
                    <li><a href="#" class="text-sm text-blueGray-400 hover:text-blueGray-500">設備</a></li>
                    <li><a href="#" class="text-sm text-blueGray-400 hover:text-blueGray-500">ねこちゃんたち</a></li>
                    <li><a href="/blogs" class="text-sm text-blueGray-400 hover:text-blueGray-500">ブログ</a></li>
                    <li><a href="#" class="text-sm text-blueGray-400 hover:text-blueGray-500">メニュー</a></li>
                    <li><a href="#" class="text-sm text-blueGray-400 hover:text-blueGray-500">よくあるご質問</a></li>
                </ul>
                <div>
                    <a href="/contact"
                        class="mr-2 inline-block px-4 py-3 text-xs text-blue-500 hover:text-blue-600 leading-none border border-blue-200 hover:border-blue-300 rounded">お問い合わせ</a>
                    <a href="/#access"
                        class="inline-block px-4 py-3 text-xs font-semibold leading-none bg-blue-500 hover:bg-blue-600 text-white rounded">アクセス</a>
                </div>
            </nav>
        </div>
    </header>
    <!-- ▲▲▲▲共通ヘッダー▲▲▲▲　-->

    <!-- ▼▼▼▼ページ毎の個別内容▼▼▼▼　-->
    <main>
        @yield('content')
    </main>
    <!-- ▼▼▼▼ページ毎の個別内容▼▼▼▼　-->

    <!-- ▼▼▼▼共通フッター▼▼▼▼　-->
    <footer class="bg-black">
        <div class="px-4 container mx-auto p-10 flex justify-between">
            <div class="text-white text-left">
                <h2 class="text-xl font-semibold">ねこカフェららべる</h2>
                <p>〒123-4567</p>
                <p>東京都墨田区押上1-2-3 Illuminateビル9F</p>
            </div>
            <ul class="text-white text-left hidden md:flex flex-wrap flex-col h-12 md:w-lg">
                <li class="ml-6"><a href="/">ホーム</a></li>
                <li class="ml-6"><a href="#">設備</a></li>
                <li class="ml-6"><a href="#">ねこちゃんたち</a></li>
                <li class="ml-6"><a href="/blogs">ブログ</a></li>
                <li class="ml-6"><a href="/#access">アクセス</a></li>
                <li class="ml-6"><a href="#">よくあるご質問</a></li>
                <li class="ml-6"><a href="/contact">お問い合わせ</a></li>
                <li class="ml-6"><a href="#">プライバシーポリシー</a></li>
            </ul>
        </div>
    </footer>
    <!-- ▼▼▼▼共通フッター▼▼▼▼　-->
</body>

</html>
