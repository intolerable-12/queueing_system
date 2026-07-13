# Laravel Queueing System Deployment Guide (Ubuntu LAMP)

This guide deploys the Queueing System on Ubuntu with Apache, MySQL, PHP, and Laravel Reverb.

It also standardizes printing to the Python print server on the Windows USB-printer host:
- PrintServer/print-server.py

## 1. Prerequisites

- Ubuntu Server with sudo access
- Apache2
- PHP 8.2+ with required Laravel extensions
- Composer
- Node.js + npm (for frontend asset build)
- MySQL or MariaDB
- Git

## 2. Clone Project

```bash
cd /var/www/html
sudo git clone https://github.com/morales-lc/queueing_system.git
cd queueing_system
```

## 3. File Ownership and Permissions

```bash
sudo chown -R www-data:www-data /var/www/html/queueing_system
sudo find /var/www/html/queueing_system -type d -exec chmod 755 {} \;
sudo find /var/www/html/queueing_system -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/html/queueing_system/storage
sudo chmod -R 775 /var/www/html/queueing_system/bootstrap/cache
```

## 4. Install Dependencies

```bash
cd /var/www/html/queueing_system
sudo -u www-data composer install --optimize-autoloader --no-dev
npm install
npm run build
```

## 5. Configure Environment

```bash
sudo cp .env.example .env
sudo nano .env
```

Recommended production baseline:

```ini
APP_NAME="Lourdes College Queueing System"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://louna.lccdo.edu.ph

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=queueing_system
DB_USERNAME=queueing_user
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=524686
REVERB_APP_KEY=your_key
REVERB_APP_SECRET=your_secret
REVERB_HOST=louna.lccdo.edu.ph
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="louna.lccdo.edu.ph"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

PRINTER_ENABLED=true
SKIP_PRINTER_VALIDATION=false
PRINTER_TYPE=http
PRINTER_TARGET=http://192.168.138.20:3000/print
PRINTER_PORT=9100
```

Generate key:

```bash
php artisan key:generate
```

## 6. Database Setup

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE queueing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'queueing_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON queueing_system.* TO 'queueing_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Run migrations and seeders:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

## 7. Apache Virtual Host

Create config file:

```bash
sudo nano /etc/apache2/sites-available/queueing_system.conf
```

Use:

```apache
<VirtualHost *:80>
    ServerName louna.lccdo.edu.ph
    ServerAdmin admin@lccdo.edu.ph

    DocumentRoot /var/www/html/queueing_system/public

    <Directory /var/www/html/queueing_system/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <Directory /var/www/html/queueing_system>
        Options -Indexes
        Require all denied
    </Directory>

    ProxyRequests Off
    ProxyPreserveHost On

    <Location /app>
        ProxyPass ws://127.0.0.1:8080/app
        ProxyPassReverse ws://127.0.0.1:8080/app
    </Location>

    ProxyPass /apps/ http://127.0.0.1:8080/apps/
    ProxyPassReverse /apps/ http://127.0.0.1:8080/apps/

    ErrorLog ${APACHE_LOG_DIR}/queueing_system-error.log
    CustomLog ${APACHE_LOG_DIR}/queueing_system-access.log combined
</VirtualHost>
```

Enable modules and site:

```bash
sudo a2enmod rewrite headers proxy proxy_http proxy_wstunnel
sudo a2dissite 000-default.conf
sudo a2ensite queueing_system.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

## 8. Reverb Service (systemd)

```bash
sudo nano /etc/systemd/system/reverb.service
```

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/queueing_system
ExecStart=/usr/bin/php /var/www/html/queueing_system/artisan reverb:start --host=0.0.0.0 --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

## 9. Queue Worker Service (Recommended)

```bash
sudo nano /etc/systemd/system/laravel-worker.service
```

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/queueing_system
ExecStart=/usr/bin/php /var/www/html/queueing_system/artisan queue:work --tries=3
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
```

## 10. Firewall Rules

```bash
sudo ufw allow 'Apache Full'
sudo ufw allow 8080/tcp
sudo ufw enable
sudo ufw status
```

## 11. Optimize Laravel for Production

```bash
cd /var/www/html/queueing_system
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

## 12. Windows USB Printer Host Setup (Python Only)

On Windows PC connected to printer (example IP: 192.168.138.20):

1. Install Python 3.11+
2. Create C:\PrintServer
3. Copy PrintServer/print-server.py to C:\PrintServer\print-server.py
4. Install packages:

```powershell
cd C:\PrintServer
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install flask pywin32 pillow
```

5. Confirm printer name:

```powershell
Get-Printer | Select-Object Name
```

6. Run process:

```powershell
python C:\PrintServer\print-server.py
```

7. Open TCP 3000 for Linux server IP in Windows firewall

Full Windows instructions:
- WINDOWS_PRINT_SERVER.md
- PRINTING_SETUP.md

## 13. Verify End-to-End

From Linux server:

```bash
curl http://192.168.138.20:3000/health
```

Expected: JSON response with can_print field.

Then test print endpoint:

```bash
curl -X POST http://192.168.138.20:3000/print \
  -H "Content-Type: application/json" \
  -d '{"ticket":{"code":"CS-001","service_type":"cashier","priority":"student","created_at":"2026-01-01T08:00:00+08:00"}}'
```

## 14. Application URLs

- Kiosk: http://louna.lccdo.edu.ph/
- Monitor: http://louna.lccdo.edu.ph/monitor
- Login: http://louna.lccdo.edu.ph/login

## 15. Troubleshooting

### 15.1 Laravel logs

```bash
tail -f /var/www/html/queueing_system/storage/logs/laravel.log
```

### 15.2 Apache logs

```bash
tail -f /var/log/apache2/queueing_system-error.log
```

### 15.3 Reverb logs

```bash
sudo systemctl status reverb
sudo journalctl -u reverb -f
```

### 15.4 Print server unreachable

1. Check Windows print process is running after boot
2. Confirm Windows firewall rule for port 3000
3. Confirm Linux can reach Windows IP
4. Verify PRINTER_TARGET in .env

### 15.5 Print health fails

1. Confirm USB printer power/cable/paper
2. Confirm PRINTER_NAME in print-server.py
3. Check /health output issues array

## 16. Update Procedure

```bash
cd /var/www/html/queueing_system
sudo git pull origin main
sudo -u www-data composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart apache2
sudo systemctl restart reverb
sudo systemctl restart laravel-worker
```

## 17. Security Recommendations

1. Use HTTPS with Let's Encrypt
2. Keep APP_DEBUG=false in production
3. Restrict Windows print server firewall to Linux server IP only
4. Do not expose Windows port 3000 publicly
5. Rotate credentials and keep system packages updated

---

Replace IPs, domains, and credentials with your environment values.
