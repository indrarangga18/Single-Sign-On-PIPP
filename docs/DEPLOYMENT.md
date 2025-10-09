# Deployment Guide - SSO PIPP

Panduan lengkap untuk deployment sistem Single Sign-On Platform Informasi Pelabuhan Perikanan (PIPP) ke environment production.

## 📋 Persyaratan Sistem

### Server Requirements

#### Minimum Requirements
- **OS**: Ubuntu 20.04 LTS atau CentOS 8+
- **CPU**: 2 vCPU
- **RAM**: 4 GB
- **Storage**: 50 GB SSD
- **Network**: 100 Mbps

#### Recommended Requirements
- **OS**: Ubuntu 22.04 LTS
- **CPU**: 4 vCPU
- **RAM**: 8 GB
- **Storage**: 100 GB SSD
- **Network**: 1 Gbps

### Software Requirements

#### Web Server
- **Nginx**: 1.18+ (recommended)
- **Apache**: 2.4+ (alternative)

#### PHP
- **Version**: 8.1+
- **Extensions**:
  - mbstring
  - xml
  - bcmath
  - pdo_mysql
  - redis
  - curl
  - gd
  - zip
  - intl
  - json
  - openssl

#### Database
- **MySQL**: 8.0+ (recommended)
- **PostgreSQL**: 13+ (alternative)

#### Cache & Session
- **Redis**: 6.0+

#### Process Manager
- **Supervisor**: For queue workers

---

## 🚀 Production Deployment

### 1. Server Setup

#### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

#### Install Required Packages
```bash
# Install basic packages
sudo apt install -y curl wget git unzip software-properties-common

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-xml php8.1-xmlrpc php8.1-curl php8.1-gd php8.1-imagick \
    php8.1-cli php8.1-dev php8.1-imap php8.1-mbstring php8.1-opcache \
    php8.1-soap php8.1-zip php8.1-intl php8.1-bcmath php8.1-redis

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Supervisor
sudo apt install -y supervisor

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Database Setup

#### MySQL Configuration
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE sso_pipp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sso_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON sso_pipp.* TO 'sso_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### MySQL Optimization
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/modify these settings:
```ini
[mysqld]
# Performance tuning
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connection settings
max_connections = 200
wait_timeout = 300
interactive_timeout = 300

# Query cache
query_cache_type = 1
query_cache_size = 128M
```

### 3. Redis Configuration

```bash
sudo nano /etc/redis/redis.conf
```

Key settings:
```ini
# Memory management
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password_here

# Network
bind 127.0.0.1
port 6379
```

Restart Redis:
```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 4. Application Deployment

#### Create Application Directory
```bash
sudo mkdir -p /var/www/sso-pipp
sudo chown -R $USER:www-data /var/www/sso-pipp
```

#### Clone Repository
```bash
cd /var/www/sso-pipp
git clone https://github.com/your-org/single-sign-on-pipp.git .
```

#### Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/sso-pipp
sudo chmod -R 755 /var/www/sso-pipp
sudo chmod -R 775 /var/www/sso-pipp/storage
sudo chmod -R 775 /var/www/sso-pipp/bootstrap/cache
```

#### Environment Configuration
```bash
cp .env.production .env
```

Edit `.env` file:
```env
APP_NAME="SSO PIPP"
APP_ENV=production
APP_KEY=base64:your_app_key_here
APP_DEBUG=false
APP_URL=https://sso.pipp.kkp.go.id

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_pipp
DB_USERNAME=sso_user
DB_PASSWORD=secure_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@pipp.kkp.go.id"
MAIL_FROM_NAME="${APP_NAME}"

# JWT Configuration
JWT_SECRET=your_jwt_secret_key_here
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# Microservices Configuration
SAHBANDAR_SERVICE_URL=https://sahbandar.pipp.kkp.go.id
SAHBANDAR_API_KEY=your_sahbandar_api_key
SAHBANDAR_CALLBACK_URL=https://sahbandar.pipp.kkp.go.id/auth/sso/callback

SPB_SERVICE_URL=https://spb.pipp.kkp.go.id
SPB_API_KEY=your_spb_api_key
SPB_CALLBACK_URL=https://spb.pipp.kkp.go.id/auth/sso/callback

SHTI_SERVICE_URL=https://shti.pipp.kkp.go.id
SHTI_API_KEY=your_shti_api_key
SHTI_CALLBACK_URL=https://shti.pipp.kkp.go.id/auth/sso/callback

EPIT_SERVICE_URL=https://epit.pipp.kkp.go.id
EPIT_API_KEY=your_epit_api_key
EPIT_CALLBACK_URL=https://epit.pipp.kkp.go.id/auth/sso/callback

# SSO Configuration
SSO_DOMAIN=pipp.kkp.go.id
SSO_TOKEN_TTL=3600
SSO_SESSION_TTL=86400
SSO_MAX_SESSIONS=5
SSO_CLEANUP_EXPIRED=true

# Security Configuration
RATE_LIMIT_LOGIN=5
RATE_LIMIT_API=60
RATE_LIMIT_SSO=100
PASSWORD_MIN_LENGTH=8
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE=strict
```

#### Generate Application Key and JWT Secret
```bash
php artisan key:generate
php artisan jwt:secret
```

#### Run Migrations and Seeders
```bash
php artisan migrate --force
php artisan db:seed --force
```

#### Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 5. Web Server Configuration

#### Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/sso-pipp
```

```nginx
server {
    listen 80;
    server_name sso.pipp.kkp.go.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sso.pipp.kkp.go.id;
    root /var/www/sso-pipp/public;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/sso.pipp.kkp.go.id.crt;
    ssl_certificate_key /etc/ssl/private/sso.pipp.kkp.go.id.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';" always;

    # Basic Configuration
    index index.php;
    charset utf-8;

    # Logging
    access_log /var/log/nginx/sso-pipp.access.log;
    error_log /var/log/nginx/sso-pipp.error.log;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API rate limiting
    location /api/auth/login {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/ {
        limit_req zone=api burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Security
        fastcgi_param HTTP_PROXY "";
        fastcgi_param SERVER_NAME $host;
        fastcgi_param HTTPS on;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }
    
    location = /robots.txt  { 
        access_log off; 
        log_not_found off; 
    }

    # Health check
    location = /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/sso-pipp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### PHP-FPM Configuration
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Key settings:
```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php8.1-fpm.log
php_admin_flag[log_errors] = on
```

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Key settings:
```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
max_input_time = 300
date.timezone = Asia/Jakarta

# Security
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# Session
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1

# OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
sudo systemctl enable php8.1-fpm
```

### 6. SSL Certificate Setup

#### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d sso.pipp.kkp.go.id

# Auto-renewal
sudo crontab -e
```

Add to crontab:
```bash
0 12 * * * /usr/bin/certbot renew --quiet
```

#### Using Custom Certificate
```bash
# Copy certificate files
sudo cp your-certificate.crt /etc/ssl/certs/sso.pipp.kkp.go.id.crt
sudo cp your-private-key.key /etc/ssl/private/sso.pipp.kkp.go.id.key

# Set permissions
sudo chmod 644 /etc/ssl/certs/sso.pipp.kkp.go.id.crt
sudo chmod 600 /etc/ssl/private/sso.pipp.kkp.go.id.key
```

### 7. Queue Worker Setup

#### Supervisor Configuration
```bash
sudo nano /etc/supervisor/conf.d/sso-pipp-worker.conf
```

```ini
[program:sso-pipp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sso-pipp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sso-pipp/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sso-pipp-worker:*
```

### 8. Cron Jobs Setup

```bash
sudo crontab -e
```

Add Laravel scheduler:
```bash
* * * * * cd /var/www/sso-pipp && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Monitoring Setup

#### Log Rotation
```bash
sudo nano /etc/logrotate.d/sso-pipp
```

```
/var/www/sso-pipp/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /usr/bin/supervisorctl restart sso-pipp-worker:*
    endscript
}
```

#### System Monitoring Script
```bash
sudo nano /usr/local/bin/sso-health-check.sh
```

```bash
#!/bin/bash

# Health check script for SSO PIPP
LOG_FILE="/var/log/sso-health-check.log"
APP_URL="https://sso.pipp.kkp.go.id"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# Check web server
if curl -f -s "$APP_URL/health" > /dev/null; then
    log_message "Web server: OK"
else
    log_message "Web server: FAILED"
    systemctl restart nginx
fi

# Check PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    log_message "PHP-FPM: OK"
else
    log_message "PHP-FPM: FAILED"
    systemctl restart php8.1-fpm
fi

# Check MySQL
if systemctl is-active --quiet mysql; then
    log_message "MySQL: OK"
else
    log_message "MySQL: FAILED"
    systemctl restart mysql
fi

# Check Redis
if systemctl is-active --quiet redis-server; then
    log_message "Redis: OK"
else
    log_message "Redis: FAILED"
    systemctl restart redis-server
fi

# Check queue workers
if supervisorctl status sso-pipp-worker:* | grep -q RUNNING; then
    log_message "Queue workers: OK"
else
    log_message "Queue workers: FAILED"
    supervisorctl restart sso-pipp-worker:*
fi
```

Make executable and add to cron:
```bash
sudo chmod +x /usr/local/bin/sso-health-check.sh
sudo crontab -e
```

Add to crontab:
```bash
*/5 * * * * /usr/local/bin/sso-health-check.sh
```

---

## 🔧 Maintenance

### Backup Strategy

#### Database Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-database.sh

BACKUP_DIR="/var/backups/sso-pipp"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="sso_pipp"
DB_USER="sso_user"
DB_PASS="secure_password_here"

mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/database_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "database_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: database_$DATE.sql.gz"
```

#### Application Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-application.sh

BACKUP_DIR="/var/backups/sso-pipp"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/sso-pipp"

mkdir -p $BACKUP_DIR

# Create application backup (excluding vendor and node_modules)
tar -czf $BACKUP_DIR/application_$DATE.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    -C /var/www sso-pipp

# Keep only last 7 days
find $BACKUP_DIR -name "application_*.tar.gz" -mtime +7 -delete

echo "Application backup completed: application_$DATE.tar.gz"
```

Schedule backups:
```bash
sudo crontab -e
```

```bash
# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-database.sh

# Weekly application backup on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/backup-application.sh
```

### Update Process

#### Application Updates
```bash
#!/bin/bash
# /usr/local/bin/update-application.sh

APP_DIR="/var/www/sso-pipp"
BACKUP_DIR="/var/backups/sso-pipp"
DATE=$(date +%Y%m%d_%H%M%S)

cd $APP_DIR

# Create backup before update
echo "Creating backup..."
/usr/local/bin/backup-database.sh
/usr/local/bin/backup-application.sh

# Put application in maintenance mode
php artisan down --message="System update in progress"

# Pull latest changes
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart services
sudo supervisorctl restart sso-pipp-worker:*
sudo systemctl reload php8.1-fpm

# Bring application back online
php artisan up

echo "Application update completed successfully"
```

### Performance Monitoring

#### System Resource Monitoring
```bash
#!/bin/bash
# /usr/local/bin/monitor-resources.sh

LOG_FILE="/var/log/sso-resource-monitor.log"

# Get system metrics
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | awk -F'%' '{print $1}')
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}')
DISK_USAGE=$(df -h / | awk 'NR==2{printf "%s", $5}' | sed 's/%//')

# Log metrics
echo "$(date '+%Y-%m-%d %H:%M:%S') - CPU: ${CPU_USAGE}%, Memory: ${MEMORY_USAGE}%, Disk: ${DISK_USAGE}%" >> $LOG_FILE

# Alert if usage is high
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - HIGH CPU USAGE: ${CPU_USAGE}%" >> $LOG_FILE
fi

if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - HIGH MEMORY USAGE: ${MEMORY_USAGE}%" >> $LOG_FILE
fi

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - HIGH DISK USAGE: ${DISK_USAGE}%" >> $LOG_FILE
fi
```

Schedule monitoring:
```bash
sudo crontab -e
```

```bash
# Monitor resources every 5 minutes
*/5 * * * * /usr/local/bin/monitor-resources.sh
```

---

## 🔒 Security Hardening

### Firewall Configuration

#### UFW Setup
```bash
# Install and enable UFW
sudo apt install -y ufw

# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH (change port if needed)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow MySQL (only from localhost)
sudo ufw allow from 127.0.0.1 to any port 3306

# Allow Redis (only from localhost)
sudo ufw allow from 127.0.0.1 to any port 6379

# Enable firewall
sudo ufw enable
```

### Fail2Ban Configuration

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Configure Fail2Ban
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
ignoreip = 127.0.0.1/8 ::1

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 10

[nginx-botsearch]
enabled = true
filter = nginx-botsearch
port = http,https
logpath = /var/log/nginx/access.log
maxretry = 2
```

Start Fail2Ban:
```bash
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### System Security

#### Disable Root Login
```bash
sudo nano /etc/ssh/sshd_config
```

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

#### Automatic Security Updates
```bash
sudo apt install -y unattended-upgrades

sudo nano /etc/apt/apt.conf.d/50unattended-upgrades
```

```
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}-security";
    "${distro_id}ESMApps:${distro_codename}-apps-security";
    "${distro_id}ESM:${distro_codename}-infra-security";
};

Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
```

---

## 📊 Load Balancing & High Availability

### Load Balancer Setup (HAProxy)

```bash
# Install HAProxy
sudo apt install -y haproxy

# Configure HAProxy
sudo nano /etc/haproxy/haproxy.cfg
```

```
global
    daemon
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy

defaults
    mode http
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms
    option httplog
    option dontlognull

frontend sso_frontend
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/sso.pipp.kkp.go.id.pem
    redirect scheme https if !{ ssl_fc }
    
    # Security headers
    http-response set-header X-Frame-Options SAMEORIGIN
    http-response set-header X-Content-Type-Options nosniff
    http-response set-header X-XSS-Protection "1; mode=block"
    
    default_backend sso_backend

backend sso_backend
    balance roundrobin
    option httpchk GET /health
    
    server sso1 10.0.1.10:80 check
    server sso2 10.0.1.11:80 check
    server sso3 10.0.1.12:80 check

listen stats
    bind *:8404
    stats enable
    stats uri /stats
    stats refresh 30s
    stats admin if TRUE
```

### Database Replication

#### Master-Slave Setup
```sql
-- On Master server
CREATE USER 'replication'@'%' IDENTIFIED BY 'replication_password';
GRANT REPLICATION SLAVE ON *.* TO 'replication'@'%';
FLUSH PRIVILEGES;

-- Get master status
SHOW MASTER STATUS;
```

```bash
# On Slave server
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
server-id = 2
relay-log = /var/log/mysql/mysql-relay-bin.log
log-bin = /var/log/mysql/mysql-bin.log
binlog_do_db = sso_pipp
```

```sql
-- On Slave server
CHANGE MASTER TO
    MASTER_HOST='master_ip',
    MASTER_USER='replication',
    MASTER_PASSWORD='replication_password',
    MASTER_LOG_FILE='mysql-bin.000001',
    MASTER_LOG_POS=154;

START SLAVE;
SHOW SLAVE STATUS\G
```

---

## 🚨 Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error
```bash
# Check error logs
sudo tail -f /var/log/nginx/sso-pipp.error.log
sudo tail -f /var/log/php8.1-fpm.log
sudo tail -f /var/www/sso-pipp/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/sso-pipp/storage
sudo chmod -R 775 /var/www/sso-pipp/storage
```

#### 2. Database Connection Issues
```bash
# Test database connection
mysql -u sso_user -p -h localhost sso_pipp

# Check MySQL status
sudo systemctl status mysql
sudo journalctl -u mysql -f
```

#### 3. Queue Jobs Not Processing
```bash
# Check supervisor status
sudo supervisorctl status sso-pipp-worker:*

# Restart workers
sudo supervisorctl restart sso-pipp-worker:*

# Check queue status
php artisan queue:work --once
```

#### 4. High Memory Usage
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head

# Optimize PHP-FPM
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
# Adjust pm.max_children, pm.start_servers, etc.
```

#### 5. SSL Certificate Issues
```bash
# Check certificate validity
openssl x509 -in /etc/ssl/certs/sso.pipp.kkp.go.id.crt -text -noout

# Test SSL configuration
openssl s_client -connect sso.pipp.kkp.go.id:443

# Renew Let's Encrypt certificate
sudo certbot renew --dry-run
```

### Performance Optimization

#### 1. Database Optimization
```sql
-- Analyze slow queries
SHOW PROCESSLIST;
SHOW FULL PROCESSLIST;

-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Optimize tables
OPTIMIZE TABLE users, sso_sessions, audit_logs;
```

#### 2. Redis Optimization
```bash
# Monitor Redis
redis-cli info memory
redis-cli info stats

# Clear cache if needed
redis-cli flushdb
```

#### 3. Application Optimization
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 📈 Scaling Considerations

### Horizontal Scaling

1. **Load Balancer**: Use HAProxy or Nginx as load balancer
2. **Multiple App Servers**: Deploy application on multiple servers
3. **Shared Storage**: Use NFS or cloud storage for shared files
4. **Database Clustering**: Implement MySQL cluster or use cloud database
5. **Redis Cluster**: Setup Redis cluster for session storage

### Vertical Scaling

1. **Increase Server Resources**: More CPU, RAM, and storage
2. **Optimize Database**: Better hardware, SSD storage
3. **Tune PHP-FPM**: Increase worker processes
4. **Optimize Nginx**: Increase worker connections

### Cloud Deployment

#### AWS Deployment
- **EC2**: Application servers
- **RDS**: Managed MySQL database
- **ElastiCache**: Managed Redis
- **ALB**: Application Load Balancer
- **S3**: File storage
- **CloudFront**: CDN

#### Docker Deployment
```dockerfile
# Dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    container_name: sso-pipp-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - sso-network

  nginx:
    image: nginx:alpine
    container_name: sso-pipp-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - sso-network

  mysql:
    image: mysql:8.0
    container_name: sso-pipp-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: sso_pipp
      MYSQL_USER: sso_user
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - sso-network

  redis:
    image: redis:alpine
    container_name: sso-pipp-redis
    restart: unless-stopped
    networks:
      - sso-network

networks:
  sso-network:
    driver: bridge

volumes:
  mysql_data:
```

---

**Panduan deployment ini akan terus diperbarui seiring dengan perkembangan sistem dan kebutuhan infrastruktur.**