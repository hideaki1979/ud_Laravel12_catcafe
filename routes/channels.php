<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    // 管理者ユーザーのみリッスン可能
    // ログイン済みで、かつ管理者権限がある場合のみ許可
    return $user !== null;
});
