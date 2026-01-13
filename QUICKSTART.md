# 🚀 Quick Start - Deployment nextstep.chat

Szybki przewodnik wdrożenia aplikacji na serwerze Ubuntu z Docker.

---

## Wymagania
- ✅ Serwer Ubuntu 20.04+
- ✅ Dostęp SSH
- ✅ Domena **nextstep.chat** wskazująca na IP serwera

---

## Krok 1: Instalacja podstawowych narzędzi (5 min)

```bash
# Połącz się z serwerem
ssh user@SERVER_IP

# Zaktualizuj system
sudo apt update && sudo apt upgrade -y

# Zainstaluj Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Zainstaluj Docker Compose
sudo apt-get install docker-compose-plugin

# Dodaj użytkownika do grupy docker
sudo usermod -aG docker $USER
newgrp docker

# Zainstaluj Nginx (reverse proxy)
sudo apt install -y nginx certbot python3-certbot-nginx

# Sprawdź instalację
docker --version
docker compose version
```

---

## Krok 2: Sklonuj i skonfiguruj projekt (5 min)

```bash
# Utwórz katalog
sudo mkdir -p /var/www
cd /var/www

# Sklonuj repozytorium
sudo git clone https://github.com/tomaszzygadlo/user-management-api.git
sudo chown -R $USER:$USER user-management-api
cd user-management-api

# Skopiuj i edytuj .env
cp .env.example .env
nano .env
```

### Najważniejsze zmienne w `.env`:
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nextstep.chat

# Ustaw SILNE hasła!
DB_PASSWORD=your_secure_mysql_password_here
REDIS_PASSWORD=your_secure_redis_password_here

# Email (opcjonalne, dla powiadomień)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=noreply@nextstep.chat

QUEUE_CONNECTION=redis
```

---

## Krok 3: Uruchom aplikację (3 min)

```bash
# Uruchom kontenery
docker compose -f docker-compose-prod.yml up -d

# Zainstaluj zależności
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader

# Wygeneruj klucz aplikacji
docker compose -f docker-compose-prod.yml exec app php artisan key:generate

# Uruchom migracje bazy danych
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force

# Cache konfiguracji (ważne dla produkcji!)
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

## Krok 4: Skonfiguruj Nginx (2 min)

```bash
# Utwórz konfigurację Nginx
sudo nano /etc/nginx/sites-available/nextstep.chat
```

Wklej:
```nginx
server {
    listen 80;
    server_name nextstep.chat www.nextstep.chat;

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

Aktywuj:
```bash
sudo ln -s /etc/nginx/sites-available/nextstep.chat /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Krok 5: Ustaw DNS (5-30 min propagacji)

W panelu domeny **nextstep.chat** ustaw:
- **Typ A**: `@` → IP twojego serwera
- **Typ A**: `www` → IP twojego serwera

Poczekaj na propagację DNS (sprawdź: `nslookup nextstep.chat`)

---

## Krok 6: Zainstaluj SSL (2 min)

```bash
# Automatyczna konfiguracja SSL
sudo certbot --nginx -d nextstep.chat -d www.nextstep.chat

# Certbot automatycznie:
# - Pobierze certyfikat
# - Skonfiguruje Nginx
# - Ustawi auto-renewal
```

**✅ Aplikacja działa na https://nextstep.chat**

---

## Krok 7: Firewall (1 min)

```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable
```

---

## Krok 8: Queue Worker - Opcjonalny (2 min)

Jeśli używasz wysyłania emaili:

```bash
# Skopiuj service file
sudo cp scripts/nextstep-worker.service /etc/systemd/system/

# Uruchom
sudo systemctl daemon-reload
sudo systemctl enable nextstep-worker
sudo systemctl start nextstep-worker

# Sprawdź status
sudo systemctl status nextstep-worker
```

---

## ✅ Testowanie

```bash
# Test API
curl https://nextstep.chat/api/health

# Sprawdź Swagger UI w przeglądarce
# https://nextstep.chat/api/documentation

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
nslookup nextstep.chat

# Sprawdź Nginx
sudo nginx -t
sudo systemctl status nginx

# Spróbuj ponownie
sudo certbot --nginx -d nextstep.chat -d www.nextstep.chat
```

### Brak uprawnień do storage/
```bash
docker compose -f docker-compose-prod.yml exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose -f docker-compose-prod.yml exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

---

## ✨ Gotowe!

Twoja aplikacja działa na **https://nextstep.chat** 🎉

