@extends('layouts.default')
@section('title', 'お問い合わせ')
@section('content')
    <section class="bg-gray-100 py-2">
        <div class="container mx-auto">
            <p class="px-4 py-2 text-gray-400"><a href="#" class="text-blue-600 hover:underline">ホーム</a><span
                    class="px-2">&gt</span>お問い合わせ</p>
            <h1 class="mt-2 text-3xl font-bold h-32 text-center p-8">お問い合わせ</h1>
        </div>
    </section>

    <section class="mt-12 pb-12">
        <div class="w-200 mx-auto">
            <p>
                以下のフォームに必要事項をご入力の上、ご送信下さい。<br>
                通常お問い合わせから3営業日以内にご入力いただいたメールアドレスに返信させていただきます。<br>
                なお、info@nekocafe.xx.xxからの返信が受信できるように事前に設定のご確認をお願い致します。
            </p>
            <div class="mt-8">
                <!-- ▼▼▼▼エラーメッセージ▼▼▼▼　-->
                @if ($errors->any())
                    <div class="mb-8 py-4 px-6 border border-pink-300 bg-pink-50 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li class="text-pink-400">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <!-- ▼▼▼▼エラーメッセージ▼▼▼▼　-->
            </div>
            <form action="{{ route('contact.send') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block p-1 my-1">お名前<span
                            class="text-white text-xs bg-yellow-500 mx-2 py-1 px-2">必須</span></label>
                    <input type="text" name="name" id="name" placeholder="例）田中太郎"
                        class=" w-full p-4 text-xs leading-none bg-blueGray-100 rounded outline-none border"
                        value="{{ old('name') }}">
                    @if ($errors->has('name'))
                        <p class="text-red-500 text-sm">{{ $errors->first('name') }}</p>
                    @endif
                </div>
                <div class="mb-4">
                    <label for="name_kana" class="block p-1 my-1">お名前（フリガナ）<span
                            class="text-white text-xs bg-yellow-500 mx-2 py-1 px-2">必須</span></label>
                    <input type="text" name="name_kana" id="name_kana" placeholder="例）タナカタロウ"
                        class=" w-full p-4 text-xs leading-none bg-blueGray-100 rounded outline-none border"
                        value="{{ old('name_kana') }}">
                    @error('name_kana')
                        <p class="text-red-500 text-sm">{{ $errors->first('name_kana') }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="phone" class="block p-1 my-1">電話番号</label>
                    <input type="text" name="phone" id="phone" placeholder="例）0312345678"
                        class=" w-full p-4 text-xs leading-none bg-blueGray-100 rounded outline-none border"
                        value="{{ old('phone') }}">
                    @error('phone')
                        <p class="text-red-500 text-sm">{{ $errors->first('phone') }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="email" class="block p-1 my-1">メールアドレス<span
                            class="text-white text-xs bg-yellow-500 mx-2 py-1 px-2">必須</span></label>
                    <input type="email" name="email" id="email" placeholder="info@example.com"
                        class=" w-full p-4 text-xs leading-none bg-blueGray-100 rounded outline-none border"
                        value="{{ old('email') }}">
                    @error('email')
                        <p class="text-red-500 text-sm">{{ $errors->first('email') }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="body" class="block p-1 my-1">お問い合わせ内容<span
                            class="text-white text-xs bg-yellow-500 mx-2 py-1 px-2">必須</span></label>
                    <textarea name="body" id="body" placeholder="ご自由にご記入ください"
                        class="w-full h-24 p-4 text-xs leading-none bg-blueGray-100 rounded outline-none border">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-500 text-sm">{{ $errors->first('body') }}</p>
                    @enderror
                </div>
                <div class="text-center">
                    <p>送信される際は、<a href="#" class="text-blue-600 hover:underline">個人情報保護方針</a>に同意したものとします。</p>
                    <button type="submit"
                        class="mt-4 text-white font-semibold leading-none bg-blue-600 hover:bg-blue-800 rounded py-4 px-6">送信</button>
                </div>
            </form>
        </div>
    </section>

@endsection
