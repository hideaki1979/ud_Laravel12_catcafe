# SAML設定ファイルの環境別切り替え

このドキュメントでは、開発環境と本番環境でSAML設定ファイルを切り替える方法を説明します。

## 📋 設定ファイル一覧

```
config/saml2/
├── keycloak_idp_settings.php       # 開発環境用（署名無効）
└── keycloak_idp_settings_prod.php  # 本番環境用（署名有効）
```

## 🔄 切り替え方法

本番環境では、セキュリティ要件を満たすために専用の設定ファイルを使用する必要があります。

> 🎯 **推奨**: `scripts/deploy.sh` を使用した自動切り替え（方法1）が最も安全で確実です。

---

### 方法1: デプロイスクリプト経由（✅ 最推奨）

**最も推奨される方法**です。`scripts/deploy.sh` を実行すると、自動的に本番環境用設定に切り替えられます。

```bash
# デプロイスクリプトの実行
./scripts/deploy.sh
```

デプロイスクリプトは以下を自動で実行します：
- ✅ 本番環境用設定ファイルの存在確認
- ✅ 既存ファイルのバックアップ
- ✅ シンボリックリンクの作成
- ✅ エラーハンドリング

### 方法2: 手動でシンボリックリンク作成

デプロイスクリプトを使わない場合、手動で切り替えることもできます。

```bash
# 本番環境デプロイ時に実行
cd config/saml2

# ファイルが存在するか確認
if [ -f "keycloak_idp_settings_prod.php" ]; then
    # 既存のシンボリックリンクを削除
    if [ -L "keycloak_idp_settings.php" ]; then
        rm keycloak_idp_settings.php
    # 既存のファイルをバックアップ
    elif [ -f "keycloak_idp_settings.php" ]; then
        mv keycloak_idp_settings.php keycloak_idp_settings_dev.php.bak
    fi
    
    # 本番環境用ファイルへのシンボリックリンクを作成
    ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
    
    # 確認
    ls -la keycloak_idp_settings.php
    # 出力例: keycloak_idp_settings.php -> keycloak_idp_settings_prod.php
else
    echo "Error: keycloak_idp_settings_prod.php not found"
    exit 1
fi
```

> ⚠️ **重要**: 手動切り替え時は必ずファイル存在確認とバックアップを行ってください。

### 方法3: ファイルのコピー

```bash
# 本番環境デプロイ時に実行
cd config/saml2

# 既存ファイルをバックアップ
if [ -f "keycloak_idp_settings.php" ] && [ ! -L "keycloak_idp_settings.php" ]; then
    cp keycloak_idp_settings.php keycloak_idp_settings_dev.php.bak
fi

# 本番環境用ファイルをコピー
cp keycloak_idp_settings_prod.php keycloak_idp_settings.php

# 確認
cat keycloak_idp_settings.php | head -20
```

> ⚠️ **注意**: この方法はシンボリックリンクではなく実ファイルをコピーするため、元ファイルが更新されても反映されません。方法1または2を推奨します。

### 方法4: Docker ボリュームマウント

`compose.prod.yaml` でボリュームマウントを使用：

```yaml
services:
    laravel:
        volumes:
            - ./config/saml2/keycloak_idp_settings_prod.php:/var/www/html/config/saml2/keycloak_idp_settings.php:ro
```

> 💡 **利点**: コンテナ外から設定を変更できる  
> ⚠️ **注意**: コンテナ再起動時に自動的に適用される

---

## 💻 デプロイスクリプトの実装詳細（推奨）

`scripts/deploy.sh` には既に堅牢な自動切り替え処理が実装されています。**これが最も推奨される方法**です。

### 実装コード

```bash
# 本番環境用SAML設定に切り替え
echo ""
echo "🔐 Switching to production SAML config..."
cd "$PROJECT_DIR/config/saml2"

if [ -f "keycloak_idp_settings_prod.php" ]; then
    # 既存のシンボリックリンクまたはファイルをバックアップ
    if [ -L "keycloak_idp_settings.php" ]; then
        echo "  Removing existing symbolic link..."
        rm keycloak_idp_settings.php
    elif [ -f "keycloak_idp_settings.php" ] && [ ! -L "keycloak_idp_settings.php" ]; then
        echo "  Backing up development config..."
        mv keycloak_idp_settings.php keycloak_idp_settings_dev.php.bak
    fi
    
    # 本番環境用設定へのシンボリックリンクを作成
    ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
    echo "✓ SAML config switched to production"
    ls -la keycloak_idp_settings.php
else
    echo "⚠️  Warning: keycloak_idp_settings_prod.php not found"
    echo "  Using existing keycloak_idp_settings.php"
fi
cd "$PROJECT_DIR"
```

**実装の安全機能**:
- ✅ ファイル存在チェック
- ✅ 既存のシンボリックリンクの自動削除
- ✅ 開発環境設定の自動バックアップ（`.bak`）
- ✅ エラーハンドリングと詳細なログ出力
- ✅ 検証表示（`ls -la`）

### 使用方法

```bash
# 本番環境にデプロイ
./scripts/deploy.sh

# 出力例:
# 🔐 Switching to production SAML config...
#   Backing up development config...
# ✓ SAML config switched to production
# lrwxr-xr-x 1 user group 32 Nov 21 10:30 keycloak_idp_settings.php -> keycloak_idp_settings_prod.php
```

> 📝 **注意**: この処理は環境変数に関係なく、デプロイスクリプト実行時に常に本番環境用設定に切り替えます。これにより、設定ミスを防ぎます。

### 手動実行する場合

デプロイスクリプトの該当部分のみを実行することもできます：

```bash
cd config/saml2

if [ -f "keycloak_idp_settings_prod.php" ]; then
    if [ -L "keycloak_idp_settings.php" ]; then
        rm keycloak_idp_settings.php
    elif [ -f "keycloak_idp_settings.php" ]; then
        mv keycloak_idp_settings.php keycloak_idp_settings_dev.php.bak
    fi
    ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
    ls -la keycloak_idp_settings.php
fi
```

---

## 📊 設定の違い

### 開発環境用設定 (`keycloak_idp_settings.php`)

```php
return [
    'strict' => env('SAML2_KEYCLOAK_STRICT', true),
    'debug' => env('APP_DEBUG', false),
    
    'security' => [
        // すべての署名・検証が無効
        'authnRequestsSigned' => false,
        'logoutRequestSigned' => false,
        'wantMessagesSigned' => false,
        'wantAssertionsSigned' => false,
        // ...
    ],
];
```

**特徴：**
-   ✅ 開発が容易（署名なしで動作）
-   ❌ セキュリティが不十分
-   ❌ 本番環境では使用不可

### 本番環境用設定 (`keycloak_idp_settings_prod.php`)

```php
return [
    'strict' => true,  // 強制的に true
    'debug' => false,  // 強制的に false
    
    'security' => [
        // すべての署名・検証が有効
        'authnRequestsSigned' => env('SAML2_KEYCLOAK_AUTHN_REQUESTS_SIGNED', true),
        'logoutRequestSigned' => env('SAML2_KEYCLOAK_LOGOUT_REQUEST_SIGNED', true),
        'wantMessagesSigned' => env('SAML2_KEYCLOAK_WANT_MESSAGES_SIGNED', true),
        'wantAssertionsSigned' => env('SAML2_KEYCLOAK_WANT_ASSERTIONS_SIGNED', true),
        // ...
    ],
    
    'proxyVars' => env('SAML2_KEYCLOAK_PROXY_VARS', true),  // ロードバランサー対応
];
```

**特徴：**
-   ✅ 本番環境のセキュリティ要件を満たす
-   ✅ 署名検証によるなりすまし防止
-   ✅ ロードバランサー対応
-   ⚠️ SP証明書・秘密鍵が必須

---

## 🔐 本番環境で必要な追加設定

### 1. SP証明書と秘密鍵の生成

```bash
# 証明書と秘密鍵の生成
openssl req -x509 -newkey rsa:4096 -keyout sp.key -out sp.crt -days 3650 -nodes \
    -subj "/C=JP/ST=Tokyo/L=Tokyo/O=La NekoCafe/CN=lanekocafe.example.com"

# 証明書を適切な場所に配置
mkdir -p storage/saml2
mv sp.key storage/saml2/
mv sp.crt storage/saml2/
chmod 600 storage/saml2/sp.key
chmod 644 storage/saml2/sp.crt
```

### 2. 環境変数の設定

`.env.prod` に以下を追加：

```env
# SP証明書と秘密鍵（改行なし、BEGIN/ENDなし）
SAML2_KEYCLOAK_SP_x509="MIIFaz..."
SAML2_KEYCLOAK_SP_PRIVATEKEY="MIIJQw..."

# 署名設定（本番環境必須）
SAML2_KEYCLOAK_AUTHN_REQUESTS_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_REQUEST_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_RESPONSE_SIGNED=true
SAML2_KEYCLOAK_SIGN_METADATA=true

# 検証設定（本番環境必須）
SAML2_KEYCLOAK_WANT_MESSAGES_SIGNED=true
SAML2_KEYCLOAK_WANT_ASSERTIONS_SIGNED=true

# プロキシ設定（ロードバランサー使用時）
SAML2_KEYCLOAK_PROXY_VARS=true
SAML2_PROXY_VARS=true
```

### 3. Keycloak側の設定更新

Keycloak 管理画面で以下を設定：

1. **Clients** → クライアントを開く
2. **Settings** タブ：
    - **Client signature required**: ON
    - **Encrypt assertions**: ON（オプション）
    - **Sign documents**: ON
    - **Sign assertions**: ON
    - **Signature algorithm**: RSA_SHA256

3. **Keys** タブ：
    - **Import** をクリック
    - SP証明書（`sp.crt`）の内容を貼り付け
    - **Confirm** をクリック

---

## ✅ 設定確認

### 1. SAMLメタデータの確認

```bash
# メタデータを取得
curl -f https://lanekocafe.example.com/saml2/keycloak/metadata

# 署名関連の設定を確認
curl -s https://lanekocafe.example.com/saml2/keycloak/metadata | grep -E 'AuthnRequestsSigned|WantAssertionsSigned'
```

期待される出力：

```xml
<md:SPSSODescriptor AuthnRequestsSigned="true" WantAssertionsSigned="true" ...>
```

### 2. ログでの確認

```bash
# Laravelログを確認
docker-compose -f compose.prod.yaml logs laravel | grep -i saml

# Keycloakログを確認
docker-compose -f compose.prod.yaml logs keycloak | grep -i signature
```

### 3. 動作テスト

1. ブラウザで `https://lanekocafe.example.com/saml2/keycloak/login` にアクセス
2. Keycloakのログイン画面にリダイレクトされる
3. ログイン後、Laravelアプリにリダイレクトされる
4. エラーが発生しないことを確認

---

## 🔧 トラブルシューティング

### 問題1: "Signature validation failed" エラー

**原因**: SP証明書がKeycloak側に登録されていない

**解決策**:

1. Keycloak管理画面でクライアントの **Keys** タブを開く
2. SP証明書をインポート
3. キャッシュをクリア：

```bash
docker-compose -f compose.prod.yaml exec laravel php artisan config:clear
```

### 問題2: "Invalid SAML response" エラー

**原因**: IdP証明書が正しく設定されていない

**解決策**:

1. Keycloakから最新の証明書を取得
2. `.env.prod` の `SAML2_KEYCLOAK_IDP_x509` を更新
3. キャッシュをクリア

### 問題3: リダイレクトURLが HTTP になる

**原因**: プロキシ設定が無効

**解決策**:

`.env.prod` に以下を追加：

```env
SAML2_KEYCLOAK_PROXY_VARS=true
SAML2_PROXY_VARS=true
TRUSTED_PROXIES=*
```

`bootstrap/app.php` を確認：

```php
$middleware->trustProxies(
    at: env('TRUSTED_PROXIES', '*'),
    headers: Request::HEADER_X_FORWARDED_FOR | ...
);
```

### 問題4: "The certificate is not valid" エラー

**原因**: 証明書のフォーマットが不正

**解決策**:

証明書は以下の形式で設定してください：

```env
# ❌ 間違い
SAML2_KEYCLOAK_SP_x509="-----BEGIN CERTIFICATE-----
MIICmzCCAYMC...
-----END CERTIFICATE-----"

# ✅ 正しい
SAML2_KEYCLOAK_SP_x509="MIICmzCCAYMCBgGU...=="
```

---

## 📚 関連ドキュメント

-   [環境変数テンプレート](./ENV_PRODUCTION_TEMPLATE.md)
-   [本番環境デプロイガイド](./PRODUCTION_DEPLOYMENT.md)
-   [Keycloak SAML設定ガイド](./KEYCLOAK_SAML_SETUP.md)

---

## 🔄 ロールバック手順

本番環境で問題が発生した場合、一時的に開発環境用設定に戻すことができます。

```bash
# 緊急時のみ実施（セキュリティリスクあり）
cd config/saml2
rm keycloak_idp_settings.php
ln -s keycloak_idp_settings_dev.php keycloak_idp_settings.php

# キャッシュクリア
docker-compose -f compose.prod.yaml exec laravel php artisan config:clear

# .env.prod で署名を無効化
SAML2_KEYCLOAK_AUTHN_REQUESTS_SIGNED=false
SAML2_KEYCLOAK_WANT_ASSERTIONS_SIGNED=false
SAML2_KEYCLOAK_WANT_MESSAGES_SIGNED=false
```

> ⚠️ **警告**: これは緊急時の一時的な措置です。問題を修正次第、すぐに本番環境用設定に戻してください。

---

**本番環境では必ず本番環境用設定ファイルを使用してください！**

