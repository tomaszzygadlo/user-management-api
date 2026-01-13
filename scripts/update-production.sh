#!/usr/bin/env bash

#################################################
# Szybki skrypt aktualizacji aplikacji Laravel
# Na serwerze produkcyjnym
#################################################

set -e

# Kolory dla outputu
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Laravel Production Update Script${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Sprawdź czy jesteśmy w katalogu Laravel
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Are you in Laravel root directory?${NC}"
    exit 1
fi

# Enable maintenance mode
echo -e "${YELLOW}[1/8] Enabling maintenance mode...${NC}"
php artisan down || true

# Pull latest changes
echo -e "${YELLOW}[2/8] Pulling latest changes from git...${NC}"
git pull origin master

# Install/update composer dependencies
echo -e "${YELLOW}[3/8] Installing Composer dependencies...${NC}"
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Install/update npm dependencies (if needed)
if [ -f "package.json" ]; then
    echo -e "${YELLOW}[4/8] Installing NPM dependencies...${NC}"
    npm install --production
    npm run build || true
else
    echo -e "${YELLOW}[4/8] Skipping NPM (no package.json found)${NC}"
fi

# Run database migrations
echo -e "${YELLOW}[5/8] Running database migrations...${NC}"
php artisan migrate --force

# Clear and cache config
echo -e "${YELLOW}[6/8] Clearing and caching configuration...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
echo -e "${YELLOW}[7/8] Fixing permissions...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

# Disable maintenance mode
echo -e "${YELLOW}[8/8] Disabling maintenance mode...${NC}"
php artisan up

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Update completed successfully!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Test the application
echo -e "${YELLOW}Testing application...${NC}"
if command -v curl &> /dev/null; then
    HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health 2>&1 || echo "000")
    if [ "$HEALTH_CHECK" = "200" ]; then
        echo -e "${GREEN}✓ Health check passed (HTTP 200)${NC}"
    else
        echo -e "${RED}✗ Health check failed (HTTP $HEALTH_CHECK)${NC}"
        echo -e "${YELLOW}Check logs: tail -f storage/logs/laravel.log${NC}"
    fi
else
    echo -e "${YELLOW}curl not installed, skipping health check${NC}"
fi

echo ""
echo -e "${GREEN}Done!${NC}"

