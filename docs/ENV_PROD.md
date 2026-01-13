# Production Environment Setup

## Overview

File `.env.prod` contains production configuration template based on current server setup.

---

## Deployment Steps

### 1. Copy to server
```bash
scp .env.prod user@server:/var/www/user-management-api/.env
```

### 2. Edit configuration
```bash
nano /var/www/user-management-api/.env
```

Update:
```env
APP_URL=https://your-domain.com
DB_PASSWORD=your-secure-password
```

### 3. Set permissions
```bash
chmod 600 .env
chown www-data:www-data .env
```

### 4. Clear cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

---

## Key Differences: Dev vs Prod

| Setting | Development | Production |
|---------|-------------|------------|
| APP_ENV | local | production |
| APP_DEBUG | true | false |
| LOG_LEVEL | debug | warning |
| QUEUE_CONNECTION | sync | redis |
| DB_HOST | mysql (Docker) | 127.0.0.1 |

---

## Production Requirements

### Redis Queue
Requires Redis server and queue worker:

```bash
# Check Redis
redis-cli ping

# Check worker
sudo systemctl status queue-worker
```

### Alternative: Database Queue
If no Redis:
```env
QUEUE_CONNECTION=database
```

---

## Testing

```bash
# Test config
php artisan config:show mail

# Test database
php artisan migrate:status

# Test email
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

---

## Deployment Checklist

- [ ] Copy `.env.prod` → `.env`
- [ ] Update `APP_URL`
- [ ] Update `DB_PASSWORD`
- [ ] Set `chmod 600`
- [ ] Clear cache
- [ ] Run migrations
- [ ] Start queue worker
- [ ] Test email sending

---

## Troubleshooting

**Mail issues:**
```bash
php artisan config:show mail
tail -f storage/logs/laravel.log
```

**Queue issues:**
```bash
redis-cli ping
sudo systemctl restart queue-worker
```

**Redis errors:**
```env
REDIS_CLIENT=predis
```

---

See also: [MAIL_CONFIGURATION.md](MAIL_CONFIGURATION.md)


