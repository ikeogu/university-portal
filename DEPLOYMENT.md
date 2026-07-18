# Deployment Runbook — Contabo VPS

Target: a self-managed Ubuntu VPS (Contabo or equivalent) with full root access — not shared
cPanel hosting. This assumes Ubuntu 22.04/24.04 LTS.

## 1. Server prerequisites

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server supervisor unzip git ufw

# PHP 8.3 + required extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-redis

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Node is a **build-time-only** dependency — build assets on your own machine or in CI and ship
`public/build/`, or install Node on the server just to run `npm run build` once per deploy.
The Node process itself never needs to run in production; this app is 100% PHP at runtime.

Firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 2. Database

```bash
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE cgpa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'cgpa'@'localhost' IDENTIFIED BY 'CHANGE_ME';"
sudo mysql -e "GRANT ALL PRIVILEGES ON cgpa.* TO 'cgpa'@'localhost'; FLUSH PRIVILEGES;"
```

## 3. Deploy the application

```bash
sudo mkdir -p /var/www/cgpa && sudo chown $USER:$USER /var/www/cgpa
git clone <your-repo-url> /var/www/cgpa
cd /var/www/cgpa

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` for production — **these differ from local dev, don't skip any**:

```dotenv
APP_ENV=production
APP_DEBUG=false                 # critical: a debug page in prod leaks env vars, queries, file paths
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=cgpa
DB_USERNAME=cgpa
DB_PASSWORD=CHANGE_ME

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis          # not required at current scale (imports run synchronously),
                                 # but ready if that ever changes — see README/plan.

LOG_LEVEL=warning               # not "debug" — avoid logging routine request detail in prod
```

Build frontend assets (either here, if Node is installed, or locally then `rsync public/build/`):

```bash
npm ci
npm run build
```

Migrate, seed settings, and bootstrap the first admin account:

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\SettingsSeeder --force
php artisan admin:create-first
```

Set ownership/permissions:

```bash
sudo chown -R www-data:www-data /var/www/cgpa
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Nginx site config

`/etc/nginx/sites-available/cgpa`:

```nginx
server {
    listen 80;
    server_name your-domain.example;
    root /var/www/cgpa/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/cgpa /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 5. TLS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.example
```

## 6. Scheduler (cron)

```bash
crontab -e
# add:
* * * * * cd /var/www/cgpa && php artisan schedule:run >> /dev/null 2>&1
```

Nothing is scheduled yet at launch — this is a no-op until a scheduled task is added — but it
costs nothing to have in place from day one.

## 7. Backups — not optional

This system stores official academic records. Set up automated daily backups before real data
exists in it, not after:

```bash
sudo tee /usr/local/bin/cgpa-backup.sh > /dev/null <<'EOF'
#!/bin/bash
TIMESTAMP=$(date +%F)
mysqldump -u cgpa -p'CHANGE_ME' cgpa | gzip > /var/backups/cgpa/db-$TIMESTAMP.sql.gz
find /var/backups/cgpa -name "db-*.sql.gz" -mtime +30 -delete
EOF
sudo chmod +x /usr/local/bin/cgpa-backup.sh
sudo mkdir -p /var/backups/cgpa
echo "0 2 * * * root /usr/local/bin/cgpa-backup.sh" | sudo tee /etc/cron.d/cgpa-backup
```

Copy backups off-server too (`rclone` to S3-compatible storage, or Contabo's own backup
add-on) — a backup that lives on the same disk as the database it protects isn't a real backup.

## 8. Redeploying after a code change

```bash
cd /var/www/cgpa
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # or rsync a locally-built public/build/
php artisan migrate --force
php artisan config:cache
php artisan route:cache
sudo systemctl reload php8.3-fpm
```

## NDPR-adjacent notes

This app stores DOB, state of origin, and marital status — personal data the Nigeria Data
Protection Regulation cares about. Beyond `APP_DEBUG=false` and HTTPS-everywhere above:

- The public result checker never displays a student's DOB or marital status back to them —
  DOB is used only as a private verification credential, never echoed.
- No custom logging in this codebase captures request bodies or PII (confirmed by direct code
  audit — the only writes to `score_audit_log` are score corrections, never personal data).
- Worth a line item for the university's own compliance review before go-live: this document
  doesn't replace that process, it just keeps the technical defaults out of the way of it.
