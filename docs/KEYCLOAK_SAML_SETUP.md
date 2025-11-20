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

---

## 6. Laravel 側の設定

> ⚠️ **重要**: このセクションでは、以下の 3 つの設定が**すべて必須**です：
>
> 1. Keycloak 証明書の取得と.env への設定
> 2. **カスタムコントローラーの設定**（6.3）← これがないとエラーになります
> 3. **metadata()メソッドの追加**（6.4）← これがないとエラーになります

### 6.1 Keycloak 証明書の取得

1. Keycloak 管理画面で **Realm settings** をクリック
2. **Keys** タブをクリック
3. **RS256** の行の **Certificate** ボタンをクリック
4. 表示された証明書をコピー（`-----BEGIN CERTIFICATE-----` から `-----END CERTIFICATE-----` まで）

### 6.2 .env ファイルの設定

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

## ライセンス

このドキュメントは La NekoCafe プロジェクトの一部です。
