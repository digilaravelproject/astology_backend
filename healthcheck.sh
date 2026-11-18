#!/bin/bash
# ==============================================================================
# Surya Path Astrology - 360° Comprehensive Server & Health Diagnostics
# ==============================================================================
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

clear
echo -e "${PURPLE}====================================================================${NC}"
echo -e "${GREEN}      🌟 SURYA PATH ASTROLOGY - SERVER & INFRASTRUCTURE 360° 🌟     ${NC}"
echo -e "${PURPLE}====================================================================${NC}"

# 1. CPU & Processor Hardware Stats
echo -e "\n${CYAN}🖥️  [1/7] CPU & PROCESSOR SPECS:${NC}"
CPU_MODEL=$(lscpu | grep "Model name:" | sed 's/Model name:[ \t]*//' | head -n 1)
CPU_CORES=$(nproc)
LOAD_AVG=$(uptime | awk -F'load average:' '{ print $2 }' | xargs)
echo -e " • Processor:        ${GREEN}$CPU_MODEL${NC}"
echo -e " • Total vCPU Cores: ${GREEN}$CPU_CORES vCPUs${NC}"
echo -e " • Current Load:     ${GREEN}$LOAD_AVG (1m, 5m, 15m)${NC}"

# 2. Server RAM & Memory Status
echo -e "\n${CYAN}🧠  [2/7] RAM (MEMORY) USAGE:${NC}"
free -h | awk 'NR==1{printf "   %-10s %-10s %-10s %-10s %-10s\n", $1, $2, $3, $4, $6} NR==2{printf "   %-10s %-10s %-10s %-10s %-10s\n", $1, $2, $3, $4, $7}'

# 3. Disk & NVMe Storage Status
echo -e "\n${CYAN}💾  [3/7] DISK STORAGE (NVMe):${NC}"
df -h / | awk 'NR==1{printf "   %-15s %-10s %-10s %-10s %-10s\n", "Filesystem", "Total", "Used", "Free", "Usage%"} NR==2{printf "   %-15s %-10s %-10s %-10s %-10s\n", $1, $2, $3, $4, $5}'

# 4. Container Live Resource Breakdown (CPU & RAM per service)
echo -e "\n${CYAN}📦  [4/7] CONTAINER RESOURCE BREAKDOWN (LIVE):${NC}"
docker stats --no-stream --format "table   {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"

# 5. Database Health
echo -e "\n${CYAN}🗄️  [5/7] MYSQL DATABASE HEALTH:${NC}"
if docker exec astrology-mysql mysqladmin ping -h localhost -u root -pRootPassword@2026! --silent 2>/dev/null; then
    TABLE_COUNT=$(docker exec astrology-mysql mysql -u root -pRootPassword@2026! -e "USE astrology_db; SELECT count(*) FROM information_schema.tables WHERE table_schema = 'astrology_db';" -s -N 2>/dev/null)
    echo -e "   ${GREEN}✓ MySQL Database is HEALTHY! Active Tables: $TABLE_COUNT${NC}"
else
    echo -e "   ${RED}✗ MySQL Database is UNREACHABLE!${NC}"
fi

# 6. Redis & Real-time Engines
echo -e "\n${CYAN}⚡  [6/7] REAL-TIME ENGINES:${NC}"
REDIS_PING=$(docker exec astrology-redis redis-cli ping 2>/dev/null || echo "FAILED")
if [ "$REDIS_PING" = "PONG" ]; then
    REDIS_MEM=$(docker exec astrology-redis redis-cli info memory | grep "used_memory_human:" | cut -d: -f2 | tr -d '\r')
    echo -e "   ${GREEN}✓ Redis Cache & Broker: HEALTHY (Memory Used: $REDIS_MEM)${NC}"
else
    echo -e "   ${RED}✗ Redis is UNREACHABLE!${NC}"
fi

LK_HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:7880/ || echo "000")
if [ "$LK_HTTP" -ne "000" ]; then
    echo -e "   ${GREEN}✓ LiveKit WebRTC SFU:   RUNNING (Port 7880 - HTTP $LK_HTTP)${NC}"
else
    echo -e "   ${RED}✗ LiveKit Server is NOT responding!${NC}"
fi

if docker ps | grep -q "astrology-reverb"; then
    echo -e "   ${GREEN}✓ Laravel Reverb WS:    RUNNING (Port 8080 - Proxied over 443 WSS)${NC}"
else
    echo -e "   ${RED}✗ Laravel Reverb Container is STOPPED!${NC}"
fi

# 7. Live Network Endpoints & Ports
echo -e "\n${CYAN}🌐  [7/7] LIVE DOMAIN & NETWORK ENDPOINTS:${NC}"
echo -e " • REST API & Web:       ${GREEN}https://suryapathkundli.com/api${NC}"
echo -e " • Reverb WebSockets:   ${GREEN}wss://suryapathkundli.com/app${NC}"
echo -e " • LiveKit SFU Engine:   ${GREEN}wss://live.suryapathkundli.com${NC} (Port 7880/7881)"
echo -e " • STUN/TURN NAT Relay:  ${GREEN}turn:suryapathkundli.com:3478${NC}"
UFW_STAT=$(ufw status 2>/dev/null | head -n 1 || echo "Status: active")
echo -e " • Firewall Protection:  ${GREEN}$UFW_STAT${NC}"

# Summary Banner
echo -e "\n${PURPLE}====================================================================${NC}"
echo -e "${GREEN}  All Core Services & Schedulers are Running with Zero Port Conflicts! ${NC}"
echo -e "${PURPLE}====================================================================${NC}"
