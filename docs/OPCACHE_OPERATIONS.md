# OPcache 運用ガイド

このドキュメントでは、本番環境での OPcache（PHP オペコードキャッシュ）の運用方法を説明します。

## 📋 目次

- [OPcache とは](#opcacheとは)
- [本番環境の設定](#本番環境の設定)
- [validate_timestamps=0 の重要性](#validate_timestamps0-の重要性)
- [デプロイ時の OPcache クリア](#デプロイ時のopcacheクリア)
- [OPcache の監視](#opcacheの監視)
- [トラブルシューティング](#トラブルシューティング)

---

## OPcache とは

OPcache（Opcode Cache）は、PHP スクリプトのコンパイル結果をメモリにキャッシュし、実行速度を大幅に向上させる PHP 拡張機能です。

### メリット

-   ✅ レスポンスタイムの短縮（最大 70%）
-   ✅ CPU リソースの削減
-   ✅ スループットの向上

### デメリット

- ⚠️ メモリ使用量の増加
- ⚠️ ファイル変更が即座に反映されない（設定による）

---

## 本番環境の設定

### 現在の設定（Dockerfile.prod）

```dockerfile
# OPcache設定
RUN echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=256" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.interned_strings_buffer=16" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.save_comments=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.fast_shutdown=1" >> "$PHP_INI_DIR/conf.d/opcache.ini"
```

### 各設定の説明

| 設定項目                          | 値    | 説明                                         |
| --------------------------------- | ----- | -------------------------------------------- |
| `opcache.enable`                  | 1     | OPcache を有効化                             |
| `opcache.memory_consumption`      | 256   | キャッシュ用メモリ（MB）                     |
| `opcache.interned_strings_buffer` | 16    | 文字列格納用メモリ（MB）                     |
| `opcache.max_accelerated_files`   | 10000 | キャッシュ可能なファイル数                   |
| `opcache.validate_timestamps`     | 0     | **タイムスタンプ検証を無効化（重要）**       |
| `opcache.save_comments`           | 1     | PHPDoc コメントを保存（Doctrine などで必要） |
| `opcache.fast_shutdown`           | 1     | 高速シャットダウン                           |

---

## validate_timestamps=0 の重要性

> ⚠️ **最重要**: この設定がデプロイ運用の鍵です

### validate_timestamps=1（開発環境）

```ini
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

**動作**:

-   ファイルの変更を自動検知
-   2 秒ごとにタイムスタンプをチェック
-   変更があればキャッシュを自動更新

**メリット**: 開発が容易  
**デメリット**: パフォーマンスが低下

### validate_timestamps=0（本番環境・推奨）

```ini
opcache.validate_timestamps=0
```

**動作**:

-   ファイルの変更を**一切検知しない**
-   キャッシュは永続的に保持
-   手動でクリアするまで古いコードが実行される

**メリット**: 最高のパフォーマンス  
**デメリット**: デプロイ時に必ず OPcache クリアが必要

---

## デプロイ時の OPcache クリア

> ⚠️ **必須手順**: `validate_timestamps=0` 使用時は、デプロイ後に必ず OPcache をクリアしてください

### 方法 1: コンテナ再起動（推奨）

**最も確実な方法**です。デプロイスクリプトに組み込まれています。

```bash
# Laravelコンテナの再起動
docker-compose -f compose.prod.yaml restart laravel

# 再起動確認
docker-compose -f compose.prod.yaml ps laravel
```

**メリット**:

-   ✅ 確実に OPcache がクリアされる
-   ✅ 追加のパッケージ不要
-   ✅ シンプルで理解しやすい

**デメリット**:

-   ⚠️ 数秒のダウンタイムが発生（ローリングデプロイで回避可能）

### 方法 2: opcache_reset()の実行

Laravel アプリ内で OPcache をクリアする方法です。

#### パッケージのインストール

```bash
composer require appstract/laravel-opcache
```

#### デプロイスクリプトに追加

```bash
# OPcacheクリア
docker-compose -f compose.prod.yaml exec laravel php artisan opcache:clear

# または
curl https://your-domain.com/opcache-clear
```

**メリット**:

-   ✅ ダウンタイムなし
-   ✅ 柔軟な制御

**デメリット**:

-   ⚠️ 追加パッケージが必要
-   ⚠️ Web サーバー経由でアクセス可能にする必要がある

### 方法 3: php-fpm プロセスの再起動

コンテナ内で PHP-FPM プロセスのみを再起動する方法です。

```bash
# PHP-FPMプロセスをリロード（Graceful Restart）
docker-compose -f compose.prod.yaml exec laravel kill -USR2 1

# または完全再起動
docker-compose -f compose.prod.yaml exec laravel kill -TERM 1
```

**メリット**:

-   ✅ コンテナ全体を再起動する必要がない

**デメリット**:

-   ⚠️ 環境によっては動作しない場合がある

---

## OPcache の監視

### OPcache の状態確認

#### Laravel Tinker で確認

```bash
docker-compose -f compose.prod.yaml exec laravel php artisan tinker

>>> opcache_get_status()
```

出力例：

```php
[
    "opcache_enabled" => true,
    "cache_full" => false,
    "restart_pending" => false,
    "restart_in_progress" => false,
    "memory_usage" => [
        "used_memory" => 45678901,
        "free_memory" => 223456789,
        "wasted_memory" => 0,
        "current_wasted_percentage" => 0.0,
    ],
    "opcache_statistics" => [
        "num_cached_scripts" => 432,
        "num_cached_keys" => 567,
        "max_cached_keys" => 16229,
        "hits" => 123456,
        "misses" => 789,
        "blacklist_misses" => 0,
        "blacklist_miss_ratio" => 0.0,
        "opcache_hit_rate" => 99.36,
    ],
]
```

### 重要な指標

| 指標                        | 説明                         | 理想値 |
| --------------------------- | ---------------------------- | ------ |
| `opcache_hit_rate`          | ヒット率                     | > 95%  |
| `cache_full`                | キャッシュが満杯             | false  |
| `current_wasted_percentage` | 無駄なメモリの割合           | < 5%   |
| `num_cached_scripts`        | キャッシュされたスクリプト数 | -      |

### OPcache GUI ツール（開発環境のみ）

```bash
# opcache.phpを作成
cat > public/opcache.php << 'EOF'
<?php
if (getenv('APP_ENV') !== 'production') {
    phpinfo(INFO_MODULES);
}
EOF

# ブラウザでアクセス
# http://localhost/opcache.php
```

> ⚠️ **セキュリティ**: 本番環境では絶対にこのファイルを公開しないでください！

---

## トラブルシューティング

### 問題 1: デプロイ後も古いコードが実行される

**症状**:

-   コードを更新したのに変更が反映されない
-   古いバージョンのコードが実行される

**原因**:

-   OPcache がクリアされていない
-   `validate_timestamps=0` により変更が検知されない

**解決策**:

```bash
# 方法1: コンテナ再起動
docker-compose -f compose.prod.yaml restart laravel

# 方法2: OPcacheクリア
docker-compose -f compose.prod.yaml exec laravel php artisan opcache:clear

# 確認
docker-compose -f compose.prod.yaml exec laravel php artisan tinker
>>> opcache_get_status()['opcache_statistics']['num_cached_scripts']
# 0 または小さい値であればクリア成功
```

### 問題 2: OPcache がメモリ不足

**症状**:

-   `cache_full = true`
-   `current_wasted_percentage` が高い

**原因**:

-   キャッシュ用メモリが不足

**解決策**:

`Dockerfile.prod` のメモリ設定を増やす：

```dockerfile
# 256MB → 512MB に増やす
&& echo "opcache.memory_consumption=512" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
```

その後、イメージを再ビルド：

```bash
docker-compose -f compose.prod.yaml build laravel
docker-compose -f compose.prod.yaml up -d laravel
```

### 問題 3: OPcache ヒット率が低い

**症状**:

-   `opcache_hit_rate < 90%`

**原因**:

-   キャッシュ可能なファイル数が不足
-   頻繁にキャッシュがクリアされている

**解決策**:

`max_accelerated_files` を増やす：

```dockerfile
# 10000 → 20000 に増やす
&& echo "opcache.max_accelerated_files=20000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
```

### 問題 4: コンテナ再起動時にダウンタイムが発生

**症状**:

-   コンテナ再起動中、リクエストが失敗する

**原因**:

-   単一コンテナ構成

**解決策**:

ゼロダウンタイムデプロイを実現するために、以下のいずれかを実施：

#### オプション 1: 複数コンテナ + ロードバランサー

```yaml
services:
    laravel-1:
        # ...
    laravel-2:
        # ...
    nginx:
        # ロードバランサー設定
```

ローリングデプロイ：

```bash
# 1つずつ再起動
docker-compose -f compose.prod.yaml restart laravel-1
sleep 10
docker-compose -f compose.prod.yaml restart laravel-2
```

#### オプション 2: Graceful Reload

```bash
# PHP-FPMをリロード（ダウンタイムなし）
docker-compose -f compose.prod.yaml exec laravel kill -USR2 1
```

---

## ベストプラクティス

### デプロイフロー

```bash
# 1. コードのデプロイ
git pull origin main

# 2. Composer依存関係の更新
docker-compose -f compose.prod.yaml exec laravel composer install --no-dev --optimize-autoloader

# 3. データベースマイグレーション
docker-compose -f compose.prod.yaml exec laravel php artisan migrate --force

# 4. キャッシュクリア
docker-compose -f compose.prod.yaml exec laravel php artisan config:clear
docker-compose -f compose.prod.yaml exec laravel php artisan route:clear
docker-compose -f compose.prod.yaml exec laravel php artisan view:clear

# 5. キャッシュ最適化
docker-compose -f compose.prod.yaml exec laravel php artisan config:cache
docker-compose -f compose.prod.yaml exec laravel php artisan route:cache
docker-compose -f compose.prod.yaml exec laravel php artisan view:cache

# 6. ⚠️ OPcacheクリア（最重要）
docker-compose -f compose.prod.yaml restart laravel

# 7. 動作確認
curl -f https://your-domain.com/health
```

### 監視とアラート

Prometheus でメトリクスを収集：

```yaml
# prometheus.yml
scrape_configs:
    - job_name: "opcache"
      static_configs:
          - targets: ["laravel:9090"]
      metrics_path: "/metrics/opcache"
```

アラートルール：

```yaml
# alerts.yml
groups:
    - name: opcache_alerts
      rules:
          - alert: OPcacheHitRateLow
            expr: opcache_hit_rate < 90
            for: 5m
            annotations:
                summary: "OPcache hit rate is low"

          - alert: OPcacheFull
            expr: opcache_cache_full == 1
            for: 1m
            annotations:
                summary: "OPcache is full"
```

---

## チェックリスト

### デプロイ時

-   [ ] コードをデプロイ
-   [ ] Composer 依存関係を更新
-   [ ] データベースマイグレーション実行
-   [ ] Laravel キャッシュをクリア
-   [ ] **OPcache をクリア（コンテナ再起動）**
-   [ ] ヘルスチェック実施
-   [ ] ログでエラー確認

### 定期監視

-   [ ] OPcache ヒット率を確認（週次）
-   [ ] メモリ使用状況を確認（週次）
-   [ ] キャッシュされたファイル数を確認（週次）

---

## まとめ

| 項目                  | 開発環境  | 本番環境               |
| --------------------- | --------- | ---------------------- |
| `validate_timestamps` | 1（有効） | 0（無効）              |
| ファイル変更検知      | 自動      | なし                   |
| デプロイ後の操作      | 不要      | **OPcache クリア必須** |
| パフォーマンス        | 標準      | 最高                   |

**本番環境では、デプロイ後に必ず OPcache をクリアしてください！**

---

## 参考資料

-   [PHP OPcache 公式ドキュメント](https://www.php.net/manual/ja/book.opcache.php)
-   [Laravel 本番環境最適化](https://laravel.com/docs/deployment#optimization)
-   [デプロイスクリプト](../scripts/deploy.sh)

---

**validate_timestamps=0 を使用する場合、デプロイ後のコンテナ再起動は必須です！**
