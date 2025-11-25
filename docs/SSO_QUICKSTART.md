# SSO クイックスタートガイド

このガイドでは、最短でSSO（シングルサインオン）環境を構築し、動作確認する手順を説明します。

## 🚀 クイックスタート（5分で完了）

### Step 1: すべてのサービスを起動

```bash
# プロジェクトルートで実行
./vendor/bin/sail up -d

# または
docker compose up -d
```

起動するサービス：
- ✅ Laravel App (http://localhost)
- ✅ Keycloak (http://localhost:8080)
- ✅ React SPA Frontend (http://localhost:3000) - **TypeScript + Vite**
- ✅ Node.js Express Backend (http://localhost:3001) - **TypeScript + tsx**
- ✅ MySQL, phpMyAdmin, Mailpit

**💡 TypeScript版について:**
React SPAとExpress BackendはTypeScriptで書かれています。開発時は自動的にTypeScriptがコンパイル・実行されます。

### Step 2: Keycloak初期設定（初回のみ）

#### 2-1. Keycloak管理画面にログイン

1. http://localhost:8080 を開く
2. 「Administration Console」をクリック
3. Username: `admin`, Password: `admin` でログイン

#### 2-2. レルム作成

1. 左上の「master」ドロップダウン → 「Create Realm」
2. Realm name: `lanekocafe`
3. 「Create」をクリック

#### 2-3. テストユーザー作成

1. 左メニュー「Users」→「Add user」
2. 以下を入力：
   - Username: `testuser`
   - Email: `testuser@example.com`
   - Email verified: **ON**
   - First name: `Test`
   - Last name: `User`
3. 「Create」をクリック
4. 「Credentials」タブ → 「Set password」
   - Password: `test1234`
   - Password confirmation: `test1234`
   - Temporary: **OFF**
5. 「Save」をクリック

#### 2-4. Laravel App用SAMLクライアント作成

1. 左メニュー「Clients」→「Create client」
2. 以下を入力：
   - Client type: `SAML`
   - Client ID: `http://localhost/saml2/keycloak/metadata`
3. 「Next」→ SAML設定：
   - Name ID format: `persistent`
   - Sign documents: **OFF**
   - Sign assertions: **OFF**
   - Client signature required: **OFF**
4. 「Save」→ Settingsタブで以下を設定：
   - Valid redirect URIs: `http://localhost/*`
   - Master SAML Processing URL: `http://localhost/saml2/keycloak/acs`
   - Assertion Consumer Service POST Binding URL: `http://localhost/saml2/keycloak/acs`
   - Logout Service POST Binding URL: `http://localhost/saml2/keycloak/sls`
5. 「Save」をクリック

#### 2-5. マッパー追加（Laravel用）

「Client scopes」タブ → dedicated scope → 「Add mapper」→「By configuration」

**Email Mapper:**
- Name: `email`
- Mapper Type: `User Property`
- Property: `email`
- SAML Attribute Name: `email`
- SAML Attribute NameFormat: `Basic`

**Name Mapper:**
- Name: `name`
- Mapper Type: `User Property`
- Property: `username`
- SAML Attribute Name: `name`
- SAML Attribute NameFormat: `Basic`

#### 2-6. React SPA用SAMLクライアント作成

1. 「Clients」→「Create client」
2. 以下を入力：
   - Client type: `SAML`
   - Client ID: `http://localhost:3001/saml/metadata`
3. 「Next」→ SAML設定（Laravel Appと同じ）：
   - Name ID format: `persistent`
   - Sign documents: **OFF**
   - Sign assertions: **OFF**
   - Client signature required: **OFF**
4. 「Save」→ Settingsタブ：
   - Valid redirect URIs: `http://localhost:3000/*`, `http://localhost:3001/*`
   - Master SAML Processing URL: `http://localhost:3001/saml/acs`
   - Assertion Consumer Service POST Binding URL: `http://localhost:3001/saml/acs`
   - Logout Service POST Binding URL: `http://localhost:3001/saml/logout`
5. 「Save」をクリック
6. 同じマッパー（Email, Name）を追加

#### 2-7. Keycloak証明書をLaravelに設定

1. Keycloak管理画面 → 「Realm settings」→「Keys」タブ
2. RS256の「Certificate」をクリック
3. 証明書をコピー（`-----BEGIN CERTIFICATE-----` から `-----END CERTIFICATE-----` まで）
4. `.env` ファイルに追加：

```env
SAML2_KEYCLOAK_IDP_x509="ここに証明書を貼り付け（改行なし）"
```

5. キャッシュクリア：

```bash
./vendor/bin/sail artisan config:clear
```

### Step 3: SSO動作確認 🎉

#### シナリオ1: Laravel → React SPA

1. http://localhost/admin/login にアクセス
2. 「Keycloakでログイン（SSO）」ボタンをクリック
3. Keycloak画面で `testuser` / `test1234` でログイン
4. Laravel管理画面にリダイレクトされる ✅
5. **新しいタブで** http://localhost:3000 を開く
6. 「Login with Keycloak (SAML SSO)」ボタンをクリック
7. **🎊 ログイン画面が表示されず、自動的にダッシュボードに遷移する！**

#### シナリオ2: React SPA → Laravel

1. すべてのブラウザタブを閉じる
2. 新しいブラウザで http://localhost:3000 にアクセス
3. 「Login with Keycloak (SAML SSO)」をクリック
4. Keycloak画面で `testuser` / `test1234` でログイン
5. React SPA ダッシュボードが表示される ✅
6. 「Laravel Appを開く」ボタンをクリック
7. **🎊 Laravelも自動的にログイン済み！**

#### シナリオ3: シングルログアウト（SLO）

1. React SPAで「ログアウト」ボタンをクリック
2. Laravel Appのタブをリロード
3. **🎊 両方からログアウトされている！**

## 🎯 成功の証

以下が確認できればSSO実装成功です：

- ✅ 一度のログインで Laravel と React SPA の両方にアクセス可能
- ✅ 2つ目のアプリでログイン画面が表示されない
- ✅ どちらかでログアウトすると両方からログアウトされる
- ✅ Keycloak管理画面でセッションを確認できる

## 🔧 トラブルシューティング

### エラー: "SAML ACS エラー"

```bash
# Laravelのログを確認
./vendor/bin/sail artisan tail

# または
tail -f storage/logs/laravel.log
```

原因：
- Keycloakの証明書が未設定または間違っている
- クライアント設定のURLが間違っている

解決策：
1. 証明書を再取得して `.env` に設定
2. `php artisan config:clear` を実行
3. Keycloakのクライアント設定を再確認

### React SPAが起動しない

```bash
# コンテナのログを確認
docker compose logs spa-frontend

# または手動で起動（TypeScript版）
cd cat-cafe-spa
npm install
npm run dev  # Vite + TypeScript開発サーバー起動
```

**TypeScript関連のトラブル:**
- `tsconfig.json`が存在しない → プロジェクトルートで再生成
- TypeScriptがインストールされていない → `npm install -D typescript`

### Node.js Expressが起動しない

```bash
# コンテナのログを確認
docker compose logs spa-backend

# または手動で起動（TypeScript版）
cd spa-backend
npm install
npm run dev  # tsx watch でTypeScript実行
```

**TypeScript関連のトラブル:**
- `tsx: command not found` → `npm install -D tsx`
- 型エラーが出る → `npm run typecheck`で確認
- ビルドエラー → `npm run build`で確認

### SSOが機能しない

原因：
- ブラウザのCookieが無効
- シークレットモードを使用している
- 異なるブラウザで開いている

解決策：
1. 通常のブラウザウィンドウで開く
2. 同じブラウザで両方のアプリを開く
3. Cookieを有効にする

## 📚 詳細ドキュメント

より詳しい情報は以下を参照してください：

- [SSO_IMPLEMENTATION_SUMMARY.md](./SSO_IMPLEMENTATION_SUMMARY.md) - SSO実装サマリー
- [SSO_TYPESCRIPT_MIGRATION.md](./SSO_TYPESCRIPT_MIGRATION.md) - **TypeScript移行ガイド（新規）**
- [KEYCLOAK_SAML_SETUP.md](./KEYCLOAK_SAML_SETUP.md) - Keycloak詳細設定
- [cat-cafe-spa/README.md](../cat-cafe-spa/README.md) - React SPA詳細
- [spa-backend/README.md](../spa-backend/README.md) - Express Backend詳細

## 🎓 学習ポイント

このSSO実装で学べること：

1. **SAML 2.0プロトコル**
   - IdP (Identity Provider) と SP (Service Provider) の関係
   - Assertion Consumer Service (ACS)
   - Single Logout Service (SLO)

2. **Enterprise SSO**
   - 中央集権的なユーザー管理
   - セッション共有の仕組み
   - 複数アプリケーション統合

3. **Keycloak**
   - オープンソースのIdP
   - レルム、クライアント、ユーザー管理
   - SAML証明書と署名

4. **実装技術**
   - Laravel: aacotroneo/laravel-saml2
   - Node.js: passport-saml (TypeScript)
   - React: SPA + バックエンドAPI (TypeScript + Vite)
   - TypeScript: 型安全な開発環境

## 🚀 次のステップ

SSO環境ができたら、以下を試してみましょう：

- [ ] 複数ユーザーを作成してテスト
- [ ] ロール（role）属性を追加して権限管理
- [ ] グループ機能を試す
- [ ] 他のアプリケーションを追加
- [ ] 本番環境への deployment を検討

## 💡 ヒント

**SSO を実感するポイント：**

1. まず Laravel でログインする
2. ログインしたまま React SPA を開く
3. **ログイン画面が出ずに即座にダッシュボードに移動する** ← これがSSO！

Keycloakが「このユーザーはすでにログイン済み」と判断し、再度ログインを求めずに認証情報を提供してくれます。

---

**🎉 Enjoy your SSO journey! 🎉**

