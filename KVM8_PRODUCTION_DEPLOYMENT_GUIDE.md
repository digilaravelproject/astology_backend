# 🚀 Surya Path Astrology - KVM 8 Production Deployment & Migration Guide

Is guide ki madad se aap apne **Hostinger KVM 8 VPS (Ubuntu 24.04 LTS)** par **Surya Path Astrology** backend ko **1-Click** me deploy kar sakte hain, bina kisi data loss aur bina kisi manual headache ke.

---

## 📑 Overview of Microservices (Docker Isolated Architecture)

| Container Name | Technology | Ports | Purpose |
|---|---|---|---|
| **`astrology-nginx`** | Nginx (Alpine) | `80`, `443` | Reverse Proxy, SSL, Reverb WebSocket & LiveKit Gateway |
| **`astrology-app`** | PHP 8.2-FPM (Alpine) | `9000` (Internal) | Laravel REST APIs, Wallet, Authentication, Razorpay |
| **`astrology-reverb`** | Laravel Reverb | `8080` (Internal) | Real-time Chat, Comments, Superchat & Call Signaling |
| **`astrology-worker`** | Supervisor Daemon | Internal | 4 Queue Workers + 6 Laravel Watchdogs/Schedulers |
| **`astrology-livekit`**| LiveKit SFU Engine | `7880`, `7881`, `50000-55000/udp` | 1-to-1 WebRTC Video/Audio Media Routing (Opus Codec) |
| **`astrology-coturn`** | Coturn STUN/TURN | `3478`, `5349`, `55001-65535/udp` | Mobile 4G/5G Symmetric NAT Traversal |
| **`astrology-mysql`**  | MySQL 8.0 | `3306` (Localhost) | Relational Database with 4GB InnoDB buffer pool |
| **`astrology-redis`**  | Redis 7 (Alpine) | `6379` (Localhost) | Cache, Sessions, Queue & LiveKit/Reverb Coordinator |
| **`astrology-certbot`**| Let's Encrypt | Automated | Auto-renewal of SSL certificates every 12 days |

---

## 💾 Step 1: Purane Server / phpMyAdmin Se Backup Export Karna

1. **Database Export:**
   * Apne purane server ke phpMyAdmin me login karein.
   * `astrology_db` database select karein -> **Export** tab par click karein -> **Quick** export choose karke `.sql` download karein.
   * *(OR agar terminal se karna ho):*
     ```bash
     mysqldump -u dbadmin -p'DbAdmin@2026!' astrology_db > backup.sql
     ```
2. **Uploaded Storage Zip (Photos, Kundli PDFs, Documents):**
   * Purane server par:
     ```bash
     cd /var/www/astrology/astology_backend/storage/app
     tar -czvf storage_backup.tar.gz public/
     ```

---

## 🖥️ Step 2: Naye KVM 8 VPS Par Code & Database Transfer Karna

Naye VPS terminal par root user se login karein (`ssh root@<NEW_KVM8_IP>`):

```bash
# 1. Project directory banayein
mkdir -p /var/www/astrology
cd /var/www/astrology

# 2. Git repo clone karein
git clone <YOUR_GIT_REPO_URL> astrology_backend
cd astrology_backend

# 3. Apna exported backup.sql file is folder me rakhein:
# (Aap apne local system se SCP ya FileZilla se /var/www/astrology/astology_backend/backup.sql par upload kar sakte hain):
# Example: scp backup.sql root@<NEW_KVM8_IP>:/var/www/astrology/astology_backend/backup.sql
```

*(Agar uploaded storage files hain, toh use `storage/app/public` me extract kar lein).*

---

## ⚡ Step 3: 1-Click Master Setup Run Karna

Project directory ke andar sirf yeh do commands chalaein:

```bash
chmod +x setup.sh import_db.sh
./setup.sh
```

### Script kya-kya automate karegi:
1. **Linux Kernel Tuning:** Network UDP/TCP buffers (`rmem_max=16MB`, `somaxconn=65535`) aur `nofile` ko `1048576` set karegi taaki 50,000+ connections bina drop hue chalein.
2. **UFW Firewall:** Sabhi required ports (`22, 80, 443, 7880, 7881, 3478, 50000-65535`) auto-open karegi.
3. **Docker Engine:** Official Docker & Compose verify/install karegi.
4. **Database Auto-Import:** Aapke `backup.sql` ko detect karke direct MySQL container me import karegi (**Zero Data Loss**).
5. **Multi-Stage Build:** Sabhi 8 containers start karegi aur permanent volumes (`mysql_data`, `redis_data`, `storage_data`) mount karegi.
6. **SSL Certificates:** Let's Encrypt SSL certificates generate karegi.

---

## 🌐 Step 4: DNS (Cloudflare / Domain) Records Point Karna

Apne DNS provider me naye KVM 8 IP ke liye ye **A Records** set karein:

| Record Type | Host | Target / Value | Cloudflare Proxy |
|---|---|---|---|
| **A** | `@` (`suryapathkundli.com`) | `NEW_KVM8_IP` | 🟠 **Proxied (Orange)** |
| **A** | `www` | `NEW_KVM8_IP` | 🟠 **Proxied (Orange)** |
| **A** | `live` (`live.suryapathkundli.com`) | `NEW_KVM8_IP` | ⚪ **DNS Only (Grey)** |
| **A** | `turn` (`turn.suryapathkundli.com`) | `NEW_KVM8_IP` | ⚪ **DNS Only (Grey)** |

> **Zaroori:** `live` aur `turn` records ko **DNS Only (Grey Cloud)** rakhna mandatory hai taaki WebRTC ke UDP packets directly LiveKit server par bypass ho sakein.

---

## 🗄️ Step 5: Database Re-Import Karna (Kabhi bhi zaroorat pade toh)

Agar aap setup ke baad kabhi bhi naya database dump import karna chahein:

```bash
./import_db.sh my_new_backup.sql
```
Yeh script automatically MySQL container ke andar bina foreign key errors ke safe import kar degi.

---

## 🛠️ Daily Management & Debugging Cheat-Sheet

```bash
# 1. 360° Real-time Health Diagnostics (One Command!):
./healthcheck.sh

# 2. Automated / Manual Database Backup Lena:
./backup_db.sh

# 3. Sabhi containers ka live status check karna:
docker compose ps

# 4. Real-time logs dekhna (sabka ek saath):
docker compose logs -f

# 5. LiveKit video/audio server ke logs dekhna:
docker compose logs -f astrology-livekit

# 6. Reverb WebSocket ke logs dekhna:
docker compose logs -f astrology-reverb

# 7. Background queue workers ke logs:
docker compose logs -f astrology-worker

# 8. Pura system restart karna:
docker compose restart

# 9. Container band karke dobara fresh start karna:
docker compose down && docker compose up -d
```

✅ **Aapka backend, database, WebRTC, Reverb aur LiveKit 100% crash-proof aur production ready hai!**
