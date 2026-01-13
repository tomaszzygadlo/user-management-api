# Quick Update Guide - Production/Testing Server

Fast guide for deploying new version to existing server.

---

## Prerequisites

- ✅ Server with application already deployed
- ✅ SSH access to server
- ✅ Git repository configured
- ✅ Application running in Docker or natively

---

## 🚀 Quick Update (5 minutes)

### ⚡ Automated Update (Recommended)

The script automatically detects Docker/native PHP and handles everything:

```bash
ssh user@your-server-ip
cd /var/www/user-management-api
bash scripts/update-production.sh
```

**First time?** Make the script executable for future use:
```bash
chmod +x scripts/update-production.sh
./scripts/update-production.sh
```

The script automatically:
- ✅ Detects Docker or native PHP environment
- ✅ Backs up database
- ✅ Pulls latest code
- ✅ Updates dependencies (composer)
- ✅ Runs migrations
- ✅ Clears and rebuilds cache
- ✅ Restarts services


---

### 📝 Manual Update Steps

If you prefer manual update or the script is unavailable:

**Requirements check:** Make sure you have installed:
- Docker + Docker Compose (for Docker setup), OR
- PHP 8.3+, Composer, MySQL/MariaDB (for native setup)

### Step 1: Connect to server

```bash
ssh user@your-server-ip
cd /var/www/user-management-api  # or your application path
```

### Step 2: Backup database (recommended)

```bash
# For Docker setup
docker compose exec mysql mysqldump -u laravel -p user_management > backup_$(date +%Y%m%d_%H%M%S).sql

# For native MySQL
mysqldump -u your_user -p user_management > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 3: Enable maintenance mode (optional)

```bash
# For Docker
docker compose -f docker-compose-prod.yml exec app php artisan down

# For native
php artisan down
```

This shows a maintenance page during update.

### Step 4: Pull latest code

```bash
git fetch --all
git pull
```

### Step 5: Update dependencies

#### If using Docker:

**IMPORTANT:** If Dockerfile was changed, rebuild the image first:

```bash
# Check if Dockerfile was changed
git diff HEAD~1 Dockerfile

# If changed, rebuild the image (takes a few minutes)
docker compose -f docker-compose-prod.yml down
docker compose -f docker-compose-prod.yml build --no-cache
docker compose -f docker-compose-prod.yml up -d
```

**Regular update (no Dockerfile changes):**

```bash
# Update PHP dependencies (Composer)
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader

# Update Node dependencies (if using npm/frontend)
# docker compose -f docker-compose-prod.yml exec app npm install --production
# docker compose -f docker-compose-prod.yml exec app npm run build
```

#### If running natively:

**First time setup - Install tools if missing:**

```bash
# Check if Composer is installed
composer --version

# If not installed, install Composer:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Check if PHP 8.3+ is installed
php -v

# If not installed (Ubuntu/Debian):
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip
```

**Update dependencies:**

```bash
# Update PHP dependencies (Composer)
composer install --no-dev --optimize-autoloader

# Update Node dependencies (if using npm/frontend)
# npm install --production
# npm run build
```

### Step 6: Run database migrations

```bash
# For Docker
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force

# For native
php artisan migrate --force
```

**Note**: `--force` flag is required in production.

### Step 7: Clear and rebuild cache

```bash
# For Docker
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml exec app php artisan route:cache
docker compose -f docker-compose-prod.yml exec app php artisan view:cache

# For native
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 8: Restart services

```bash
# For Docker
docker compose -f docker-compose-prod.yml restart app

# For native
sudo systemctl restart php8.3-fpm

# Restart queue worker (if using)
sudo systemctl restart queue-worker
```

### Step 9: Disable maintenance mode

```bash
# For Docker
docker compose -f docker-compose-prod.yml exec app php artisan up

# For native
php artisan up
```

### Step 10: Verify deployment

```bash
# Check application status
curl http://localhost:8000/api/health

# Check logs for errors
tail -f storage/logs/laravel.log

# For Docker:
docker compose -f docker-compose-prod.yml logs -f app
```

---

## 📋 Detailed Update Process

For advanced users who want manual control:

#### 1. Prepare for update

```bash
# Check current status
docker compose -f docker-compose-prod.yml ps
# or
systemctl status php8.3-fpm nginx

# Check disk space
df -h

# Check current version (if using git tags)
git describe --tags
```

#### 2. Backup (important!)

```bash
# Database backup
docker compose exec mysql mysqldump -u laravel -p user_management > backup_$(date +%Y%m%d_%H%M%S).sql

# Files backup (optional, for critical updates)
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz storage/app public/uploads
```

#### 3. Enable maintenance mode

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app php artisan down

# Native
php artisan down
```

This shows a maintenance page to users during update.

### Step 3: Pull new code

```bash
# Stash any local changes (if needed)
git stash

# Pull latest version (script auto-detects branch)
git fetch --all
git pull

# Or use the update script which handles this automatically
bash scripts/update-production.sh
```

#### 5. Update dependencies

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader

# Native
composer install --no-dev --optimize-autoloader
```

#### 6. Update environment variables (if needed)

```bash
# Check if .env needs updates
nano .env

# Compare with .env.example for new variables
diff .env .env.example
```

#### 7. Run database migrations

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force

# Native
php artisan migrate --force
```

**Note**: `--force` flag is required in production environment.

#### 8. Publish assets (if updated)

```bash
# If Sanctum or other packages were updated
docker compose -f docker-compose-prod.yml exec app php artisan vendor:publish --all --force

# Regenerate Swagger documentation (if API changed)
docker compose -f docker-compose-prod.yml exec app php artisan l5-swagger:generate
```

#### 9. Clear and rebuild cache

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml exec app php artisan route:cache
docker compose -f docker-compose-prod.yml exec app php artisan view:cache

# Native
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 10. Set permissions (if needed)

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose -f docker-compose-prod.yml exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Native
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 11. Restart services

```bash
# Docker - restart application container
docker compose -f docker-compose-prod.yml restart app

# Native - restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx (if configuration changed)
sudo systemctl restart nginx

# Restart queue worker
sudo systemctl restart queue-worker
```

#### 12. Disable maintenance mode

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app php artisan up

# Native
php artisan up
```

#### 13. Verify deployment

```bash
# Test health endpoint
curl http://localhost:8000/api/health

# Test authentication (if applicable)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'

# Check Swagger UI
curl http://localhost:8000/api/documentation

# Monitor logs
tail -f storage/logs/laravel.log

# For Docker
docker compose -f docker-compose-prod.yml logs -f app
```

---

## 🔥 Rollback Procedure

If something goes wrong:

```bash
# 1. Go back to previous version
git log --oneline -5
git checkout PREVIOUS_COMMIT_HASH

# 2. Restore dependencies and clear cache
composer install --no-dev
php artisan config:clear
php artisan cache:clear

# 3. Rollback migrations (if needed)
php artisan migrate:rollback

# 4. Restart services
# Docker: docker compose -f docker-compose-prod.yml restart app
# Native: sudo systemctl restart php8.3-fpm

# 5. Restore database from backup (if needed)
mysql -u your_user -p user_management < backup_YYYYMMDD_HHMMSS.sql
```

---

## 📊 Update Checklist

- [ ] Backup database
- [ ] Pull latest code
- [ ] Update dependencies
- [ ] Run migrations
- [ ] Clear cache
- [ ] Restart services
- [ ] Test health endpoint
- [ ] Monitor logs

---

## 🔍 Troubleshooting

### Application returns 500 error
```bash
tail -100 storage/logs/laravel.log
php artisan config:clear && php artisan cache:clear
php artisan config:cache
```

### Migrations fail
```bash
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate
```

### Permission errors
```bash
# Docker
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Native
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue not working
```bash
sudo systemctl restart queue-worker
php artisan queue:work --once
```

### Redis errors
```bash
# Use predis instead of phpredis
REDIS_CLIENT=predis  # in .env
composer require predis/predis

# Or use sync queue
QUEUE_CONNECTION=sync  # in .env
```

### NPM not found in Docker container
If you see warning: `exec: "npm": executable file not found in $PATH`

**You can safely ignore this warning.** This API doesn't require npm in the Docker container.

The update script will automatically:
1. Try to find npm in the container
2. Fall back to npm on the host (if available)
3. Continue without npm if not available

**If you need to build frontend assets** (optional for future):
```bash
# Build locally and commit
npm install --production
npm run build
git add public/build/
git commit -m "Build frontend assets"
```

---

## 🎯 Testing Server Update

For testing server, the process is similar but less strict:

```bash
# 1. Connect to test server
ssh user@test-server-ip
cd /var/www/user-management-api

# 2. Pull latest code (can skip backup)
git pull origin develop  # or test branch

# 3. Update
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan config:cache
docker compose restart app

# 4. Test
curl http://test-server/api/health
```

**Note**: Testing server can skip:
- Database backup (optional)
- Maintenance mode (optional)
- Some cache clearing steps

---

## 📚 Related Documentation

- **[Full Deployment Guide](DEPLOYMENT.md)** - Complete deployment instructions
- **[Production Hotfix](PRODUCTION_HOTFIX.md)** - Emergency fixes
- **[Installation Guide](INSTALL.md)** - Initial setup and troubleshooting
- **[Mail Configuration](MAIL_CONFIGURATION.md)** - SMTP and queue setup
- **[API Documentation](API.md)** - Testing endpoints after update


