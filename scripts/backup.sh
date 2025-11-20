#!/bin/bash

# ============================================
# La NekoCafe - バックアップスクリプト
# ============================================
#
# このスクリプトは以下をバックアップします：
# - MySQLデータベース
# - Keycloak PostgreSQLデータベース
# - アップロードファイル（storage/app）
# - Keycloakデータディレクトリ
#
# 使用方法:
#   ./scripts/backup.sh
#
# Cron設定例:
#   0 3 * * * /path/to/scripts/backup.sh >> /var/log/backup.log 2>&1
# ============================================

set -e

# 設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS="${RETENTION_DAYS:-30}"

# 環境変数の読み込み
if [ -f "$PROJECT_DIR/.env.prod" ]; then
    set -a
    source "$PROJECT_DIR/.env.prod"
    set +a
else
    echo "Error: .env.prod file not found"
    exit 1
fi

# バックアップディレクトリの作成
mkdir -p "$BACKUP_DIR"/{mysql,keycloak,storage}

echo "==================================="
echo "Backup started at $(date)"
echo "==================================="

# 1. MySQLバックアップ
echo "Backing up MySQL database..."
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T mysql mysqldump \
    -u root -p"${DB_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "${DB_DATABASE}" | gzip > "${BACKUP_DIR}/mysql/mysql_${DATE}.sql.gz"

if [ $? -eq 0 ]; then
    echo "✓ MySQL backup completed: mysql_${DATE}.sql.gz"
    MYSQL_SIZE=$(du -h "${BACKUP_DIR}/mysql/mysql_${DATE}.sql.gz" | cut -f1)
    echo "  Size: $MYSQL_SIZE"
else
    echo "✗ MySQL backup failed"
    exit 1
fi

# 2. Keycloak PostgreSQLバックアップ
echo "Backing up Keycloak database..."
docker-compose -f "$PROJECT_DIR/compose.prod.yaml" exec -T keycloak-postgres pg_dump \
    -U "${KEYCLOAK_DB_USERNAME}" \
    "${KEYCLOAK_DB_NAME}" | gzip > "${BACKUP_DIR}/keycloak/keycloak_${DATE}.sql.gz"

if [ $? -eq 0 ]; then
    echo "✓ Keycloak backup completed: keycloak_${DATE}.sql.gz"
    KEYCLOAK_SIZE=$(du -h "${BACKUP_DIR}/keycloak/keycloak_${DATE}.sql.gz" | cut -f1)
    echo "  Size: $KEYCLOAK_SIZE"
else
    echo "✗ Keycloak backup failed"
    exit 1
fi

# 3. Storageディレクトリバックアップ
echo "Backing up storage files..."
tar czf "${BACKUP_DIR}/storage/storage_${DATE}.tar.gz" \
    -C "$PROJECT_DIR" storage/app

if [ $? -eq 0 ]; then
    echo "✓ Storage backup completed: storage_${DATE}.tar.gz"
    STORAGE_SIZE=$(du -h "${BACKUP_DIR}/storage/storage_${DATE}.tar.gz" | cut -f1)
    echo "  Size: $STORAGE_SIZE"
else
    echo "✗ Storage backup failed"
    exit 1
fi

# 4. Keycloakデータディレクトリバックアップ
echo "Backing up Keycloak data..."
docker run --rm \
    -v cat-cafe_keycloak-data:/data \
    -v "${BACKUP_DIR}/keycloak":/backup \
    alpine tar czf /backup/keycloak_data_${DATE}.tar.gz -C /data .

if [ $? -eq 0 ]; then
    echo "✓ Keycloak data backup completed: keycloak_data_${DATE}.tar.gz"
    KEYCLOAK_DATA_SIZE=$(du -h "${BACKUP_DIR}/keycloak/keycloak_data_${DATE}.tar.gz" | cut -f1)
    echo "  Size: $KEYCLOAK_DATA_SIZE"
else
    echo "✗ Keycloak data backup failed"
    exit 1
fi

# 5. S3へのアップロード（オプション）
if [ -n "$AWS_BACKUP_BUCKET" ]; then
    echo "Uploading backups to S3..."
    
    aws s3 cp "${BACKUP_DIR}/mysql/mysql_${DATE}.sql.gz" \
        "s3://${AWS_BACKUP_BUCKET}/mysql/" --storage-class STANDARD_IA
    
    aws s3 cp "${BACKUP_DIR}/keycloak/keycloak_${DATE}.sql.gz" \
        "s3://${AWS_BACKUP_BUCKET}/keycloak/" --storage-class STANDARD_IA
    
    aws s3 cp "${BACKUP_DIR}/keycloak/keycloak_data_${DATE}.tar.gz" \
        "s3://${AWS_BACKUP_BUCKET}/keycloak/" --storage-class STANDARD_IA
    
    aws s3 cp "${BACKUP_DIR}/storage/storage_${DATE}.tar.gz" \
        "s3://${AWS_BACKUP_BUCKET}/storage/" --storage-class STANDARD_IA
    
    echo "✓ S3 upload completed"
fi

# 6. 古いバックアップの削除
echo "Cleaning up old backups..."
find "$BACKUP_DIR/mysql" -name "mysql_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR/keycloak" -name "keycloak_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR/keycloak" -name "keycloak_data_*.tar.gz" -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR/storage" -name "storage_*.tar.gz" -mtime +${RETENTION_DAYS} -delete

echo "✓ Old backups cleaned up (retention: ${RETENTION_DAYS} days)"

# 7. バックアップサマリー
echo ""
echo "==================================="
echo "Backup Summary"
echo "==================================="
echo "Backup Location: $BACKUP_DIR"
echo "MySQL:           $MYSQL_SIZE"
echo "Keycloak DB:     $KEYCLOAK_SIZE"
echo "Keycloak Data:   $KEYCLOAK_DATA_SIZE"
echo "Storage:         $STORAGE_SIZE"
echo "Completed at:    $(date)"
echo "==================================="

exit 0

