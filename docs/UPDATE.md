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

### Step 3: Pull latest code

```bash
git fetch --all
git pull
```

### Step 4: Update application

#### If using Docker:

```bash
# Update dependencies
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader

# Run migrations
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force

# Clear and rebuild cache
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml exec app php artisan route:cache
docker compose -f docker-compose-prod.yml exec app php artisan view:cache

# Restart application
docker compose -f docker-compose-prod.yml restart app

# Restart queue worker (if using)
sudo systemctl restart queue-worker
```

#### If running natively:

```bash
# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM (adjust service name if needed)
sudo systemctl restart php8.3-fpm

# Restart queue worker (if using)
sudo systemctl restart queue-worker
```

### Step 5: Verify deployment

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

### Option A: Using automated script

We provide a convenient update script that **automatically detects** your environment (Docker or native PHP):

```bash
# Make script executable (first time only)
chmod +x scripts/update-production.sh

# Run update
./scripts/update-production.sh
```

**If you get "Permission denied" error:**
```bash
# Fix permissions
chmod +x scripts/update-production.sh

# Or run with bash directly
bash scripts/update-production.sh
```

**If you get "php: command not found" or "composer: command not found":**

The script now automatically detects if you're using Docker and adjusts commands accordingly. Make sure:
- Docker is running: `docker compose ps`
- Or PHP is installed: `php -v`

The script automatically:
- ✅ **Detects Docker or native PHP environment**
- ✅ Uses appropriate commands (`docker compose exec` or direct `php`)
- Backs up the database
- Pulls latest code
- Updates dependencies
- Runs migrations
- Clears cache
- Restarts services

### Option B: Manual step-by-step

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

### Quick Rollback

```bash
# 1. Go back to previous version
git log --oneline -10  # find previous commit
git checkout PREVIOUS_COMMIT_HASH

# 2. Restore dependencies
docker compose -f docker-compose-prod.yml exec app composer install --no-dev

# 3. Rollback migrations (if needed)
docker compose -f docker-compose-prod.yml exec app php artisan migrate:rollback

# 4. Clear cache
docker compose -f docker-compose-prod.yml exec app php artisan config:clear
docker compose -f docker-compose-prod.yml exec app php artisan cache:clear

# 5. Restart
docker compose -f docker-compose-prod.yml restart app
```

### Database Rollback

```bash
# Restore from backup
mysql -u your_user -p user_management < backup_20260113_150000.sql

# Or for Docker
docker compose exec -T mysql mysql -u laravel -p user_management < backup_20260113_150000.sql
```

---

## 📊 Update Checklist

Use this checklist for every deployment:

- [ ] Backup database
- [ ] Backup critical files (optional)
- [ ] Check disk space
- [ ] Enable maintenance mode
- [ ] Pull latest code
- [ ] Update .env (if needed)
- [ ] Update dependencies
- [ ] Run migrations
- [ ] Clear cache
- [ ] Set permissions
- [ ] Restart services
- [ ] Disable maintenance mode
- [ ] Test health endpoint
- [ ] Test critical features
- [ ] Monitor logs for errors
- [ ] Notify team (if applicable)

---

## 🔍 Troubleshooting

### Application returns 500 error after update

```bash
# Check logs
tail -100 storage/logs/laravel.log

# Clear all cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan config:cache
php artisan route:cache
```

### Migrations fail

```bash
# Check migration status
php artisan migrate:status

# Try running specific migration
php artisan migrate --path=/database/migrations/YYYY_MM_DD_HHMMSS_migration_name.php

# If stuck, rollback last batch and retry
php artisan migrate:rollback
php artisan migrate
```

### Permission errors

```bash
# Docker
docker compose -f docker-compose-prod.yml exec app chown -R www-data:www-data /var/www
docker compose -f docker-compose-prod.yml exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Native
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue not processing

```bash
# Check queue worker status
sudo systemctl status queue-worker

# Restart queue worker
sudo systemctl restart queue-worker

# Check queue jobs
php artisan queue:work --once  # process one job
php artisan queue:failed       # list failed jobs
```

### Cache issues

```bash
# Nuclear option - clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled

# Then rebuild
php artisan config:cache
php artisan route:cache
php artisan view:cache
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

## 📅 Update Schedule Recommendations

### Production:
- **Minor updates**: Weekly or bi-weekly (off-peak hours)
- **Major updates**: Monthly (planned maintenance window)
- **Security patches**: As soon as possible
- **Best time**: Late evening or early morning (low traffic)

### Testing:
- **Updates**: Daily or after each feature completion
- **Best time**: Anytime during business hours

---

## 🔐 Security Considerations

Before update:
- [ ] Review changelog for breaking changes
- [ ] Check for security advisories
- [ ] Test on staging/testing server first
- [ ] Have rollback plan ready
- [ ] Notify users about maintenance (for major updates)

After update:
- [ ] Monitor error logs for 24 hours
- [ ] Check application performance
- [ ] Verify all critical features work
- [ ] Review security logs

---

## 📞 Emergency Contacts

Keep this information handy:

```
Server Details:
- IP: _________________
- SSH User: ___________
- App Path: ___________

Database:
- Host: _______________
- User: _______________
- Database: ___________

Services:
- PHP-FPM: php8.3-fpm
- Web Server: nginx
- Queue: queue-worker
```

---

## 📚 Related Documentation

- **[Full Deployment Guide](DEPLOYMENT.md)** - Complete deployment instructions
- **[Production Hotfix](PRODUCTION_HOTFIX.md)** - Emergency fixes
- **[Installation Guide](INSTALL.md)** - Initial setup and troubleshooting
- **[API Documentation](API.md)** - Testing endpoints after update

---

**⚡ Pro Tip**: Create a cron job to automatically backup database daily:

```bash
# Add to crontab (crontab -e)
0 2 * * * cd /var/www/user-management-api && docker compose exec -T mysql mysqldump -u laravel -p'password' user_management > backups/db_$(date +\%Y\%m\%d).sql
```

