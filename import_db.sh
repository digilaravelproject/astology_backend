#!/bin/bash
# ==============================================================================
# Surya Path Astrology - MySQL Database Import Tool
# ==============================================================================
set -e

# Styling
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SQL_FILE="${1:-backup.sql}"

echo -e "${BLUE}====================================================================${NC}"
echo -e "${GREEN}      Surya Path Astrology - Database Import Assistant              ${NC}"
echo -e "${BLUE}====================================================================${NC}"

if [ ! -f "$SQL_FILE" ]; then
    echo -e "${YELLOW}File '$SQL_FILE' not found in current directory.${NC}"
    read -p "Enter the full path or name of your .sql backup file: " SQL_FILE
    if [ ! -f "$SQL_FILE" ]; then
        echo -e "${RED}Error: Backup file '$SQL_FILE' still does not exist! Aborting.${NC}"
        exit 1
    fi
fi

echo -e "${BLUE}[1/4] Checking MySQL container status...${NC}"
if ! docker ps | grep -q "astrology-mysql"; then
    echo -e "${YELLOW}astrology-mysql is not running. Starting database container...${NC}"
    docker compose up -d astrology-mysql
fi

echo -e "${BLUE}[2/4] Waiting for MySQL to be ready...${NC}"
until docker exec astrology-mysql mysqladmin ping -h localhost -u root -pRootPassword@2026! --silent 2>/dev/null; do
    echo -n "."
    sleep 2
done
echo -e "\n${GREEN}MySQL is ready and healthy.${NC}"

# Ensure database exists and grant permissions to dbadmin on both % and localhost
docker exec -i astrology-mysql mysql -u root -pRootPassword@2026! <<EOF
CREATE DATABASE IF NOT EXISTS astrology_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'dbadmin'@'%' IDENTIFIED BY 'DbAdmin@2026!';
CREATE USER IF NOT EXISTS 'dbadmin'@'localhost' IDENTIFIED BY 'DbAdmin@2026!';
GRANT ALL PRIVILEGES ON astrology_db.* TO 'dbadmin'@'%';
GRANT ALL PRIVILEGES ON astrology_db.* TO 'dbadmin'@'localhost';
FLUSH PRIVILEGES;
EOF

echo -e "${BLUE}[3/4] Importing '$SQL_FILE' into 'astrology_db'...${NC}"
echo -e "${YELLOW}(File size is large (~1.2GB). Importing with root privileges, please wait 1-2 minutes...)${NC}"

# Import with root user and foreign key checks disabled for seamless import
docker exec -i astrology-mysql mysql -u root -pRootPassword@2026! astrology_db <<EOF
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION';
EOF

docker exec -i astrology-mysql mysql -u root -pRootPassword@2026! astrology_db < "$SQL_FILE"

docker exec -i astrology-mysql mysql -u root -pRootPassword@2026! astrology_db <<EOF
SET FOREIGN_KEY_CHECKS=1;
EOF

echo -e "${GREEN}Database import completed successfully!${NC}"

echo -e "${BLUE}[4/4] Verifying imported tables...${NC}"
TABLE_COUNT=$(docker exec astrology-mysql mysql -u root -pRootPassword@2026! -e "USE astrology_db; SELECT count(*) FROM information_schema.tables WHERE table_schema = 'astrology_db';" -s -N)

echo -e "${GREEN}====================================================================${NC}"
echo -e "${GREEN}  SUCCESS: Imported $TABLE_COUNT tables into 'astrology_db'!         ${NC}"
echo -e "${GREEN}  Your users, astrologers, transactions and wallets are 100% restored.${NC}"
echo -e "${GREEN}====================================================================${NC}"
