#!/bin/bash
# ==============================================================================
# Surya Path Astrology - Automated Database Backup & Retention Tool
# ==============================================================================
set -e

BACKUP_DIR="${BACKUP_DIR:-/var/backups/astrology_db}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="astrology_db_backup_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "Creating compressed MySQL dump: $BACKUP_DIR/$FILENAME ..."

docker exec astrology-mysql mysqldump -u dbadmin -pDbAdmin@2026! \
    --single-transaction \
    --quick \
    --triggers \
    astrology_db | gzip > "$BACKUP_DIR/$FILENAME"

FILE_SIZE=$(du -h "$BACKUP_DIR/$FILENAME" | cut -f1)
echo "✓ Backup created successfully ($FILE_SIZE) at: $BACKUP_DIR/$FILENAME"

# Delete backups older than 7 days
find "$BACKUP_DIR" -type f -name "astrology_db_backup_*.sql.gz" -mtime +7 -exec rm {} \;
echo "✓ Old backups pruned (7-day retention preserved)."
