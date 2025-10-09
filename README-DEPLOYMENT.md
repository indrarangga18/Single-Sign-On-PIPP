# SSO PIPP Production Deployment Guide

This guide provides comprehensive instructions for deploying the SSO PIPP (Single Sign-On Pelabuhan Indonesia Persero Pelabuhan) application to production.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Security Configuration](#security-configuration)
4. [Deployment Process](#deployment-process)
5. [Monitoring and Logging](#monitoring-and-logging)
6. [Maintenance](#maintenance)
7. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **Operating System**: Ubuntu 20.04 LTS or CentOS 8+
- **RAM**: Minimum 8GB, Recommended 16GB+
- **CPU**: Minimum 4 cores, Recommended 8+ cores
- **Storage**: Minimum 100GB SSD
- **Network**: Static IP address with proper DNS configuration

### Required Software

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- SSL certificates for HTTPS

### Installation Commands

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

## Environment Setup

### 1. Clone Repository

```bash
git clone https://github.com/your-org/Single-Sign-On-PIPP.git
cd Single-Sign-On-PIPP
```

### 2. Configure Environment Variables

Copy the production environment template and customize it:

```bash
cp .env.production .env
```

**Critical variables to change:**

```bash
# Generate a secure 32-character key
APP_KEY=base64:$(openssl rand -base64 32)

# Database credentials
DB_PASSWORD=your_secure_database_password

# Redis password
REDIS_PASSWORD=your_secure_redis_password

# JWT secret (64 characters)
JWT_SECRET=$(openssl rand -hex 32)

# SSO client credentials
SSO_CLIENT_SECRET=your_sso_client_secret

# Service API keys
SAHBANDAR_SERVICE_KEY=your_sahbandar_key
SPB_SERVICE_KEY=your_spb_key
SHTI_SERVICE_KEY=your_shti_key
EPIT_SERVICE_KEY=your_epit_key

# Mail configuration
MAIL_PASSWORD=your_mail_password

# Slack webhook for alerts
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

### 3. SSL Certificate Setup

Place your SSL certificates in the appropriate directory:

```bash
mkdir -p docker/nginx/ssl
# Copy your certificates
cp your-domain.crt docker/nginx/ssl/
cp your-domain.key docker/nginx/ssl/
```

## Security Configuration

### 1. Firewall Setup

```bash
# Allow SSH, HTTP, and HTTPS
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. Docker Security

Create a dedicated Docker network:

```bash
docker network create sso-pipp-network
```

### 3. File Permissions

```bash
# Set proper permissions
chmod 600 .env
chmod 600 docker/nginx/ssl/*
chmod +x deploy.sh
```

## Deployment Process

### 1. Initial Deployment

```bash
# Make deployment script executable
chmod +x deploy.sh

# Run initial deployment
./deploy.sh --deploy
```

### 2. Manual Deployment Steps

If you prefer manual deployment:

```bash
# 1. Build and start services
docker-compose up -d --build

# 2. Wait for services to be ready
sleep 30

# 3. Run database migrations
docker-compose exec app php artisan migrate --force

# 4. Optimize application
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# 5. Create admin user (if needed)
docker-compose exec app php artisan db:seed --class=AdminUserSeeder
```

### 3. Verify Deployment

```bash
# Check container status
docker-compose ps

# Check application health
curl -f http://localhost/api/health

# Check logs
docker-compose logs app
```

## Monitoring and Logging

### 1. Access Monitoring Dashboards

- **Grafana**: http://your-domain:3000 (admin/admin)
- **Prometheus**: http://your-domain:9090
- **Kibana**: http://your-domain:5601

### 2. Log Locations

```bash
# Application logs
docker-compose logs app

# Nginx logs
docker-compose logs nginx

# Database logs
docker-compose logs postgres

# All services
docker-compose logs
```

### 3. Health Monitoring

The application provides several health check endpoints:

- `/api/health` - Basic health check
- `/api/health/detailed` - Detailed system health
- `/api/monitoring/metrics` - Application metrics

### 4. Alerts Configuration

Configure Slack alerts by setting up the webhook URL in your environment:

```bash
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
LOG_SLACK_CHANNEL=#sso-alerts
```

## Maintenance

### 1. Regular Backups

```bash
# Create database backup
./deploy.sh --backup

# Automated daily backups (add to crontab)
0 2 * * * /path/to/sso-pipp/deploy.sh --backup
```

### 2. Updates and Patches

```bash
# Pull latest changes
git pull origin main

# Deploy updates
./deploy.sh --deploy
```

### 3. Log Rotation

Configure log rotation to prevent disk space issues:

```bash
# Add to /etc/logrotate.d/docker-containers
/var/lib/docker/containers/*/*.log {
    rotate 7
    daily
    compress
    size=1M
    missingok
    delaycompress
    copytruncate
}
```

### 4. Cleanup Old Data

```bash
# Clean up old backups
./deploy.sh --cleanup

# Clean up Docker images
docker system prune -f

# Clean up old logs (older than 30 days)
docker-compose exec app php artisan logs:cleanup --days=30
```

## Troubleshooting

### 1. Common Issues

**Container won't start:**
```bash
# Check logs
docker-compose logs [service-name]

# Check resource usage
docker stats

# Restart specific service
docker-compose restart [service-name]
```

**Database connection issues:**
```bash
# Check database status
docker-compose exec postgres pg_isready

# Check database logs
docker-compose logs postgres

# Reset database connection
docker-compose restart app
```

**High memory usage:**
```bash
# Check memory usage
free -h
docker stats

# Restart services if needed
docker-compose restart
```

### 2. Performance Issues

**Slow response times:**
```bash
# Check application metrics
curl http://localhost/api/monitoring/metrics/performance

# Check database performance
docker-compose exec postgres psql -U sso_user -d sso_pipp -c "SELECT * FROM pg_stat_activity;"

# Check Redis performance
docker-compose exec redis redis-cli info stats
```

### 3. Security Issues

**Suspicious activity:**
```bash
# Check security logs
curl http://localhost/api/monitoring/logs/security

# Check failed login attempts
docker-compose exec app php artisan security:check-failed-logins

# Review audit logs
docker-compose exec app php artisan audit:review --recent
```

### 4. Emergency Procedures

**Rollback deployment:**
```bash
./deploy.sh --rollback
```

**Emergency shutdown:**
```bash
docker-compose down
```

**Restore from backup:**
```bash
# List available backups
ls -la backups/

# Restore database (replace with actual backup file)
docker-compose exec -T postgres psql -U sso_user -d sso_pipp < backups/db_backup_YYYYMMDD_HHMMSS.sql
```

## Performance Tuning

### 1. Database Optimization

```sql
-- Add indexes for frequently queried columns
CREATE INDEX CONCURRENTLY idx_users_email ON users(email);
CREATE INDEX CONCURRENTLY idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX CONCURRENTLY idx_sessions_user_id ON sessions(user_id);
```

### 2. Redis Configuration

Optimize Redis for your workload:

```bash
# In docker-compose.yml, add Redis configuration
redis:
  command: redis-server --maxmemory 2gb --maxmemory-policy allkeys-lru
```

### 3. PHP-FPM Tuning

Adjust PHP-FPM settings in `docker/php/php-fpm.conf`:

```ini
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

## Support and Documentation

- **Application Logs**: Check `/var/log/laravel/` inside the container
- **System Health**: Use the built-in health check commands
- **API Documentation**: Available at `/api/documentation` (if enabled)
- **Monitoring**: Use Grafana dashboards for real-time monitoring

For additional support, contact the development team or check the project's issue tracker.

---

**Last Updated**: $(date +"%Y-%m-%d")
**Version**: 1.0.0