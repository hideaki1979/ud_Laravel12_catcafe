# Keycloak SAML 認証設定ガイド

このガイドでは、La NekoCafe Laravel アプリケーションに **Keycloak** を使用した SAML 2.0 認証を設定する手順を説明します。

## 📋 目次

-   [概要](#概要)
-   [前提条件](#前提条件)
-   [1. Keycloak の起動](#1-keycloak-の起動)
-   [2. Keycloak の初期設定](#2-keycloak-の初期設定)
-   [3. レルムの作成](#3-レルムの作成)
-   [4. SAML クライアントの作成](#4-saml-クライアントの作成)
    -   [4.4 Client Scope の削除（重要）](#44-client-scope-の削除重要)
    -   [4.5 SAML マッパーの設定（オプション）](#45-saml-マッパーの設定オプション---スキップ推奨)
-   [5. ユーザーの作成](#5-ユーザーの作成)
-   [6. Laravel 側の設定](#6-laravel-側の設定)
    -   [6.3 カスタムコントローラーの設定（重要）](#63-カスタムコントローラーの設定重要)
    -   [6.4 SamlAuthController への metadata() メソッド追加](#64-samlauthcontroller-への-metadata-メソッド追加)
    -   [6.5 routesMiddleware の設定（重要）](#65-routesmiddleware-の設定重要)
    -   [6.6 CSRF 保護からの除外設定（重要）](#66-csrf保護からの除外設定重要)
-   [7. 動作確認](#7-動作確認)
-   [トラブルシューティング](#トラブルシューティング)

---

## 概要

### なぜ Keycloak なのか？

✅ **完全無料・オープンソース**  
✅ **Docker で簡単にローカル起動可能**  
✅ **SAML 2.0 を完全サポート**  
✅ **クレジットカード不要、組織アカウント不要**  
✅ **実際の SAML フローを完全にテスト可能**  
✅ **本番環境でも使用可能**（Red Hat 製品）

### システム構成

```
┌─────────────────┐         SAML 2.0          ┌─────────────────┐
│                 │◄──────────────────────────►│                 │
│  Laravel App    │   - SSO Login Request      │    Keycloak     │
│  (Service       │   - Assertion Response     │    (Identity    │
│   Provider)     │   - Logout Request         │     Provider)   │
│                 │                            │                 │
└─────────────────┘                            └─────────────────┘
  localhost:80                                   localhost:8080
```

---

## 前提条件

-   ✅ Docker と Docker Compose がインストール済み
-   ✅ Laravel Sail が起動している
-   ✅ `laravel-saml2` パッケージがインストール済み

---

## 1. Keycloak の起動

### 1.1 Docker Compose で起動

`compose.yaml` に Keycloak サービスが既に追加されています：

```yaml
keycloak:
    image: "quay.io/keycloak/keycloak:26.0"
    command: start-dev
    ports:
        - "${KEYCLOAK_PORT:-8080}:8080"
    environment:
        KEYCLOAK_ADMIN: admin
        KEYCLOAK_ADMIN_PASSWORD: admin
        KC_DB: dev-file
        KC_HTTP_RELATIVE_PATH: /
        KC_HOSTNAME_STRICT: false
        KC_HOSTNAME_STRICT_HTTPS: false
        KC_HTTP_ENABLED: true
        KC_HEALTH_ENABLED: true
    volumes:
        - keycloak-data:/opt/keycloak/data
    networks:
        - sail
    healthcheck:
        test:
            [
                "CMD-SHELL",
                "exec 3<>/dev/tcp/127.0.0.1/8080;echo -e \"GET /health/ready HTTP/1.1\r\nhost: 127.0.0.1:8080\r\nConnection: close\r\n\r\n\" >&3;grep \"HTTP/1.1 200 OK\" <&3",
            ]
        interval: 10s
        timeout: 5s
        retries: 30
        start_period: 30s
```

### 1.2 データ永続化について

Keycloak は `dev-file` データベースモードで起動し、データは `keycloak-data` ボリュームに保存されます。これにより、コンテナを再起動してもレルム設定やユーザーデータが保持されます。

`compose.yaml` の最後に以下の volumes 定義が必要です（既に追加済み）：

```yaml
volumes:
    sail-mysql:
        driver: local
    keycloak-data:
        driver: local
```

### 1.3 Keycloak を起動

```bash
# Sailを起動（Keycloakも自動的に起動）
./vendor/bin/sail up -d

# Keycloakのログを確認
./vendor/bin/sail logs keycloak
```

### 1.4 Keycloak 管理画面にアクセス

Keycloak の起動には少し時間がかかります（初回は特に）。以下のコマンドでヘルスチェックが通るまで待ちます：

```bash
# Keycloakの状態を確認
./vendor/bin/sail ps keycloak

# ログで "Running the server" が表示されるまで待つ
./vendor/bin/sail logs -f keycloak
```

ブラウザで以下の URL にアクセス：

```
http://localhost:8080
```

ログイン情報：

-   **ユーザー名**: `admin`
-   **パスワード**: `admin`

---

## 2. Keycloak の初期設定

### 2.1 管理コンソールにログイン

1. `http://localhost:8080` にアクセス
2. **Administration Console** をクリック
3. 以下の情報でログイン：
    - Username: `admin`
    - Password: `admin`

---

## 3. レルムの作成

Keycloak では、**レルム（Realm）** がユーザーとアプリケーションを管理する単位です。

### 3.1 新しいレルムを作成

1. 左上の **master** プルダウンをクリック
2. **Create Realm** をクリック
3. 以下の情報を入力：
    - **Realm name**: `lanekocafe`
    - **Enabled**: ON（デフォルト）
4. **Create** をクリック

---

## 4. SAML クライアントの作成

> ⚠️ **重要**: クライアント作成後、**必ず Client Scope を削除**してください（4.4）。これを忘れると属性重複エラーが発生します。

### 4.1 クライアント作成

1. 左メニューから **Clients** をクリック
2. **Create client** をクリック
3. **General Settings** タブ：
    - **Client type**: `SAML`
    - **Client ID**: `http://localhost/saml2/keycloak/metadata`
        > ⚠️ これは Laravel 側の SP Entity ID と一致する必要があります
4. **Next** をクリック

### 4.2 ログイン設定

**Login settings** タブ：

| 項目                              | 値                                    |
| --------------------------------- | ------------------------------------- |
| **Valid redirect URIs**           | `http://localhost/saml2/keycloak/*`   |
| **IDP-Initiated SSO URL name**    | `lanekocafe`                          |
| **IDP Initiated SSO Relay State** | （空欄）                              |
| **Master SAML Processing URL**    | `http://localhost/saml2/keycloak/acs` |

**Save** をクリック

### 4.3 クライアント詳細設定

**Settings** タブで以下を確認・変更：

| 項目                          | 値         | 説明                       |
| ----------------------------- | ---------- | -------------------------- |
| **Client signature required** | OFF        | 署名なしのリクエストを許可 |
| **Force POST binding**        | ON         | POST binding を強制        |
| **Include AuthnStatement**    | ON         | 認証ステートメントを含める |
| **Sign documents**            | ON         | ドキュメントに署名         |
| **Sign assertions**           | ON         | Assertion に署名           |
| **Signature algorithm**       | RSA_SHA256 | 署名アルゴリズム           |
| **SAML signature key name**   | KEY_ID     | 署名キー名                 |
| **Canonicalization method**   | EXCLUSIVE  | 正規化メソッド             |
| **Name ID format**            | persistent | NameID フォーマット        |

#### ログアウト設定（Single Logout / SLO）

> ⚠️ **重要**: マルチ SP 環境で SLO を正しく動作させるには、以下の設定が必須です。

| 項目                                    | 値                                    | 説明                                                             |
| --------------------------------------- | ------------------------------------- | ---------------------------------------------------------------- |
| **Front channel logout**                | OFF                                   | Back-Channel Logout を使用（推奨）                               |
| **Logout Service POST Binding URL**     | `http://localhost/saml2/keycloak/sls` | Back-Channel Logout で Keycloak が POST リクエストを送信する URL |
| **Logout Service Redirect Binding URL** | `http://localhost/saml2/keycloak/sls` | リダイレクト方式でのログアウト URL                               |

> 📝 **Back-Channel Logout vs Front-Channel Logout**
>
> -   **Back-Channel Logout（推奨）**: Keycloak がサーバーサイドで HTTP POST リクエストを各 SP に送信。ブラウザを経由しないため信頼性が高い。
> -   **Front-Channel Logout**: Keycloak がブラウザ経由（iframe/リダイレクト）で各 SP にログアウトリクエストを送信。ブラウザの制限や CORS 問題が発生しやすい。

**Save** をクリック

### 4.4 Client Scope の削除（重要）

> ⚠️ **必須手順**: デフォルトで割り当てられている Client Scope が SAML Assertion の属性重複エラーを引き起こします。

1. **Client scopes** タブをクリック
2. **Assigned client scopes** セクションで、以下のスコープを削除：
    - **`role_list`**（SAML role list）の右側の **⋮** → **Remove**
    - **`saml_organization`**（Organization Membership）の右側の **⋮** → **Remove**
3. 削除後、残るのは以下のみ：
    - `http://localhost/saml2/keycloak/metadata-dedicated` (None)

> **📝 注意**: Dedicated scope の "None" は正常です。この状態で問題ありません。

### 4.5 SAML マッパーの設定（オプション - スキップ推奨）

> 💡 **推奨**: 初回設定時は**マッパーなし（No mappers）**で進めてください。マッパーがなくても SAML 認証は正常に動作します。

ユーザーの詳細情報（email、名前など）を SAML Assertion に含める必要がある場合のみ、以下の手順でマッパーを設

#### マッパー設定前の注意事項

⚠️ **重要な注意点**:

-   **デフォルトマッパーが既に存在する場合があります**（X500 surname、X500 email など）
-   これらのマッパーは**重複エラーの原因**となるため、すべて削除することを推奨します
-   マッパーなしでも認証は成功し、**NameID**（ユーザーの一意識別子）は取得できます

#### 既存マッパーの削除（推奨）

1. **Client scopes** タブをクリック
2. `lanekocafe-dedicated` をクリック
3. **Mappers** タブをクリック
4. 既にマッパーが存在する場合（X500 surname、X500 email、X500 givenName など）：
    - 各マッパーの行をクリックして詳細画面を開く
    - **Delete** ボタンをクリック
    - すべてのマッパーを削除して「**No mappers**」状態にする

#### 新しいマッパーの追加（必要な場合のみ）

> 📌 **注意**: まずはマッパーなしでログインテストを完了させてください。必要に応じて後から追加できます。

ユーザー情報が必要な場合は、以下のマッパーを**1 つずつ**追加します：

##### メールアドレスマッパー

1. **Configure a new mapper** をクリック
2. **User Property** を選択
3. 以下の情報を入力：

| 項目                          | 値    |
| ----------------------------- | ----- |
| **Name**                      | email |
| **Property**                  | email |
| **SAML Attribute Name**       | email |
| **SAML Attribute NameFormat** | Basic |

4. **Save** をクリック

##### 名前マッパー

1. **Configure a new mapper** をクリック
2. **User Property** を選択
3. 以下の情報を入力：

| 項目                          | 値       |
| ----------------------------- | -------- |
| **Name**                      | name     |
| **Property**                  | username |
| **SAML Attribute Name**       | name     |
| **SAML Attribute NameFormat** | Basic    |

4. **Save** をクリック

> ⚠️ **Add Predefined mapper は使用しないでください**: X500 形式のマッパーが追加され、重複エラーの原因となります。

---

## 5. ユーザーの作成

### 5.1 新しいユーザーを作成

1. 左メニューから **Users** をクリック
2. **Create new user** をクリック
3. 以下の情報を入力：
    - **Username**: `testuser`
    - **Email**: `testuser@example.com`
    - **First name**: `Test`
    - **Last name**: `User`
    - **Email verified**: ON
4. **Create** をクリック

### 5.2 パスワードの設定

1. 作成したユーザーをクリック
2. **Credentials** タブをクリック
3. **Set password** をクリック
4. 以下の情報を入力：
    - **Password**: `password`
    - **Password confirmation**: `password`
    - **Temporary**: OFF（一時的なパスワードではない）
5. **Save** をクリック

### 5.3 メールアドレスの重要性（本番環境）

> ⚠️ **本番環境では必須**: ユーザーにメールアドレスを設定してください

本番環境では、以下の理由からメールアドレスが必須です：

-   パスワードリセット機能
-   通知メールの送信
-   ユーザー識別とコミュニケーション
-   GDPR 等のコンプライアンス対応

**推奨設定**:

1. **Realm settings** → **Login** タブ
2. **Email as username**: ON
3. **Verify email**: ON
4. すべてのユーザーに有効なメールアドレスを設定

**開発環境**: メールアドレスなしでもダミーメールが自動生成されます（警告ログが出力されます）

> 📚 詳細は [SAML 認証 メールアドレス運用ポリシー](./SAML_EMAIL_POLICY.md) を参照してください

---

## 6. Laravel 側の設定（開発環境）

> ⚠️ **重要**: このセクションは開発環境用の設定です。本番環境では [本番環境デプロイメントガイド](#本番環境デプロイメントガイド) を参照してください。
>
> 開発環境で必須の設定：
>
> 1. Keycloak 証明書の取得と.env への設定
> 2. **カスタムコントローラーの設定**（6.3）
> 3. **metadata()メソッドの追加**（6.4）

### 6.1 Keycloak 証明書の取得

1. Keycloak 管理画面で **Realm settings** をクリック
2. **Keys** タブをクリック
3. **RS256** の行の **Certificate** ボタンをクリック
4. 表示された証明書をコピー（`-----BEGIN CERTIFICATE-----` から `-----END CERTIFICATE-----` まで）

### 6.2 本番環境用 IdP 設定ファイルの使用

> ⚠️ **本番環境では専用の設定ファイルを使用してください**

本番環境では、セキュリティ要件を満たすために専用の IdP 設定ファイルを使用する必要があります：

-   **開発環境用**: `config/saml2/keycloak_idp_settings.php`（署名無効）
-   **本番環境用**: `config/saml2/keycloak_idp_settings_prod.php`（署名有効）

**切り替え方法**:

```bash
# 本番環境デプロイ時に実行
cd config/saml2
ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
```

詳細は [SAML 設定ファイルの切り替えガイド](./SAML_CONFIG_SWITCHING.md) を参照してください。

### 6.3 .env ファイルの設定

`.env` ファイルに以下を追加：

```env
# Keycloak SAML設定
SAML2_KEYCLOAK_BASE_URL=http://localhost:8080
SAML2_KEYCLOAK_REALM=lanekocafe

# Keycloak IdP証明書（取得した証明書を1行にして貼り付け）
# 注意: 証明書は改行を含まず、BEGIN/ENDヘッダーも含まない本文のみを設定
SAML2_KEYCLOAK_IDP_x509="MIICmzCCAYMCBgGU...（証明書の内容）...=="

# SP (Laravel) の設定
SAML2_KEYCLOAK_SP_ENTITYID="${APP_URL}/saml2/keycloak/metadata"
SAML2_KEYCLOAK_SP_ACS_URL="${APP_URL}/saml2/keycloak/acs"
SAML2_KEYCLOAK_SP_SLS_URL="${APP_URL}/saml2/keycloak/sls"

# IdP エンドポイント（自動生成されるが、明示的に指定も可能）
SAML2_KEYCLOAK_IDP_ENTITYID="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}"
SAML2_KEYCLOAK_IDP_SSO_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"
SAML2_KEYCLOAK_IDP_SL_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"

# 連絡先情報（オプション）
SAML2_CONTACT_NAME="La NekoCafe Support"
SAML2_CONTACT_EMAIL="support@lanekocafe.example.com"

# 組織情報（オプション）
SAML2_ORGANIZATION_NAME="La NekoCafe"
SAML2_ORGANIZATION_DISPLAYNAME="La NekoCafe 猫カフェ"
```

> **📝 注意**: 証明書の形式
>
> -   `-----BEGIN CERTIFICATE-----` と `-----END CERTIFICATE-----` は除外してください
> -   証明書本文のみを改行なしの 1 行で設定します
> -   Keycloak から取得した証明書をそのままコピー&ペーストすれば正しい形式になります

### 6.3 カスタムコントローラーの設定（重要）

> ⚠️ **必須手順**: `config/saml2_settings.php` でカスタムコントローラーを指定しないと、パッケージのデフォルトコントローラーが使用され、エラーが発生します。

`config/saml2_settings.php` を開き、以下の行を**コメント解除**して設定します：

```php
/**
 * (Optional) Which class implements the route functions.
 * If commented out, defaults to this lib's controller (Aacotroneo\Saml2\Http\Controllers\Saml2Controller).
 * If you need to extend Saml2Controller (e.g. to override the `login()` function to pass
 * a `$returnTo` argument), this value allows you to pass your own controller, and have
 * it used in the routes definition.
 */
'saml2_controller' => \App\Http\Controllers\Auth\SamlAuthController::class,
```

**変更前**:

```php
// 'saml2_controller' => '',
```

**変更後**:

```php
'saml2_controller' => \App\Http\Controllers\Auth\SamlAuthController::class,
```

### 6.4 SamlAuthController への metadata() メソッド追加

カスタムコントローラーを使用する場合、`metadata()` メソッドの実装が必要です。

`app/Http/Controllers/Auth/SamlAuthController.php` の `sls()` メソッドの後に、以下を追加します：

```php
/**
 * SAML メタデータを返す
 * IdP（Keycloak）がSPの情報を取得するために使用
 */
public function metadata(Saml2Auth $saml2Auth)
{
    $metadata = $saml2Auth->getMetadata();

    return response($metadata, 200, [
        'Content-Type' => 'text/xml'
    ]);
}
```

### 6.5 routesMiddleware の設定（重要）

> ⚠️ **必須手順**: SAML ルートに `web` ミドルウェアグループを適用しないと、セッション管理が機能せず、認証後にログイン画面にリダイレクトされます。

`config/saml2_settings.php` を開き、`routesMiddleware` を以下のように設定します：

**変更前**:

```php
'routesMiddleware' => [],
```

**変更後**:

```php
'routesMiddleware' => ['web'],
```

この設定により、SAML ルートで以下が利用可能になります：

-   セッション管理
-   Cookie 処理
-   認証状態の保持

### 6.6 CSRF 保護からの除外設定（重要）

> ⚠️ **必須手順**: Keycloak（外部 IdP）からの POST リクエストには CSRF トークンが含まれないため、SAML の ACS エンドポイントを CSRF 保護から除外する必要があります。

`bootstrap/app.php` を開き、`withMiddleware` セクションに以下を追加します：

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn() => route('admin.login'));
    $middleware->redirectUsersTo(fn() => route('admin.blogs.index'));

    // SAML ACSエンドポイントをCSRF保護から除外
    $middleware->validateCsrfTokens(except: [
        'saml2/keycloak/acs',
    ]);
})
```

この設定により、`/saml2/keycloak/acs` エンドポイントへの POST リクエストが 419 エラーなく処理されます。

### 6.7 設定キャッシュのクリア

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
```

---

## 7. 動作確認

### 7.1 SAML メタデータの確認

ブラウザで以下の URL にアクセスして、SAML メタデータが正しく生成されているか確認：

```
http://localhost/saml2/keycloak/metadata
```

以下のような XML が表示されれば成功です：

```xml
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     entityID="http://localhost/saml2/keycloak/metadata">
    <md:SPSSODescriptor AuthnRequestsSigned="false"
                        WantAssertionsSigned="false"
                        protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="http://localhost/saml2/keycloak/sls"/>
        <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</md:NameIDFormat>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                     Location="http://localhost/saml2/keycloak/acs"
                                     index="1"/>
    </md:SPSSODescriptor>
</md:EntityDescriptor>
```

> **エラーが表示される場合**: トラブルシューティングの「問題 6」を参照してください。

### 7.2 SAML ログインテスト

1. ブラウザで以下の URL にアクセス：

    ```
    http://localhost/saml2/keycloak/login
    ```

2. Keycloak のログイン画面にリダイレクトされます

3. 先ほど作成したユーザーでログイン：

    - **Username**: `testuser`
    - **Password**: `password`

4. ログインに成功すると、Laravel アプリにリダイレクトされます

### 7.3 SamlAuthController での処理確認

`app/Http/Controllers/Auth/SamlAuthController.php` の `acs()` メソッドで、以下の情報が取得できることを確認：

```php
$user = $saml2Auth->getSaml2User();
$attributes = $user->getAttributes();

// 取得できる情報の例
[
    'email' => 'testuser@example.com',
    'name' => 'testuser',
]
```

---

## トラブルシューティング

### 問題 1: "Invalid SAML response" エラー

**原因**: 証明書が正しく設定されていない

**解決策**:

1. Keycloak の証明書を再取得
2. `.env` ファイルの `SAML2_KEYCLOAK_IDP_x509` を更新
3. `./vendor/bin/sail artisan config:clear` を実行

### 問題 2: ログイン後にリダイレクトされない

**原因**: ACS URL が正しく設定されていない

**解決策**:

1. Keycloak の **Valid redirect URIs** を確認
2. `http://localhost/saml2/keycloak/*` が設定されているか確認

### 問題 3: Keycloak が起動しない / 起動に時間がかかる

**原因 1**: 初回起動時はデータベースの初期化に時間がかかる（1〜2 分程度）

**解決策**:

1. ログを確認して起動を待つ：
    ```bash
    ./vendor/bin/sail logs -f keycloak
    ```
2. "Running the server" というメッセージが表示されるまで待つ
3. healthcheck が通るまで待つ（最大 5 分）

**原因 2**: ポート 8080 が既に使用されている

**解決策**:

1. `.env` ファイルに `KEYCLOAK_PORT=8081` を追加
2. `compose.yaml` を再起動: `./vendor/bin/sail restart`

**原因 3**: 以前の不完全なデータが残っている

**解決策**:

1. Keycloak のボリュームを削除して再起動：
    ```bash
    ./vendor/bin/sail down -v
    docker volume rm cat-cafe_keycloak-data
    ./vendor/bin/sail up -d
    ```

### 問題 4: "Client signature required" エラー

**原因**: Keycloak が署名を要求している

**解決策**:

1. Keycloak 管理画面でクライアント設定を開く
2. **Client signature required** を **OFF** に設定

### 問題 5: "Found an Attribute element with duplicated Name" エラー

**原因**: Keycloak の Client Scope（`role_list`、`saml_organization` など）が重複した属性を送信している

**解決策**:

1. Keycloak 管理画面で **Clients** → `http://localhost/saml2/keycloak/metadata` をクリック
2. **Client scopes** タブをクリック
3. **Assigned client scopes** セクションを確認
4. 以下のスコープを **Remove** する：
    - `role_list`（SAML role list、Type: Default）
    - `saml_organization`（Organization Membership、Type: Default）
5. 残るのは `http://localhost/saml2/keycloak/metadata-dedicated` のみ
6. ブラウザで再度ログインテスト

> **📝 注意**: Dedicated scope 内のマッパーも重複エラーの原因になる場合があります。その場合は、Dedicated scope の Mappers タブですべてのマッパーを削除してください。

### 問題 6: "Call to undefined method ...SamlAuthController::metadata()" エラー

**原因**: カスタムコントローラーに `metadata()` メソッドが実装されていない

**解決策**:

1. `app/Http/Controllers/Auth/SamlAuthController.php` に `metadata()` メソッドを追加：

```php
public function metadata(Saml2Auth $saml2Auth)
{
    $metadata = $saml2Auth->getMetadata();

    return response($metadata, 200, [
        'Content-Type' => 'text/xml'
    ]);
}
```

2. キャッシュをクリア：
    ```bash
    ./vendor/bin/sail artisan route:clear
    ```

### 問題 7: ログイン後に Laravel のログイン画面に戻ってしまう

**原因 1**: `config/saml2_settings.php` でカスタムコントローラーが設定されていない

**解決策**:

1. `config/saml2_settings.php` を開く
2. 以下の行を追加（コメント解除）：
    ```php
    'saml2_controller' => \App\Http\Controllers\Auth\SamlAuthController::class,
    ```
3. キャッシュをクリア：
    ```bash
    ./vendor/bin/sail artisan config:clear
    ./vendor/bin/sail artisan route:clear
    ```

**原因 2**: `users` テーブルに `saml_id` カラムが存在しない

**解決策**: マイグレーションを実行して `saml_id` カラムを追加（次のステップを参照）

### 問題 8: "Unknown column 'saml_id' in 'where clause'" エラー

**原因**: `users` テーブルに `saml_id` カラムが存在しない

**解決策**: 「次のステップ」セクションの手順に従って、マイグレーションを実行してください。

### 問題 9: "419 Page Expired" エラー

**原因**: Keycloak（外部 IdP）からの POST リクエストに CSRF トークンが含まれていない

**解決策**:

`bootstrap/app.php` で SAML ACS エンドポイントを CSRF 保護から除外してください（セクション 6.6 参照）：

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn() => route('admin.login'));
    $middleware->redirectUsersTo(fn() => route('admin.blogs.index'));

    // SAML ACSエンドポイントをCSRF保護から除外
    $middleware->validateCsrfTokens(except: [
        'saml2/keycloak/acs',
    ]);
})
```

### 問題 10: ログイン後にログイン画面にリダイレクトされる

**原因**: SAML ルートに `web` ミドルウェアグループが適用されていないため、セッション管理が機能していない

**症状**:

-   ログは「SAML 認証成功」と表示される
-   しかし、ログイン画面に戻される
-   `sessions` テーブルの `user_id` が `NULL` になっている

**解決策**:

`config/saml2_settings.php` で `routesMiddleware` を設定してください（セクション 6.5 参照）：

```php
'routesMiddleware' => ['web'],
```

設定後、キャッシュをクリア：

```bash
./vendor/bin/sail artisan config:clear
```

### 問題 11: `saml_id` がデータベースに保存されない（NULL のまま）

**原因**: User モデルの `$fillable` プロパティに `saml_id` が含まれていない

**症状**:

-   ログには `saml_id` が表示される（例: `G-d5caa5a3-19ff-4975-82cc-5b1e9829bbdf`）
-   しかし、データベースの `users.saml_id` カラムが `NULL` のまま

**解決策**:

`app/Models/User.php` の `$fillable` に `saml_id` を追加してください（「次のステップ」セクションのステップ 4 参照）：

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'image',
    'introduction',
    'saml_id',  // ← 追加
];
```

### 問題 12: マルチ SP 環境で SLO（Single Logout）が他の SP に伝播しない

**症状**:

-   SPA 側でログアウト → SPA のセッションは終了、Keycloak セッションも終了
-   しかし、Laravel 側はリロードしてもダッシュボードが表示される（セッションが残っている）

**原因 1**: Keycloak の Logout Service URL が設定されていない

**解決策**:

Keycloak 管理画面で各 SAML クライアントに**Logout Service URL**を設定してください：

1. **Clients** → 対象のクライアント（例: `http://localhost/saml2/keycloak/metadata`）を開く
2. **Settings** タブで以下を設定：
    - **Front channel logout**: OFF
    - **Logout Service POST Binding URL**: `http://localhost/saml2/keycloak/sls`
    - **Logout Service Redirect Binding URL**: `http://localhost/saml2/keycloak/sls`
3. **Save** をクリック

> ⚠️ **重要**: すべての SAML クライアント（Laravel 用、SPA Backend 用など）に同様の設定が必要です。

**原因 2**: Laravel 側で Saml2LogoutEvent リスナーが未実装

**解決策**:

`app/Providers/AppServiceProvider.php` にイベントリスナーを追加してください：

```php
use Aacotroneo\Saml2\Events\Saml2LogoutEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;

public function boot(): void
{
    // ... 既存のコード ...

    // SAML SLO イベントリスナー
    Event::listen(Saml2LogoutEvent::class, function (Saml2LogoutEvent $event) {
        Auth::logout();
        Session::save();
    });
}
```

> 📝 **参考**: [aacotroneo/laravel-saml2 公式 GitHub](https://github.com/aacotroneo/laravel-saml2)

**原因 3**: CSRF 保護により SLS エンドポイントがブロックされている

**解決策**:

`bootstrap/app.php` で SLS エンドポイントを CSRF 保護から除外してください：

```php
$middleware->validateCsrfTokens(except: [
    'saml2/keycloak/acs',
    'saml2/keycloak/sls',  // ← SLSも除外
]);
```

**原因 4**: AuthController::logout() が SAML ユーザーの場合に Keycloak へ LogoutRequest を送信していない

**症状**:

-   Laravel 側のログアウトボタンをクリックしても、SPA 側のセッションがクリアされない
-   ローカルセッションのみクリアされ、Keycloak セッションは維持される

**解決策**:

`app/Http/Controllers/Admin/AuthController.php` の `logout()` メソッドを修正し、SAML でログインしたユーザー（`saml_id`が設定されている）の場合は`saml2_logout`ルートにリダイレクトします：

```php
public function logout(Request $request)
{
    $user = Auth::user();

    // SAMLでログインしたユーザーの場合はSAML SLOルートにリダイレクト
    // SamlAuthController::logout() が KeycloakへのLogoutRequestを送信
    if ($user && !empty($user->saml_id)) {
        // ローカルセッションをクリア
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // 'keycloak' をルートパラメータとして渡し、SAMLパッケージに正しいIdP設定をロードさせる
        return redirect()->route('saml2_logout', 'keycloak');
    }

    // 通常のフォームログインの場合は従来通り
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login');
}
```

> ⚠️ **重要な設計原則**:
>
> -   `AuthController`: ローカルのフォームログイン/ログアウト専用
> -   `SamlAuthController`: SAML 認証（SSO/SLO）専用
> -   SAML ユーザーのログアウトは必ず`SamlAuthController::logout()`経由で Keycloak に LogoutRequest を送信する

**原因 5**: Keycloak が POST で LogoutRequest を送信するが、Laravel 側に POST ルートがない

**症状**:

-   Keycloak からの Back-Channel Logout が届かない
-   `405 Method Not Allowed` エラー

**解決策**:

`routes/web.php` に POST 版の SLS ルートを追加してください：

```php
use App\Http\Controllers\Auth\SamlAuthController;

// SAML SLS (Single Logout Service) - POST版を追加
// パッケージのデフォルトルートはGETのみだが、KeycloakはPOSTでLogoutRequestを送信する場合がある
Route::middleware(config('saml2_settings.routesMiddleware'))
    ->prefix(config('saml2_settings.routesPrefix'))
    ->group(function () {
        Route::post('/{idpName}/sls', [SamlAuthController::class, 'sls'])
            ->name('saml2_sls_post');
    });
```

> 📝 **参考**: Keycloak は`Front channel logout`が OFF の場合でも、Back-Channel Logout で POST リクエストを送信することがあります。

---

## 次のステップ

✅ Keycloak SAML 認証の基本設定が完了しました

### 現在の状態

-   ✅ Keycloak の起動とレルム作成完了
-   ✅ SAML クライアント作成完了
-   ✅ Client Scope の削除完了（属性重複エラー解消）
-   ✅ Laravel 側の設定完了（カスタムコントローラー、metadata メソッド）
-   ✅ `routesMiddleware` 設定完了（セッション管理有効化）
-   ✅ CSRF 保護からの除外設定完了（419 エラー解消）
-   ✅ `users` テーブルへの `saml_id` カラム追加完了
-   ✅ User モデルの `$fillable` に `saml_id` 追加完了
-   ✅ **SAML 認証フロー完全動作確認済み**（Keycloak ログイン → Laravel 管理画面遷移成功）

### 次に必要な実装

1. **User モデルへの `saml_id` カラム追加**（必須）

    **ステップ 1**: マイグレーションファイル作成

    ```bash
    ./vendor/bin/sail artisan make:migration add_saml_id_to_users_table --table=users
    ```

    **ステップ 2**: マイグレーションファイルの内容を編集

    ```php
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('saml_id')->nullable()->unique()->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('saml_id');
        });
    }
    ```

    **ステップ 3**: マイグレーション実行

    ```bash
    ./vendor/bin/sail artisan migrate
    ```

    **ステップ 4**: User モデルの `$fillable` に `saml_id` を追加（重要）

    > ⚠️ **重要**: この手順を忘れると、`saml_id` がデータベースに保存されません（Laravel の Mass Assignment Protection により無視されます）。

    `app/Models/User.php` を開き、`$fillable` プロパティに `saml_id` を追加します：

    **変更前**:

    ```php
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'introduction'
    ];
    ```

    **変更後**:

    ```php
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'introduction',
        'saml_id',
    ];
    ```

2. **ログイン画面に Keycloak ログインボタン追加**

    - ログイン画面の UI 更新
    - `/saml2/keycloak/login` へのリンク追加

3. **Laravel Reverb のインストールと設定**
    - リアルタイム通知機能の実装

---

## 参考情報

### Keycloak 公式ドキュメント

-   [Keycloak Documentation](https://www.keycloak.org/documentation)
-   [SAML Clients](https://www.keycloak.org/docs/latest/server_admin/#_saml-clients)

### Laravel SAML2 パッケージ

-   [laravel-saml2 GitHub](https://github.com/aacotroneo/laravel-saml2)

### SAML 2.0 仕様

-   [SAML 2.0 Technical Overview](http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-tech-overview-2.0.html)

---

## 本番環境デプロイメントガイド

> ⚠️ **重要**: 以下のセクションは本番環境にデプロイする際に必要な設定を説明しています。開発環境では不要です。

### 概要

本番環境では、セキュリティ、パフォーマンス、可用性、コンプライアンスの観点から、開発環境とは異なる多くの設定が必要になります。

---

## 本番環境の前提条件

### 必須要件

-   ✅ HTTPS/TLS 証明書（Let's Encrypt または商用 CA）
-   ✅ 本番用データベース（PostgreSQL または MySQL）
-   ✅ リバースプロキシ（Nginx または Apache）
-   ✅ ドメイン名（例: `lanekocafe.example.com`、`auth.example.com`）
-   ✅ ファイアウォールとセキュリティグループの設定
-   ✅ バックアップストレージ（S3 または互換ストレージ）

### 推奨要件

-   📌 ロードバランサー（AWS ALB、Nginx など）
-   📌 監視ツール（Prometheus + Grafana、CloudWatch など）
-   📌 ログ集約システム（ELK Stack、CloudWatch Logs など）
-   📌 CDN（CloudFront、Cloudflare など）
-   📌 WAF（Web Application Firewall）

---

## 1. 本番環境 Keycloak のデプロイ

### 1.1 Docker Compose 設定（本番環境）

`compose.prod.yaml` を作成（または既存の `compose.yaml` を本番用に調整）：

```yaml
services:
    keycloak:
        image: "quay.io/keycloak/keycloak:26.0"
        command: start --optimized
        ports:
            - "8443:8443" # HTTPS
        environment:
            # 管理者アカウント（初回起動時のみ）
            KEYCLOAK_ADMIN: ${KEYCLOAK_ADMIN_USERNAME}
            KEYCLOAK_ADMIN_PASSWORD: ${KEYCLOAK_ADMIN_PASSWORD}

            # データベース設定（PostgreSQL）
            KC_DB: postgres
            KC_DB_URL: jdbc:postgresql://${DB_HOST}:${DB_PORT}/${DB_NAME}
            KC_DB_USERNAME: ${DB_USERNAME}
            KC_DB_PASSWORD: ${DB_PASSWORD}

            # HTTPS/TLS設定
            KC_HTTPS_CERTIFICATE_FILE: /opt/keycloak/conf/tls.crt
            KC_HTTPS_CERTIFICATE_KEY_FILE: /opt/keycloak/conf/tls.key

            # ホスト名設定
            KC_HOSTNAME: auth.example.com
            KC_HOSTNAME_STRICT: true
            KC_HOSTNAME_STRICT_HTTPS: true
            KC_HTTP_ENABLED: false # HTTPを無効化

            # プロキシ設定（リバースプロキシ使用時）
            KC_PROXY: edge # または reencrypt/passthrough
            KC_PROXY_HEADERS: xforwarded

            # ヘルスチェック
            KC_HEALTH_ENABLED: true
            KC_METRICS_ENABLED: true

            # ログレベル
            KC_LOG_LEVEL: INFO

            # キャッシュ設定（クラスタリング時）
            KC_CACHE: ispn
            KC_CACHE_STACK: kubernetes # またはtcp
        volumes:
            - ./tls/tls.crt:/opt/keycloak/conf/tls.crt:ro
            - ./tls/tls.key:/opt/keycloak/conf/tls.key:ro
            - keycloak-data:/opt/keycloak/data
        networks:
            - internal
        healthcheck:
            test:
                [
                    "CMD-SHELL",
                    "exec 3<>/dev/tcp/127.0.0.1/8443; echo -e \"GET /health/ready HTTP/1.1\r\nhost: 127.0.0.1:8443\r\nConnection: close\r\n\r\n\" >&3; grep \"HTTP/1.1 200 OK\" <&3",
                ]
            interval: 30s
            timeout: 10s
            retries: 3
            start_period: 60s

    postgres:
        image: postgres:16-alpine
        environment:
            POSTGRES_DB: keycloak
            POSTGRES_USER: ${DB_USERNAME}
            POSTGRES_PASSWORD: ${DB_PASSWORD}
        volumes:
            - postgres-data:/var/lib/postgresql/data
        networks:
            - internal
        healthcheck:
            test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
            interval: 10s
            timeout: 5s
            retries: 5

volumes:
    keycloak-data:
        driver: local
    postgres-data:
        driver: local

networks:
    internal:
        driver: bridge
```

### 1.2 環境変数の管理

`.env.prod` ファイルを作成（**Git にコミットしない**）：

```env
# Keycloak管理者アカウント
KEYCLOAK_ADMIN_USERNAME=admin
KEYCLOAK_ADMIN_PASSWORD=<強力なパスワード>

# データベース設定
DB_HOST=postgres
DB_PORT=5432
DB_NAME=keycloak
DB_USERNAME=keycloak
DB_PASSWORD=<強力なパスワード>
```

> 🔒 **セキュリティ**: 本番環境では、AWS Secrets Manager、Azure Key Vault、HashiCorp Vault などのシークレット管理サービスを使用してください。

### 1.3 TLS/SSL 証明書の取得

#### Let's Encrypt の使用（推奨）

```bash
# Certbotのインストール
sudo apt-get update
sudo apt-get install certbot

# 証明書の取得（スタンドアロンモード）
sudo certbot certonly --standalone \
    -d auth.example.com \
    --email admin@example.com \
    --agree-tos \
    --non-interactive

# 証明書をコピー
sudo cp /etc/letsencrypt/live/auth.example.com/fullchain.pem ./tls/tls.crt
sudo cp /etc/letsencrypt/live/auth.example.com/privkey.pem ./tls/tls.key
sudo chmod 644 ./tls/tls.crt
sudo chmod 600 ./tls/tls.key
```

#### 証明書の自動更新

```bash
# Cronで90日ごとに更新
sudo crontab -e

# 以下を追加
0 0 1 */3 * certbot renew --quiet --deploy-hook "docker restart keycloak"
```

### 1.4 データベースの初期化と移行

```bash
# PostgreSQLコンテナ起動
docker-compose -f compose.prod.yaml up -d postgres

# データベース接続確認
docker-compose -f compose.prod.yaml exec postgres psql -U keycloak -c "\l"

# Keycloak起動（自動的にスキーマ作成）
docker-compose -f compose.prod.yaml up -d keycloak

# マイグレーション確認
docker-compose -f compose.prod.yaml logs keycloak | grep "Migrating"
```

---

## 2. セキュリティ強化

### 2.1 本番環境用 IdP 設定ファイルの使用

> ⚠️ **重要**: 本番環境では専用の設定ファイルを使用してください

本番環境では、`config/saml2/keycloak_idp_settings_prod.php` を使用します。このファイルは以下のセキュリティ機能が有効化されています：

-   ✅ strict モードが強制的に有効
-   ✅ すべての署名機能が有効
-   ✅ メッセージと Assertion の検証が必須
-   ✅ ロードバランサー対応（proxyVars）
-   ✅ HTTPS 必須
-   ✅ デバッグモード無効

**設定ファイルの切り替え**:

```bash
cd config/saml2
ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
```

> 📚 詳細は [SAML 設定ファイルの切り替えガイド](./SAML_CONFIG_SWITCHING.md) を参照してください

### 2.2 SAML 署名と暗号化の有効化

本番環境用 IdP 設定ファイル (`keycloak_idp_settings_prod.php`) では、以下のセキュリティ設定がデフォルトで有効化されています：

```php
'security' => [
    // SP -> IdP の署名設定（本番環境必須）
    'authnRequestsSigned' => true,      // 認証リクエストに署名
    'logoutRequestSigned' => true,      // ログアウトリクエストに署名
    'logoutResponseSigned' => true,     // ログアウトレスポンスに署名
    'signMetadata' => true,             // メタデータに署名

    // IdP -> SP の検証設定（本番環境必須）
    'wantMessagesSigned' => true,       // メッセージの署名を要求
    'wantAssertionsSigned' => true,     // Assertionの署名を要求（なりすまし防止）

    // 暗号化設定（オプション）
    'wantAssertionsEncrypted' => false, // Assertionの暗号化を要求
    'nameIdEncrypted' => false,         // NameIDの暗号化

    // 署名アルゴリズム
    'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
],
```

#### 環境変数での制御

`.env.prod` で署名設定を制御できます：

```env
# SP -> IdP の署名設定
SAML2_KEYCLOAK_AUTHN_REQUESTS_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_REQUEST_SIGNED=true
SAML2_KEYCLOAK_LOGOUT_RESPONSE_SIGNED=true
SAML2_KEYCLOAK_SIGN_METADATA=true

# IdP -> SP の検証設定（本番環境必須）
SAML2_KEYCLOAK_WANT_MESSAGES_SIGNED=true
SAML2_KEYCLOAK_WANT_ASSERTIONS_SIGNED=true

# プロキシ設定（ロードバランサー使用時）
SAML2_KEYCLOAK_PROXY_VARS=true
SAML2_PROXY_VARS=true
```

> 📚 完全な環境変数リストは [ENV_PRODUCTION_TEMPLATE.md](./ENV_PRODUCTION_TEMPLATE.md) を参照してください

### 2.3 SP 証明書とキーの生成

> ⚠️ **必須**: 本番環境で署名を有効化する場合、SP 証明書と秘密鍵が必要です

```bash
# 証明書とキーの生成（10年有効）
openssl req -x509 -newkey rsa:4096 -keyout sp.key -out sp.crt -days 3650 -nodes \
    -subj "/C=JP/ST=Tokyo/L=Tokyo/O=La NekoCafe/CN=lanekocafe.example.com"

# 証明書を適切な場所に配置
mkdir -p storage/saml2
mv sp.key storage/saml2/
mv sp.crt storage/saml2/
chmod 600 storage/saml2/sp.key
chmod 644 storage/saml2/sp.crt
```

#### 環境変数に証明書を設定

`.env.prod` に証明書を追加：

```env
# SP証明書（改行なし、BEGIN/ENDなし）
SAML2_SP_x509CERT="MIIFaz..."

# SP秘密鍵（改行なし、BEGIN/ENDなし）
SAML2_SP_PRIVATE_KEY="MIIJQw..."
```

### 2.4 Keycloak クライアント設定の更新

Keycloak 管理画面で以下を更新：

1. **Clients** → `https://lanekocafe.example.com/saml2/keycloak/metadata` を開く
2. **Settings** タブ：
    - **Client signature required**: ON
    - **Encrypt assertions**: ON（オプション）
    - **Sign documents**: ON
    - **Sign assertions**: ON
    - **Signature algorithm**: RSA_SHA256
3. **Keys** タブ：
    - **Import** をクリック
    - `sp.crt` の内容を貼り付け（`-----BEGIN CERTIFICATE-----` から `-----END CERTIFICATE-----` まで）
    - **Confirm** をクリック

### 2.5 セキュリティヘッダーの設定

#### Nginx リバースプロキシ設定

```nginx
server {
    listen 443 ssl http2;
    server_name lanekocafe.example.com;

    # TLS設定
    ssl_certificate /etc/letsencrypt/live/lanekocafe.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/lanekocafe.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;

    # セキュリティヘッダー
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;

    # レート制限
    limit_req_zone $binary_remote_addr zone=saml_login:10m rate=5r/m;
    limit_req_zone $binary_remote_addr zone=general:10m rate=100r/m;

    # SAML エンドポイント（レート制限）
    location /saml2/ {
        limit_req zone=saml_login burst=10 nodelay;
        proxy_pass http://laravel:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 一般的なリクエスト
    location / {
        limit_req zone=general burst=50 nodelay;
        proxy_pass http://laravel:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 2.6 セッション管理とタイムアウト設定

#### Laravel セッション設定

`.env`:

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=480  # 8時間
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

`config/session.php`:

```php
'lifetime' => env('SESSION_LIFETIME', 480),
'expire_on_close' => true,
'encrypt' => true,
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => env('SESSION_HTTP_ONLY', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

#### Keycloak セッション設定

Keycloak 管理画面：

1. **Realm settings** → **Tokens** タブ
2. 以下を設定：
    - **SSO Session Idle**: 30 minutes
    - **SSO Session Max**: 8 hours
    - **Client Session Idle**: 30 minutes
    - **Client Session Max**: 8 hours
    - **Login timeout**: 5 minutes

### 2.7 監査ログの設定

#### Keycloak 監査ログ有効化

Keycloak 管理画面：

1. **Realm settings** → **Events** タブ
2. **User events settings**:
    - **Save Events**: ON
    - **Expiration**: 365 days
    - **Include Events**: すべて選択
3. **Admin events settings**:
    - **Save Events**: ON
    - **Include Representation**: ON

#### Laravel 監査ログ

`app/Http/Controllers/Auth/SamlAuthController.php`:

```php
use Illuminate\Support\Facades\Log;

public function acs(Saml2Auth $saml2Auth)
{
    $user = $saml2Auth->getSaml2User();
    $samlId = $user->getNameId();
    $attributes = $user->getAttributes();

    // 監査ログ記録
    Log::channel('audit')->info('SAML login attempt', [
        'saml_id' => $samlId,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'timestamp' => now(),
    ]);

    // ... 認証処理 ...

    Log::channel('audit')->info('SAML login successful', [
        'saml_id' => $samlId,
        'user_id' => $laravelUser->id,
        'ip_address' => request()->ip(),
    ]);

    return redirect()->intended(route('admin.blogs.index'));
}
```

`config/logging.php`:

```php
'channels' => [
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 365,
    ],
],
```

---

## 3. 高可用性構成

### 3.1 Keycloak クラスタリング

複数の Keycloak インスタンスを起動し、ロードバランサーで負荷分散：

```yaml
services:
    keycloak-1:
        image: "quay.io/keycloak/keycloak:26.0"
        command: start --optimized
        environment:
            # ... 基本設定 ...
            KC_CACHE: ispn
            KC_CACHE_STACK: tcp
            JGROUPS_DISCOVERY_PROTOCOL: JDBC_PING
            JGROUPS_DISCOVERY_PROPERTIES: datasource_jndi_name=java:jboss/datasources/KeycloakDS

    keycloak-2:
        image: "quay.io/keycloak/keycloak:26.0"
        command: start --optimized
        environment:
            # keycloak-1と同じ設定

    loadbalancer:
        image: nginx:alpine
        ports:
            - "443:443"
        volumes:
            - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
        depends_on:
            - keycloak-1
            - keycloak-2
```

### 3.2 データベースレプリケーション

PostgreSQL のマスター・スレーブ構成：

```yaml
services:
    postgres-master:
        image: postgres:16-alpine
        environment:
            POSTGRES_DB: keycloak
            POSTGRES_USER: keycloak
            POSTGRES_PASSWORD: ${DB_PASSWORD}
            POSTGRES_REPLICATION_USER: replicator
            POSTGRES_REPLICATION_PASSWORD: ${REPLICATION_PASSWORD}
        volumes:
            - postgres-master-data:/var/lib/postgresql/data
            - ./postgres/master/postgresql.conf:/etc/postgresql/postgresql.conf
            - ./postgres/master/pg_hba.conf:/etc/postgresql/pg_hba.conf

    postgres-slave:
        image: postgres:16-alpine
        environment:
            POSTGRES_MASTER_HOST: postgres-master
            POSTGRES_REPLICATION_USER: replicator
            POSTGRES_REPLICATION_PASSWORD: ${REPLICATION_PASSWORD}
        volumes:
            - postgres-slave-data:/var/lib/postgresql/data
```

---

## 4. 運用とモニタリング

### 4.1 ヘルスチェックとアラート設定

#### Prometheus メトリクス収集

`prometheus.yml`:

```yaml
global:
    scrape_interval: 15s

scrape_configs:
    - job_name: "keycloak"
      static_configs:
          - targets: ["keycloak:8443"]
      metrics_path: "/metrics"
      scheme: "https"
      tls_config:
          insecure_skip_verify: true

    - job_name: "laravel"
      static_configs:
          - targets: ["laravel:9090"]
```

#### Grafana ダッシュボード

以下のメトリクスを監視：

-   Keycloak ログインレート（成功/失敗）
-   セッション数
-   データベース接続プール使用率
-   API レスポンスタイム
-   エラーレート

### 4.2 ログ集約と分析

#### ELK Stack 構成

```yaml
services:
    elasticsearch:
        image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
        environment:
            - discovery.type=single-node
        volumes:
            - elasticsearch-data:/usr/share/elasticsearch/data

    logstash:
        image: docker.elastic.co/logstash/logstash:8.11.0
        volumes:
            - ./logstash/logstash.conf:/usr/share/logstash/pipeline/logstash.conf

    kibana:
        image: docker.elastic.co/kibana/kibana:8.11.0
        ports:
            - "5601:5601"
```

`logstash.conf`:

```
input {
  file {
    path => "/var/log/keycloak/*.log"
    type => "keycloak"
  }
  file {
    path => "/var/www/storage/logs/audit.log"
    type => "laravel-audit"
  }
}

filter {
  if [type] == "laravel-audit" {
    json {
      source => "message"
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "%{type}-%{+YYYY.MM.dd}"
  }
}
```

### 4.3 バックアップとリストア手順

#### 自動バックアップスクリプト

`backup.sh`:

```bash
#!/bin/bash

# 設定
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# PostgreSQLバックアップ
docker-compose exec -T postgres pg_dump -U keycloak keycloak | gzip > "${BACKUP_DIR}/keycloak_db_${DATE}.sql.gz"

# Keycloakデータディレクトリバックアップ
docker run --rm -v keycloak-data:/data -v ${BACKUP_DIR}:/backup alpine tar czf /backup/keycloak_data_${DATE}.tar.gz -C /data .

# S3にアップロード（オプション）
aws s3 cp "${BACKUP_DIR}/keycloak_db_${DATE}.sql.gz" s3://my-backups/keycloak/
aws s3 cp "${BACKUP_DIR}/keycloak_data_${DATE}.tar.gz" s3://my-backups/keycloak/

# 古いバックアップを削除
find ${BACKUP_DIR} -name "keycloak_*" -mtime +${RETENTION_DAYS} -delete

echo "Backup completed: ${DATE}"
```

Cron 設定：

```bash
# 毎日午前3時にバックアップ
0 3 * * * /path/to/backup.sh >> /var/log/keycloak-backup.log 2>&1
```

#### リストア手順

```bash
# データベースリストア
gunzip < keycloak_db_20250120_030000.sql.gz | docker-compose exec -T postgres psql -U keycloak keycloak

# データディレクトリリストア
docker run --rm -v keycloak-data:/data -v /backups:/backup alpine tar xzf /backup/keycloak_data_20250120_030000.tar.gz -C /data

# Keycloak再起動
docker-compose restart keycloak
```

### 4.4 パフォーマンスチューニング

#### Keycloak JVM 設定

`compose.prod.yaml`:

```yaml
keycloak:
    environment:
        JAVA_OPTS: >-
            -Xms2g
            -Xmx4g
            -XX:MetaspaceSize=256m
            -XX:MaxMetaspaceSize=512m
            -XX:+UseG1GC
            -XX:MaxGCPauseMillis=200
            -XX:ParallelGCThreads=4
            -XX:ConcGCThreads=2
            -XX:InitiatingHeapOccupancyPercent=45
```

#### PostgreSQL 設定

`postgresql.conf`:

```
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
min_wal_size = 1GB
max_wal_size = 4GB
```

---

## 5. コンプライアンスとガバナンス

### 5.1 GDPR / 個人情報保護法への対応

#### データ保持ポリシー

Keycloak 管理画面：

1. **Realm settings** → **Events**
2. **Expiration**: 365 days（法令に応じて調整）

#### ユーザーデータのエクスポート

`app/Console/Commands/ExportUserData.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExportUserData extends Command
{
    protected $signature = 'user:export {user_id}';
    protected $description = 'Export user data for GDPR compliance';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::with(['blogs', 'contacts'])->find($userId);

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $data = [
            'personal_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'saml_id' => $user->saml_id,
                'created_at' => $user->created_at,
            ],
            'blogs' => $user->blogs->toArray(),
            'contacts' => $user->contacts->toArray(),
        ];

        $filename = "user_data_{$userId}_" . date('Ymd_His') . ".json";
        file_put_contents(storage_path("app/gdpr/{$filename}"), json_encode($data, JSON_PRETTY_PRINT));

        $this->info("User data exported: {$filename}");
        return 0;
    }
}
```

#### ユーザーデータの削除

```php
class DeleteUserData extends Command
{
    protected $signature = 'user:delete {user_id} {--force}';
    protected $description = 'Delete user data for GDPR compliance';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Delete all data for user {$user->email}?")) {
                return 0;
            }
        }

        // 監査ログに記録
        Log::channel('audit')->warning('User data deletion requested', [
            'user_id' => $userId,
            'email' => $user->email,
            'requested_by' => auth()->id(),
        ]);

        // 関連データの削除
        $user->blogs()->delete();
        $user->contacts()->delete();
        $user->delete();

        $this->info("User data deleted: {$user->email}");
        return 0;
    }
}
```

### 5.2 アクセス制御とロールベース認証

#### Keycloak ロール設定

1. **Realm roles** → **Create role**
2. 以下のロールを作成：
    - `admin`: 管理者
    - `editor`: 編集者
    - `viewer`: 閲覧者

#### Laravel でのロール制御

`app/Http/Controllers/Auth/SamlAuthController.php`:

```php
public function acs(Saml2Auth $saml2Auth)
{
    $user = $saml2Auth->getSaml2User();
    $attributes = $user->getAttributes();

    // ロールの取得
    $roles = $attributes['Role'] ?? [];

    // Laravelユーザーにロールを同期
    $laravelUser->syncRoles($roles);

    // 権限チェック
    if (!$laravelUser->hasRole(['admin', 'editor'])) {
        Log::channel('audit')->warning('Unauthorized access attempt', [
            'saml_id' => $samlId,
            'roles' => $roles,
        ]);

        Auth::logout();
        return redirect()->route('admin.login')
            ->with('error', 'アクセス権限がありません');
    }

    return redirect()->intended(route('admin.blogs.index'));
}
```

### 5.3 監査証跡の保持

#### 監査ログテーブルの作成

マイグレーション：

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type'); // login, logout, create, update, delete
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('saml_id')->nullable();
    $table->string('ip_address');
    $table->text('user_agent')->nullable();
    $table->json('data')->nullable();
    $table->timestamp('created_at');

    $table->index('event_type');
    $table->index('user_id');
    $table->index('saml_id');
    $table->index('created_at');
});
```

#### 監査ログミドルウェア

```php
class AuditLog
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check() && $request->isMethod('post')) {
            \App\Models\AuditLog::create([
                'event_type' => $this->getEventType($request),
                'user_id' => auth()->id(),
                'saml_id' => auth()->user()->saml_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'input' => $request->except(['password', '_token']),
                ],
            ]);
        }

        return $response;
    }
}
```

---

## 6. ディザスタリカバリ計画

### 6.1 復旧手順書

#### RTO（Recovery Time Objective）: 4 時間

#### RPO（Recovery Point Objective）: 24 時間

### 6.2 バックアップ戦略

-   **フル バックアップ**: 毎日午前 3 時
-   **増分バックアップ**: 6 時間ごと
-   **バックアップ保存先**:
    -   プライマリ: AWS S3（または同等のオブジェクトストレージ）
    -   セカンダリ: 別リージョンの S3 バケット
-   **保持期間**:
    -   日次バックアップ: 30 日間
    -   週次バックアップ: 12 週間
    -   月次バックアップ: 12 ヶ月

### 6.3 障害シナリオと対応

#### シナリオ 1: Keycloak サービス停止

```bash
# ヘルスチェック
curl -f https://auth.example.com/health/ready || exit 1

# ログ確認
docker-compose logs keycloak --tail=100

# 再起動
docker-compose restart keycloak

# クラスタノードの切り替え
# ロードバランサーで自動的に健全なノードに切り替え
```

#### シナリオ 2: データベース障害

```bash
# スレーブへのフェイルオーバー
pg_ctl promote -D /var/lib/postgresql/data

# マスターの復旧
# 1. 最新のバックアップからリストア
# 2. WALログの適用
# 3. レプリケーション再構成
```

#### シナリオ 3: 完全なシステム障害

```bash
# 1. 新しいインフラストラクチャをプロビジョニング
terraform apply -var-file=disaster_recovery.tfvars

# 2. データベースをリストア
./restore_database.sh

# 3. Keycloakをデプロイ
docker-compose -f compose.prod.yaml up -d

# 4. DNSを更新（新しいIPアドレスに切り替え）
# 5. TLS証明書を再発行（必要に応じて）

# 6. 動作確認
./health_check.sh
```

---

## 7. デプロイメントチェックリスト

### 本番環境デプロイ前の確認事項

#### インフラストラクチャ

-   [ ] ドメイン名の取得と DNS 設定完了
-   [ ] TLS/SSL 証明書の取得と設置
-   [ ] ファイアウォールルールの設定
-   [ ] ロードバランサーの設定
-   [ ] データベースのセットアップとバックアップ設定

#### Keycloak 設定

-   [ ] 本番用データベース（PostgreSQL/MySQL）の使用
-   [ ] HTTPS の有効化（`KC_HTTP_ENABLED=false`）
-   [ ] 管理者パスワードの変更（強力なパスワード）
-   [ ] レルムとクライアントの作成
-   [ ] Client Scope の削除（属性重複エラー対策）
-   [ ] 署名と暗号化の有効化
-   [ ] セッションタイムアウトの設定
-   [ ] 監査ログの有効化

#### Laravel 設定

-   [ ] `.env` ファイルの本番用設定（HTTPS URL）
-   [ ] SP 証明書と秘密鍵の生成と設置
-   [ ] SAML 署名・暗号化設定の有効化
-   [ ] セッションドライバの変更（Redis/Memcached）
-   [ ] CSRF 保護の除外設定
-   [ ] `users.saml_id` カラムの追加
-   [ ] 監査ログの実装

#### セキュリティ

-   [ ] セキュリティヘッダーの設定
-   [ ] レート制限の設定
-   [ ] WAF の設定（オプション）
-   [ ] シークレット管理サービスの使用
-   [ ] アクセスログの有効化

#### 監視とアラート

-   [ ] Prometheus + Grafana のセットアップ
-   [ ] アラートルールの設定
-   [ ] ログ集約システムの設定
-   [ ] ヘルスチェックエンドポイントの確認

#### バックアップとディザスタリカバリ

-   [ ] 自動バックアップスクリプトの設定
-   [ ] バックアップのリストアテスト
-   [ ] ディザスタリカバリ手順書の作成
-   [ ] RTO/RPO の定義と合意

#### コンプライアンス

-   [ ] GDPR/個人情報保護法対応の確認
-   [ ] データ保持ポリシーの設定
-   [ ] ユーザーデータエクスポート機能の実装
-   [ ] 監査証跡の実装

#### 動作確認

-   [ ] SAML メタデータの生成確認
-   [ ] ログインフローのテスト
-   [ ] ログアウトフローのテスト
-   [ ] セッションタイムアウトのテスト
-   [ ] エラーハンドリングのテスト
-   [ ] パフォーマンステスト（負荷テスト）

---

## 8. 本番環境設定例

### 8.1 Laravel .env（本番環境）

```env
# アプリケーション基本設定
APP_NAME="La NekoCafe"
APP_ENV=production
APP_KEY=<Laravel App Key>
APP_DEBUG=false
APP_URL=https://lanekocafe.example.com

# データベース
DB_CONNECTION=mysql
DB_HOST=db.example.com
DB_PORT=3306
DB_DATABASE=lanekocafe_production
DB_USERNAME=lanekocafe
DB_PASSWORD=<強力なパスワード>

# セッション
SESSION_DRIVER=redis
SESSION_LIFETIME=480
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Redis
REDIS_HOST=redis.example.com
REDIS_PASSWORD=<強力なパスワード>
REDIS_PORT=6379

# SAML2 Keycloak設定（本番環境）
SAML2_KEYCLOAK_BASE_URL=https://auth.example.com
SAML2_KEYCLOAK_REALM=lanekocafe

# IdP証明書
SAML2_KEYCLOAK_IDP_x509="MIICmzCCAYMCBgGU..."

# SP (Laravel) の設定
SAML2_KEYCLOAK_SP_ENTITYID="${APP_URL}/saml2/keycloak/metadata"
SAML2_KEYCLOAK_SP_ACS_URL="${APP_URL}/saml2/keycloak/acs"
SAML2_KEYCLOAK_SP_SLS_URL="${APP_URL}/saml2/keycloak/sls"

# SP証明書と秘密鍵
SAML2_SP_x509CERT="MIIFaz..."
SAML2_SP_PRIVATE_KEY="MIIJQw..."

# IdP エンドポイント
SAML2_KEYCLOAK_IDP_ENTITYID="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}"
SAML2_KEYCLOAK_IDP_SSO_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"
SAML2_KEYCLOAK_IDP_SL_URL="${SAML2_KEYCLOAK_BASE_URL}/realms/${SAML2_KEYCLOAK_REALM}/protocol/saml"

# メール設定（本番環境）
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@lanekocafe.example.com
MAIL_FROM_NAME="${APP_NAME}"
AWS_ACCESS_KEY_ID=<AWS Key>
AWS_SECRET_ACCESS_KEY=<AWS Secret>
AWS_DEFAULT_REGION=ap-northeast-1

# ログ設定
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### 8.2 Keycloak 環境変数（本番環境）

```env
# 管理者アカウント
KEYCLOAK_ADMIN_USERNAME=admin
KEYCLOAK_ADMIN_PASSWORD=<強力なパスワード>

# データベース設定（PostgreSQL）
KC_DB=postgres
KC_DB_URL=jdbc:postgresql://postgres.example.com:5432/keycloak
KC_DB_USERNAME=keycloak
KC_DB_PASSWORD=<強力なパスワード>

# HTTPS設定
KC_HOSTNAME=auth.example.com
KC_HOSTNAME_STRICT=true
KC_HOSTNAME_STRICT_HTTPS=true
KC_HTTP_ENABLED=false
KC_HTTPS_CERTIFICATE_FILE=/opt/keycloak/conf/tls.crt
KC_HTTPS_CERTIFICATE_KEY_FILE=/opt/keycloak/conf/tls.key

# プロキシ設定
KC_PROXY=edge
KC_PROXY_HEADERS=xforwarded

# ログとメトリクス
KC_LOG_LEVEL=INFO
KC_HEALTH_ENABLED=true
KC_METRICS_ENABLED=true

# キャッシュ設定（クラスタリング）
KC_CACHE=ispn
KC_CACHE_STACK=tcp

# JVM設定
JAVA_OPTS=-Xms2g -Xmx4g -XX:+UseG1GC
```

---

## 9. トラブルシューティング（本番環境）

### 問題 1: HTTPS 接続エラー

**原因**: TLS 証明書の設定ミス

**解決策**:

```bash
# 証明書の有効性確認
openssl x509 -in tls.crt -text -noout

# 証明書とキーのペア確認
openssl x509 -noout -modulus -in tls.crt | openssl md5
openssl rsa -noout -modulus -in tls.key | openssl md5
```

### 問題 2: クラスタノード間の通信エラー

**原因**: ファイアウォールでクラスタポートがブロックされている

**解決策**:

-   JGroups が使用するポート（7800, 7900）を開放
-   `JGROUPS_DISCOVERY_PROTOCOL=JDBC_PING` を使用（ファイアウォール不要）

### 問題 3: パフォーマンス低下

**原因**: データベース接続プールの枯渇

**解決策**:

```bash
# PostgreSQL接続数確認
SELECT count(*) FROM pg_stat_activity;

# Keycloak設定で接続プールサイズを増やす
KC_DB_POOL_INITIAL_SIZE=50
KC_DB_POOL_MAX_SIZE=200
```

---

## まとめ

本番環境へのデプロイは、開発環境とは大きく異なる多くの考慮事項があります。このガイドで説明した設定を実施することで：

✅ **セキュリティ**: HTTPS、署名、暗号化、セキュリティヘッダー  
✅ **可用性**: クラスタリング、ロードバランシング、フェイルオーバー  
✅ **監視**: メトリクス、ログ、アラート  
✅ **コンプライアンス**: GDPR 対応、監査ログ、データ保持ポリシー  
✅ **運用**: バックアップ、リストア、ディザスタリカバリ

実際のデプロイ時には、組織のセキュリティポリシーやコンプライアンス要件に応じて、これらの設定をカスタマイズしてください。

---

## ライセンス

このドキュメントは La NekoCafe プロジェクトの一部です。
