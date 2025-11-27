# SSO TypeScript移行ガイド

React SPAとExpress BackendをTypeScriptで書き直しました。

## 📋 目次

1. [変更概要](#変更概要)
2. [React SPA（TypeScript版）](#react-spa-typescript版)
3. [Express Backend（TypeScript版）](#express-backend-typescript版)
4. [開発環境での実行](#開発環境での実行)
5. [トラブルシューティング](#トラブルシューティング)

---

## 変更概要

### TypeScript化のメリット

✅ **型安全性**: コンパイル時に型エラーを検出  
✅ **自動補完**: IDEでの開発体験が大幅に向上  
✅ **リファクタリング**: 安全な変更が可能  
✅ **ドキュメント**: 型定義が自己文書化の役割  
✅ **バグ削減**: 実行前にエラーを検出

### 移行内容

| 項目 | JavaScript版 | TypeScript版 |
|------|-------------|--------------|
| React SPA | `.jsx` | `.tsx` |
| Express Backend | `.js` | `.ts` |
| 型チェック | なし | あり |
| ビルド | 不要 | `tsc`でコンパイル |
| 開発実行 | `node` | `tsx` |

---

## React SPA（TypeScript版）

### ファイル構成

```
cat-cafe-reactspa/
├── src/
│   ├── main.tsx              # エントリーポイント
│   ├── App.tsx               # メインコンポーネント
│   ├── App.css
│   ├── index.css
│   ├── api/
│   │   ├── axios.ts          # Axiosインスタンス設定
│   │   └── auth.ts           # 認証関連API
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
├── tsconfig.json             # TypeScript設定
├── tsconfig.app.json         # アプリ用TypeScript設定
├── tsconfig.node.json        # Node用TypeScript設定
├── vite.config.ts            # Vite設定
├── eslint.config.js          # ESLint設定
├── index.html
└── package.json
```

### 主な変更点

#### 1. TypeScript設定ファイル

**tsconfig.json**:
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "jsx": "react-jsx",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "strict": true,
    "noEmit": true
  }
}
```

#### 2. 型注釈の追加

**App.tsx**:
```typescript
import { useState } from 'react'

function App() {
  const [count, setCount] = useState<number>(0)  // 型注釈追加
  // ...
}
```

#### 3. index.htmlの更新

```html
<script type="module" src="/src/main.tsx"></script>
```

### 開発コマンド

```bash
cd cat-cafe-reactspa
npm run dev     # 開発サーバー起動
npm run build   # ビルド
npm run lint    # ESLintチェック
```

---

## Express Backend（TypeScript版）

### ファイル構成

```
spa-backend/
├── src/
│   ├── server.ts             # メインサーバー（旧server.js）
│   ├── config/
│   │   └── saml.ts           # SAML設定（旧saml-config.js）
│   └── types/
│       ├── user.ts           # ユーザー型定義
│       └── express.d.ts      # Express拡張型定義
├── dist/                     # ビルド出力（自動生成）
├── tsconfig.json             # TypeScript設定
└── package.json
```

### 主な変更点

#### 1. TypeScript設定ファイル

**tsconfig.json**:
```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "NodeNext",
    "moduleResolution": "NodeNext",
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true
  }
}
```

#### 2. 型定義の追加

**src/types/user.ts**:
```typescript
export interface User {
  id: string;
  email: string;
  name: string;
  samlId: string;
  attributes: Record<string, unknown>;
}

export interface SamlProfile {
  id?: string;
  email?: string;
  name?: string;
  nameID?: string;
  [key: string]: unknown;
}
```

**src/types/express.d.ts**:
```typescript
import { User } from './user';

declare global {
  namespace Express {
    interface User extends User {}
  }
}
```

#### 3. SAML設定のTypeScript化

**src/config/saml.ts**:
```typescript
import type { SamlConfig } from '@node-saml/passport-saml';

export const samlConfig: SamlConfig = {
  callbackUrl: `${SP_BASE_URL}/saml/acs`,
  entryPoint: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
  logoutUrl: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
  logoutCallbackUrl: `${SP_BASE_URL}/saml/sls`,
  // ...
};
```

> **Note:** `passport-saml` は `@node-saml/passport-saml` v5.x に移行しています。

#### 4. サーバーコードの型注釈

**src/server.ts**:
```typescript
import express, { Request, Response, NextFunction } from 'express';
import { Strategy as SamlStrategy, Profile } from 'passport-saml';

app.get('/api/auth/check', (req: Request, res: Response) => {
  if (req.isAuthenticated()) {
    res.json({
      authenticated: true,
      user: req.user
    });
  } else {
    res.json({
      authenticated: false
    });
  }
});
```

#### 5. package.jsonスクリプトの更新

```json
{
  "scripts": {
    "build": "tsc",
    "start": "node dist/server.js",
    "dev": "tsx watch src/server.ts",
    "typecheck": "tsc --noEmit"
  }
}
```

### 開発コマンド

```bash
cd spa-backend

# 開発モード（ホットリロード）
npm run dev

# TypeScript型チェック
npm run typecheck

# ビルド
npm run build

# 本番実行（ビルド後）
npm start
```

---

## 開発環境での実行

### 1. 個別起動（推奨：開発時）

#### Express Backend
```bash
cd spa-backend
npm install
npm run dev
```

#### React SPA
```bash
cd cat-cafe-reactspa
npm install
npm run dev
```

### 2. Docker Composeで起動

```bash
# プロジェクトルートで
docker compose up -d spa-backend spa-frontend
```

`compose.yaml`の設定:
```yaml
spa-backend:
  image: node:22.21-alpine
  command: sh -c "npm install && npm run dev"
  # TypeScript開発モードで起動（tsx watch）

spa-frontend:
  image: node:22.21-alpine
  command: sh -c "npm install && npm run dev"
  # Vite開発サーバー起動
```

---

## トラブルシューティング

### TypeScriptコンパイルエラー

#### 問題: 型エラーが出る

```bash
cd spa-backend
npm run typecheck
```

よくあるエラー:
- `Cannot find module`: import文のパスを確認
- `Type 'X' is not assignable to type 'Y'`: 型注釈を修正
- `Property 'X' does not exist on type 'Y'`: 型定義を追加

### React SPAが起動しない

#### 問題: `Cannot find module './main.jsx'`

**解決策**:
`index.html`を確認：
```html
<script type="module" src="/src/main.tsx"></script>
```

#### 問題: TypeScriptパッケージがない

**解決策**:
```bash
cd cat-cafe-reactspa
npm install -D typescript
```

### Express Backendが起動しない

#### 問題: `tsx: command not found`

**解決策**:
```bash
cd spa-backend
npm install -D tsx
```

#### 問題: 型定義が見つからない

**解決策**:
```bash
cd spa-backend
npm install -D @types/express @types/node
```

> **Note:** `@node-saml/passport-saml` v5.x には型定義が含まれているため、別途インストールは不要です。

### ポート衝突

#### React SPA（デフォルト: 3000）

`cat-cafe-reactspa/vite.config.ts`で変更:
```typescript
server: {
  port: 3002
}
```

#### Express Backend（デフォルト: 3001）

`.env`で変更:
```env
PORT=3003
```

### ホットリロードが効かない

#### Express Backend

`tsx watch`を使用していることを確認:
```bash
npm run dev
```

`package.json`:
```json
{
  "scripts": {
    "dev": "tsx watch src/server.ts"
  }
}
```

#### React SPA

Viteの開発サーバーが起動していることを確認:
```bash
npm run dev
```

---

## 型定義のベストプラクティス

### 1. 型を明示的に定義

```typescript
// ❌ 悪い例
const user = { name: 'test' };

// ✅ 良い例
interface User {
  name: string;
  email: string;
}
const user: User = { name: 'test', email: 'test@example.com' };
```

### 2. ユニオン型を活用

```typescript
type Status = 'pending' | 'success' | 'error';

function handleStatus(status: Status) {
  // ...
}
```

### 3. ジェネリクスを活用

```typescript
interface ApiResponse<T> {
  data: T;
  error?: string;
}

const response: ApiResponse<User> = {
  data: { id: '1', name: 'test', email: 'test@example.com', samlId: '123', attributes: {} }
};
```

---

## 参考資料

- [TypeScript公式ドキュメント](https://www.typescriptlang.org/)
- [React + TypeScript Cheatsheet](https://react-typescript-cheatsheet.netlify.app/)
- [tsx（TypeScript実行環境）](https://github.com/esbuild-kit/tsx)
- [Vite + TypeScript](https://vitejs.dev/guide/features.html#typescript)

---

## まとめ

TypeScript化により、以下のメリットが得られました：

✅ 型安全性の向上  
✅ 開発体験の向上（自動補完、エラー検出）  
✅ コードの可読性向上  
✅ バグの早期発見  
✅ リファクタリングの容易化

今後の開発では、TypeScriptの型システムを最大限活用して、堅牢なSSOシステムを構築していきます。

