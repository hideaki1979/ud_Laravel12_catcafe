<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    // 管理者ユーザーのみリッスン可能
    // ログイン済みであれば許可（ユーザー権限がある場合はここでチェック）
    return $user !== null;
});
