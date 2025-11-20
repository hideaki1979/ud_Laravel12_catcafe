# 本番環境デプロイ - クイックスタート

このガイドでは、`compose.prod.yaml` を使用した本番環境への最速デプロイ手順を説明します。

## 📁 本番環境用ファイル一覧

```
cat-cafe/
├── compose.prod.yaml              # 本番環境用Docker Compose設定
├── Dockerfile.prod                 # 本番環境用Dockerfile
├── .env.prod                       # 本番環境用環境変数（要作成）
├── docs/
│   ├── PRODUCTION_DEPLOYMENT.md   # 詳細なデプロイガイド
│   ├── PRODUCTION_QUICKSTART.md   # このファイル
│   └── KEYCLOAK_SAML_SETUP.md     # SAML設定ガイド（本番環境セクション追加済み）
└── scripts/
    ├── backup.sh                   # バックアップスクリプト
    ├── restore.sh                  # リストアスクリプト
    └── deploy.sh                   # デプロイスクリプト
```

## ⚡ クイックスタート（5ステップ）

### ステップ1: 環境変数の設定

```bash
# .env.prod を作成
cp .env.example .env.prod

# 以下の項目を必ず設定:
# - APP_KEY（php artisan key:generate で生成）
# - DB_PASSWORD（強力なパスワード）
# - REDIS_PASSWORD（強力なパスワード）
# - KEYCLOAK_ADMIN_PASSWORD（強力なパスワード）
# - KEYCLOAK_DB_PASSWORD（強力なパスワード）
# - GRAFANA_ADMIN_PASSWORD（強力なパスワード）
# - APP_URL（本番環境のURL）
# - KEYCLOAK_HOSTNAME（Keycloakのホスト名）
```

### ステップ2: TLS証明書の配置

```bash
# ディレクトリ作成
mkdir -p docker/ssl docker/keycloak/tls

# 証明書をコピー（Let's Encryptの場合）
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/ssl/
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/ssl/
sudo cp /etc/letsencrypt/live/auth-domain.com/fullchain.pem docker/keycloak/tls/tls.crt
sudo cp /etc/letsencrypt/live/auth-domain.com/privkey.pem docker/keycloak/tls/tls.key

# パーミッション設定
sudo chmod 644 docker/ssl/fullchain.pem
sudo chmod 600 docker/ssl/privkey.pem
sudo chmod 644 docker/keycloak/tls/tls.crt
sudo chmod 600 docker/keycloak/tls/tls.key
```

### ステップ3: 必要な設定ファイルの作成

詳細は [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md) を参照してください。

最低限必要な設定ファイル：

```bash
# Nginx設定
docker/nginx/nginx.prod.conf
docker/nginx/conf.d/laravel.conf

# MySQL設定
docker/mysql/my.cnf

# PostgreSQL設定
docker/postgres/postgresql.conf

# Prometheus設定
docker/prometheus/prometheus.yml
```

### ステップ4: コンテナの起動

```bash
# 全サービスを起動
docker-compose -f compose.prod.yaml up -d

# ログ確認
docker-compose -f compose.prod.yaml logs -f

# すべてのコンテナが起動したことを確認
docker-compose -f compose.prod.yaml ps
```

### ステップ5: アプリケーションの初期化

```bash
# デプロイスクリプトの実行
./scripts/deploy.sh

# または手動で実行:
docker-compose -f compose.prod.yaml exec laravel php artisan migrate --force
docker-compose -f compose.prod.yaml exec laravel php artisan config:cache
docker-compose -f compose.prod.yaml exec laravel php artisan route:cache
docker-compose -f compose.prod.yaml exec laravel php artisan view:cache
```

## ✅ 動作確認チェックリスト

```bash
# 1. Nginxヘルスチェック
curl -f http://localhost/health
# 期待: "healthy"

# 2. Laravelヘルスチェック
docker-compose -f compose.prod.yaml exec laravel php artisan inspire
# 期待: 名言が表示される

# 3. MySQLヘルスチェック
docker-compose -f compose.prod.yaml exec mysql mysqladmin ping -h localhost --silent
# 期待: "mysqld is alive"

# 4. Redisヘルスチェック
docker-compose -f compose.prod.yaml exec redis redis-cli ping
# 期待: "PONG"

# 5. Keycloakヘルスチェック
curl -f -k https://localhost:8443/health/ready
# 期待: {"status": "UP"}

# 6. Prometheusヘルスチェック
curl -f http://localhost:9090/-/healthy
# 期待: "Prometheus is Healthy."

# 7. Grafanaヘルスチェック
curl -f http://localhost:3000/api/health
# 期待: {"database": "ok"}
```

## 🔐 セキュリティチェックリスト

デプロイ後、以下を必ず確認してください：

-   [ ] すべてのパスワードが強力なものに変更されている
-   [ ] `.env.prod` が Git にコミットされていない
-   [ ] TLS/SSL 証明書が正しく設定されている
-   [ ] ファイアウォールで必要なポートのみ開放されている
-   [ ] セキュリティヘッダーが設定されている（Nginx）
-   [ ] レート制限が設定されている
-   [ ] CSRF 保護が有効になっている
-   [ ] セッションが HTTPS のみに制限されている

## 📊 監視ダッシュボードへのアクセス

-   **Grafana**: `http://your-domain:3000`
    -   デフォルト: admin / (GRAFANA_ADMIN_PASSWORD)
-   **Prometheus**: `http://your-domain:9090`

## 🔄 バックアップの設定

```bash
# 手動バックアップ
./scripts/backup.sh

# Cron設定（毎日午前3時）
crontab -e
# 以下を追加:
0 3 * * * /path/to/cat-cafe/scripts/backup.sh >> /var/log/backup.log 2>&1
```

## 🚀 デプロイの自動化

```bash
# デプロイスクリプトの実行
./scripts/deploy.sh

# このスクリプトは以下を実行します:
# 1. Gitプル
# 2. Composer依存関係の更新
# 3. データベースマイグレーション
# 4. キャッシュクリアと最適化
# 5. PHP-FPM再起動
# 6. ヘルスチェック
```

## 📝 日常運用コマンド

```bash
# ログ確認
docker-compose -f compose.prod.yaml logs -f [service_name]

# コンテナの再起動
docker-compose -f compose.prod.yaml restart [service_name]

# コンテナの停止
docker-compose -f compose.prod.yaml stop

# コンテナの起動
docker-compose -f compose.prod.yaml start

# すべてのコンテナを停止して削除（データは保持）
docker-compose -f compose.prod.yaml down

# すべてのコンテナとボリュームを削除（⚠️ データも削除されます）
docker-compose -f compose.prod.yaml down -v
```

## 🔧 トラブルシューティング

### コンテナが起動しない

```bash
# ログ確認
docker-compose -f compose.prod.yaml logs [service_name]

# コンテナの状態確認
docker-compose -f compose.prod.yaml ps

# 強制再作成
docker-compose -f compose.prod.yaml up -d --force-recreate [service_name]
```

### データベース接続エラー

```bash
# MySQLコンテナに入る
docker-compose -f compose.prod.yaml exec mysql bash

# MySQL接続確認
mysql -u root -p${DB_PASSWORD}

# データベース一覧確認
SHOW DATABASES;
```

### Keycloak起動が遅い

Keycloak の初回起動は 2〜3 分かかります。以下のコマンドでログを確認してください：

```bash
docker-compose -f compose.prod.yaml logs -f keycloak
# "Running the server" が表示されるまで待つ
```

## 📚 詳細ドキュメント

より詳細な情報は以下のドキュメントを参照してください：

-   [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md) - 詳細なデプロイ手順
-   [KEYCLOAK_SAML_SETUP.md](./KEYCLOAK_SAML_SETUP.md) - SAML 認証設定（本番環境セクション含む）

## 🆘 サポート

問題が発生した場合は、以下の情報を含めて報告してください：

1. エラーメッセージ
2. コンテナログ（`docker-compose logs [service_name]`）
3. コンテナの状態（`docker-compose ps`）
4. 環境情報（OS、Docker バージョン等）

---

## 📌 重要な注意事項

> ⚠️ **本番環境では必ず以下を実施してください：**
>
> 1. 強力なパスワードの設定
> 2. HTTPS の使用
> 3. 定期的なバックアップ
> 4. セキュリティアップデートの適用
> 5. 監視とログの確認
> 6. ファイアウォールの設定
> 7. 脆弱性スキャンの実施

---

**デプロイの成功を祈ります！ 🎉**

