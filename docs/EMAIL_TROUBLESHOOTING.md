# 🔧 Email Troubleshooting

## Problem
Emails not arriving despite `"message": "Welcome emails queued successfully"`

---

## ⚡ Step 1: Automated Diagnostics

```bash
cd /var/www/user-management-api
./scripts/diagnose-email.sh 4  # replace 4 with user ID
```

**Script will check everything and tell you what to fix.**

If it shows **✅ All checks passed** → go to **Step 2**.

---

## 🔍 Step 2: Most Common Issues

### A. Queue worker not running (90% of problems)

**Check:**
```bash
sudo systemctl status laravel-queue-worker
```

**If not running - start it:**
```bash
sudo ./scripts/setup-queue-worker.sh
```

---

### B. User has no email

**Check:**
```bash
docker compose -f docker-compose-prod.yml exec app php artisan tinker
```
```php
User::find(4)->emails;  // Should show emails
exit
```

**If empty - add email:**
```php
$user = User::find(4);
$user->emails()->create(['email' => 'user@example.com', 'is_primary' => true]);
```

---

### C. Wrong SMTP configuration

**Check `.env`:**
```bash
docker compose -f docker-compose-prod.yml exec app cat .env | grep MAIL_
```

**Correct configuration (Gmail example):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=app-password-here  # NOT your regular password!
```

**Important:** Port 587 = TLS, Port 465 = SSL

---

### D. Redis not working

**Test:**
```bash
docker compose -f docker-compose-prod.yml exec app php artisan tinker
```
```php
Redis::ping();  // Should return: "PONG"
```

**If error - switch to database queue:**
```env
QUEUE_CONNECTION=database
```

---

## 📋 Step 3: Check Logs

```bash
# Today's logs
docker compose -f docker-compose-prod.yml exec app tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log

# Queue worker logs
sudo journalctl -u laravel-queue-worker -f

# Errors only
docker compose -f docker-compose-prod.yml exec app grep ERROR storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## 🧪 Step 4: Test Email Sending

```bash
# Terminal 1 - Logs
docker compose -f docker-compose-prod.yml exec app tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Terminal 2 - Send email
TOKEN=$(docker compose -f docker-compose-prod.yml exec -T app php artisan tinker --execute="echo User::first()->createToken('test')->plainTextToken;" | tail -1)

curl -X POST http://localhost/api/users/4/welcome \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Terminal 1 will show:**
```
=== WELCOME EMAIL REQUEST START ===
>>> UserService->sendWelcomeEmails() START
>>> Notification queued on: default
=== WELCOME EMAIL REQUEST SUCCESS ===
Processing: Illuminate\Notifications\SendQueuedNotifications
Processed: Illuminate\Notifications\SendQueuedNotifications
```

---

## 💡 Quick Fixes

### Email in SPAM
Check SPAM, Promotions, Social folders in Gmail.

### Failed jobs
```bash
docker compose -f docker-compose-prod.yml exec app php artisan queue:failed
docker compose -f docker-compose-prod.yml exec app php artisan queue:retry all
```

### Restart everything
```bash
docker compose -f docker-compose-prod.yml restart
sudo systemctl restart laravel-queue-worker
docker compose -f docker-compose-prod.yml exec app php artisan config:clear
```

### Temporary fix (sync queue)
```env
QUEUE_CONNECTION=sync  # Emails send immediately, no worker needed
```

---

## 📞 More Information

- SMTP configuration: `docs/MAIL_CONFIGURATION.md`
- Production updates: `docs/UPDATE.md`
- Utility scripts: `scripts/README.md`

