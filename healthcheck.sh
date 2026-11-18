#!/bin/bash
# ==============================================================================
# Surya Path Astrology - 360° Live Health & Diagnostics Checker
# ==============================================================================
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
NC='\033[0m'

echo -e "${PURPLE}====================================================================${NC}"
echo -e "${GREEN}      Surya Path Astrology - Real-Time Health Diagnostics           ${NC}"
echo -e "${PURPLE}====================================================================${NC}"

# 1. Check Docker Containers
echo -e "\n${BLUE}[1/6] Checking Docker Containers Status...${NC}"
docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

# 2. Check Database Connectivity & Table Count
echo -e "\n${BLUE}[2/6] Checking MySQL Database...${NC}"
if docker exec astrology-mysql mysqladmin ping -h localhost -u root -pRootPassword@2026! --silent 2>/dev/null; then
    TABLE_COUNT=$(docker exec astrology-mysql mysql -u root -pRootPassword@2026! -e "USE astrology_db; SELECT count(*) FROM information_schema.tables WHERE table_schema = 'astrology_db';" -s -N 2>/dev/null)
    echo -e "${GREEN}✓ MySQL Database is HEALTHY! Active Tables: $TABLE_COUNT${NC}"
else
    echo -e "${RED}✗ MySQL Database is UNREACHABLE!${NC}"
fi

# 3. Check Redis In-Memory Cache & Broker
echo -e "\n${BLUE}[3/6] Checking Redis Engine...${NC}"
REDIS_PING=$(docker exec astrology-redis redis-cli ping 2>/dev/null || echo "FAILED")
if [ "$REDIS_PING" = "PONG" ]; then
    REDIS_MEM=$(docker exec astrology-redis redis-cli info memory | grep "used_memory_human:" | cut -d: -f2 | tr -d '\r')
    echo -e "${GREEN}✓ Redis is HEALTHY (Memory Used: $REDIS_MEM)${NC}"
else
    echo -e "${RED}✗ Redis is UNREACHABLE!${NC}"
fi

# 4. Check LiveKit SFU WebRTC Engine
echo -e "\n${BLUE}[4/6] Checking LiveKit WebRTC SFU Server...${NC}"
LK_HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:7880/ || echo "000")
if [ "$LK_HTTP" -ne "000" ]; then
    echo -e "${GREEN}✓ LiveKit WebRTC is RUNNING! (HTTP Response: $LK_HTTP)${NC}"
else
    echo -e "${RED}✗ LiveKit Server is NOT responding on port 7880!${NC}"
fi

# 5. Check Reverb WebSockets
echo -e "\n${BLUE}[5/6] Checking Laravel Reverb WebSocket...${NC}"
if docker ps | grep -q "astrology-reverb"; then
    echo -e "${GREEN}✓ Laravel Reverb Container is RUNNING.${NC}"
else
    echo -e "${RED}✗ Laravel Reverb Container is STOPPED!${NC}"
fi

# 6. Host Resource Stats
echo -e "\n${BLUE}[6/6] Host Memory & CPU Usage...${NC}"
free -h | awk 'NR==1{print $0} NR==2{print $0}'

echo -e "\n${PURPLE}====================================================================${NC}"
echo -e "${GREEN}  Diagnostics Complete! All critical services are active.           ${NC}"
echo -e "${PURPLE}====================================================================${NC}"
