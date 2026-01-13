#!/usr/bin/env bash
set -e

DOMAIN="nextstep.chat"
WEBROOT="/var/www/nextstep"
REPO_URL="<REPO_URL>"

# Update
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git curl unzip php8.1 php8.1-fpm php8.1-mbstring php8.1-xml php8.1-mysql php8.1-zip php8.1-curl php8.1-bcmath mysql-server composer nodejs npm certbot python3-certbot-nginx

# Create webroot and clone
sudo mkdir -p ${WEBROOT}
sudo chown -R $USER:$USER ${WEBROOT}
git clone ${REPO_URL} ${WEBROOT}
cd ${WEBROOT}

# Install app
cp .env.example .env
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
npm install
npm run build || true

php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Permissions
sudo chown -R www-data:www-data ${WEBROOT}
sudo chmod -R ug+rwx ${WEBROOT}/storage ${WEBROOT}/bootstrap/cache

# Nginx config
sudo cp deploy/nginx_nextstep.conf /etc/nginx/sites-available/nextstep
sudo ln -sf /etc/nginx/sites-available/nextstep /etc/nginx/sites-enabled/nextstep
sudo nginx -t
sudo systemctl reload nginx

# SSL
sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} --non-interactive --agree-tos -m admin@${DOMAIN}

# Systemd worker
sudo cp deploy/nextstep-worker.service /etc/systemd/system/nextstep-worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now nextstep-worker

echo "Deployment finished. Visit: https://${DOMAIN}"

