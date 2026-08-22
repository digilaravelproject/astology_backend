# 🚀 Complete Production VPS Migration & Setup Guide (Step-by-Step)
**Project:** Astrology & Kundli Backend (Laravel 12 + Reverb WebSockets + Redis + LiveKit WebRTC + MySQL)  
**Target Server:** Fresh Linux VPS (Ubuntu 22.04 LTS / Ubuntu 24.04 LTS)  
**Domain:** `suryapathkundli.com` & `live.suryapathkundli.com`

---

## 📑 Summary of Installed Services
| Component | Technology | Default Port | Managed By |
|---|---|---|---|
| **Web Server** | Nginx + PHP 8.2/8.3-FPM | 80, 443 | Systemd (`nginx`, `php8.2-fpm`) |
| **Database** | MySQL 8.0 / MariaDB | 3306 | Systemd (`mysql`) |
| **Cache, Session & Queue** | Redis | 6379 | Systemd (`redis-server`) |
| **Real-time WebSockets** | Laravel Reverb | 8080 (Proxied to 443 WSS) | Supervisor (`laravel-reverb`) |
| **Queue Worker** | Laravel Queue Worker | Background | Supervisor (`laravel-worker`) |
| **WebRTC Audio/Video** | LiveKit Server | 7880 (Proxied to 443 WSS) | Systemd (`livekit-server`) |
| **Cron Scheduler** | Laravel Scheduler | Scheduled | Linux `crontab` |

---

## 📦 PHASE 1: OLD SERVER SE BACKUP LENA (OLD SERVER COMMANDS)

Naye server par migrate karne se pehle old server se ye 3 cheezein backup karein:

### 1. Database Dump Lena:
Old server ke terminal par run karein:
```bash
# Database dump export karein
mysqldump -u dbadmin -p'DbAdmin@2026!' astrology_db > /root/astrology_db_backup.sql

# Check karein file size
ls -lh /root/astrology_db_backup.sql
```

### 2. Uploaded Media / Profile Photos & Charts Zip Karna:
```bash
cd /var/www/astrology/astology_backend/storage/app
tar -czvf /root/uploaded_storage_public.tar.gz public/
```

### 3. LiveKit Config Backup:
```bash
cp /etc/livekit.yaml /root/livekit.yaml 2>/dev/null || cp /var/www/astrology/livekit.yaml /root/livekit.yaml 2>/dev/null
```

---

## 🖥️ PHASE 2: NEW VPS INITIAL SETUP & PACKAGES INSTALLATION

Naye fresh VPS terminal par root user se login karein (`ssh root@<NEW_VPS_IP>`):

### Step 1: System Update & Essential Tools Install Karein
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip zip software-properties-common ufw supervisor certbot python3-certbot-nginx
```

### Step 2: Firewall (UFW) Ports Open Karein
```bash
sudo ufw allow 22/tcp        # SSH
sudo ufw allow 80/tcp        # HTTP
sudo ufw allow 443/tcp       # HTTPS
sudo ufw allow 3478/udp      # TURN / STUN
sudo ufw allow 50000:60000/udp # LiveKit WebRTC media traffic
sudo ufw --force enable
sudo ufw status
```

### Step 3: PHP 8.2 & Required Extensions Install Karein
```bash
# Add Ondrej PPA for latest PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 and required Laravel modules
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-mbstring \
php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-soap php8.2-readline

# Verify PHP version
php -v
```

### Step 4: Composer & Node.js 20.x Install Karein
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# Install Node.js 20 LTS & npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### Step 5: MySQL & Redis Install Karein
```bash
# Install MySQL & Redis
sudo apt install -y mysql-server redis-server

# Enable & Start Services
sudo systemctl enable mysql redis-server
sudo systemctl start mysql redis-server

# Verify Redis
redis-cli ping
# Output aana chahiye: PONG
```

---

## 🗄️ PHASE 3: DATABASE SETUP & DUMP IMPORT

### Step 1: MySQL Database & User Create Karein
MySQL terminal open karein:
```bash
sudo mysql
```
MySQL ke andar ye queries run karein:
```sql
CREATE DATABASE astrology_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dbadmin'@'127.0.0.1' IDENTIFIED BY 'DbAdmin@2026!';
CREATE USER 'dbadmin'@'localhost' IDENTIFIED BY 'DbAdmin@2026!';
GRANT ALL PRIVILEGES ON astrology_db.* TO 'dbadmin'@'127.0.0.1';
GRANT ALL PRIVILEGES ON astrology_db.* TO 'dbadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 2: Old Server Se Backup Dump Import Karein
*(Aap `scp` se ya manual paste karke `/root/astrology_db_backup.sql` ko naye server par laa sakte hain).*
```bash
# Import database dump
mysql -u dbadmin -p'DbAdmin@2026!' astrology_db < /root/astrology_db_backup.sql

# Verify import
mysql -u dbadmin -p'DbAdmin@2026!' -e "USE astrology_db; SHOW TABLES;"
```

---

## 📂 PHASE 4: CODEBASE DEPLOYMENT & PERMISSIONS

### Step 1: Code Clone / Transfer Karein
```bash
# Directory create karein
sudo mkdir -p /var/www/astrology/astology_backend
cd /var/www/astrology

# Clone your git repository (ya old server se copy karein)
git clone <YOUR_GIT_REPO_URL> astology_backend
# (OR agar direct folder ho toh ensure path: /var/www/astrology/astology_backend)

cd /var/www/astrology/astology_backend
```

### Step 2: `.env` File Create Karein
```bash
nano /var/www/astrology/astology_backend/.env
```
Isme aapki production `.env` paste karein:
```env
APP_NAME=astrology
APP_ENV=production
APP_DEBUG=false
APP_INSTALL=true
APP_LOG_LEVEL=error
APP_MODE=live
APP_URL=https://suryapathkundli.com
ASSET_URL=https://suryapathkundli.com
APP_KEY=base64:O2ujywXthsiLjb8rd9PbKQdJa9mHhkkYGAyDjlRp1rE=
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_TIMEZONE="+05:30"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=astrology_db
DB_USERNAME=dbadmin
DB_PASSWORD="DbAdmin@2026!"

BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb

CACHE_DRIVER=redis
CACHE_STORE=redis

SESSION_DRIVER=redis
SESSION_LIFETIME=120

QUEUE_CONNECTION=redis
QUEUE_DRIVER=redis

REVERB_APP_ID=astrology-app
REVERB_APP_KEY=astrology-key
REVERB_APP_SECRET=astrology-secret

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=suryapathkundli.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

REVERB_APP_PING_INTERVAL=60
REVERB_APP_ACTIVITY_TIMEOUT=604800

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

PURCHASE_CODE=93c6ee22-6631-44cd-b85e-7ad50dd13d61
BUYER_USERNAME=thedigiempire
SOFTWARE_ID=MzY3NzIxMTI=

SOFTWARE_VERSION=3.5
REACT_APP_KEY=45370351

RAZORPAY_KEY_ID=rzp_test_TFj2EcTaL8Q3Lc
RAZORPAY_KEY_SECRET=4txtZjJ6rjvzSGYI91AckBQU
RAZORPAY_WEBHOOK_SECRET=8esSABFrAQrY8r14S7T22Q4D

LIVEKIT_WS_URL=wss://live.suryapathkundli.com
LIVEKIT_SERVER_URL=http://127.0.0.1:7880
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=d8f1056b9524b66d2fdbe1c38e057138

TURN_SERVER_URL=turn:187.127.173.87:3478
TURN_SERVER_SECRET=livekit_secret_2024
TURN_CREDENTIAL_TTL=86400
TURN_SERVER_USERNAME=livekit
TURN_SERVER_CREDENTIAL=livekit_secret_2024

EXOTEL_ACCOUNT_SID=suryapathkundliandlifeguidance1
EXOTEL_API_KEY=suryapathkundliandlifeguidance1
EXOTEL_API_TOKEN=de052d923c1e08593411cf1c4da8761d3d20c53e
EXOTEL_SUBDOMAIN=api
EXOTEL_SENDER_ID=SUPKUN
EXOTEL_DLT_ENTITY_ID=1101508600000095813
EXOTEL_DLT_TEMPLATE_ID=1107178180354805313
```

### Step 3: Composer Install & Storage Setup
```bash
cd /var/www/astrology/astology_backend

# Install PHP dependencies without dev packages
composer install --no-dev --optimize-autoloader

# Restore uploaded storage files
tar -xzvf /root/uploaded_storage_public.tar.gz -C /var/www/astrology/astology_backend/storage/app/

# Create symlink for public storage
php artisan storage:link

# Permissions set karein (Super Important)
sudo chown -R www-data:www-data /var/www/astrology/astology_backend
sudo chmod -R 775 /var/www/astrology/astology_backend/storage
sudo chmod -R 775 /var/www/astrology/astology_backend/bootstrap/cache

# Run migrations (if any pending)
php artisan migrate --force

# Optimize Laravel caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📹 PHASE 5: LIVEKIT WEBRTC SERVER SETUP

LiveKit audio aur video calls handle karta hai.

### Step 1: LiveKit Binary Install Karein
```bash
curl -sSL https://get.livekit.io | bash
# Binary install location: /usr/local/bin/livekit-server
livekit-server --version
```

### Step 2: LiveKit Configuration File Banayein
```bash
sudo mkdir -p /etc/livekit
sudo nano /etc/livekit/config.yaml
```
Isme ye paste karein:
```yaml
port: 7880
bind_addresses:
  - ""
rtc:
  tcp_port: 7881
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: true
keys:
  devkey: d8f1056b9524b66d2fdbe1c38e057138
turn:
  enabled: true
  domain: suryapathkundli.com
  tls_port: 3478
  udp_port: 3478
  secret: livekit_secret_2024
```

### Step 3: LiveKit Systemd Service Create Karein
```bash
sudo nano /etc/systemd/system/livekit-server.service
```
Paste karein:
```ini
[Unit]
Description=LiveKit Server
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/livekit-server --config /etc/livekit/config.yaml
Restart=always
RestartSec=5
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```

Enable & Start LiveKit:
```bash
sudo systemctl daemon-reload
sudo systemctl enable livekit-server
sudo systemctl restart livekit-server
sudo systemctl status livekit-server
```

---

## ⚡ PHASE 6: SUPERVISOR SETUP (REVERB WEBSOCKET + QUEUE WORKER)

### Step 1: Queue Worker Service Config
```bash
sudo nano /etc/supervisor/conf.d/astrology-worker.conf
```
Paste karein:
```ini
[program:astrology-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/astrology/astology_backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/astrology/astology_backend/storage/logs/worker.log
stopwaitsecs=3600
```

### Step 2: Reverb WebSocket Service Config
```bash
sudo nano /etc/supervisor/conf.d/astrology-reverb.conf
```
Paste karein:
```ini
[program:astrology-reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/astrology/astology_backend/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/astrology/astology_backend/storage/logs/reverb.log
```

### Step 3: Supervisor Reload & Start Karein
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```
*(Status me `astrology-worker` aur `astrology-reverb` dono **RUNNING** dikhne chahiye).*

---

## ⏰ PHASE 7: LARAVEL CRON SCHEDULER SETUP

Laravel ke automated jobs, billing ticks, aur missed session cleaners ke liye cronjob add karein:

```bash
sudo crontab -e -u www-data
```
Neeche ye line paste karein:
```cron
* * * * * cd /var/www/astrology/astology_backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🌐 PHASE 8: NGINX CONFIGURATION & SSL CERTIFICATE

### Step 1: Main Nginx Server Block Create Karein
```bash
sudo nano /etc/nginx/sites-available/suryapathkundli.conf
```
Paste karein:
```nginx
# 1. Main Laravel Backend & API
server {
    listen 80;
    listen [::]:80;
    server_name suryapathkundli.com www.suryapathkundli.com;
    root /var/www/astrology/astology_backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Reverb WebSockets Proxy (/app/)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }

    # PHP-FPM Processor
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# 2. LiveKit WebRTC Subdomain (live.suryapathkundli.com)
server {
    listen 80;
    listen [::]:80;
    server_name live.suryapathkundli.com;

    location / {
        proxy_pass http://127.0.0.1:7880;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }
}
```

### Step 2: Nginx Site Enable & Test Karein
```bash
sudo ln -s /etc/nginx/sites-available/suryapathkundli.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 3: SSL Certificates Issue Karein (Let's Encrypt Certbot)
*(DNS me A record point hone ke baad run karein):*
```bash
sudo certbot --nginx -d suryapathkundli.com -d www.suryapathkundli.com -d live.suryapathkundli.com --non-interactive --agree-tos -m admin@suryapathkundli.com
```

---

## 🔍 PHASE 9: SMOKE TESTING & VERIFICATION COMMANDS

Migration complete hone ke baad ye commands run karke verify karein ki sabhi services live hain:

```bash
# 1. Check all critical system services
sudo systemctl status nginx php8.2-fpm mysql redis-server livekit-server

# 2. Check Supervisor Daemons (Workers & Reverb)
sudo supervisorctl status

# 3. Test Redis connectivity
redis-cli ping

# 4. Check Laravel Logs for any runtime errors
tail -n 50 /var/www/astrology/astology_backend/storage/logs/laravel.log

# 5. Check Reverb WebSocket Log
tail -n 50 /var/www/astrology/astology_backend/storage/logs/reverb.log

# 6. Test LiveKit healthcheck
curl -I http://127.0.0.1:7880/
```

🎉 **MIGRATION COMPLETE!** Aapka pura platform zero downtime ke sath naye VPS par live ho jayega!
