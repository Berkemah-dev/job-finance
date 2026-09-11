# Panduan Deployment — JobFinance

## Prasyarat

| Komponen | Versi Minimum |
|----------|---------------|
| PHP | 8.2 |
| MySQL / MariaDB | 8.0 / 10.6 |
| Node.js | 20 LTS |
| Composer | 2.x |
| Nginx / Apache | Nginx 1.20+ |

---

## 1. Setup Environment

```bash
# Clone repository
git clone https://github.com/Berkemah-dev/job-finance.git
cd job-finance

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Copy environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai lingkungan:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jobfinance.example.com

DB_HOST=127.0.0.1
DB_DATABASE=jobfinance_prod
DB_USERNAME=jobfinance
DB_PASSWORD=your-secure-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS=noreply@example.com

BACKUP_DISK=local
BACKUP_RETAIN_DAYS=30
```

---

## 2. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force   # Hanya saat fresh install
```

---

## 3. Storage

```bash
php artisan storage:link
mkdir -p storage/app/private/job-documents
mkdir -p storage/app/private/backups
chmod -R 755 storage bootstrap/cache
```

---

## 4. Konfigurasi Nginx

```nginx
server {
    listen 80;
    server_name jobfinance.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name jobfinance.example.com;

    ssl_certificate     /etc/letsencrypt/live/jobfinance.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/jobfinance.example.com/privkey.pem;

    root /var/www/job-finance/public;
    index index.php;

    charset utf-8;
    client_max_body_size 25M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. PHP-FPM (php.ini rekomendasi)

```ini
upload_max_filesize = 25M
post_max_size = 26M
memory_limit = 256M
max_execution_time = 60
```

---

## 6. Scheduler (Cron)

Tambah entry crontab untuk Laravel scheduler:

```bash
crontab -e
```

```cron
* * * * * cd /var/www/job-finance && php artisan schedule:run >> /dev/null 2>&1
```

Ini akan menjalankan backup database otomatis setiap hari pukul 02:00 (lihat `routes/console.php`).

---

## 7. Queue Worker (Opsional)

Jika menggunakan queue untuk email SOA:

```bash
# Supervisor config: /etc/supervisor/conf.d/jobfinance-worker.conf
[program:jobfinance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/job-finance/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/jobfinance-worker.log
```

---

## 8. Deploy Update (CI/CD Manual)

```bash
cd /var/www/job-finance
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM
sudo systemctl reload php8.2-fpm
```

---

## 9. Backup Database

Manual backup:

```bash
php artisan app:backup-database
```

File backup disimpan di `storage/app/private/backups/`.

Untuk backup ke S3, set di `.env`:
```env
BACKUP_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=your-bucket
```

---

## 10. Monitoring & Log

- Laravel log: `storage/logs/laravel.log`
- Nginx access: `/var/log/nginx/access.log`
- Nginx error: `/var/log/nginx/error.log`

Untuk monitoring sederhana, gunakan `php artisan telescope:install` (dev) atau integrasi dengan Sentry/Datadog di production.

---

## Catatan Keamanan

> [!CAUTION]
> - Pastikan `.env` **TIDAK** dikomit ke git (sudah ada di `.gitignore`)
> - Gunakan password DB yang kuat dan user DB dengan privilege minimal
> - Enable SSL/HTTPS wajib di production
> - File dokumen job tersimpan di `storage/app/private/` — tidak dapat diakses langsung via URL
