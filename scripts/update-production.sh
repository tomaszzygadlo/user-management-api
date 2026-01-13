#!/usr/bin/env bash

#################################################
# Laravel Production Update Script
#
# Quick update script for Laravel application
#
# Usage:
#   bash scripts/update-production.sh
#   OR
#   chmod +x scripts/update-production.sh
#   ./scripts/update-production.sh
#################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Laravel Production Update Script${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Check if we're in Laravel root directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Are you in Laravel root directory?${NC}"
    exit 1
fi

# Detect environment (Docker or Native)
USING_DOCKER=false
if [ -f "docker-compose.yml" ] || [ -f "docker-compose-prod.yml" ]; then
    if command -v docker &> /dev/null && docker compose ps &> /dev/null 2>&1; then
        USING_DOCKER=true
        echo -e "${GREEN}✓ Detected Docker environment${NC}"
        COMPOSE_FILE="docker-compose-prod.yml"
        if [ ! -f "$COMPOSE_FILE" ]; then
            COMPOSE_FILE="docker-compose.yml"
        fi
        PHP_CMD="docker compose -f $COMPOSE_FILE exec app php"
        COMPOSER_CMD="docker compose -f $COMPOSE_FILE exec app composer"
    else
        echo -e "${YELLOW}⚠ Docker files found but Docker not running or not installed${NC}"
        echo -e "${YELLOW}Falling back to native commands...${NC}"
    fi
fi

if [ "$USING_DOCKER" = false ]; then
    echo -e "${GREEN}✓ Using native PHP environment${NC}"
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        echo -e "${RED}Error: PHP command not found. Please install PHP or start Docker.${NC}"
        exit 1
    fi
    PHP_CMD="php"
    COMPOSER_CMD="composer"
fi

echo ""

# Enable maintenance mode
echo -e "${YELLOW}[1/8] Enabling maintenance mode...${NC}"
$PHP_CMD artisan down || true

# Pull latest changes
echo -e "${YELLOW}[2/8] Pulling latest changes from git...${NC}"
git pull origin main || git pull origin master

# Install/update composer dependencies
echo -e "${YELLOW}[3/8] Installing Composer dependencies...${NC}"
$COMPOSER_CMD install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Install/update npm dependencies (if needed)
if [ -f "package.json" ]; then
    echo -e "${YELLOW}[4/8] Installing NPM dependencies...${NC}"
    if [ "$USING_DOCKER" = true ]; then
        docker compose -f $COMPOSE_FILE exec app npm install --production
        docker compose -f $COMPOSE_FILE exec app npm run build || true
    else
        npm install --production
        npm run build || true
    fi
else
    echo -e "${YELLOW}[4/8] Skipping NPM (no package.json found)${NC}"
fi

# Run database migrations
echo -e "${YELLOW}[5/8] Running database migrations...${NC}"
$PHP_CMD artisan migrate --force

# Publish Sanctum configuration (if not already published)
echo -e "${YELLOW}[5.5/8] Publishing Sanctum configuration...${NC}"
$PHP_CMD artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force || true

# Clear and cache config
echo -e "${YELLOW}[6/8] Clearing and caching configuration...${NC}"
$PHP_CMD artisan config:clear
$PHP_CMD artisan cache:clear
$PHP_CMD artisan route:clear
$PHP_CMD artisan view:clear
$PHP_CMD artisan config:cache
$PHP_CMD artisan route:cache
$PHP_CMD artisan view:cache

# Fix permissions
echo -e "${YELLOW}[7/8] Fixing permissions...${NC}"
if [ "$USING_DOCKER" = true ]; then
    docker compose -f $COMPOSE_FILE exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    docker compose -f $COMPOSE_FILE exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
else
    sudo chown -R www-data:www-data storage bootstrap/cache
    sudo chmod -R ug+rwx storage bootstrap/cache
fi

# Disable maintenance mode
echo -e "${YELLOW}[8/8] Disabling maintenance mode...${NC}"
$PHP_CMD artisan up

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Update completed successfully!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Test the application
echo -e "${YELLOW}Testing application...${NC}"
if command -v curl &> /dev/null; then
    HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/health 2>&1 || echo "000")
    if [ "$HEALTH_CHECK" = "200" ]; then
        echo -e "${GREEN}✓ Health check passed (HTTP 200)${NC}"
    else
        echo -e "${RED}✗ Health check failed (HTTP $HEALTH_CHECK)${NC}"
        echo -e "${YELLOW}Check logs:${NC}"
        if [ "$USING_DOCKER" = true ]; then
            echo -e "${YELLOW}  docker compose -f $COMPOSE_FILE logs -f app${NC}"
        else
            echo -e "${YELLOW}  tail -f storage/logs/laravel.log${NC}"
        fi
    fi
else
    echo -e "${YELLOW}curl not installed, skipping health check${NC}"
fi

echo ""
if [ "$USING_DOCKER" = true ]; then
    echo -e "${GREEN}Docker containers status:${NC}"
    docker compose -f $COMPOSE_FILE ps
fi

echo ""
echo -e "${GREEN}Done!${NC}"

