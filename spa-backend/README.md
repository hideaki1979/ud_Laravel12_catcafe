# SPA Backend - Node.js Express + SAML 2.0 (TypeScript)

React SPA 用のバックエンド API サーバー（TypeScript 版）

Keycloak との SAML 2.0 認証を処理し、セッション管理とユーザー情報の提供を行います。

## 技術スタック

-   **Node.js**: JavaScript/TypeScript ランタイム
-   **Express**: Web フレームワーク
-   **TypeScript**: 型安全な開発
-   **@node-saml/passport-saml v5.x**: SAML 2.0 認証（セキュリティ対応済み）
-   **express-session**: セッション管理
-   **tsx**: TypeScript 実行環境（開発用）

## ディレクトリ構造

```
spa-backend/
├── src/
│   ├── config/
│   │   └── saml.ts          # SAML設定
│   ├── types/
│   │   ├── user.ts          # ユーザー型定義
│   │   └── express.d.ts     # Express拡張型定義
│   └── server.ts            # メインサーバーファイル
├── dist/                    # ビルド出力（自動生成）
├── tsconfig.json            # TypeScript設定
├── package.json
└── README.md
```

## セットアップ

### 1. 依存パッケージのインストール

```bash
npm install
```

### 2. 環境変数の設定（オプション）

`.env`ファイルを作成して、必要に応じて環境変数を設定：

```env
# Keycloak設定
SAML2_KEYCLOAK_BASE_URL=http://keycloak:8080
SAML2_KEYCLOAK_REALM=lanekocafe
SAML2_KEYCLOAK_IDP_x509=（Keycloakの証明書）

# SP設定
SP_BASE_URL=http://localhost:3001

# フロントエンド設定
FRONTEND_URL=http://localhost:3000

# セッション設定
SESSION_SECRET=cat-cafe-sso-secret-key

# サーバー設定
PORT=3001
NODE_ENV=development
```

## 開発

### TypeScript 開発モード（ホットリロード）

```bash
npm run dev
```

`tsx watch`を使用して、ファイル変更時に自動的に再起動します。

### TypeScript の型チェック

```bash
npm run typecheck
```

## ビルド

```bash
npm run build
```

TypeScript ファイルを`dist/`ディレクトリにコンパイルします。

## 本番環境での実行

```bash
npm start
```

コンパイル済みの JavaScript ファイルを実行します。

## API エンドポイント

### 認証関連

-   `GET /saml/login` - SAML 認証開始
-   `POST /saml/acs` - SAML Assertion Consumer Service（認証後のコールバック）
-   `GET /saml/metadata` - SAML メタデータ
-   `GET /saml/logout` - **SAML Single Logout（SLO）** - IdP にログアウトリクエストを送信
-   `POST /saml/sls` - **SAML Single Logout Service（SLS）** - IdP からのログアウト要求を受信
-   `POST /api/auth/logout` - ローカルログアウト（セッションのみクリア）
-   `GET /api/auth/check` - 認証状態確認

### ユーザー情報

-   `GET /api/user` - ログイン中のユーザー情報取得

### その他

-   `GET /health` - ヘルスチェック
-   `GET /api/protected` - 保護されたリソースの例

## 型定義

### User 型

```typescript
interface User {
    id: string;
    email: string;
    name: string;
    samlId: string;
    attributes: Record<string, unknown>;
}
```

### SamlProfile 型

```typescript
interface SamlProfile {
    id?: string;
    email?: string;
    name?: string;
    nameID?: string;
    [key: string]: unknown;
}
```

## TypeScript 化のメリット

1. **型安全性**: コンパイル時に型エラーを検出
2. **自動補完**: IDE での開発体験向上
3. **リファクタリング**: 安全な変更が可能
4. **ドキュメント**: 型定義が自己文書化
5. **バグ削減**: 実行前にエラーを検出

## SAML 設定について

`src/config/saml.ts`で SAML 設定を管理しています。

### @node-saml/passport-saml v5.x への移行

2024 年以降、`passport-saml`パッケージは非推奨（deprecated）となり、`@node-saml/passport-saml`への移行が推奨されています。

**移行理由：**

-   セキュリティ脆弱性（CVE-2022-39299）の修正
-   継続的なメンテナンス
-   型定義の内蔵（`@types/passport-saml`不要）

**主な変更点：**

-   パッケージ名: `passport-saml` → `@node-saml/passport-saml`
-   設定キー: `cert` → `idpCert`
-   Strategy コンストラクタ: 3 引数（options, signonVerify, logoutVerify）
-   型: `SamlConfig` は `@node-saml/node-saml` からインポート

主な設定項目：

-   `entryPoint`: Keycloak の認証エンドポイント
-   `callbackUrl`: SAML 認証後のコールバック URL（ACS）
-   `issuer`: SP 識別子
-   `idpCert`: IdP 証明書（v5.x で `cert` から変更、型定義上必須）
-   `logoutUrl`: IdP のログアウトエンドポイント（SLO 用）
-   `logoutCallbackUrl`: IdP からのログアウト応答を受け取るエンドポイント（SLS 用）

## Single Logout（SLO）について

このアプリケーションは**完全な SAML Single Logout**を実装しています。

### SP 発行ログアウト（`GET /saml/logout`）

1. ユーザーがこのアプリでログアウトボタンをクリック
2. バックエンドが IdP（Keycloak）にログアウトリクエストを送信
3. IdP が他の全ての SP（例：Laravel App）にログアウト通知を送信
4. 全てのアプリケーションからログアウト完了

### IdP 発行ログアウト（`POST /saml/sls`）

1. 他の SP（例：Laravel App）でログアウト
2. IdP が全ての SP にログアウト通知を送信
3. このバックエンドが SLS エンドポイントで通知を受信
4. セッションをクリアしてログアウト完了

これにより、**どのアプリでログアウトしても全てのアプリからログアウトされる**という、真の SSO が実現されます。

## セキュリティ設定

学習用のため、以下の設定を簡略化しています：

```typescript
wantAssertionsSigned: false,  // 署名検証なし
```

本番環境では、必ず Keycloak の証明書を設定し、署名検証を有効にしてください。

## トラブルシューティング

### TypeScript コンパイルエラー

```bash
npm run typecheck
```

で型エラーを確認できます。

### 実行時エラー

開発モードで詳細なエラーログを確認：

```bash
npm run dev
```

### ポート衝突

`.env`ファイルで`PORT`を変更：

```env
PORT=3002
```

## Docker 対応

`compose.yaml`で自動的に TypeScript のビルドと実行が行われます：

```yaml
spa-backend:
    build:
        context: ./spa-backend
    command: npm run dev # 開発時
    # command: npm start  # 本番時（ビルド後）
```

## 参考資料

-   [TypeScript 公式ドキュメント](https://www.typescriptlang.org/)
-   [Express 公式ドキュメント](https://expressjs.com/)
-   [@node-saml/passport-saml 公式 GitHub](https://github.com/node-saml/passport-saml)
-   [passport-saml 公式ドキュメント](https://www.passportjs.org/packages/passport-saml/)
-   [tsx](https://github.com/esbuild-kit/tsx)
