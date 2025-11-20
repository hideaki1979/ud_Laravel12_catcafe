#!/bin/bash

# ============================================
# La NekoCafe - デプロイスクリプト
# ============================================
#
# このスクリプトは本番環境へのデプロイを自動化します
#
# 使用方法:
#   ./scripts/deploy.sh
# ============================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "==================================="
echo "La NekoCafe Deployment Script"
echo "==================================="
echo "Started at: $(date)"
echo ""

# 環境変数の確認
if [ ! -f "$PROJECT_DIR/.env.prod" ]; then
    echo "Error: .env.prod file not found"
    echo "Please create .env.prod file first"
    exit 1
fi

# 環境変数の読み込み
set -a
source "$PROJECT_DIR/.env.prod"
set +a

# 本番環境用SAML設定に切り替え
echo ""
echo "🔐 Switching to production SAML config..."
cd "$PROJECT_DIR/config/saml2"
if [ -f "keycloak_idp_settings_prod.php" ]; then
    # 既存のシンボリックリンクまたはファイルをバックアップ
    if [ -L "keycloak_idp_settings.php" ]; then
        echo "  Removing existing symbolic link..."
        rm keycloak_idp_settings.php
    elif [ -f "keycloak_idp_settings.php" ] && [ ! -L "keycloak_idp_settings.php" ]; then
        echo "  Backing up development config..."
        mv keycloak_idp_settings.php keycloak_idp_settings_dev.php.bak
    fi
    
    # 本番環境用設定へのシンボリックリンクを作成
    ln -sf keycloak_idp_settings_prod.php keycloak_idp_settings.php
    echo "✓ SAML config switched to production"
    ls -la keycloak_idp_settings.php
else
    echo "⚠️  Warning: keycloak_idp_settings_prod.php not found"
    echo "  Using existing keycloak_idp_settings.php"
fi
cd "$PROJECT_DIR"

# Gitリポジトリの確認
cd "$PROJECT_DIR"

if [ -d .git ]; then
    echo "📦 Pulling latest changes..."
    git pull origin main
    echo "✓ Git pull completed"
else
    echo "⚠️  Not a git repository, skipping git pull"
fi

# Composerの依存関係更新
echo ""
echo "📦 Updating Composer dependencies..."
docker-compose -f compose.prod.yaml run --rm laravel composer install --no-dev --optimize-autoloader --no-interaction
echo "✓ Composer dependencies updated"

# データベースマイグレーション
echo ""
echo "🗄️  Running database migrations..."
docker-compose -f compose.prod.yaml exec laravel php artisan migrate --force
echo "✓ Database migrations completed"

# キャッシュのクリア
echo ""
echo "🧹 Clearing caches..."
docker-compose -f compose.prod.yaml exec laravel php artisan config:clear
docker-compose -f compose.prod.yaml exec laravel php artisan route:clear
docker-compose -f compose.prod.yaml exec laravel php artisan view:clear
docker-compose -f compose.prod.yaml exec laravel php artisan cache:clear
echo "✓ Caches cleared"

# キャッシュの最適化
echo ""
echo "⚡ Optimizing caches..."
docker-compose -f compose.prod.yaml exec laravel php artisan config:cache
docker-compose -f compose.prod.yaml exec laravel php artisan route:cache
docker-compose -f compose.prod.yaml exec laravel php artisan view:cache
echo "✓ Caches optimized"

# ストレージリンクの作成
echo ""
echo "🔗 Creating storage link..."
docker-compose -f compose.prod.yaml exec laravel php artisan storage:link
echo "✓ Storage link created"

# OPcacheのリセット
echo ""
echo "♻️  Restarting PHP-FPM..."
docker-compose -f compose.prod.yaml restart laravel
echo "✓ PHP-FPM restarted"

# コンテナの状態確認
echo ""
echo "🔍 Checking container status..."
docker-compose -f compose.prod.yaml ps

# ヘルスチェック
echo ""
echo "💚 Running health checks..."

# Laravel ヘルスチェック
if docker-compose -f compose.prod.yaml exec laravel php artisan inspire > /dev/null 2>&1; then
    echo "✓ Laravel: Healthy"
else
    echo "✗ Laravel: Unhealthy"
    exit 1
fi

# MySQL ヘルスチェック
if docker-compose -f compose.prod.yaml exec mysql mysqladmin ping -h localhost --silent > /dev/null 2>&1; then
    echo "✓ MySQL: Healthy"
else
    echo "✗ MySQL: Unhealthy"
    exit 1
fi

# Redis ヘルスチェック
if docker-compose -f compose.prod.yaml exec redis redis-cli ping > /dev/null 2>&1; then
    echo "✓ Redis: Healthy"
else
    echo "✗ Redis: Unhealthy"
    exit 1
fi

# Keycloak ヘルスチェック
if curl -f -k https://localhost:8443/health/ready > /dev/null 2>&1; then
    echo "✓ Keycloak: Healthy"
else
    echo "⚠️  Keycloak: Starting (may take a few minutes)"
fi

echo ""
echo "==================================="
echo "Deployment Summary"
echo "==================================="
echo "Status: ✅ Successfully deployed"
echo "Completed at: $(date)"
echo ""
echo "Next steps:"
echo "1. Verify application at your domain"
echo "2. Test SAML authentication"
echo "3. Check monitoring dashboard"
echo "4. Review application logs"
echo "==================================="

exit 0

