# 本番環境デプロイメントガイド

このドキュメントでは、`compose.prod.yaml` を使用した本番環境へのデプロイ手順を説明します。

## 📋 目次

-   [前提条件](#前提条件)
-   [1. 環境変数の設定](#1-環境変数の設定)
-   [2. 必要なディレクトリと設定ファイルの作成](#2-必要なディレクトリと設定ファイルの作成)
-   [3. TLS 証明書の準備](#3-tls証明書の準備)
-   [4. データベースの初期化](#4-データベースの初期化)
-   [5. アプリケーションのデプロイ](#5-アプリケーションのデプロイ)
-   [6. 動作確認](#6-動作確認)
-   [7. 監視設定](#7-監視設定)
-   [8. バックアップ設定](#8-バックアップ設定)

---

## 前提条件

-   ✅ Docker と Docker Compose がインストール済み
-   ✅ ドメイン名の取得（例: `lanekocafe.example.com`、`auth.example.com`）
-   ✅ DNS レコードの設定完了
-   ✅ TLS/SSL 証明書の取得（Let's Encrypt または商用 CA）
-   ✅ 必要なポートの開放（80, 443, 3306, 6379, 8443）

---

## 1. 環境変数の設定

### 1.1 本番環境用 .env ファイルの作成

```bash
# .env.prod ファイルを作成
cp .env.example .env.prod
```

### 1.2 必須環境変数の設定

`.env.prod` を編集し、以下の項目を設定します：

```env
# アプリケーション
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lanekocafe.example.com
APP_KEY=  # php artisan key:generate で生成

# データベース（強力なパスワードを設定）
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD

# Redis（強力なパスワードを設定）
REDIS_PASSWORD=CHANGE_THIS_REDIS_PASSWORD

# セッション設定
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true

# Keycloak SAML設定
SAML2_KEYCLOAK_BASE_URL=https://auth.example.com
SAML2_KEYCLOAK_IDP_x509="証明書の内容"
SAML2_SP_x509CERT="SP証明書の内容"
SAML2_SP_PRIVATE_KEY="SP秘密鍵の内容"

# Keycloak設定
KEYCLOAK_HOSTNAME=auth.example.com
KEYCLOAK_ADMIN_PASSWORD=CHANGE_THIS_KEYCLOAK_ADMIN_PASSWORD
KEYCLOAK_DB_PASSWORD=CHANGE_THIS_KEYCLOAK_DB_PASSWORD

# メール設定（Amazon SES推奨）
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret

# 監視設定
GRAFANA_ADMIN_PASSWORD=CHANGE_THIS_GRAFANA_PASSWORD
```

> 🔒 **セキュリティ**: `.env.prod` ファイルは Git にコミットしないでください！

---

## 2. 必要なディレクトリと設定ファイルの作成

### 2.1 ディレクトリ構造の作成

```bash
# Docker設定用ディレクトリ作成
mkdir -p docker/{nginx/conf.d,keycloak/tls,mysql,postgres,prometheus,grafana/provisioning}
mkdir -p docker/ssl
```

### 2.2 Nginx 設定ファイルの作成

`docker/nginx/nginx.prod.conf`:

```nginx
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 4096;
    use epoll;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 20M;

    # Gzip圧縮
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript
               application/json application/javascript application/xml+rss;

    include /etc/nginx/conf.d/*.conf;
}
```

`docker/nginx/conf.d/laravel.conf`:

```nginx
# レート制限ゾーンの定義
limit_req_zone $binary_remote_addr zone=saml_login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=general:10m rate=100r/m;

# HTTPからHTTPSへのリダイレクト
server {
    listen 80;
    server_name lanekocafe.example.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS設定
server {
    listen 443 ssl http2;
    server_name lanekocafe.example.com;

    root /var/www/html/public;
    index index.php index.html;

    # TLS設定
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # セキュリティヘッダー
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;" always;

    # ヘルスチェックエンドポイント
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # SAML エンドポイント（レート制限）
    location /saml2/ {
        limit_req zone=saml_login burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 静的ファイル
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP処理
    location / {
        limit_req zone=general burst=50 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass laravel:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # 隠しファイルへのアクセス拒否
    location ~ /\. {
        deny all;
    }
}
```

### 2.3 MySQL 設定ファイルの作成

`docker/mysql/my.cnf`:

```ini
[mysqld]
# 基本設定
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
default-authentication-plugin=mysql_native_password

# パフォーマンス設定
max_connections=200
innodb_buffer_pool_size=2G
innodb_log_file_size=256M
innodb_flush_log_at_trx_commit=2
innodb_flush_method=O_DIRECT

# スロークエリログ
slow_query_log=1
slow_query_log_file=/var/log/mysql/slow-query.log
long_query_time=2

# バイナリログ（レプリケーション・バックアップ用）
log_bin=/var/log/mysql/mysql-bin.log
binlog_expire_logs_seconds=604800
max_binlog_size=100M

[client]
default-character-set=utf8mb4
```

### 2.4 PostgreSQL 設定ファイルの作成

`docker/postgres/postgresql.conf`:

```ini
# 接続設定
max_connections = 200
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 10MB

# WAL設定
min_wal_size = 1GB
max_wal_size = 4GB
wal_level = replica
max_wal_senders = 3
wal_keep_size = 1GB

# ログ設定
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
log_rotation_age = 1d
log_rotation_size = 100MB
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '
log_min_duration_statement = 1000

# パフォーマンス
shared_preload_libraries = 'pg_stat_statements'
```

### 2.5 Prometheus 設定ファイルの作成

`docker/prometheus/prometheus.yml`:

```yaml
global:
    scrape_interval: 15s
    evaluation_interval: 15s
    external_labels:
        monitor: "lanekocafe-prod"

scrape_configs:
    # Keycloakメトリクス
    - job_name: "keycloak"
      static_configs:
          - targets: ["keycloak:9000"]
      metrics_path: "/metrics"
      scheme: "http"

    # Prometheusセルフモニタリング
    - job_name: "prometheus"
      static_configs:
          - targets: ["localhost:9090"]
```

---

## 3. TLS 証明書の準備

### 3.1 Let's Encrypt 証明書の取得

```bash
# Certbotのインストール
sudo apt-get update
sudo apt-get install certbot

# Laravel用証明書の取得
sudo certbot certonly --standalone \
    -d lanekocafe.example.com \
    --email admin@example.com \
    --agree-tos \
    --non-interactive

# Keycloak用証明書の取得
sudo certbot certonly --standalone \
    -d auth.example.com \
    --email admin@example.com \
    --agree-tos \
    --non-interactive
```

### 3.2 証明書のコピー

```bash
# Nginx用（Laravel）
sudo cp /etc/letsencrypt/live/lanekocafe.example.com/fullchain.pem docker/ssl/
sudo cp /etc/letsencrypt/live/lanekocafe.example.com/privkey.pem docker/ssl/

# Keycloak用
sudo cp /etc/letsencrypt/live/auth.example.com/fullchain.pem docker/keycloak/tls/tls.crt
sudo cp /etc/letsencrypt/live/auth.example.com/privkey.pem docker/keycloak/tls/tls.key

# パーミッション設定
sudo chmod 644 docker/ssl/fullchain.pem
sudo chmod 600 docker/ssl/privkey.pem
sudo chmod 644 docker/keycloak/tls/tls.crt
sudo chmod 600 docker/keycloak/tls/tls.key
```

### 3.3 証明書の自動更新設定

```bash
# Cron設定
sudo crontab -e

# 以下を追加（3ヶ月ごとに更新）
0 0 1 */3 * certbot renew --quiet --deploy-hook "cd /path/to/cat-cafe && docker-compose -f compose.prod.yaml restart nginx keycloak"
```

---

## 4. データベースの初期化

### 4.1 データベースコンテナの起動

```bash
# データベースのみ起動
docker-compose -f compose.prod.yaml up -d mysql redis keycloak-postgres
```

### 4.2 Laravel マイグレーション実行

```bash
# Laravelコンテナに入る
docker-compose -f compose.prod.yaml exec laravel bash

# マイグレーション実行
php artisan migrate --force

# シーダー実行（必要に応じて）
php artisan db:seed --force
```

---

## 5. アプリケーションのデプロイ

### 5.1 全コンテナの起動

```bash
# すべてのサービスを起動
docker-compose -f compose.prod.yaml up -d

# ログ確認
docker-compose -f compose.prod.yaml logs -f
```

### 5.2 キャッシュのクリアと最適化

```bash
# Laravelコンテナに入る
docker-compose -f compose.prod.yaml exec laravel bash

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5.3 OPcache 運用（重要）

> ⚠️ **必読**: 本番環境では `validate_timestamps=0` を使用しているため、デプロイ後に必ず OPcache をクリアしてください

### OPcache 設定の確認

`Dockerfile.prod` には以下の OPcache 設定が含まれています：

```dockerfile
opcache.enable=1
opcache.memory_consumption=256
opcache.validate_timestamps=0  # ← 重要: ファイル変更を検知しない
```

**重要**: `validate_timestamps=0` の設定により、最高のパフォーマンスを実現していますが、**デプロイ後にコンテナを再起動しないと古いコードが実行され続けます**。

### デプロイ後の OPcache クリア方法

デプロイスクリプト（`scripts/deploy.sh`）には自動的にコンテナ再起動が含まれていますが、手動デプロイの場合は以下を実行してください：

#### 方法 1: コンテナ再起動（推奨）

```bash
# Laravelコンテナの再起動（OPcacheクリア）
docker-compose -f compose.prod.yaml restart laravel

# 再起動確認
docker-compose -f compose.prod.yaml ps laravel
```

#### 方法 2: OPcache パッケージ経由

```bash
# パッケージのインストール（初回のみ）
docker-compose -f compose.prod.yaml exec laravel composer require appstract/laravel-opcache

# OPcacheクリア
docker-compose -f compose.prod.yaml exec laravel php artisan opcache:clear
```

### OPcache 状態の確認

```bash
# OPcacheの状態を確認
docker-compose -f compose.prod.yaml exec laravel php artisan tinker

>>> opcache_get_status()['opcache_statistics']['opcache_hit_rate']
# 95%以上であれば正常
```

> 📚 詳細は [OPcache 運用ガイド](./OPCACHE_OPERATIONS.md) を参照してください

---

## 6. 動作確認

### 6.1 ヘルスチェック

```bash
# Nginxヘルスチェック
curl -f http://localhost/health

# Keycloakヘルスチェック
curl -f -k https://localhost:8443/health/ready

# MySQL接続確認
docker-compose -f compose.prod.yaml exec mysql mysql -u root -p${DB_PASSWORD} -e "SELECT 1"

# Redis接続確認
docker-compose -f compose.prod.yaml exec redis redis-cli -a ${REDIS_PASSWORD} ping
```

### 6.2 SAML 認証テスト

1. ブラウザで `https://lanekocafe.example.com` にアクセス
2. SAML ログインをテスト
3. ログイン後、管理画面にアクセスできることを確認

---

## 7. 監視設定

### 7.1 Grafana ダッシュボードのセットアップ

1. `http://localhost:3000` にアクセス
2. 管理者でログイン（`GRAFANA_ADMIN_USER` / `GRAFANA_ADMIN_PASSWORD`）
3. データソースを追加:
    - Type: Prometheus
    - URL: `http://prometheus:9090`
4. ダッシュボードをインポート

### 7.2 アラート設定

Prometheus アラートルールを `docker/prometheus/alerts.yml` に作成：

```yaml
groups:
    - name: application_alerts
      interval: 30s
      rules:
          - alert: HighErrorRate
            expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
            for: 5m
            labels:
                severity: warning
            annotations:
                summary: "High error rate detected"

          - alert: DatabaseDown
            expr: up{job="mysql"} == 0
            for: 1m
            labels:
                severity: critical
            annotations:
                summary: "Database is down"
```

---

## 8. バックアップ設定

### 8.1 自動バックアップスクリプトの作成

`scripts/backup.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# MySQLバックアップ
docker-compose -f compose.prod.yaml exec -T mysql mysqldump \
    -u root -p${DB_PASSWORD} ${DB_DATABASE} | gzip > "${BACKUP_DIR}/mysql_${DATE}.sql.gz"

# Keycloak PostgreSQLバックアップ
docker-compose -f compose.prod.yaml exec -T keycloak-postgres pg_dump \
    -U ${KEYCLOAK_DB_USERNAME} ${KEYCLOAK_DB_NAME} | gzip > "${BACKUP_DIR}/keycloak_${DATE}.sql.gz"

# S3にアップロード
aws s3 cp "${BACKUP_DIR}/mysql_${DATE}.sql.gz" s3://your-backup-bucket/mysql/
aws s3 cp "${BACKUP_DIR}/keycloak_${DATE}.sql.gz" s3://your-backup-bucket/keycloak/

# 古いバックアップを削除
find ${BACKUP_DIR} -name "*.sql.gz" -mtime +${RETENTION_DAYS} -delete

echo "Backup completed: ${DATE}"
```

### 8.2 Cron 設定

```bash
# 毎日午前3時にバックアップ
0 3 * * * /path/to/scripts/backup.sh >> /var/log/backup.log 2>&1
```

---

## トラブルシューティング

### コンテナが起動しない場合

```bash
# ログ確認
docker-compose -f compose.prod.yaml logs コンテナ名

# コンテナの状態確認
docker-compose -f compose.prod.yaml ps

# ボリュームのリセット（データが削除されます）
docker-compose -f compose.prod.yaml down -v
```

### パーミッションエラーが発生する場合

```bash
# storageディレクトリのパーミッション修正
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## セキュリティチェックリスト

-   [ ] 強力なパスワードを設定した
-   [ ] TLS/SSL 証明書が正しく設定されている
-   [ ] ファイアウォールが設定されている
-   [ ] 不要なポートが閉じられている
-   [ ] セキュリティヘッダーが設定されている
-   [ ] レート制限が設定されている
-   [ ] 監査ログが有効になっている
-   [ ] バックアップが正常に動作している
-   [ ] .env.prod が Git にコミットされていない

---

## 参考資料

-   [Keycloak SAML 設定ガイド](./KEYCLOAK_SAML_SETUP.md)
-   [Laravel デプロイメントドキュメント](https://laravel.com/docs/deployment)
-   [Docker Compose 本番環境ベストプラクティス](https://docs.docker.com/compose/production/)
