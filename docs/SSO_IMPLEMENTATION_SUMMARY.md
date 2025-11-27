# SSO実装 まとめドキュメント

## 実装内容

Keycloak + SAML 2.0を使用したEnterprise SSO環境を構築しました。  
**React SPAとExpress BackendはTypeScriptで実装されています。**

### 構成

```
┌─────────────────┐
│   Keycloak      │ ← http://localhost:8080
│   (IdP)         │    レルム: lanekocafe
│   ポート: 8080   │
└────────┬────────┘
         │ SAML 2.0
    ┌────┴─────┬───────────┐
    │          │           │
┌───▼────┐  ┌─▼────────┐  │
│Laravel │  │React SPA │  │
│  App   │  │(TypeScript)│ │
│Port 80 │  │Port 3000 │  │
└────────┘  └──────┬───┘  │
                   │       │
         ┌─────────▼───────▼───┐
         │ Node.js Express     │
         │ (TypeScript)        │
         │ Port 3001           │
         └─────────────────────┘
```

## 実装したコンポーネント

### 1. Laravel App（既存）

**変更ファイル:**
- `resources/views/admin/login.blade.php`
  - Keycloakログインボタンを追加

**既存のSAML実装を活用:**
- `app/Http/Controllers/Auth/SamlAuthController.php`
- `config/saml2/keycloak_idp_settings.php`
- `config/saml2_settings.php`

**自動登録されるルート:**
- `GET /saml2/keycloak/login` - SAML認証開始
- `POST /saml2/keycloak/acs` - Assertion Consumer Service
- `GET /saml2/keycloak/logout` - ログアウト
- `GET /saml2/keycloak/metadata` - メタデータ
- `GET /saml2/keycloak/sls` - Single Logout Service

### 2. React SPA（TypeScript実装）

**ディレクトリ:** `cat-cafe-reactspa/`

**主要ファイル:**
- `src/App.tsx` - メインコンポーネント
- `src/pages/Login.tsx` - ログインページ
- `src/pages/Dashboard.tsx` - ダッシュボード（認証後）
- `src/pages/NotFound.tsx` - 404ページ
- `src/api/axios.ts` - Axiosインスタンス設定
- `src/api/auth.ts` - 認証関連API
- `src/contexts/AuthContext.tsx` - 認証状態管理
- `src/contexts/AuthProvider.tsx` - 認証プロバイダー
- `src/hooks/useAuth.ts` - 認証フック
- `src/components/ProtectedRoute.tsx` - 認証が必要なルート
- `src/types/` - 型定義（user.ts, auth.ts）
- `vite.config.ts` - Vite設定（TypeScript）
- `package.json` - 依存関係

**技術スタック:**
- React 19
- TypeScript 5.9
- Vite 7
- Tailwind CSS 4
- React Router 7
- Axios

**機能:**
- Keycloak SAML認証
- ユーザー情報表示
- Laravel Appへのリンク
- SSO説明UI
- 型安全な実装

### 3. Node.js Express SAML Backend（TypeScript実装）

**ディレクトリ:** `spa-backend/`

**主要ファイル:**
- `src/server.ts` - Expressサーバー + Passport SAML
- `src/config/saml.ts` - SAML 2.0設定
- `src/types/user.ts` - ユーザー型定義
- `src/types/express.d.ts` - Express拡張型定義
- `package.json` - 依存関係

**エンドポイント:**
- `GET /saml/login` - SAML認証開始
- `POST /saml/acs` - Assertion Consumer Service
- `GET /saml/logout` - シングルログアウト（SP発行）
- `GET /saml/sls` - Single Logout Service（IdPからのリクエスト処理）
- `POST /saml/sls` - Single Logout Service（POSTバージョン）
- `GET /saml/metadata` - SAMLメタデータ
- `GET /api/auth/check` - 認証状態確認
- `GET /api/user` - ユーザー情報取得
- `POST /api/auth/logout` - ローカルログアウト
- `GET /health` - ヘルスチェック

**使用技術:**
- Express 5
- TypeScript 5.9
- @node-saml/passport-saml v5.1.0
- Passport.js
- express-session
- tsx（開発時実行）
- CORS対応

### 4. Docker Compose統合

**ファイル:** `compose.yaml`

**追加したサービス:**
- `spa-frontend` - React開発サーバー（ポート3000）
- `spa-backend` - Node.js Express（ポート3001）

**既存サービス:**
- `laravel.test` - Laravel App（ポート80）
- `keycloak` - Keycloak IdP（ポート8080）
- `mysql` - データベース
- `phpmyadmin` - データベース管理
- `mailpit` - メールテスト

### 5. ドキュメント

**作成したドキュメント:**
- `docs/SSO_QUICKSTART.md` - クイックスタートガイド（5分で完了）
- `docs/SSO_IMPLEMENTATION_SUMMARY.md` - 実装まとめ（このファイル）
- `docs/SSO_TYPESCRIPT_MIGRATION.md` - TypeScript移行ガイド
- `docs/REACT_SPA_QUICKSTART.md` - React SPAクイックスタート
- `docs/REACT_SPA_IMPLEMENTATION_PLAN.md` - React SPA実装計画書
- `docs/KEYCLOAK_SAML_SETUP.md` - Keycloak SAML設定詳細

**更新したドキュメント:**
- `README.md` - プロジェクトREADMEにSSO情報を追加

### 6. スクリプト

**作成したスクリプト:**
- `scripts/start-sso.sh` - SSO環境起動スクリプト

## Keycloak設定

### レルム設定
- レルム名: `lanekocafe`
- ベースURL: `http://localhost:8080/realms/lanekocafe`

### ユーザー
- Username: `testuser`
- Email: `testuser@example.com`
- Password: `test1234`

### SAMLクライアント

#### Laravel App
- Client ID: `http://localhost/saml2/keycloak/metadata`
- ACS URL: `http://localhost/saml2/keycloak/acs`
- Logout URL: `http://localhost/saml2/keycloak/sls`
- Name ID Format: `persistent`
- 署名: OFF（学習用）

#### React SPA
- Client ID: `http://localhost:3001/saml/metadata`
- ACS URL: `http://localhost:3001/saml/acs`
- Logout URL: `http://localhost:3001/saml/logout`
- Name ID Format: `persistent`
- 署名: OFF（学習用）

### マッパー設定（両クライアント共通）
- Email Mapper: `email` → `email`
- Name Mapper: `username` → `name`

## 動作確認シナリオ

### シナリオ1: Laravel → React SPA
1. http://localhost/admin/login でKeycloakログイン
2. 認証成功後、Laravel管理画面にリダイレクト
3. 新しいタブで http://localhost:3000 を開く
4. ログインボタンをクリック
5. **自動的にログイン済み**（SSO成功！）

### シナリオ2: React SPA → Laravel
1. http://localhost:3000 でKeycloakログイン
2. 認証成功後、SPAダッシュボード表示
3. 「Laravel Appを開く」ボタンをクリック
4. **自動的にLaravelもログイン済み**（SSO成功！）

### シナリオ3: シングルログアウト（SLO）
1. どちらかのアプリでログアウト
2. もう一方のアプリをリロード
3. **両方からログアウトされている**（SLO成功！）

## 技術スタック

### Laravel側
- **Framework:** Laravel 12.x
- **SAML Library:** aacotroneo/laravel-saml2
- **Protocol:** SAML 2.0

### React SPA側
- **Frontend:** React 19 + Vite 7 + TypeScript 5.9
- **Styling:** Tailwind CSS 4
- **Routing:** React Router 7
- **HTTP Client:** Axios

### Express Backend側
- **Runtime:** Node.js 22 + Express 5
- **Language:** TypeScript 5.9
- **SAML Library:** @node-saml/passport-saml v5.1.0
- **Session:** express-session
- **Dev Tool:** tsx（TypeScript実行）

### IdP
- **Identity Provider:** Keycloak 26.0
- **Protocol:** SAML 2.0
- **Storage:** dev-file（開発用）

### インフラ
- **Container:** Docker Compose
- **Database:** MySQL 8.0
- **Network:** Bridge（sail network）

## ファイル一覧

### 新規作成ファイル

```
cat-cafe-reactspa/
├── src/
│   ├── main.tsx
│   ├── App.tsx
│   ├── App.css
│   ├── index.css
│   ├── api/
│   │   ├── axios.ts
│   │   └── auth.ts
│   ├── components/
│   │   └── ProtectedRoute.tsx
│   ├── contexts/
│   │   ├── AuthContext.tsx
│   │   ├── AuthProvider.tsx
│   │   └── index.ts
│   ├── hooks/
│   │   └── useAuth.ts
│   ├── pages/
│   │   ├── Login.tsx
│   │   ├── Dashboard.tsx
│   │   └── NotFound.tsx
│   └── types/
│       ├── user.ts
│       ├── auth.ts
│       └── index.ts
├── index.html
├── vite.config.ts
├── tsconfig.json
├── tsconfig.app.json
├── tsconfig.node.json
├── eslint.config.js
├── package.json
└── README.md

spa-backend/
├── src/
│   ├── server.ts
│   ├── config/
│   │   └── saml.ts
│   └── types/
│       ├── user.ts
│       └── express.d.ts
├── tsconfig.json
├── package.json
└── README.md

docs/
├── SSO_QUICKSTART.md
├── SSO_IMPLEMENTATION_SUMMARY.md
├── SSO_TYPESCRIPT_MIGRATION.md
├── REACT_SPA_QUICKSTART.md
├── REACT_SPA_IMPLEMENTATION_PLAN.md
└── KEYCLOAK_SAML_SETUP.md

scripts/
└── start-sso.sh
```

### 変更したファイル

```
- resources/views/admin/login.blade.php  # Keycloakログインボタン追加
- compose.yaml                            # spa-frontend, spa-backend追加
- README.md                               # SSO情報追加
- app/Http/Controllers/Admin/AuthController.php  # SAML SLO対応
- app/Http/Controllers/Auth/SamlAuthController.php  # SAML認証処理
- routes/web.php                          # SAML SLS POSTルート追加
```

## 起動方法

### 方法1: Docker Composeで一括起動（推奨）

```bash
# すべてのサービスを起動
./vendor/bin/sail up -d

# または
docker compose up -d

# または起動スクリプトを使用
chmod +x scripts/start-sso.sh
./scripts/start-sso.sh
```

### 方法2: 個別起動

```bash
# Laravel（別ターミナル）
php artisan serve

# React SPA Frontend（別ターミナル）
cd cat-cafe-reactspa
npm install
npm run dev

# Node.js Backend（別ターミナル）
cd spa-backend
npm install
npm run dev  # tsx watchでTypeScript実行

# Keycloak（別ターミナル）
docker run -p 8080:8080 \
  -e KEYCLOAK_ADMIN=admin \
  -e KEYCLOAK_ADMIN_PASSWORD=admin \
  quay.io/keycloak/keycloak:26.0 start-dev
```

## アクセスURL

| サービス | URL | 認証情報 |
|---------|-----|---------|
| Laravel App | http://localhost | - |
| React SPA | http://localhost:3000 | - |
| Node.js Backend | http://localhost:3001 | - |
| Keycloak 管理画面 | http://localhost:8080 | admin / admin |
| phpMyAdmin | http://localhost:8888 | - |
| Mailpit | http://localhost:8025 | - |

## 学習ポイント

### Enterprise SSOとは

**シングルサインオン（SSO）の価値:**
1. **ユーザー体験の向上**
   - 一度のログインで複数アプリにアクセス
   - パスワードを覚える必要が減る

2. **セキュリティ向上**
   - 中央集権的なユーザー管理
   - パスワードポリシーの統一
   - 多要素認証（MFA）の一元管理

3. **管理コスト削減**
   - アプリごとのユーザー管理不要
   - 一括でユーザー追加/削除
   - 監査ログの一元化

### SAML 2.0プロトコル

**主要コンポーネント:**
- **IdP (Identity Provider):** 認証を提供（Keycloak）
- **SP (Service Provider):** サービスを提供（Laravel、React SPA）
- **Assertion:** 認証情報を含むXML文書
- **ACS (Assertion Consumer Service):** Assertionを受け取るエンドポイント
- **SLO (Single Logout):** 一括ログアウト

**認証フロー:**
1. ユーザーがSPにアクセス
2. SPがIdPにリダイレクト
3. ユーザーがIdPで認証
4. IdPがAssertionを生成してSPに送信
5. SPがAssertionを検証してユーザーをログイン

**シングルログアウト（SLO）フロー:**
1. ユーザーがいずれかのSPでログアウトボタンをクリック
2. SPがIdP（Keycloak）にLogoutRequestを送信
3. IdPが他のすべてのSPにLogoutRequestを送信（Back-Channel）
4. 各SPがローカルセッションをクリア
5. IdPがログアウト完了画面を表示

### 実装のポイント

**Laravel側:**
- `aacotroneo/laravel-saml2` パッケージ使用
- 自動ルート登録機能
- カスタムコントローラーで柔軟な処理
- SAMLユーザーのSLO対応（AuthController::logout）

**Node.js側:**
- `@node-saml/passport-saml` v5 で標準的なSAML実装
- TypeScriptで型安全な実装
- Express Sessionでセッション管理
- CORS設定でSPAと連携
- SLS（Single Logout Service）対応

**Keycloak側:**
- クライアント署名をOFFで簡略化（学習用）
- マッパーで属性マッピング
- persistent NameID形式で一意識別
- Back-Channel Logout推奨設定

## トラブルシューティング

### よくある問題

**1. SAML ACS エラー**
- 原因: 証明書未設定、URL不一致
- 解決: Keycloakから証明書取得、クライアント設定確認

**2. SSOが機能しない**
- 原因: Cookie無効、異なるブラウザ
- 解決: 同じブラウザで開く、Cookieを有効化

**3. React SPAが起動しない**
- 原因: ポート競合、依存関係未インストール
- 解決: `npm install` 実行、ポート確認

**4. Node.js Backendエラー**
- 原因: 証明書未設定、環境変数不足
- 解決: `.env` ファイル確認、証明書設定

**5. SLO（シングルログアウト）が他のSPに伝播しない**
- 原因1: AuthController::logout()がSAMLユーザーの場合にKeycloakへLogoutRequestを送信していない
- 解決: SAMLユーザー（saml_idが設定されている）の場合は`saml2_logout`ルートにリダイレクト
- 原因2: KeycloakのLogout Service URLが未設定
- 解決: 各SAMLクライアントにLogout Service POST/Redirect Binding URLを設定
- 原因3: routes/web.phpにPOST版SLSルートがない
- 解決: POST版のSLSルートを追加

### ログ確認方法

```bash
# Laravelログ
tail -f storage/logs/laravel.log

# Docker Composeログ
docker compose logs -f spa-frontend
docker compose logs -f spa-backend
docker compose logs -f keycloak

# Node.jsコンソールログ
# ターミナルに直接出力されます
```

## 今後の拡張案

### 短期的な改善

- [ ] 環境変数の整理と.env.example作成
- [ ] エラーハンドリングの改善
- [ ] ユーザーロール・権限管理の実装
- [ ] 複数ユーザーでのテスト

### 中期的な改善

- [ ] 本番環境用の設定（署名有効化）
- [ ] HTTPSサポート
- [ ] セッションストレージの改善（Redis等）
- [ ] ロギングとモニタリング

### 長期的な拡張

- [ ] 他のアプリケーションを追加（3つ目、4つ目のSP）
- [ ] OAuth 2.0 / OpenID Connectへの対応
- [ ] Active Directory / LDAP連携
- [ ] 多要素認証（MFA）の実装

## まとめ

### 達成したこと

✅ Keycloak + SAML 2.0によるSSO環境構築
✅ Laravel AppとReact SPAの統合
✅ Docker Composeによる一括環境管理
✅ 詳細なドキュメント作成
✅ クイックスタートガイドで5分で動作確認可能

### 学習価値

この実装により、以下を実践的に学べます：

1. **Enterprise SSO**の概念と実装
2. **SAML 2.0**プロトコルの理解
3. **Keycloak**の基本的な使い方
4. 複数アプリケーションの統合
5. Docker Composeでのマイクロサービス構成
6. **TypeScript**での型安全なバックエンド/フロントエンド開発

### 次のステップ

1. クイックスタートガイドに従ってSSO動作確認
2. 複数ユーザーを作成してテスト
3. ロール・権限管理を実装
4. 本番環境への展開を検討

---

**🎉 SSO実装完了！Enterprise SSOの世界へようこそ！ 🎉**

