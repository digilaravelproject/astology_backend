# 🔐 Surya Path Astrology - Master Credentials & Server Cheat-Sheet

Yeh document aapke pure production infrastructure ke sabhi passwords, keys, server IPs aur configuration details ko ek jagah preserve karta hai.

---

## 🖥️ 1. Hostinger KVM 8 VPS Access

| Item | Value |
|---|---|
| **Server Public IPv4** | `189.72.157.135` |
| **SSH Username** | `root` |
| **SSH Password** | `DigiAstro@7979` |
| **SSH Port** | `22` |
| **SSH Command** | `ssh root@189.72.157.135` |
| **Operating System** | Ubuntu 24.04 LTS (64-bit) |
| **Hardware Specs** | AMD EPYC 9355P (8 vCPU Cores), 32 GB RAM, 387 GB NVMe |
| **Project Location** | `/var/www/astrology/astology_backend` |

---

## 🗄️ 2. MySQL 8.0 Database (Docker Container: `astrology-mysql`)

| Setting | Value |
|---|---|
| **Database Name** | `astrology_db` |
| **Standard User** | `dbadmin` |
| **Standard Password** | `DbAdmin@2026!` |
| **Root User** | `root` |
| **Root Password** | `RootPassword@2026!` |
| **Host (Inside Containers)**| `astrology-mysql` |
| **Host (From VPS Terminal)**| `127.0.0.1` |
| **Port** | `3306` |
| **Docker Volume** | `mysql_data` (Zero data-loss persistence) |
| **Table Count** | 67 Active Tables |

---

## ⚡ 3. Redis 7 Cache & Queues (Docker Container: `astrology-redis`)

| Setting | Value |
|---|---|
| **Host (Inside Containers)**| `astrology-redis` |
| **Host (From VPS Terminal)**| `127.0.0.1` |
| **Port** | `6379` |
| **Password** | `null` (None) |
| **Docker Volume** | `redis_data` (AOF Persistent) |
| **Used For** | Sessions, Cache, Laravel Queue, Reverb & LiveKit Coordination |

---

## 📹 4. LiveKit WebRTC SFU Engine (Container: `astrology-livekit`)

| Setting | Value |
|---|---|
| **Public WebSocket URL** | `wss://live.suryapathkundli.com` |
| **Internal Server URL** | `http://host.docker.internal:7880` |
| **API Key** | `devkey` |
| **API Secret** | `d8f1056b9524b66d2fdbe1c38e057138` |
| **Signaling Port** | `7880` (HTTP/WS) |
| **WebRTC TCP Fallback** | `7881` |
| **Media UDP Range** | `50000 – 55000` |
| **Audio Codec** | Opus (24–32 kbps mobile-optimized) |
| **Config File** | `docker/livekit/livekit.yaml` |

---

## 🛡️ 5. Coturn STUN/TURN NAT Server (Container: `astrology-coturn`)

| Setting | Value |
|---|---|
| **Public TURN URL** | `turn:suryapathkundli.com:3478` (or `turn:189.72.157.135:3478`) |
| **TURN Secret** | `livekit_secret_2024` |
| **Static Username** | `livekit` |
| **Static Credential** | `livekit_secret_2024` |
| **STUN/TURN Port** | `3478` (UDP/TCP) |
| **TURNS Port** | `5349` (TLS) |
| **Relay Media UDP Range** | `55001 – 65535` |
| **Config File** | `docker/coturn/turnserver.conf` |

---

## 💬 6. Laravel Reverb WebSockets (Container: `astrology-reverb`)

| Setting | Value |
|---|---|
| **Public WebSocket URL** | `wss://suryapathkundli.com/app` |
| **App ID** | `astrology-app` |
| **App Key** | `astrology-key` |
| **App Secret** | `astrology-secret` |
| **Port** | `8080` (Proxied over port `443 WSS` via Nginx) |
| **Scheme** | `https` / `wss` |

---

## 💳 7. Razorpay Payment Gateway

| Parameter | Value |
|---|---|
| **Key ID** | `rzp_test_TFj2EcTaL8Q3Lc` |
| **Key Secret** | `4txtZjJ6rjvzSGYI91AckBQU` |
| **Webhook Secret** | `8esSABFrAQrY8r14S7T22Q4D` |

---

## 📱 8. Exotel SMS & OTP Gateway

| Parameter | Value |
|---|---|
| **Account SID** | `suryapathkundliandlifeguidance1` |
| **API Key** | `suryapathkundliandlifeguidance1` |
| **API Token** | `de052d923c1e08593411cf1c4da8761d3d20c53e` |
| **Subdomain** | `api` |
| **Sender ID** | `SUPKUN` |
| **DLT Entity ID** | `1101508600000095813` |
| **DLT Template ID** | `1107178180354805313` |

---

## ☁️ 9. Cloudflare Configuration & Free CDN Caching

| Setting | Value |
|---|---|
| **Account Email** | `vinodmishra030303@gmail.com` |
| **Global API Key** | `cfk_WdyR...020643250` (Configured in Cloudflare API) |
| **Zone ID** | `473d56397399665ce330d27489d8a8cb` |
| **Account ID** | `020c982c26051de4d9cd5a67382b0d94` |
| **SSL/TLS Mode** | `Full` |
| **WebSockets** | `ON (Enabled)` |
| **Active Page Rules** | 1. `*suryapathkundli.com/*.ts` ➡️ Cache Everything (1 day)<br>2. `*suryapathkundli.com/*.m3u8` ➡️ Bypass Cache (Live)<br>3. `*suryapathkundli.com/storage/*` ➡️ Cache Everything (30 days) |

---

## 🌐 10. Live Public Domain Endpoints

* **Main REST API & Website:** `https://suryapathkundli.com`
* **Realtime WebSockets (Reverb):** `wss://suryapathkundli.com/app`
* **LiveKit WebRTC Media Server:** `wss://live.suryapathkundli.com`
* **STUN/TURN NAT Server:** `turn:suryapathkundli.com:3478`

---

## 🛠️ 11. Most Useful Server Commands

```bash
# 1. 360° Live Diagnostics (CPU, RAM, Disk, Containers):
./healthcheck.sh

# 2. Database Backup lena:
./backup_db.sh

# 3. Naya Database dump import karna:
./import_db.sh [filename.sql]

# 4. Sabhi container logs dekhna:
docker compose logs -f

# 5. Specific container ke logs (e.g. LiveKit ya Reverb ya App):
docker compose logs -f astrology-livekit
docker compose logs -f astrology-reverb
docker compose logs -f astrology-app

# 6. Pura system restart karna:
docker compose down && docker compose up -d
```
