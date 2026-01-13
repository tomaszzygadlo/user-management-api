# 🚀 Quick Start - Production Deployment

Fast deployment guide for Ubuntu server with Docker.

---

## Requirements
- ✅ Ubuntu 20.04+ server
- ✅ SSH access
- ✅ Domain pointing to server IP

---

## Step 1: Install basic tools (5 min)

```bash
# Connect to server
ssh user@YOUR_SERVER_IP

# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt-get install docker-compose-plugin

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Install Nginx (reverse proxy)
sudo apt install -y nginx certbot python3-certbot-nginx

# Verify installation
docker --version
docker compose version
```

---

## Step 2: Clone and configure project (5 min)

```bash
# Create directory
sudo mkdir -p /var/www
cd /var/www

# Clone repository
sudo git clone YOUR_REPOSITORY_URL user-management-api
sudo chown -R $USER:$USER user-management-api
cd user-management-api

# Copy and edit .env
cp .env.example .env
nano .env
```

### Important variables in `.env`:
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Set STRONG passwords!
DB_PASSWORD=your_secure_mysql_password_here
REDIS_PASSWORD=your_secure_redis_password_here

# Email (optional, for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com

QUEUE_CONNECTION=redis
```

---

## Step 3: Start application (3 min)

```bash
# Start containers
docker compose -f docker-compose-prod.yml up -d

# Install dependencies
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader

# Generate application key
docker compose -f docker-compose-prod.yml exec app php artisan key:generate

# Run database migrations
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force

# Cache configuration (important for production!)
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml exec app php artisan route:cache
docker compose -f docker-compose-prod.yml exec app php artisan view:cache

# Ustaw uprawnienia
docker compose -f docker-compose-prod.yml exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose -f docker-compose-prod.yml exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Sprawdź status
docker compose -f docker-compose-prod.yml ps
```

**✅ Aplikacja działa na http://localhost:8000**

---

## Step 4: Configure Nginx (2 min)

```bash
# Create Nginx configuration
sudo nano /etc/nginx/sites-available/yourdomain.com
```

Paste:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

Activate:
```bash
sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Step 5: Set up DNS (5-30 min propagation)

In your domain panel set:
- **Type A**: `@` → Your server IP
- **Type A**: `www` → Your server IP

Wait for DNS propagation (check: `nslookup yourdomain.com`)

---

## Step 6: Install SSL (2 min)

```bash
# Automatic SSL configuration
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Certbot will automatically:
# - Get certificate
# - Configure Nginx
# - Set up auto-renewal
```

**✅ Application running at https://yourdomain.com**

---

## Step 7: Firewall (1 min)

```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable
```

---

## Step 8: Queue Worker - Optional (2 min)

If you're using email sending:

```bash
# Copy service file (adjust paths in the file first)
sudo cp scripts/queue-worker.service /etc/systemd/system/

# Start
sudo systemctl daemon-reload
sudo systemctl enable queue-worker
sudo systemctl start queue-worker

# Check status
sudo systemctl status queue-worker
```

---

## ✅ Testowanie

```bash
# Test API
curl https://yourdomain.com/api/health

# Sprawdź Swagger UI w przeglądarce
# https://yourdomain.com/api/documentation

# Sprawdź logi
docker compose -f docker-compose-prod.yml logs -f app
```

---

## 🔧 Przydatne komendy

```bash
# Restart aplikacji
docker compose -f docker-compose-prod.yml restart app

# Sprawdź logi
docker compose -f docker-compose-prod.yml logs -f

# Wejdź do kontenera
docker compose -f docker-compose-prod.yml exec app bash

# Czyszczenie cache
docker compose -f docker-compose-prod.yml exec app php artisan cache:clear
docker compose -f docker-compose-prod.yml exec app php artisan config:clear

# Aktualizacja (po git pull)
docker compose -f docker-compose-prod.yml exec app composer install --no-dev
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml restart app
```

---

## ⏱️ Całkowity czas: ~25-45 minut

(w zależności od propagacji DNS)

---

## 📚 Więcej informacji

- **Pełny przewodnik**: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- **Troubleshooting**: [docs/INSTALL.md](docs/INSTALL.md)
- **API Docs**: [docs/API.md](docs/API.md)

---

## 🆘 Problemy?

### Docker permission denied
```bash
sudo usermod -aG docker $USER
newgrp docker
# lub logout i login ponownie
```

### Port 8000 zajęty
```bash
# Zmień port w docker-compose-prod.yml
# ports: "8001:80"
```

### SSL nie działa
```bash
# Sprawdź DNS
nslookup yourdomain.com

# Sprawdź Nginx
sudo nginx -t
sudo systemctl status nginx

# Spróbuj ponownie
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Brak uprawnień do storage/
```bash
docker compose -f docker-compose-prod.yml exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose -f docker-compose-prod.yml exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

---

## ✨ Gotowe!

Twoja aplikacja działa na **https://yourdomain.com** 🎉

