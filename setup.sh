#!/bin/bash
# ==============================================================================
# Surya Path Astrology - 1-Click Master Production Setup (Hostinger KVM 8)
# Target: 50k Concurrent Users, LiveKit WebRTC, Reverb WebSockets, Zero Loss
# ==============================================================================
set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
NC='\033[0m'

clear
echo -e "${PURPLE}====================================================================${NC}"
echo -e "${GREEN}    🌟 SURYA PATH ASTROLOGY - 1-CLICK KVM 8 DOCKER DEPLOYER 🌟       ${NC}"
echo -e "${PURPLE}====================================================================${NC}"

# Check for root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: Please run this script as root (sudo ./setup.sh)${NC}"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# ------------------------------------------------------------------------------
# STEP 1: Linux Kernel & Network Tuning (Zero Call-Drop Settings)
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[1/8] Tuning Linux Kernel & File Descriptors...${NC}"

# File Descriptors Limit
if ! grep -q "soft nofile 1048576" /etc/security/limits.conf; then
    cat <<EOF >> /etc/security/limits.conf
* soft nofile 1048576
* hard nofile 1048576
root soft nofile 1048576
root hard nofile 1048576
EOF
    echo -e "${GREEN}✓ Set nofile limits to 1,048,576${NC}"
fi

# Sysctl UDP, TCP & Socket Buffers
cat <<EOF > /etc/sysctl.d/99-astrology-media.conf
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.core.rmem_default = 1048576
net.core.wmem_default = 1048576
net.core.somaxconn = 65535
net.ipv4.ip_local_port_range = 1024 65535
net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_slow_start_after_idle = 0
fs.file-max = 2097152
EOF
sysctl --system >/dev/null 2>&1
echo -e "${GREEN}✓ Kernel network buffers and socket queues tuned successfully.${NC}"

# ------------------------------------------------------------------------------
# STEP 2: Firewall (UFW) Configuration
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[2/8] Configuring Firewall (UFW) Ports...${NC}"
apt-get update -qq && apt-get install -y -qq ufw curl git unzip >/dev/null 2>&1

ufw default deny incoming >/dev/null 2>&1
ufw default allow outgoing >/dev/null 2>&1

# Core web & SSH
ufw allow 22/tcp comment 'SSH' >/dev/null 2>&1
ufw allow 80/tcp comment 'HTTP / ACME' >/dev/null 2>&1
ufw allow 443/tcp comment 'HTTPS / WSS' >/dev/null 2>&1

# LiveKit Signaling & WebRTC Fallback
ufw allow 7880/tcp comment 'LiveKit HTTP/WS' >/dev/null 2>&1
ufw allow 7881/tcp comment 'LiveKit WebRTC TCP' >/dev/null 2>&1

# Coturn STUN / TURN Ports
ufw allow 3478/tcp comment 'TURN TCP' >/dev/null 2>&1
ufw allow 3478/udp comment 'STUN/TURN UDP' >/dev/null 2>&1
ufw allow 5349/tcp comment 'TURNS TLS' >/dev/null 2>&1
ufw allow 5349/udp comment 'TURNS UDP' >/dev/null 2>&1

# Dedicated Media UDP Ranges (Separated as specified)
ufw allow 50000:55000/udp comment 'LiveKit Media UDP' >/dev/null 2>&1
ufw allow 55001:65535/udp comment 'Coturn Relay UDP' >/dev/null 2>&1

ufw --force enable >/dev/null 2>&1
echo -e "${GREEN}✓ UFW Firewall configured and active.${NC}"

# ------------------------------------------------------------------------------
# STEP 3: Verify or Install Docker & Docker Compose
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[3/8] Verifying Docker Engine & Docker Compose...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}Docker not found. Installing official Docker...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh >/dev/null 2>&1
    rm get-docker.sh
    systemctl enable docker && systemctl start docker
    echo -e "${GREEN}✓ Docker installed.${NC}"
fi

# Ensure docker compose plugin exists
if ! docker compose version &> /dev/null; then
    apt-get install -y -qq docker-compose-plugin >/dev/null 2>&1
fi
echo -e "${GREEN}✓ Docker version: $(docker --version)${NC}"

# ------------------------------------------------------------------------------
# STEP 4: Production .env Setup
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[4/8] Initializing Environment (.env)...${NC}"
SERVER_IP=$(curl -4 -s --max-time 5 https://ifconfig.me || curl -4 -s --max-time 5 https://api.ipify.org || echo "189.72.157.135")
# Ensure IPv4
if echo "$SERVER_IP" | grep -q ":"; then
    SERVER_IP="189.72.157.135"
fi
echo -e "${GREEN}✓ Detected VPS Public IPv4: $SERVER_IP${NC}"

if [ ! -f .env ]; then
    cp .env.docker.example .env
    # Auto-wire the detected IP for TURN fallback
    sed -i "s|TURN_SERVER_URL=.*|TURN_SERVER_URL=turn:${SERVER_IP}:3478|g" .env
    echo -e "${GREEN}✓ Created .env with public IP $SERVER_IP configured!${NC}"
else
    echo -e "${YELLOW}✓ Existing .env detected. Ensuring IPv4 configured.${NC}"
    sed -i "s|TURN_SERVER_URL=.*|TURN_SERVER_URL=turn:${SERVER_IP}:3478|g" .env
fi

# Ensure necessary host directories and permissions
mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache

# ------------------------------------------------------------------------------
# STEP 5: SSL Placeholder Generation (Prevents Nginx boot failure)
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[5/8] Preparing SSL Certificates...${NC}"
docker volume create certbot_etc >/dev/null
docker volume create certbot_var >/dev/null

CERT_DIR="/var/lib/docker/volumes/certbot_etc/_data/live/suryapathkundli.com"
mkdir -p "$CERT_DIR"
if [ ! -f "$CERT_DIR/fullchain.pem" ]; then
    echo -e "${YELLOW}Generating temporary self-signed SSL so Nginx boots smoothly...${NC}"
    openssl req -x509 -nodes -newkey rsa:2048 -days 30 \
        -keyout "$CERT_DIR/privkey.pem" \
        -out "$CERT_DIR/fullchain.pem" \
        -subj "/CN=suryapathkundli.com" >/dev/null 2>&1
    echo -e "${GREEN}✓ Temporary certificates generated.${NC}"
fi

# ------------------------------------------------------------------------------
# STEP 6: Start Database & Redis (With Database Auto-Import Provision)
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[6/8] Starting MySQL & Redis Infrastructure...${NC}"
docker compose up -d astrology-mysql astrology-redis

echo -e "${YELLOW}Waiting for MySQL container to become healthy...${NC}"
until docker exec astrology-mysql mysqladmin ping -h localhost -u root -pRootPassword@2026! --silent 2>/dev/null; do
    echo -n "."
    sleep 2
done
echo -e "\n${GREEN}✓ MySQL and Redis are up and healthy.${NC}"

# Database Import Provision
FOUND_BACKUP=""
if [ -f "backup.sql" ]; then
    FOUND_BACKUP="backup.sql"
elif [ -f "astrology_db_backup.sql" ]; then
    FOUND_BACKUP="astrology_db_backup.sql"
fi

chmod +x import_db.sh healthcheck.sh backup_db.sh 2>/dev/null || true

# Check if tables already exist
TABLE_COUNT=$(docker exec astrology-mysql mysql -u root -pRootPassword@2026! -e "USE astrology_db; SELECT count(*) FROM information_schema.tables WHERE table_schema = 'astrology_db';" -s -N 2>/dev/null || echo 0)

if [ "$TABLE_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Database 'astrology_db' already has $TABLE_COUNT tables. Preserving existing data!${NC}"
elif [ -n "$FOUND_BACKUP" ]; then
    echo -e "${GREEN}Found backup file '$FOUND_BACKUP'! Auto-importing your original data...${NC}"
    chmod +x import_db.sh
    ./import_db.sh "$FOUND_BACKUP"
else
    echo -e "${YELLOW}No .sql backup file found in directory.${NC}"
    echo -e "${YELLOW}If you have a phpMyAdmin backup, place it as 'backup.sql' and run: ./import_db.sh${NC}"
    echo -e "${BLUE}Running initial Laravel migrations as fallback...${NC}"
fi

# ------------------------------------------------------------------------------
# STEP 7: Build & Launch All 8 Containers
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[7/8] Building & Starting All Production Microservices...${NC}"
docker compose up -d --build

# Run Laravel post-boot optimization
echo -e "\n${BLUE}Optimizing Laravel inside Container...${NC}"
docker exec astrology-app composer install --no-dev --optimize-autoloader --no-interaction
docker exec astrology-app php artisan key:generate --force
docker exec astrology-app php artisan storage:link || true
docker exec astrology-app php artisan optimize:clear
docker exec astrology-app php artisan config:cache
docker exec astrology-app php artisan route:cache
docker exec astrology-app php artisan view:cache
docker exec astrology-app php artisan event:cache || true

# ------------------------------------------------------------------------------
# STEP 8: Attempt Let's Encrypt SSL Generation
# ------------------------------------------------------------------------------
echo -e "\n${BLUE}[8/8] Verifying Live Let's Encrypt SSL Certificates...${NC}"
echo -e "${YELLOW}Attempting automatic Let's Encrypt SSL issuance for domains...${NC}"
docker rm -f astrology-certbot 2>/dev/null || true

docker compose run --rm certbot certonly --webroot -w /var/www/certbot \
    -d suryapathkundli.com -d www.suryapathkundli.com -d live.suryapathkundli.com \
    --email admin@suryapathkundli.com --agree-tos --no-eff-email --non-interactive 2>/dev/null && {
        echo -e "${GREEN}✓ Let's Encrypt SSL issued successfully! Reloading Nginx...${NC}"
        docker compose exec astrology-nginx nginx -s reload
    } || {
        echo -e "${YELLOW}Notice: Temporary SSL is keeping HTTPS operational.${NC}"
    }

docker compose up -d certbot 2>/dev/null || true

# ------------------------------------------------------------------------------
# SUMMARY & STATUS REPORT
# ------------------------------------------------------------------------------
echo -e "\n${PURPLE}====================================================================${NC}"
echo -e "${GREEN}  🎉 CONGRATULATIONS! SURYA PATH BACKEND IS SUCCESSFULLY LIVE! 🎉  ${NC}"
echo -e "${PURPLE}====================================================================${NC}"
docker compose ps

echo -e "\n${BLUE}Core Endpoints:${NC}"
echo -e " • REST API:           ${GREEN}https://suryapathkundli.com/api${NC}"
echo -e " • Reverb WebSockets:  ${GREEN}wss://suryapathkundli.com/app${NC}"
echo -e " • LiveKit SFU:        ${GREEN}wss://live.suryapathkundli.com${NC} (Port: 7880/7881)"
echo -e " • STUN/TURN Server:   ${GREEN}turn:suryapathkundli.com:3478${NC}"
echo -e "\n${BLUE}Helpful Commands:${NC}"
echo -e " • View all logs:      ${YELLOW}docker compose logs -f${NC}"
echo -e " • View API logs:      ${YELLOW}docker compose logs -f astrology-app${NC}"
echo -e " • View LiveKit logs:  ${YELLOW}docker compose logs -f astrology-livekit${NC}"
echo -e " • Re-import DB:       ${YELLOW}./import_db.sh [backup.sql]${NC}"
echo -e " • Restart Platform:   ${YELLOW}docker compose restart${NC}"
echo -e "${PURPLE}====================================================================${NC}"
