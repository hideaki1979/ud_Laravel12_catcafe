# React SPA クイックスタートガイド 🚀

React SPA（TypeScript）の起動とSSO動作確認を5分で完了！

## 📋 前提条件

- Node.js 22以上がインストール済み（または Node.js 18以上）
- Keycloakが起動済み（`http://localhost:8080`）
- Express Backendが設定済み（`spa-backend/`）

## 🚀 起動手順

### 1. React SPA環境変数の設定

`cat-cafe-reactspa/`ディレクトリに`.env`ファイルを作成：

```bash
cd cat-cafe-reactspa
```

`.env`ファイル内容（必要に応じて）：

```env
VITE_API_BASE_URL=http://localhost:3001
```

> **Note:** デフォルトでは `http://localhost:3001` が使用されるため、`.env`ファイルは省略可能です。

### 2. Express Backendの起動

**別のターミナルで：**

```bash
cd spa-backend
npm run dev
```

以下のような出力が表示されればOK：

```
===========================================
🚀 SPA Backend Server Started
===========================================
📍 Server URL: http://localhost:3001
🔐 SAML Login: http://localhost:3001/saml/login
📄 SAML Metadata: http://localhost:3001/saml/metadata
🏥 Health Check: http://localhost:3001/health
===========================================
```

### 3. React SPAの起動

**元のターミナルで：**

```bash
cd cat-cafe-reactspa
npm run dev
```

以下のような出力が表示されればOK：

```
  VITE v7.x.x  ready in XXX ms

  ➜  Local:   http://localhost:3000/
  ➜  Network: use --host to expose
```

### 4. ブラウザでアクセス

ブラウザで以下にアクセス：

```
http://localhost:3000
```

ログインページが表示されます！

## 🧪 SSO動作確認（5分で完了）

### テスト1: ログイン

1. 「Keycloakでログイン」ボタンをクリック
2. Keycloakの認証画面にリダイレクト
3. 以下でログイン：
   - **Username**: `testuser`
   - **Password**: `test1234`
4. ダッシュボードが表示される ✅

### テスト2: ユーザー情報表示

ダッシュボードに以下が表示されることを確認：

- **名前**: testuser
- **メール**: testuser@example.com
- **SAML ID**: （Keycloakから発行されたID）

### テスト3: SSO動作確認（React → Laravel）

1. ダッシュボードの「Laravel Appを開く」ボタンをクリック
2. 新しいタブでLaravel管理画面が開く
3. **自動的にログイン済み**になっている ✅

これがSSO（シングルサインオン）の動作です！

### テスト4: SSO動作確認（Laravel → React）

1. Laravel管理画面からログアウト
2. 再度Laravel管理画面でKeycloakログイン
3. 新しいタブで`http://localhost:3000`を開く
4. **自動的にダッシュボードが表示**される ✅

### テスト5: シングルログアウト（SLO）

1. React SPAのダッシュボードで「ログアウト」ボタンをクリック
2. ログインページにリダイレクト
3. Laravel管理画面のタブをリロード
4. **ログアウトされている** ✅

これがSLO（シングルログアウト）の動作です！

## 🎉 完了！

すべてのテストが成功すれば、React SPA + SAML SSOの実装は完了です！

## 🔧 トラブルシューティング

### Express Backendに接続できない

**症状**: "Network Error" または "Failed to fetch"

**解決策**:

1. Express Backendが起動しているか確認：
   ```bash
   curl http://localhost:3001/health
   ```
   
   正常な場合：
   ```json
   {
     "status": "healthy",
     "service": "SPA Backend",
     "timestamp": "..."
   }
   ```

2. ポートが使用されているか確認：
   ```bash
   lsof -i :3001
   ```

### Keycloakに接続できない

**症状**: Keycloakの認証画面が表示されない

**解決策**:

1. Keycloakが起動しているか確認：
   ```bash
   curl http://localhost:8080
   ```

2. Dockerで起動している場合：
   ```bash
   docker ps | grep keycloak
   ```

### ログインループ

**症状**: ログインしてもログインページに戻される

**解決策**:

1. ブラウザのCookieを削除
2. ブラウザを再起動
3. Express Backendを再起動：
   ```bash
   cd spa-backend
   npm run dev
   ```

### 認証状態が保持されない

**症状**: ページをリロードするとログアウトされる

**解決策**:

1. `withCredentials: true` が設定されているか確認：
   ```typescript
   // cat-cafe-reactspa/src/api/axios.ts
   withCredentials: true
   ```

2. Express BackendのCORS設定を確認：
   ```typescript
   // spa-backend/src/server.ts
   credentials: true
   ```

### ポート衝突

**症状**: "Port 3000 is already in use"

**解決策**:

1. 別のプロセスを停止：
   ```bash
   lsof -ti:3000 | xargs kill -9
   ```

2. または別のポートを使用：
   ```typescript
   // cat-cafe-reactspa/vite.config.ts
   server: {
     port: 3002
   }
   ```

## 📚 次のステップ

- [詳細実装計画書](./REACT_SPA_IMPLEMENTATION_PLAN.md)を読む
- カスタマイズしてみる（UI変更、新しいページ追加など）
- 他のアプリケーションをSSOに統合してみる

## 🔗 関連リンク

- [React SPA README](../cat-cafe-reactspa/README.md)
- [Express Backend README](../spa-backend/README.md)
- [Keycloak SAML設定](./KEYCLOAK_SAML_SETUP.md)
- [SSO実装まとめ](./SSO_IMPLEMENTATION_SUMMARY.md)
- [SSO クイックスタート](./SSO_QUICKSTART.md)

---

**作成日**: 2025-11-25  
**更新日**: 2025-11-27  
**所要時間**: 約5分

