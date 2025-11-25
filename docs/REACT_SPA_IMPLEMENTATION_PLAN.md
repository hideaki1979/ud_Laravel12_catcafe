# React SPA + SAML SSO 実装計画書

## 📋 概要

Keycloak SAML認証を使用したReact SPA（TypeScript）の実装計画書。
Express Backend（spa-backend）と連携し、LaravelアプリとシームレスなSSO体験を提供します。

## 🎯 実装目標

1. ✅ Vite + React + TypeScript + Tailwind CSS の環境構築（完了）
2. 🚧 React Router による画面遷移
3. 🚧 SAML認証フロー実装
4. 🚧 Express Backend との API 連携
5. 🚧 認証状態管理
6. 🚧 ユーザー情報表示
7. 🚧 シングルログアウト（SLO）

## 🏗️ アーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                         Keycloak                            │
│                  (SAML 2.0 Identity Provider)               │
│                   http://localhost:8080                     │
└──────────────────────┬──────────────────────────────────────┘
                       │ SAML 2.0
            ┌──────────┴──────────┐
            │                     │
┌───────────▼──────────┐  ┌──────▼────────────────────────┐
│   Laravel App        │  │   Express Backend             │
│   (SAML SP)          │  │   (SAML SP + API Server)      │
│   Port: 80           │  │   Port: 3001                  │
└──────────────────────┘  └──────┬────────────────────────┘
                                 │ REST API
                                 │ (JSON)
                       ┌─────────▼─────────┐
                       │   React SPA       │
                       │   (Frontend)      │
                       │   Port: 3000      │
                       └───────────────────┘
```

## 📦 必要なパッケージ

### 依存関係（dependencies）

```json
{
  "react": "^19.2.0",
  "react-dom": "^19.2.0",
  "react-router-dom": "^7.1.3",
  "axios": "^1.7.9",
  "tailwindcss": "^4.1.17",
  "@tailwindcss/vite": "^4.1.17"
}
```

### 開発依存関係（devDependencies）

すでにインストール済み：
- TypeScript
- Vite
- ESLint
- @types/react, @types/react-dom

## 📂 ディレクトリ構造

```
cat-cafe-reactspa/
├── src/
│   ├── api/                    # API通信層
│   │   ├── axios.ts            # Axios インスタンス設定
│   │   └── auth.ts             # 認証関連API
│   ├── components/             # 再利用可能なコンポーネント
│   │   ├── Layout.tsx          # 共通レイアウト
│   │   ├── Header.tsx          # ヘッダー
│   │   ├── Footer.tsx          # フッター
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
│   │   └── auth.ts             # 認証関連型
│   ├── App.tsx                 # ルートコンポーネント
│   ├── main.tsx                # エントリーポイント
│   ├── index.css               # グローバルスタイル
│   └── vite-env.d.ts           # Vite型定義
├── public/
│   └── vite.svg
├── index.html
├── vite.config.ts
├── tsconfig.json
├── package.json
└── README.md
```

## 🔐 認証フロー

### 1. 初回アクセス時（未認証）

```
1. ユーザーが http://localhost:3000 にアクセス
   ↓
2. React SPA が Express Backend に認証状態を確認
   GET /api/auth/check
   ↓
3. 未認証の場合、Loginページへリダイレクト
   ↓
4. ユーザーが「Keycloakでログイン」ボタンをクリック
   ↓
5. Express Backend の SAML エンドポイントにリダイレクト
   GET http://localhost:3001/saml/login
   ↓
6. Keycloak の認証画面にリダイレクト
   ↓
7. ユーザーがKeycloakで認証（testuser/test1234）
   ↓
8. Keycloak が SAML Assertion を生成
   ↓
9. Express Backend の ACS にPOST
   POST http://localhost:3001/saml/acs
   ↓
10. Express Backend がセッション作成
   ↓
11. React SPA の Callback ページにリダイレクト
   http://localhost:3000/callback
   ↓
12. Callback ページで認証状態を更新してDashboardへ
```

### 2. 認証済みアクセス

```
1. ユーザーが http://localhost:3000 にアクセス
   ↓
2. Express Backend に認証状態を確認
   GET /api/auth/check
   ↓
3. 認証済みの場合、Dashboardを表示
```

### 3. ログアウト

```
1. ユーザーが「ログアウト」ボタンをクリック
   ↓
2. Express Backend のログアウトエンドポイント
   GET http://localhost:3001/saml/logout
   ↓
3. Keycloak のシングルログアウト（SLO）
   ↓
4. すべてのアプリケーション（Laravel + React SPA）からログアウト
   ↓
5. React SPA のLoginページにリダイレクト
```

## 🛠️ 実装ステップ

### Step 1: 必要なパッケージのインストール

```bash
cd cat-cafe-reactspa
npm install react-router-dom axios
```

### Step 2: 型定義の作成

#### `src/types/user.ts`

```typescript
export interface User {
  id: string;
  email: string;
  name: string;
  samlId: string;
  attributes?: Record<string, unknown>;
}
```

#### `src/types/auth.ts`

```typescript
import { User } from './user';

export interface AuthCheckResponse {
  authenticated: boolean;
  user?: User;
}

export interface AuthContextType {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  login: () => void;
  logout: () => void;
  checkAuth: () => Promise<void>;
}
```

### Step 3: Axios インスタンスの設定

#### `src/api/axios.ts`

```typescript
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001';

const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // セッションCookieを含める（重要）
  headers: {
    'Content-Type': 'application/json',
  },
});

// リクエストインターセプター（必要に応じて）
axiosInstance.interceptors.request.use(
  (config) => {
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// レスポンスインターセプター（エラーハンドリング）
axiosInstance.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      // 未認証エラーの場合
      console.error('認証エラー: ログインが必要です');
    }
    return Promise.reject(error);
  }
);

export default axiosInstance;
```

### Step 4: 認証関連APIの作成

#### `src/api/auth.ts`

```typescript
import axios from './axios';
import { AuthCheckResponse, User } from '../types';

export const authApi = {
  // 認証状態確認
  checkAuth: async (): Promise<AuthCheckResponse> => {
    const response = await axios.get<AuthCheckResponse>('/api/auth/check');
    return response.data;
  },

  // ユーザー情報取得
  getUser: async (): Promise<User> => {
    const response = await axios.get<User>('/api/user');
    return response.data;
  },

  // ログアウト（ローカル）
  logout: async (): Promise<void> => {
    await axios.post('/api/auth/logout');
  },
};

// SAML認証開始（リダイレクト）
export const startSamlLogin = () => {
  window.location.href = 'http://localhost:3001/saml/login';
};

// SAMLログアウト（リダイレクト）
export const startSamlLogout = () => {
  window.location.href = 'http://localhost:3001/saml/logout';
};
```

### Step 5: 認証コンテキストの作成

#### `src/contexts/AuthContext.tsx`

```typescript
import { createContext, useState, useEffect, ReactNode } from 'react';
import { authApi } from '../api/auth';
import { User, AuthContextType } from '../types';

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  // 認証状態確認
  const checkAuth = async () => {
    try {
      const result = await authApi.checkAuth();
      if (result.authenticated && result.user) {
        setUser(result.user);
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('認証状態の確認に失敗:', error);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  // 初回マウント時に認証状態を確認
  useEffect(() => {
    checkAuth();
  }, []);

  const login = () => {
    // SAML認証開始（Express Backendにリダイレクト）
    window.location.href = 'http://localhost:3001/saml/login';
  };

  const logout = () => {
    // SAMLログアウト（Express Backendにリダイレクト）
    window.location.href = 'http://localhost:3001/saml/logout';
  };

  const value: AuthContextType = {
    user,
    loading,
    isAuthenticated: user !== null,
    login,
    logout,
    checkAuth,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
```

### Step 6: カスタムフックの作成

#### `src/hooks/useAuth.ts`

```typescript
import { useContext } from 'react';
import { AuthContext } from '../contexts/AuthContext';

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

### Step 7: ページコンポーネントの作成

#### `src/pages/Login.tsx`

```typescript
import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function Login() {
  const { isAuthenticated, login, loading } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (isAuthenticated) {
      navigate('/dashboard');
    }
  }, [isAuthenticated, navigate]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-xl">読み込み中...</div>
      </div>
    );
  }

  return (
    <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      <div className="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 className="text-3xl font-bold text-center mb-6 text-gray-800">
          🐱 La NekoCafe
        </h1>
        <p className="text-center text-gray-600 mb-8">
          React SPA with SAML SSO
        </p>
        
        <button
          onClick={login}
          className="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 transition duration-200 font-semibold"
        >
          Keycloakでログイン
        </button>

        <div className="mt-6 text-sm text-gray-500 text-center">
          <p>テストユーザー:</p>
          <p className="font-mono">testuser / test1234</p>
        </div>
      </div>
    </div>
  );
}
```

#### `src/pages/Dashboard.tsx`

```typescript
import { useAuth } from '../hooks/useAuth';

export default function Dashboard() {
  const { user, logout } = useAuth();

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
          <h1 className="text-3xl font-bold text-gray-900">
            🐱 La NekoCafe Dashboard
          </h1>
          <button
            onClick={logout}
            className="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-200"
          >
            ログアウト
          </button>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h2 className="text-2xl font-semibold mb-4">ようこそ！</h2>
          <div className="space-y-2">
            <p className="text-gray-700">
              <span className="font-semibold">名前:</span> {user?.name}
            </p>
            <p className="text-gray-700">
              <span className="font-semibold">メール:</span> {user?.email}
            </p>
            <p className="text-gray-700">
              <span className="font-semibold">SAML ID:</span> {user?.samlId}
            </p>
          </div>
        </div>

        <div className="bg-indigo-50 rounded-lg p-6">
          <h3 className="text-xl font-semibold mb-3 text-indigo-900">
            ✅ SSO動作確認
          </h3>
          <ul className="space-y-2 text-gray-700">
            <li>✓ Keycloak SAML認証でログイン成功</li>
            <li>✓ Express Backendとの連携完了</li>
            <li>✓ セッション管理動作中</li>
          </ul>
          <div className="mt-4">
            <a
              href="http://localhost/admin/dashboard"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-block bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-200"
            >
              Laravel Appを開く（SSO確認）
            </a>
          </div>
        </div>
      </main>
    </div>
  );
}
```

#### `src/pages/Callback.tsx`

```typescript
import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function Callback() {
  const { checkAuth } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    const handleCallback = async () => {
      // 認証状態を再確認
      await checkAuth();
      // ダッシュボードにリダイレクト
      navigate('/dashboard');
    };

    handleCallback();
  }, [checkAuth, navigate]);

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <div className="text-xl mb-4">認証処理中...</div>
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
      </div>
    </div>
  );
}
```

#### `src/pages/NotFound.tsx`

```typescript
import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-50">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-gray-800 mb-4">404</h1>
        <p className="text-xl text-gray-600 mb-8">ページが見つかりません</p>
        <Link
          to="/"
          className="bg-indigo-600 text-white py-2 px-6 rounded-lg hover:bg-indigo-700 transition duration-200"
        >
          ホームに戻る
        </Link>
      </div>
    </div>
  );
}
```

### Step 8: Protected Route コンポーネントの作成

#### `src/components/ProtectedRoute.tsx`

```typescript
import { Navigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export default function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-xl">読み込み中...</div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}
```

### Step 9: App.tsx の設定

#### `src/App.tsx`

```typescript
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Callback from './pages/Callback';
import NotFound from './pages/NotFound';

function App() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/callback" element={<Callback />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}

export default App;
```

### Step 10: Vite設定の更新

#### `vite.config.ts`

```typescript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 3000,
    proxy: {
      // APIリクエストをExpress Backendにプロキシ
      '/api': {
        target: 'http://localhost:3001',
        changeOrigin: true,
      },
      '/saml': {
        target: 'http://localhost:3001',
        changeOrigin: true,
      },
    },
  },
});
```

### Step 11: 環境変数の設定

#### `.env`

```env
VITE_API_BASE_URL=http://localhost:3001
```

### Step 12: TypeScript設定の確認

`tsconfig.json` と `tsconfig.app.json` が適切に設定されていることを確認。

## 🧪 テストシナリオ

### シナリオ1: 初回ログイン

1. `http://localhost:3000` にアクセス
2. ログインページが表示される
3. 「Keycloakでログイン」ボタンをクリック
4. Keycloakの認証画面にリダイレクト
5. `testuser` / `test1234` でログイン
6. ダッシュボードにリダイレクト
7. ユーザー情報が表示される

### シナリオ2: SSO確認（React → Laravel）

1. React SPAでログイン済み
2. 「Laravel Appを開く」ボタンをクリック
3. 新しいタブでLaravel管理画面が開く
4. **自動的にログイン済み**（SSO成功）

### シナリオ3: SSO確認（Laravel → React）

1. `http://localhost/admin/login` でKeycloakログイン
2. Laravel管理画面にログイン
3. 新しいタブで `http://localhost:3000` を開く
4. **自動的にダッシュボードが表示**（SSO成功）

### シナリオ4: シングルログアウト

1. React SPAでログアウトボタンをクリック
2. ログインページにリダイレクト
3. Laravel管理画面をリロード
4. **ログアウトされている**（SLO成功）

## 🚀 起動手順

### 1. Express Backend起動

```bash
cd spa-backend
npm install
npm run dev
```

### 2. React SPA起動

```bash
cd cat-cafe-reactspa
npm install
npm run dev
```

### 3. Keycloak起動（Docker）

```bash
./vendor/bin/sail up -d keycloak
```

### 4. Laravel起動

```bash
./vendor/bin/sail up -d
```

## ✅ 実装チェックリスト

### 環境構築
- [x] Vite + React + TypeScript + Tailwind CSS セットアップ
- [ ] React Router インストール
- [ ] Axios インストール
- [ ] 環境変数設定

### 型定義
- [ ] User型定義
- [ ] Auth型定義

### API層
- [ ] Axiosインスタンス設定
- [ ] 認証API実装

### 認証管理
- [ ] AuthContext実装
- [ ] useAuthフック実装
- [ ] ProtectedRoute実装

### ページ実装
- [ ] Loginページ
- [ ] Dashboardページ
- [ ] Callbackページ
- [ ] NotFoundページ

### ルーティング
- [ ] App.tsx にRoutes設定
- [ ] ProtectedRoute適用

### 動作確認
- [ ] ログインフロー確認
- [ ] ダッシュボード表示確認
- [ ] SSO動作確認（React → Laravel）
- [ ] SSO動作確認（Laravel → React）
- [ ] シングルログアウト確認

## 📝 注意事項

### セキュリティ

1. **withCredentials: true** を必ず設定
   - セッションCookieを送受信するために必須
   
2. **CORS設定**
   - Express Backend側で適切なCORS設定が必要
   - `credentials: true` を設定

3. **本番環境**
   - HTTPS必須
   - セッションシークレットの環境変数化
   - CSRF対策

### トラブルシューティング

#### 認証状態が保持されない
- `withCredentials: true` の設定を確認
- Express Backend の CORS設定を確認
- Cookie の SameSite 属性を確認

#### リダイレクトループ
- Express Backend の callbackUrl を確認
- React Router の Navigate 条件を確認

#### 型エラー
- `npm run build` で型チェック
- `tsconfig.json` の設定を確認

## 🎉 完成後の機能

✅ Keycloak SAML認証によるログイン
✅ ユーザー情報の表示
✅ Laravel AppとのシームレスなSSO
✅ シングルログアウト（SLO）
✅ 認証状態の永続化（セッション）
✅ TypeScriptによる型安全な実装
✅ Tailwind CSSによるモダンなUI

---

**作成日**: 2025-11-25  
**バージョン**: 1.0

