# React SPA - La NekoCafe

Keycloak SAML認証を使用したReact SPA（TypeScript）

## 🎯 機能

- ✅ Keycloak SAML認証によるログイン
- ✅ Express Backendとの連携
- ✅ ユーザー情報の表示
- ✅ Laravel AppとのシームレスなSSO
- ✅ シングルログアウト（SLO）
- ✅ TypeScriptによる型安全な実装
- ✅ Tailwind CSSによるモダンなUI

## 🛠️ 技術スタック

- **React** 19.2.0
- **TypeScript** 5.9.3
- **Vite** 7.2.4
- **React Router** 7.1.3
- **Axios** 1.7.9
- **Tailwind CSS** 4.1.17

## 📦 セットアップ

### 1. 依存パッケージのインストール

```bash
npm install
```

### 2. 環境変数の設定

`.env`ファイルを作成：

```bash
# Express Backend API URL
VITE_API_BASE_URL=http://localhost:3001
```

### 3. Express Backendの起動

別ターミナルでExpress Backendを起動してください：

```bash
cd ../spa-backend
npm run dev
```

Express Backendが`http://localhost:3001`で起動している必要があります。

### 4. React SPAの起動

```bash
npm run dev
```

ブラウザで`http://localhost:3000`にアクセスしてください。

## 📂 ディレクトリ構造

```
cat-cafe-reactspa/
├── src/
│   ├── api/                    # API通信層
│   │   ├── axios.ts            # Axios インスタンス設定
│   │   └── auth.ts             # 認証関連API
│   ├── components/             # 再利用可能なコンポーネント
│   │   └── ProtectedRoute.tsx  # 認証が必要なルート
│   ├── pages/                  # ページコンポーネント
│   │   ├── Login.tsx           # ログインページ
│   │   ├── Dashboard.tsx       # ダッシュボード（ログイン後）
│   │   ├── Callback.tsx        # SAML認証後のコールバック処理
│   │   └── NotFound.tsx        # 404ページ
│   ├── contexts/               # React Context
│   │   └── AuthContext.tsx     # 認証状態管理
│   ├── hooks/                  # カスタムフック
│   │   └── useAuth.ts          # 認証フック
│   ├── types/                  # 型定義
│   │   ├── user.ts             # ユーザー型
│   │   ├── auth.ts             # 認証関連型
│   │   └── index.ts            # 型エクスポート
│   ├── App.tsx                 # ルートコンポーネント
│   ├── main.tsx                # エントリーポイント
│   └── index.css               # グローバルスタイル
├── public/
├── index.html
├── vite.config.ts              # Vite設定（プロキシ設定含む）
├── tsconfig.json               # TypeScript設定
└── package.json
```

## 🔐 認証フロー

### 1. 初回ログイン

```
1. ユーザーが http://localhost:3000 にアクセス
   ↓
2. 未認証の場合、Loginページへリダイレクト
   ↓
3. 「Keycloakでログイン」ボタンをクリック
   ↓
4. Express Backend の /saml/login にリダイレクト
   ↓
5. Keycloak の認証画面にリダイレクト
   ↓
6. ユーザーが認証（testuser/test1234）
   ↓
7. Keycloak が SAML Assertion を生成
   ↓
8. Express Backend が認証処理してセッション作成
   ↓
9. React SPA の /callback にリダイレクト
   ↓
10. 認証状態を更新してDashboardへ
```

### 2. SSO動作

Laravel Appでログイン済みの場合、React SPAでも自動的にログイン状態になります（その逆も同様）。

### 3. ログアウト

ログアウトボタンをクリックすると、Keycloakのシングルログアウト（SLO）が実行され、すべてのアプリケーションからログアウトされます。

## 🧪 テストシナリオ

### シナリオ1: 初回ログイン

1. `http://localhost:3000` にアクセス
2. 「Keycloakでログイン」ボタンをクリック
3. `testuser` / `test1234` でログイン
4. ダッシュボードが表示される

### シナリオ2: SSO確認（React → Laravel）

1. React SPAでログイン済み
2. 「Laravel Appを開く」ボタンをクリック
3. **自動的にLaravel管理画面もログイン済み**（SSO成功）

### シナリオ3: シングルログアウト

1. React SPAでログアウトボタンをクリック
2. Laravel管理画面をリロード
3. **ログアウトされている**（SLO成功）

## 🔧 開発コマンド

```bash
# 開発サーバー起動（ホットリロード）
npm run dev

# 本番ビルド
npm run build

# ビルドのプレビュー
npm run preview

# Lint実行
npm run lint
```

## 📝 重要なポイント

### withCredentials: true

`src/api/axios.ts`で`withCredentials: true`を設定しています。これにより、セッションCookieが自動的に送受信されます。

```typescript
const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // 重要！
  headers: {
    'Content-Type': 'application/json',
  },
});
```

### Vite Proxy設定

`vite.config.ts`でExpress Backendへのプロキシを設定しています：

```typescript
server: {
  port: 3000,
  proxy: {
    '/api': {
      target: 'http://localhost:3001',
      changeOrigin: true,
    },
    '/saml': {
      target: 'http://localhost:3001',
      changeOrigin: true,
    },
  },
}
```

### 認証コンテキスト

`AuthProvider`でアプリ全体の認証状態を管理しています。`useAuth`フックを使用して、どのコンポーネントからでも認証状態にアクセスできます。

```typescript
const { user, isAuthenticated, login, logout } = useAuth();
```

## 🐛 トラブルシューティング

### 認証状態が保持されない

- `withCredentials: true` の設定を確認
- Express Backend の CORS設定を確認
- Cookie の SameSite 属性を確認

### リダイレクトループ

- Express Backend の callbackUrl を確認
- React Router の Navigate 条件を確認

### APIエラー

- Express Backendが起動しているか確認
- `http://localhost:3001/health`にアクセスして確認

### ポート衝突

別のアプリケーションがポート3000を使用している場合、`vite.config.ts`で変更できます：

```typescript
server: {
  port: 3002, // 別のポートに変更
}
```

## 📚 関連ドキュメント

- [実装計画書](../docs/REACT_SPA_IMPLEMENTATION_PLAN.md)
- [SSO実装まとめ](../docs/SSO_IMPLEMENTATION_SUMMARY.md)
- [Express Backend README](../spa-backend/README.md)
- [Keycloak SAML設定](../docs/KEYCLOAK_SAML_SETUP.md)

## 🚀 起動確認

すべてのサービスが正しく起動しているか確認：

| サービス | URL | 状態確認 |
|---------|-----|---------|
| React SPA | http://localhost:3000 | ログインページが表示される |
| Express Backend | http://localhost:3001 | /health で "OK" が返る |
| Keycloak | http://localhost:8080 | 管理画面にアクセス可能 |
| Laravel App | http://localhost | トップページが表示される |

---

**作成日**: 2025-11-25  
**バージョン**: 1.0
