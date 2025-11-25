<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="/css/tailwind/tailwind.min.css">

    <link rel="icon" type="image/png" sizes="16x16" href="/favicon.png">
    <script src="/js/main.js"></script>
    <title>管理者ログイン</title>
</head>

<body class="antialiased bg-body text-body font-body">
    <div>
        <section class="h-screen py-32 bg-blueGray-100">
            <div class="container px-4 mx-auto">
                <div class="flex max-w-md mx-auto flex-col text-center">
                    <div class="mt-12 mb-8 p-8 bg-white rounded shadow">
                        <h1 class="mb-6 text-2xl font-bold">管理者ログイン</h1>
                        @if ($errors->any())
                            <div class="mb-8 py-4 px-6 border border-red-300 bg-red-50 rounded">
                                <p class="text-red-400">ログインに失敗しました</p>
                            </div>
                        @endif
                        <form action="{{ route('admin.login') }}" method="POST">
                            @csrf
                            <div class="flex mb-4 px-4 bg-blueGray-100 rounded">
                                <input type="text" placeholder="メールアドレス" name="email" value="{{ old('email') }}"
                                    class="w-full py-4 text-xs placeholder-blueGray-400 font-semibold leading-none bg-blueGray-100 outline-none">
                                <svg class="h-6 w-6 ml-4 my-auto text-blueGray-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                    </path>
                                </svg>
                            </div>

                            <div class="flex mb-4 px-4 bg-blueGray-100 rounded">
                                <input type="password" placeholder="パスワード" name="password"
                                    class="w-full py-4 text-xs placeholder-blueGray-400 font-semibold leading-none bg-blueGray-100 outline-none">
                                <button>
                                    <svg class="h-6 w-6 my-auto text-blueGray-300" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewbox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <button type="submit"
                                class="block w-full p-4 text-center text-sm text-white font-semibold leading-none bg-blue-600 hover:bg-blue-700 rounded">
                                ログイン
                            </button>
                        </form>

                        <!-- Keycloak SAML Login -->
                        <div class="mt-4">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">または</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('saml2_login', 'keycloak') }}"
                                class="block w-full p-3 text-center text-sm text-gray-700 font-semibold leading-none bg-white border-2 border-gray-300 hover:bg-gray-100 rounded"
                                >
                                <svg class="inline-block h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Keycloakでログイン（SSO）
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>
