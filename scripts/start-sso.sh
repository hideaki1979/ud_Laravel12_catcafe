#!/bin/bash

###############################################################################
# SSO環境起動スクリプト
#
# このスクリプトは、Keycloak SSO環境を起動し、すべてのサービスを開始します。
###############################################################################

set -e

echo "=========================================="
echo "🚀 Keycloak SSO 環境を起動します"
echo "=========================================="
echo ""

# Docker Composeでサービスを起動
echo "📦 Docker Composeでサービスを起動中..."
docker compose up -d --wait

echo ""
echo "⏳ サービスの起動を待機中..."

echo ""
echo "=========================================="
echo "✅ すべてのサービスが起動しました！"
echo "=========================================="
echo ""
echo "🌐 アクセス先URL:"
echo "   - Laravel App:     http://localhost"
echo "   - React SPA:       http://localhost:3000"
echo "   - Keycloak:        http://localhost:8080"
echo "   - phpMyAdmin:      http://localhost:8888"
echo "   - Mailpit:         http://localhost:8025"
echo ""
echo "🔐 Keycloak 管理画面:"
echo "   - URL:             http://localhost:8080"
echo "   - Username:        admin"
echo "   - Password:        admin"
echo ""
echo "👤 テストユーザー（初期設定後に使用）:"
echo "   - Username:        testuser"
echo "   - Password:        test1234"
echo ""
echo "=========================================="
echo "📚 次のステップ:"
echo "=========================================="
echo ""
echo "1. Keycloak初期設定（初回のみ）"
echo "   詳細: docs/SSO_QUICKSTART.md"
echo ""
echo "2. SSO動作確認"
echo "   - Laravel: http://localhost/admin/login"
echo "   - React SPA: http://localhost:3000"
echo ""
echo "3. サービス停止"
echo "   docker compose down"
echo ""
echo "=========================================="
echo ""
