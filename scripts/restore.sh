#!/bin/bash

# ============================================
# La NekoCafe - リストアスクリプト
# ============================================
#
# このスクリプトはバックアップからデータを復元します
#
# 使用方法:
#   ./scripts/restore.sh <backup_date>
#
# 例:
#   ./scripts/restore.sh 20250120_030000
# ============================================

set -e

# 引数チェック
if [ $# -ne 1 ]; then
    echo "Usage: $0 <backup_date>"
    echo "Example: $0 20250120_030000"
    exit 1
fi

BACKUP_DATE=$1
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${BACKUP_DIR:-/backups}"

# 環境変数の読み込み
if [ -f "$PROJECT_DIR/.env.prod" ]; then
    set -a
    source "$PROJECT_DIR/.env.prod"
    set +a
else
    echo "Error: .env.prod file not found"
    exit 1
fi

echo "==================================="
echo "Restore started at $(date)"
echo "==================================="
echo "⚠️  WARNING: This will overwrite existing data!"
echo "Backup date: $BACKUP_DATE"
echo ""

# 確認プロンプト
read -p "Are you sure you want to continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled"
    exit 0
fi

# バックアップファイルの存在確認
MYSQL_BACKUP="${BACKUP_DIR}/mysql/mysql_${BACKUP_DATE}.sql.gz"
KEYCLOAK_BACKUP="${BACKUP_DIR}/keycloak/keycloak_${BACKUP_DATE}.sql.gz"
KEYCLOAK_DATA_BACKUP="${BACKUP_DIR}/keycloak/keycloak_data_${BACKUP_DATE}.tar.gz"
STORAGE_BACKUP="${BACKUP_DIR}/storage/storage_${BACKUP_DATE}.tar.gz"

if [ ! -f "$MYSQL_BACKUP" ]; then
    echo "Error: MySQL backup not found: $MYSQL_BACKUP"
    exit 1
fi

if [ ! -f "$KEYCLOAK_BACKUP" ]; then
    echo "Error: Keycloak backup not found: $KEYCLOAK_BACKUP"
    exit 1
fi

# 1. MySQLリストア
echo "Restoring MySQL database..."
gunzip < "$MYSQL_BACKUP" | docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T mysql sh -c \
    mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${DB_DATABASE}"

if [ $? -eq 0 ]; then
    echo "✓ MySQL restore completed"
else
    echo "✗ MySQL restore failed"
    exit 1
fi

# 2. Keycloak PostgreSQLリストア
echo "Restoring Keycloak database..."

# データベースをドロップして再作成
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T keycloak-postgres psql \
    -U "${KEYCLOAK_DB_USERNAME}" \
    -c "DROP DATABASE IF EXISTS ${KEYCLOAK_DB_NAME};"

docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T keycloak-postgres psql \
    -U "${KEYCLOAK_DB_USERNAME}" \
    -c "CREATE DATABASE ${KEYCLOAK_DB_NAME};"

# バックアップからリストア
gunzip < "$KEYCLOAK_BACKUP" | docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T keycloak-postgres psql \
    -U "${KEYCLOAK_DB_USERNAME}" \
    "${KEYCLOAK_DB_NAME}"

if [ $? -eq 0 ]; then
    echo "✓ Keycloak database restore completed"
else
    echo "✗ Keycloak database restore failed"
    exit 1
fi

# 3. Keycloakデータディレクトリリストア
if [ -f "$KEYCLOAK_DATA_BACKUP" ]; then
    echo "Restoring Keycloak data..."

    # Keycloakを停止
    docker-compose -f "$PROJECT_DIR/compose.prod.yaml" stop keycloak

    # プロジェクト名を取得
    PROJECT_NAME=${docker-compose -f "$PROJECT_DIR/compose.prod.yaml" config --services | head -1 | xargs docker inspect --format='{{index .Config.Labels "com.docker.compose.project"}}' 2>/dev/null || echo "cat-cafe"}
    VOLUME_NAME="${PROJECT_NAME}_keycloak-data"

    # データをリストア
    docker run --rm \
        -v "${VOLUME_NAME}":/data \
        -v "${BACKUP_DIR}/keycloak":/backup \
        alpine sh -c "rm -rf /data/* && tar xzf /backup/keycloak_data_${BACKUP_DATE}.tar.gz -C /data"

    if [ $? -eq 0 ]; then
        echo "✓ Keycloak data restore completed"
    else
        echo "✗ Keycloak data restore failed"
        exit 1
    fi

    # Keycloakを再起動
    docker-compose -f "$PROJECT_DIR/compose.prod.yaml" start keycloak
fi

# 4. Storageディレクトリリストア
if [ -f "$STORAGE_BACKUP" ]; then
    echo "Restoring storage files..."

    # 既存のstorageをバックアップ
    if [ -d "$PROJECT_DIR/storage/app" ]; then
        mv "$PROJECT_DIR/storage/app" "$PROJECT_DIR/storage/app.backup.$(date +%Y%m%d_%H%M%S)"
    fi

    # バックアップからリストア
    tar xzf "$STORAGE_BACKUP" -C "$PROJECT_DIR"

    if [ $? -eq 0 ]; then
        echo "✓ Storage restore completed"
    else
        echo "✗ Storage restore failed"
        exit 1
    fi
fi

# 5. キャッシュクリア
echo "Clearing caches..."
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec laravel php artisan cache:clear
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec laravel php artisan config:clear
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec laravel php artisan route:clear
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec laravel php artisan view:clear

echo "✓ Caches cleared"

echo ""
echo "==================================="
echo "Restore completed successfully!"
echo "Completed at: $(date)"
echo "==================================="

exit 0

