# SPA Backend - Node.js Express + SAML 2.0 (TypeScript)

React SPA 用のバックエンド API サーバー（TypeScript 版）

Keycloak との SAML 2.0 認証を処理し、セッション管理とユーザー情報の提供を行います。

## 技術スタック

-   **Node.js**: JavaScript/TypeScript ランタイム
-   **Express**: Web フレームワーク
-   **TypeScript**: 型安全な開発
-   **passport-saml**: SAML 2.0 認証
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
-   `POST /saml/acs` - SAML Assertion Consumer Service
-   `GET /saml/metadata` - SAML メタデータ
-   `GET /saml/logout` - SAML ログアウト
-   `POST /api/auth/logout` - ローカルログアウト
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

主な設定項目：

-   `entryPoint`: Keycloak の認証エンドポイント
-   `callbackUrl`: SAML 認証後のコールバック URL（ACS）
-   `issuer`: SP 識別子
-   `cert`: IdP 証明書（オプション、学習用では不要）

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
-   [passport-saml](https://github.com/node-saml/passport-saml)
-   [tsx](https://github.com/esbuild-kit/tsx)
