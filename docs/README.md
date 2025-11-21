# La NekoCafe - ドキュメント一覧

このディレクトリには、La NekoCafe プロジェクトのドキュメントが含まれています。

## 📚 ドキュメント一覧

### SAML認証関連

| ドキュメント | 説明 |
|------------|------|
| [KEYCLOAK_SAML_SETUP.md](./KEYCLOAK_SAML_SETUP.md) | Keycloak SAML認証の完全セットアップガイド（開発環境・本番環境） |
| [SAML_EMAIL_POLICY.md](./SAML_EMAIL_POLICY.md) | SAML認証におけるメールアドレス運用ポリシー |
| [SAML_CONFIG_SWITCHING.md](./SAML_CONFIG_SWITCHING.md) | 開発環境/本番環境でのSAML設定切り替え方法 |

### 本番環境デプロイ関連

| ドキュメント | 説明 |
|------------|------|
| [PRODUCTION_QUICKSTART.md](./PRODUCTION_QUICKSTART.md) | 本番環境デプロイのクイックスタートガイド（5ステップ） |
| [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md) | 本番環境デプロイの詳細ガイド |
| [ENV_PRODUCTION_TEMPLATE.md](./ENV_PRODUCTION_TEMPLATE.md) | 本番環境用環境変数テンプレート |

### 運用・保守関連

| ドキュメント | 説明 |
|------------|------|
| [OPCACHE_OPERATIONS.md](./OPCACHE_OPERATIONS.md) | **OPcache運用ガイド（重要）** |

---

## 🚀 クイックリンク

### 開発環境セットアップ

1. [Keycloak SAML認証セットアップ](./KEYCLOAK_SAML_SETUP.md)

### 本番環境デプロイ

1. [本番環境クイックスタート](./PRODUCTION_QUICKSTART.md)
2. [環境変数テンプレート](./ENV_PRODUCTION_TEMPLATE.md)
3. [SAML設定の切り替え](./SAML_CONFIG_SWITCHING.md)
4. **[OPcache運用（必読）](./OPCACHE_OPERATIONS.md)** ⚠️

---

## ⚠️ 重要な注意事項

### 本番環境デプロイ時

-   [ ] [環境変数テンプレート](./ENV_PRODUCTION_TEMPLATE.md)を参照して`.env.prod`を作成
-   [ ] [SAML設定](./SAML_CONFIG_SWITCHING.md)を本番環境用に切り替え
-   [ ] Keycloak側でメールアドレスを必須に設定（[メールポリシー](./SAML_EMAIL_POLICY.md)）
-   [ ] **デプロイ後は必ずコンテナを再起動してOPcacheをクリア**（[OPcache運用](./OPCACHE_OPERATIONS.md)）

### OPcache運用

> ⚠️ **最重要**: 本番環境では `validate_timestamps=0` を使用しているため、**コードをデプロイした後は必ずLaravelコンテナを再起動**してください。

```bash
# デプロイ後（必須）
docker-compose -f compose.prod.yaml restart laravel
```

詳細は [OPcache運用ガイド](./OPCACHE_OPERATIONS.md) を参照してください。

---

## 📖 推奨読書順序

### 初めてデプロイする場合

1. [本番環境クイックスタート](./PRODUCTION_QUICKSTART.md) - まずはこれ
2. [環境変数テンプレート](./ENV_PRODUCTION_TEMPLATE.md) - 環境変数の設定
3. [SAML設定の切り替え](./SAML_CONFIG_SWITCHING.md) - SAML設定
4. **[OPcache運用ガイド](./OPCACHE_OPERATIONS.md)** - 運用の要点

### トラブルシューティング

-   [Keycloak SAML設定ガイド](./KEYCLOAK_SAML_SETUP.md) - トラブルシューティングセクション
-   [メールアドレス運用ポリシー](./SAML_EMAIL_POLICY.md) - メール関連の問題
-   [OPcache運用ガイド](./OPCACHE_OPERATIONS.md) - デプロイ後の動作不良

---

**本番環境デプロイ後は必ずOPcacheをクリアしてください！**

