# Scripts Documentation

Utility scripts for managing the Laravel application.

---

## Available Scripts

### update-production.sh

Quick update for production.

**Usage:**
```bash
./scripts/update-production.sh
```

**Does:**
- Maintenance mode ON
- Git pull
- Composer install
- Migrations
- Cache clear
- Maintenance mode OFF

---### 2. deploy.sh

**Purpose**: Initial deployment script for setting up the application.

### 3. fix-permissions.sh

**Purpose**: Fixes file permissions for Laravel storage and cache directories.

**Usage**:
```bash
bash scripts/fix-permissions.sh
```

### 4. fix-permissions.ps1

**Purpose**: PowerShell version of permissions fix for Windows development.

**Usage**:
```powershell
.\scripts\fix-permissions.ps1
```

### 5. nginx_nextstep.conf

**Purpose**: Example Nginx configuration for production deployment.
### deploy.sh

Deploy to production.

**Usage:** `./scripts/deploy.sh`

---

### fix-permissions.sh / fix-permissions.ps1

Fix file permissions (Linux/Windows).

**Usage:** `./scripts/fix-permissions.sh`

---

### diagnose-email.sh

Automatic email diagnostics.

**Usage:**
```bash
./scripts/diagnose-email.sh 4  # zamień 4 na ID użytkownika
```

**Checks:** User, SMTP, queue, Redis, worker, logs.

---

### test-database.sh

Test database connection and list users.

**Usage:** `./scripts/test-database.sh`

---

### diagnose-queue.sh

Check queue configuration and Redis.

**Usage:** `./scripts/diagnose-queue.sh`

---

### setup-queue-worker.sh

Install systemd service for queue worker.

**Usage:**
```bash
sudo ./scripts/setup-queue-worker.sh
```

**After:**
- `sudo systemctl status laravel-queue-worker`
- `sudo systemctl restart laravel-queue-worker`

---

### enable-laravel-logs-stdout.sh

Redirect Laravel logs to Docker stdout.

**Usage:** `./scripts/enable-laravel-logs-stdout.sh`

---

## Troubleshooting

### Script permission denied
```bash
chmod +x scripts/*.sh
```

### NPM warnings
Safe to ignore for API-only apps.

### Worker not processing
```bash
sudo systemctl restart laravel-queue-worker
```

---## NPM in Production

**Dla tego projektu (REST API)**: NPM nie jest wymagane w kontenerze Docker.

Jeśli w przyszłości będziesz potrzebować budować frontend assets:

**Opcja 1: Pre-build assets (Zalecana)**
```bash
# Lokalnie lub w CI/CD
npm install --production
npm run build
git add public/build/
git commit -m "Build frontend assets"
```

**Opcja 2: Używaj npm z hosta**
Skrypt `update-production.sh` automatycznie wykryje npm na hoście jeśli jest dostępny.

## Related Documentation

- [EMAIL_TROUBLESHOOTING.md](../docs/EMAIL_TROUBLESHOOTING.md) - Email troubleshooting
- [UPDATE.md](../docs/UPDATE.md) - Update procedures
- [DEPLOYMENT.md](../docs/DEPLOYMENT.md) - Deployment guide
