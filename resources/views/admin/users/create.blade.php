@extends('layouts.admin')

@section('content')
    <section class="py-8">
        <div class="container px-4 mx-auto">
            <div class="py-4 bg-white rounded">
                <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex px-6 pb-4 border-b">
                        <h3 class="text-xl font-bold">ユーザー登録</h3>
                        <div class="ml-auto">
                            <button type="submit"
                                class="py-2 px-3 text-sm text-white font-semibold bg-indigo-500 rounded-md">
                                登録
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 px-6">
                        <!-- ▼▼▼▼エラーメッセージ▼▼▼▼　-->
                        @if ($errors->any())
                            <div class="mb-8 py-4 px-6 border border-red-300 bg-red-50 rounded">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li class="text-red-500 ">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <!-- ▼▼▼▼エラーメッセージ▼▼▼▼　-->

                        <div class="mb-6">
                            <label for="name" class="block text-sm mb-2">名前</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                class="block w-full px-4 py-3 mb-2 text-sm bg-white border rounded">
                        </div>

                        <div class="mb-6">
                            <label for="email" class="block text-sm mb-2">メールアドレス</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="block w-full px-4 py-3 mb-2 text-sm bg-white border rounded">
                        </div>

                        <div class="mb-6">
                            <label for="password" class="block text-sm mb-2">パスワード</label>
                            <input type="password" id="password" name="password" value="{{ old('password') }}"
                                class="block w-full px-4 py-3 mb-2 text-sm bg-white border rounded">
                        </div>

                        <div class="mb-6">
                            <label for="password_confirmation" class="block text-sm mb-2">パスワード（確認）</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                value="{{ old('password_confirmation') }}"
                                class="block w-full px-4 py-3 mb-2 text-sm bg-white border rounded">
                        </div>

                        <div class="mb-6">
                            <label for="image" class="block text-sm mb-2">画像</label>
                            <div>
                                <img src="/images/admin/noimage.jpg" data-noimage="/images/admin/noimage.jpg" alt=""
                                    id="previewImage" class="rounded-full shadow-md w-32">
                                <input type="file" id="image" accept="image/*" name="image"
                                    class="block w-full px-4 py-3 mb-2">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="introduction" class="block text-sm mb-2">自己紹介文</label>
                            <textarea name="introduction" id="introduction" rows="2"
                                class="block w-full px-4 py-3 mb-2 text-sm bg-white border rounded">{{ old('introduction') }}</textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        // 画像プレビュー
        document.getElementById('image').addEventListener('change', e => {
            const previewImageNode = document.getElementById('previewImage')
            const fileReader = new FileReader()
            fileReader.onload = () => previewImageNode.src = fileReader.result
            if (e.target.files.length > 0) {
                fileReader.readAsDataURL(e.target.files[0])
            } else {
                previewImageNode.src = previewImageNode.dataset.noimage
            }
        })
    </script>
@endsection
