# Deploy aplikacji na serwerze Ubuntu (przykład dla domeny nextstep.chat)

## Wymagania serwera
- Ubuntu 20.04 / 22.04
- konto z uprawnieniami sudo
- domena wskazująca na IP serwera (DNS A record)

## Krok 0 — połączenie SSH
ssh user@SERVER_IP

## Krok 1 — aktualizacja i podstawowe pakiety
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git unzip curl

## Krok 2 — PHP, Composer, Node (dla Laravel)
sudo apt install -y php8.1 php8.1-fpm php8.1-mbstring php8.1-xml php8.1-mysql php8.1-pgsql php8.1-zip php8.1-curl php8.1-bcmath
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
sudo apt install -y nodejs npm

## Krok 3 — baza danych
# Zainstaluj MySQL lub Postgres zgodnie z preferencją, skonfiguruj użytkownika i bazę.
sudo apt install -y mysql-server
# potem skonfiguruj DB i użytkownika (przykład MySQL)
# mysql -u root -p
# CREATE DATABASE nextstep; CREATE USER 'nextstep'@'localhost' IDENTIFIED BY 'strongpassword'; GRANT ALL ON nextstep.* TO 'nextstep'@'localhost';

## Krok 4 — katalog aplikacji
sudo mkdir -p /var/www/nextstep
sudo chown -R $USER:$USER /var/www/nextstep
cd /var/www/nextstep
git clone <URL_REPO> .    # wstaw adres repo

## Krok 5 — konfiguracja środowiska
cp .env.example .env
# Edytuj .env: APP_URL=https://nextstep.chat, ustaw DB_*, MAIL_*, itp.
composer install --no-dev --optimize-autoloader
npm install
npm run build        # lub npm run production zależnie od projektu

php artisan key:generate
php artisan migrate --force
php artisan storage:link

## Krok 6 — uprawnienia
sudo chown -R www-data:www-data /var/www/nextstep
sudo find /var/www/nextstep -type f -exec chmod 644 {} \;
sudo find /var/www/nextstep -type d -exec chmod 755 {} \;
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

## Krok 7 — Konfiguracja Nginx (reverse proxy)
```bash
# Utwórz konfigurację Nginx
sudo nano /etc/nginx/sites-available/nextstep.chat
```

Wklej następującą konfigurację:
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
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

Aktywuj konfigurację:
```bash
sudo ln -s /etc/nginx/sites-available/nextstep.chat /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Krok 8 — Konfiguracja DNS
W panelu domeny `nextstep.chat` ustaw rekordy DNS:
- **Typ A**: `@` → IP serwera
- **Typ A**: `www` → IP serwera

Poczekaj na propagację DNS (może potrwać do 24h, zwykle kilka minut).

## Krok 9 — SSL z Let's Encrypt
```bash
# Instalacja Certbot
sudo apt install -y certbot python3-certbot-nginx

# Uzyskaj certyfikat SSL
sudo certbot --nginx -d nextstep.chat -d www.nextstep.chat

# Sprawdź automatyczne odnawianie
sudo certbot renew --dry-run

# Sprawdź status timera
sudo systemctl status certbot.timer
```

## Krok 10 — Worker dla kolejek (opcjonalne)
Jeśli używasz kolejek (wysyłanie emaili):

```bash
# Uruchom worker w tle
docker compose -f docker-compose-prod.yml exec -d app php artisan queue:work --tries=3
```

Lub użyj systemd (bardziej niezawodne):
```bash
# Skopiuj plik service
sudo cp scripts/nextstep-worker.service /etc/systemd/system/

# Edytuj ścieżki w pliku (jeśli potrzeba)
sudo nano /etc/systemd/system/nextstep-worker.service

# Uruchom service
sudo systemctl daemon-reload
sudo systemctl enable nextstep-worker
sudo systemctl start nextstep-worker

# Sprawdź status
sudo systemctl status nextstep-worker
```

## Krok 11 — Firewall
```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable
sudo ufw status
```

## Krok 12 — Testowanie
```bash
# Sprawdź czy kontenery działają
docker compose -f docker-compose-prod.yml ps

# Sprawdź logi
docker compose -f docker-compose-prod.yml logs -f app

# Test API
curl https://nextstep.chat/api/health

# Test Swagger
# Otwórz w przeglądarce: https://nextstep.chat/api/documentation
```

---

# Metoda 2: Wdrożenie natywne (bez Docker)

## Wymagania
- Ubuntu 20.04+
- PHP 8.3+
- MySQL 8.0+ lub PostgreSQL
- Nginx
- Redis (dla kolejek)

## Szybka instalacja
```bash
# PHP i rozszerzenia
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Nginx
sudo apt install -y nginx

# Utwórz bazę danych
sudo mysql -e "CREATE DATABASE nextstep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'nextstep'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
sudo mysql -e "GRANT ALL PRIVILEGES ON nextstep.* TO 'nextstep'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Sklonuj projekt
cd /var/www
sudo git clone https://github.com/YOUR_USERNAME/user-management-api.git nextstep
sudo chown -R www-data:www-data /var/www/nextstep
cd nextstep

# Konfiguracja
cp .env.example .env
# Edytuj .env (ustaw DB credentials, APP_URL, etc.)
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Uprawnienia
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Nginx config
sudo cp scripts/nginx_nextstep.conf /etc/nginx/sites-available/nextstep
# Edytuj plik i dostosuj ścieżki
sudo ln -s /etc/nginx/sites-available/nextstep /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL
sudo certbot --nginx -d nextstep.chat -d www.nextstep.chat

# Queue worker (systemd)
sudo cp scripts/nextstep-worker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now nextstep-worker
```

---

# Maintenance i troubleshooting

## Aktualizacja aplikacji
```bash
cd /var/www/user-management-api
git pull origin main

# Docker
docker compose -f docker-compose-prod.yml exec app composer install --no-dev --optimize-autoloader
docker compose -f docker-compose-prod.yml exec app php artisan migrate --force
docker compose -f docker-compose-prod.yml exec app php artisan config:cache
docker compose -f docker-compose-prod.yml exec app php artisan route:cache
docker compose -f docker-compose-prod.yml exec app php artisan view:cache
docker compose -f docker-compose-prod.yml restart app

# Natywne
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.3-fpm
```

## Sprawdzanie logów
```bash
# Docker
docker compose -f docker-compose-prod.yml logs -f app
docker compose -f docker-compose-prod.yml exec app tail -f storage/logs/laravel.log

# Natywne
tail -f /var/www/nextstep/storage/logs/laravel.log
sudo journalctl -u php8.3-fpm -f
sudo journalctl -u nginx -f
```

## Backup bazy danych
```bash
# Docker
docker compose -f docker-compose-prod.yml exec mysql mysqldump -u laravel -p user_management > backup_$(date +%Y%m%d).sql

# Natywne
mysqldump -u nextstep -p nextstep > backup_$(date +%Y%m%d).sql
```

## Restart serwisów
```bash
# Docker
docker compose -f docker-compose-prod.yml restart

# Natywne
sudo systemctl restart php8.3-fpm nginx redis-server
sudo systemctl restart nextstep-worker
```

## Wskazówki bezpieczeństwa
1. **Firewall**: Ogranicz dostęp tylko do portów 80, 443, 22
2. **SSH**: Wyłącz logowanie root, używaj kluczy SSH
3. **Hasła**: Używaj mocnych, unikalnych haseł
4. **Aktualizacje**: `sudo apt update && sudo apt upgrade` regularnie
5. **Backup**: Automatyczne backupy bazy danych
6. **Monitoring**: Użyj narzędzi jak Uptime Robot, Prometheus
7. **Rate Limiting**: Włączone domyślnie w Laravel
8. **HTTPS**: Zawsze używaj SSL/TLS

## Przydatne komendy
```bash
# Status kontenerów
docker compose -f docker-compose-prod.yml ps

# Wejście do kontenera
docker compose -f docker-compose-prod.yml exec app bash

# Restart pojedynczego kontenera
docker compose -f docker-compose-prod.yml restart app

# Wyświetl użycie zasobów
docker stats

# Czyszczenie cache
docker compose -f docker-compose-prod.yml exec app php artisan cache:clear
docker compose -f docker-compose-prod.yml exec app php artisan config:clear
docker compose -f docker-compose-prod.yml exec app php artisan route:clear
docker compose -f docker-compose-prod.yml exec app php artisan view:clear
```

---

## Checklist przed uruchomieniem
- [ ] DNS wskazuje na serwer
- [ ] `.env` skonfigurowany z production credentials
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Silne hasła dla DB i Redis
- [ ] SSL certyfikat zainstalowany
- [ ] Firewall skonfigurowany
- [ ] Worker dla kolejek uruchomiony
- [ ] Backup strategy w miejscu
- [ ] Monitoring ustawiony

## Wsparcie
Jeśli napotkasz problemy:
1. Sprawdź logi aplikacji
2. Sprawdź logi Nginx
3. Zobacz [INSTALL.md](INSTALL.md) dla troubleshooting
4. Przejrzyj dokumentację Laravel: https://laravel.com/docs

