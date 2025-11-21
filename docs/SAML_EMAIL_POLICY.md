# SAML認証 メールアドレス運用ポリシー

このドキュメントでは、SAML認証におけるメールアドレスの取り扱いと運用ポリシーを説明します。

## 📋 目次

-   [概要](#概要)
-   [環境別の動作](#環境別の動作)
-   [本番環境の推奨設定](#本番環境の推奨設定)
-   [開発環境の設定](#開発環境の設定)
-   [ダミーメールアドレスの問題点](#ダミーメールアドレスの問題点)
-   [Keycloak側の設定](#keycloak側の設定)
-   [トラブルシューティング](#トラブルシューティング)

---

## 概要

SAML認証では、IdP（Keycloak）から提供される属性情報からユーザーのメールアドレスを取得します。メールアドレスは以下の用途で使用されます：

-   ✅ ユーザーの一意識別（サブ識別子）
-   ✅ パスワードリセット機能
-   ✅ 通知メールの送信
-   ✅ ユーザー間のコミュニケーション

### 運用ポリシーの選択肢

| 環境 | ポリシー | メールアドレスなしの動作 |
|------|---------|----------------------|
| **本番環境** | **必須**（推奨） | エラーとして扱い、ログイン拒否 |
| 開発環境 | オプション | ダミーメールアドレスを自動生成 |

---

## 環境別の動作

### 本番環境（推奨設定）

```env
# .env.prod
APP_ENV=production

# メールアドレスを必須とする（デフォルト: true）
SAML2_REQUIRE_EMAIL=true
```

**動作**:

1. Keycloakから属性を取得
2. メールアドレスが含まれていない場合：
   - エラーログを記録
   - ユーザーにエラーメッセージを表示
   - ログインを拒否
   - 管理者への連絡を促す

**ログ例**:

```
[error] SAML認証失敗: メールアドレスが必須です
{
    "attributes": {...},
    "nameId": "G-d5caa5a3-...",
    "samlId": "G-d5caa5a3-...",
    "environment": "production"
}
```

### 開発環境

```env
# .env
APP_ENV=local

# メールアドレスをオプションとする
SAML2_REQUIRE_EMAIL=false

# ダミーメールのドメイン（オプション）
SAML2_DUMMY_EMAIL_DOMAIN=lanekocafe.local
```

**動作**:

1. Keycloakから属性を取得
2. メールアドレスが含まれていない場合：
   - 警告ログを記録
   - ダミーメールアドレスを自動生成
   - ログインを許可

**生成されるダミーメール例**:

```
saml_5f4dcc3b5aa765d61d8327deb882cf99af4f9d3c3c8e8e8e8e8e8e8e8e8e@lanekocafe.local
```

> ⚠️ **警告**: ダミーメールアドレスは開発環境専用です。本番環境では使用しないでください。

---

## 本番環境の推奨設定

### ステップ1: 環境変数の設定

`.env.prod`:

```env
# 本番環境
APP_ENV=production

# メールアドレスを必須とする
SAML2_REQUIRE_EMAIL=true
```

### ステップ2: Keycloak側でメールアドレスを必須化

Keycloak管理画面での設定：

1. **Realm settings** → **Login** タブ
2. **Email settings**:
   - **Email as username**: ON（推奨）
   - **Verify email**: ON（推奨）

3. **Users** → **Required user actions**:
   - **Verify Email** を追加

4. 各ユーザーの設定:
   - **Email** フィールドを必須入力
   - **Email verified**: ON

### ステップ3: SAMLマッパーの設定

Keycloak管理画面：

1. **Clients** → クライアントを開く
2. **Client scopes** → Dedicated scope を開く
3. **Mappers** タブ → **Add mapper** → **By configuration**
4. **User Property** を選択
5. 以下を設定：

| 項目 | 値 |
|-----|-----|
| **Name** | email |
| **Property** | email |
| **SAML Attribute Name** | email |
| **SAML Attribute NameFormat** | Basic |

6. **Save** をクリック

### ステップ4: 動作確認

```bash
# テストユーザーでログイン
# メールアドレスなしの場合、エラーメッセージが表示されることを確認

# ログの確認
docker-compose -f compose.prod.yaml logs laravel | grep "SAML認証失敗"
```

---

## 開発環境の設定

### ステップ1: 環境変数の設定

`.env`:

```env
# 開発環境
APP_ENV=local

# メールアドレスをオプションとする（ダミーメール生成）
SAML2_REQUIRE_EMAIL=false

# ダミーメールのドメイン（オプション）
SAML2_DUMMY_EMAIL_DOMAIN=dev.lanekocafe.local
```

### ステップ2: Keycloakでの設定（オプション）

開発環境では、メールアドレスなしでも動作するため、Keycloak側の設定は柔軟に行えます。

**テスト用ユーザーの作成**:

```bash
# メールアドレスなしのテストユーザー
Username: testuser_no_email
Email: （空欄）
First name: Test
Last name: User
Email verified: OFF
```

---

## ダミーメールアドレスの問題点

### 1. メール送信機能が使用できない

```php
// パスワードリセット機能が動作しない
Mail::to($user)->send(new PasswordResetMail($token));
// ⚠️ ダミーメールアドレスに送信されるため、届かない
```

### 2. ドメイン名の問題

-   `.local` ドメインは本番環境では不適切
-   存在しないドメインへのメール送信はエラーになる可能性
-   メールサーバーのブラックリストに追加される危険性

### 3. ユーザー識別の問題

-   実際のメールアドレスでないため、ユーザーに連絡できない
-   メールアドレスを使った機能（メール通知、招待など）が使用できない

### 4. データの整合性

-   データベースに実在しないメールアドレスが保存される
-   データ分析やユーザー管理が困難になる

### 5. セキュリティとコンプライアンス

-   GDPR等の個人情報保護法への対応が困難
-   ユーザーデータのエクスポート時に問題が発生

---

## Keycloak側の設定

### 推奨設定（本番環境）

#### 1. メールアドレスの必須化

**Realm settings** → **Login**:

```
Email as username: ON
Verify email: ON
Login with email: ON
Duplicate emails: OFF
```

#### 2. ユーザー作成時の検証

**Users** → **Add user**:

```
Email: （必須入力）
Email verified: ON
Required user actions: Verify Email
```

#### 3. SAMLマッパーの設定

**Clients** → Client → **Client scopes** → Dedicated scope → **Mappers**:

```yaml
Email Mapper:
  Name: email
  Mapper Type: User Property
  Property: email
  SAML Attribute Name: email
  SAML Attribute NameFormat: Basic
```

#### 4. 既存ユーザーの確認

```bash
# Keycloak管理画面で確認
# Users → View all users → 各ユーザーの Email フィールドを確認
```

---

## トラブルシューティング

### 問題1: 本番環境でメールアドレスが取得できない

**症状**:

```
ユーザー情報の取得に失敗しました。メールアドレスが見つかりません。
管理者に連絡してください。
```

**原因**:

-   Keycloak側でメールアドレスが設定されていない
-   SAMLマッパーが設定されていない
-   メールアドレスの属性名が一致していない

**解決策**:

1. Keycloak管理画面でユーザーのメールアドレスを確認
2. SAMLマッパーの設定を確認
3. ログで属性情報を確認：

```bash
docker-compose -f compose.prod.yaml logs laravel | grep "SAML認証失敗"
```

4. 属性名を`SamlAuthController::getEmailFromAttributes()`に追加

### 問題2: 開発環境でダミーメールが生成されない

**症状**:

-   開発環境でもエラーになる

**原因**:

-   `SAML2_REQUIRE_EMAIL=true` になっている

**解決策**:

`.env`:

```env
SAML2_REQUIRE_EMAIL=false
```

### 問題3: ダミーメールのドメインを変更したい

**症状**:

-   `@lanekocafe.local` 以外のドメインを使用したい

**解決策**:

`.env`:

```env
SAML2_DUMMY_EMAIL_DOMAIN=dev.example.com
```

### 問題4: メール送信時にエラーが発生する

**症状**:

```
Failed to send email to saml_5f4dcc3b...@lanekocafe.local
```

**原因**:

-   ダミーメールアドレスに送信しようとしている

**解決策**:

メール送信前にダミーメールかどうかを確認：

```php
// app/Mail/PasswordResetMail.php
public function __construct($user)
{
    // ダミーメールアドレスの場合は送信しない
    if (str_starts_with($user->email, 'saml_') && 
        str_ends_with($user->email, '@lanekocafe.local')) {
        throw new \Exception('ダミーメールアドレスにはメールを送信できません');
    }
    
    $this->user = $user;
}
```

---

## 実装詳細

### SamlAuthController のロジック

```php
// メールアドレスの取得
$email = $this->getEmailFromAttributes($attributes);

// メールアドレスが取得できない場合の処理
if (empty($email)) {
    // 本番環境ではメールアドレスを必須とする（推奨）
    if (config('saml2.require_email', env('APP_ENV') === 'production')) {
        Log::error('SAML認証失敗: メールアドレスが必須です', [...]);
        return redirect()->route('admin.login')
            ->with('error', 'ユーザー情報の取得に失敗しました。メールアドレスが見つかりません。管理者に連絡してください。');
    }

    // 開発環境のみ: ダミーメールアドレスを生成
    $email = $this->generateDummyEmail($samlId);
    
    Log::warning('SAML認証: メールアドレスが取得できなかったため、ダミーメールを生成しました（開発環境のみ）', [...]);
}
```

### 環境変数の優先順位

1. `SAML2_REQUIRE_EMAIL` 環境変数
2. `APP_ENV` が `production` の場合は必須
3. それ以外はオプション

---

## チェックリスト

### 本番環境デプロイ前

-   [ ] `.env.prod` で `APP_ENV=production` を設定
-   [ ] `SAML2_REQUIRE_EMAIL=true` を設定（または未設定）
-   [ ] Keycloak側でメールアドレスを必須化
-   [ ] SAMLマッパーでメールアドレスを送信
-   [ ] すべてのユーザーにメールアドレスが設定されている
-   [ ] テストユーザーでログインテストを実施
-   [ ] メールアドレスなしの場合のエラー動作を確認

### 開発環境セットアップ時

-   [ ] `.env` で `APP_ENV=local` を設定
-   [ ] `SAML2_REQUIRE_EMAIL=false` を設定
-   [ ] `SAML2_DUMMY_EMAIL_DOMAIN` を設定（オプション）
-   [ ] メールアドレスなしでログインできることを確認
-   [ ] ダミーメールが生成されることを確認

---

## まとめ

### 本番環境

✅ **メールアドレスを必須とする**（推奨）

-   Keycloak側で必須化
-   SAMLマッパーで送信
-   Laravelでエラーチェック
-   セキュリティとコンプライアンスに準拠

### 開発環境

✅ **ダミーメールアドレスを許可**

-   開発の柔軟性を確保
-   テストが容易
-   警告ログで注意喚起

---

**本番環境では必ずメールアドレスを必須としてください！**

