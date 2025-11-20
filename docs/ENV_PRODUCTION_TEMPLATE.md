# 本番環境用 環境変数テンプレート

このドキュメントは、本番環境で必要なすべての環境変数のテンプレートです。

## 📋 使用方法

1. `.env.prod` ファイルを作成
2. 以下の内容をコピー
3. `CHANGE_THIS_*` の部分を適切な値に置き換え
4. `.env.prod` が `.gitignore` に含まれていることを確認

## 🔒 セキュリティ注意事項

> **⚠️ 重要**: `.env.prod` ファイルは**絶対に** Git にコミットしないでください！

- すべてのパスワードは強力なものを使用（最低 16 文字、英数字記号混在）
- 本番環境では AWS Secrets Manager、Azure Key Vault、HashiCorp Vault などのシークレット管理サービスの使用を推奨

---

## 環境変数テンプレート

```env
# ============================================
# アプリケーション基本設定
# ============================================
APP_NAME="La NekoCafe"
APP_ENV=production
APP_KEY=CHANGE_THIS_APP_KEY  # php artisan key:generate で生成
APP_DEBUG=false
APP_TIMEZONE=Asia/Tokyo
APP_URL=https://lanekocafe.example.com  # 本番環境のURL
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP

# ============================================
# データベース設定（MySQL）
# ============================================
DB_CONNECTION=mysql
DB_HOST=mysql  # Docker Composeのサービス名
DB_PORT=3306
DB_DATABASE=lanekocafe_production
DB_USERNAME=lanekocafe
DB_PASSWORD=CHANGE_THIS_STRONG_DB_PASSWORD  # 強力なパスワード

# ポートフォワード設定（外部からの接続用）
FORWARD_DB_PORT=3306

# ============================================
# Redis設定（セッション・キャッシュ）
# ============================================
REDIS_CLIENT=phpredis
REDIS_HOST=redis  # Docker Composeのサービス名
REDIS_PASSWORD=CHANGE_THIS_STRONG_REDIS_PASSWORD  # 強力なパスワード
REDIS_PORT=6379

# ポートフォワード設定
FORWARD_REDIS_PORT=6379

# ============================================
# セッション設定
# ============================================
SESSION_DRIVER=redis
SESSION_LIFETIME=480  # 8時間（分単位）
SESSION_DOMAIN=.example.com  # 本番環境のドメイン
SESSION_SECURE_COOKIE=true  # HTTPS必須
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# ============================================
# キャッシュ設定
# ============================================
CACHE_STORE=redis
CACHE_PREFIX=lanekocafe_cache

# ============================================
# キュー設定
# ============================================
QUEUE_CONNECTION=redis

# ============================================
# メール設定（Amazon SES推奨）
# ============================================
MAIL_MAILER=ses
MAIL_HOST=email-smtp.ap-northeast-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=CHANGE_THIS_SES_USERNAME
MAIL_PASSWORD=CHANGE_THIS_SES_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@lanekocafe.example.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS設定（SES用）
AWS_ACCESS_KEY_ID=CHANGE_THIS_AWS_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=CHANGE_THIS_AWS_SECRET_KEY
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=lanekocafe-production-bucket  # S3バケット名

# S3バックアップ用バケット（オプション）
AWS_BACKUP_BUCKET=lanekocafe-backups

# ============================================
# ログ設定
# ============================================
LOG_CHANNEL=stack
LOG_STACK=single,daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning  # 本番環境: info または warning

# ============================================
# Keycloak SAML設定（本番環境）
# ============================================

# Keycloak ベースURL（HTTPS必須）
SAML2_KEYCLOAK_BASE_URL=https://auth.example.com
SAML2_KEYCLOAK_REALM=lanekocafe

# Keycloak IdP証明書（Realm Settings > Keys > RS256 の Certificate）
# 注意: 改行を含まず、BEGIN/ENDヘッダーも含まない本文のみを設定
SAML2_KEYCLOAK_IDP_x509="CHANGE_THIS_PASTE_YOUR_IDP_CERTIFICATE_HERE"

# 証明書ローテーション用（オプション）
# SAML2_KEYCLOAK_IDP_x509_NEW=""

# --------------------------------------------
# SP (Laravel) の設定
# --------------------------------------------
SAML2_KEYCLOAK_SP_ENTITYID="${APP_URL}/saml2/keycloak/metadata"
SAML2_KEYCLOAK_SP_ACS_URL="${APP_URL}/saml2/keycloak/acs"
SAML2_KEYCLOAK_SP_SLS_URL="${APP_URL}/saml2/keycloak/sls"

# SP証明書と秘密鍵（本番環境必須）
# 生成方法: openssl req -x509 -newkey rsa:4096 -keyout sp.key -out sp.crt -days 3650 -nodes
SAML2_KEYCLOAK_SP_x509="CHANGE_THIS_PASTE_YOUR_SP_CERTIFICATE_HERE"
SAML2_KEYCLOAK_SP_PRIVATEKEY="CHANGE_THIS_PASTE_YOUR_SP_PRIVATE_KEY_HERE"

# --------------------------------------------
# IdP エンドポイント（自動生成されるが明示的に指定可能）
# --------------------------------------------
SAML2_KEYCLOAK_IDP_ENTITYID="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}"
SAML2_KEYCLOAK_IDP_SSO_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"
SAML2_KEYCLOAK_IDP_SL_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"

# --------------------------------------------
# SAML セキュリティ設定（本番環境）
# --------------------------------------------

# SP -> IdP の署名設定（本番環境では true 推奨）
SAML2_KEYCLOAK_AUTHN_REQUESTS_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_REQUEST_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_RESPONSE_SIGNED=true
SAML2_KEYCLOAK_SIGN_METADATA=true

# IdP -> SP の検証設定（本番環境必須）
SAML2_KEYCLOAK_WANT_MESSAGES_SIGNED=true
SAML2_KEYCLOAK_WANT_ASSERTIONS_SIGNED=true
SAML2_KEYCLOAK_WANT_ASSERTIONS_ENCRYPTED=false  # オプション（HTTPSと併用）

# 暗号化設定（オプション）
SAML2_KEYCLOAK_NAMEID_ENCRYPTED=false
SAML2_KEYCLOAK_WANT_NAMEID_ENCRYPTED=false

# 認証コンテキスト
SAML2_KEYCLOAK_REQUESTED_AUTHN_CONTEXT=true

# 署名アルゴリズム
SAML2_KEYCLOAK_SIGNATURE_ALGORITHM="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"
SAML2_KEYCLOAK_DIGEST_ALGORITHM="http://www.w3.org/2001/04/xmlenc#sha256"

# プロキシ設定（ロードバランサー使用時は true）
SAML2_KEYCLOAK_PROXY_VARS=true
SAML2_PROXY_VARS=true

# --------------------------------------------
# 連絡先情報
# --------------------------------------------
SAML2_CONTACT_TECHNICAL_NAME="La NekoCafe Technical Support"
SAML2_CONTACT_TECHNICAL_EMAIL="tech@lanekocafe.example.com"
SAML2_CONTACT_SUPPORT_NAME="La NekoCafe User Support"
SAML2_CONTACT_SUPPORT_EMAIL="support@lanekocafe.example.com"

# --------------------------------------------
# 組織情報
# --------------------------------------------
SAML2_ORGANIZATION_NAME="La NekoCafe"
SAML2_ORGANIZATION_DISPLAYNAME="La NekoCafe 猫カフェ"
SAML2_ORGANIZATION_NAME_EN="La NekoCafe"
SAML2_ORGANIZATION_DISPLAYNAME_EN="La NekoCafe Cat Cafe"

# ============================================
# Keycloak設定
# ============================================

# Keycloak ホスト名（HTTPS）
KEYCLOAK_HOSTNAME=auth.example.com
KEYCLOAK_PORT=8443

# 管理者アカウント（初回起動時のみ使用）
KEYCLOAK_ADMIN_USERNAME=admin
KEYCLOAK_ADMIN_PASSWORD=CHANGE_THIS_STRONG_KEYCLOAK_ADMIN_PASSWORD

# Keycloak データベース設定（PostgreSQL）
KEYCLOAK_DB_NAME=keycloak
KEYCLOAK_DB_USERNAME=keycloak
KEYCLOAK_DB_PASSWORD=CHANGE_THIS_STRONG_KEYCLOAK_DB_PASSWORD

# ============================================
# 監視・メトリクス設定
# ============================================

# Grafana設定
GRAFANA_ADMIN_USER=admin
GRAFANA_ADMIN_PASSWORD=CHANGE_THIS_STRONG_GRAFANA_PASSWORD

# ============================================
# セキュリティ設定
# ============================================

# 信頼するプロキシ
# '*' = すべてのプロキシを信頼
# 本番環境では特定のIPアドレスを指定推奨
# 例: "10.0.0.1,10.0.0.2"
TRUSTED_PROXIES=*

# ============================================
# ファイルシステム設定
# ============================================
FILESYSTEM_DISK=local

# S3を使用する場合:
# FILESYSTEM_DISK=s3
# AWS_BUCKET=your-bucket-name

# ============================================
# ブロードキャスティング設定
# ============================================
BROADCAST_CONNECTION=log

# Laravel Reverb使用時（オプション）
# BROADCAST_CONNECTION=reverb
# REVERB_APP_ID=your-app-id
# REVERB_APP_KEY=your-app-key
# REVERB_APP_SECRET=your-app-secret
# REVERB_HOST=localhost
# REVERB_PORT=8080
# REVERB_SCHEME=https

# ============================================
# Vite設定
# ============================================
VITE_APP_NAME="${APP_NAME}"

# ============================================
# 追加の本番環境設定
# ============================================

# タイムゾーン
TZ=Asia/Tokyo

# SSL/TLS設定
FORCE_HTTPS=true

# レート制限
RATE_LIMIT_PER_MINUTE=60

# ============================================
# バックアップ設定（スクリプト用）
# ============================================
BACKUP_DIR=/backups
RETENTION_DAYS=30
```

---

## 🔐 証明書の生成方法

### SP証明書と秘密鍵の生成

```bash
# 4096ビットRSA鍵で10年間有効な証明書を生成
openssl req -x509 -newkey rsa:4096 -keyout sp.key -out sp.crt -days 3650 -nodes \
    -subj "/C=JP/ST=Tokyo/L=Tokyo/O=La NekoCafe/CN=lanekocafe.example.com"

# 証明書の内容を表示（改行なし、BEGIN/ENDなし）
openssl x509 -in sp.crt -noout -text
awk 'NF {sub(/\r/, ""); printf "%s",$0;}' sp.crt | sed 's/-----BEGIN CERTIFICATE-----//;s/-----END CERTIFICATE-----//'

# 秘密鍵の内容を表示（改行なし、BEGIN/ENDなし）
awk 'NF {sub(/\r/, ""); printf "%s",$0;}' sp.key | sed 's/-----BEGIN PRIVATE KEY-----//;s/-----END PRIVATE KEY-----//'
```

### IdP証明書の取得

1. Keycloak 管理画面にログイン
2. **Realm settings** → **Keys** タブ
3. **RS256** の行の **Certificate** ボタンをクリック
4. 表示された証明書をコピー（既に改行なし、BEGIN/ENDなしの形式）

---

## ✅ 設定チェックリスト

デプロイ前に、以下を確認してください：

### 必須項目

-   [ ] `APP_KEY` が生成されている（`php artisan key:generate`）
-   [ ] すべてのパスワードが強力なもの（16文字以上）
-   [ ] `APP_URL` が本番環境のURLになっている（HTTPS）
-   [ ] `KEYCLOAK_HOSTNAME` が正しい
-   [ ] IdP証明書（`SAML2_KEYCLOAK_IDP_x509`）が設定されている
-   [ ] SP証明書と秘密鍵が設定されている
-   [ ] メール設定が正しい（SES認証情報）
-   [ ] データベースパスワードが設定されている

### セキュリティ項目

-   [ ] `APP_DEBUG=false` になっている
-   [ ] `APP_ENV=production` になっている
-   [ ] すべてのSAML署名設定が `true` になっている
-   [ ] `SESSION_SECURE_COOKIE=true` になっている
-   [ ] `SAML2_PROXY_VARS=true`（ロードバランサー使用時）

### オプション項目

-   [ ] AWS S3設定（ファイルストレージ用）
-   [ ] バックアップ用S3バケット設定
-   [ ] Grafanaパスワード設定
-   [ ] 信頼するプロキシのIP指定

---

## 📚 関連ドキュメント

-   [本番環境デプロイガイド](./PRODUCTION_DEPLOYMENT.md)
-   [クイックスタートガイド](./PRODUCTION_QUICKSTART.md)
-   [Keycloak SAML設定ガイド](./KEYCLOAK_SAML_SETUP.md)

---

## 🆘 トラブルシューティング

### アプリケーションキーエラー

```bash
docker-compose -f compose.prod.yaml exec laravel php artisan key:generate --show
# 出力された値を APP_KEY に設定
```

### 証明書フォーマットエラー

証明書は以下の形式で設定してください：

```env
# ❌ 間違い（改行やBEGIN/ENDが含まれている）
SAML2_KEYCLOAK_IDP_x509="-----BEGIN CERTIFICATE-----
MIICmzCCAYMC...
-----END CERTIFICATE-----"

# ✅ 正しい（改行なし、BEGIN/ENDなし、1行）
SAML2_KEYCLOAK_IDP_x509="MIICmzCCAYMCBgGU...=="
```

---

**本番環境の設定が完了したら、必ずセキュリティチェックリストを確認してください！**

