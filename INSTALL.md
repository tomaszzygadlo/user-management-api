# Installation Guide

Complete step-by-step installation instructions for User Management API.

## Prerequisites

Ensure you have the following installed on your system:

- **PHP**: 8.3 or higher
- **Composer**: Latest version
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Redis** (optional): For queue and cache
- **Node.js & NPM** (optional): For frontend assets

### Verify Prerequisites

```bash
# Check PHP version
php -v  # Should show 8.3.x or higher

# Check Composer
composer --version

# Check MySQL
mysql --version

# Check Redis (optional)
redis-cli --version
```

## Installation Methods

### Method 1: Standard Installation (Recommended)

#### Step 1: Clone Repository

```bash
git clone <repository-url>
cd user-management-api
```

#### Step 2: Install Dependencies

```bash
composer install
```

If you encounter memory issues:
```bash
php -d memory_limit=-1 /usr/bin/composer install
```

#### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### Step 4: Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Create database:**
```bash
# MySQL
mysql -u root -p
CREATE DATABASE user_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### Step 5: Run Migrations

```bash
# Run migrations
php artisan migrate

# Run migrations with sample data
php artisan migrate --seed
```

#### Step 6: Configure Queue

For development (synchronous):
```env
QUEUE_CONNECTION=sync
```

For production (Redis):
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### Step 7: Configure Mail

For development (log driver):
```env
MAIL_MAILER=log
```

For production (SMTP):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

#### Step 8: Start Development Server

```bash
# Start Laravel development server
php artisan serve
```

The API will be available at: `http://localhost:8000`

#### Step 9: Start Queue Worker (Optional)

In a separate terminal:
```bash
php artisan queue:work
```

---

### Method 2: Docker Installation

#### Step 1: Clone Repository

```bash
git clone <repository-url>
cd user-management-api
```

#### Step 2: Start Docker Containers

```bash
docker-compose up -d
```

This will start:
- App container (PHP-FPM)
- MySQL container
- Redis container
- Mailpit container (email testing)
- Nginx container

#### Step 3: Install Dependencies

```bash
docker-compose exec app composer install
```

#### Step 4: Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

#### Step 5: Run Migrations

```bash
docker-compose exec app php artisan migrate --seed
```

#### Step 6: Start Queue Worker

```bash
docker-compose exec app php artisan queue:work
```

#### Access Points

- **API**: http://localhost:8000
- **Mailpit UI**: http://localhost:8025 (email testing)
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

#### Docker Commands Reference

```bash
# View logs
docker-compose logs -f app

# Stop containers
docker-compose down

# Rebuild containers
docker-compose up -d --build

# Run artisan commands
docker-compose exec app php artisan [command]

# Access MySQL
docker-compose exec mysql mysql -u laravel -p user_management

# Access Redis
docker-compose exec redis redis-cli
```

---

## Post-Installation

### Verify Installation

```bash
# Run health check
curl http://localhost:8000/api/health

# Expected response:
# {
#   "status": "ok",
#   "timestamp": "2024-01-15T10:30:00Z",
#   "service": "User Management API",
#   "version": "1.0.0"
# }
```

### Run Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Code Quality Checks

```bash
# Fix code style
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse
```

### Seed Sample Data

```bash
php artisan db:seed

# Or with fresh database
php artisan migrate:fresh --seed
```

---

## Configuration

### Performance Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear all caches
php artisan optimize:clear
```

### Queue Configuration

**Supervisor configuration** for production queue workers:

```ini
[program:user-management-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/queue.log
stopwaitsecs=3600
```

### Cron Jobs

Add to crontab for scheduled tasks:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Troubleshooting

### Permission Issues

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Set ownership (Linux)
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Failed

```bash
# Test database connection
php artisan tinker
> DB::connection()->getPdo();

# Clear config cache
php artisan config:clear
```

### Queue Not Processing

```bash
# Check queue table exists
php artisan migrate

# Restart queue worker
php artisan queue:restart
php artisan queue:work

# Check failed jobs
php artisan queue:failed
```

### Composer Install Fails

```bash
# Clear Composer cache
composer clear-cache

# Update Composer
composer self-update

# Install with no dev dependencies
composer install --no-dev
```

### Port Already in Use

```bash
# Use different port
php artisan serve --port=8001

# Or find and kill process using port 8000
lsof -ti:8000 | xargs kill -9
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY`
- [ ] Configure production database
- [ ] Set up Redis for queue and cache
- [ ] Configure mail server (SMTP)
- [ ] Set up SSL/TLS certificate
- [ ] Configure queue workers with Supervisor
- [ ] Set up cron jobs
- [ ] Configure backups
- [ ] Set up monitoring and logging
- [ ] Configure firewall rules
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`

### Deployment Commands

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart
```

---

## System Requirements

### Minimum Requirements

- PHP 8.3+
- MySQL 8.0+ or PostgreSQL 13+
- 512 MB RAM
- 100 MB disk space

### Recommended Requirements

- PHP 8.3+
- MySQL 8.0+ or PostgreSQL 15+
- 1 GB RAM
- 500 MB disk space
- Redis 7+
- SSL certificate

### PHP Extensions Required

- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Redis (optional, for queue)

---

## Support

### Getting Help

- **Documentation**: See README.md, ARCHITECTURE.md, API.md
- **Issues**: Open an issue on GitHub
- **Discussions**: Use GitHub Discussions

### Reporting Bugs

See CONTRIBUTING.md for bug reporting guidelines.

---

**Last Updated:** January 2026  
**Version:** 1.0.0
