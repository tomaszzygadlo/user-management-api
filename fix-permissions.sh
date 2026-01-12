#!/bin/bash

# Fix permissions script for User Management API
# Run this script if you encounter permission issues

echo "Fixing permissions for User Management API..."

# Docker environment
if command -v docker &> /dev/null && [ -f "docker-compose.yml" ]; then
    echo "Docker environment detected"

    echo "Setting permissions for storage and bootstrap/cache..."
    docker compose exec app chmod -R 775 storage bootstrap/cache
    docker compose exec app chown -R www-data:www-data storage bootstrap/cache

    echo "Setting permissions for logs directory..."
    docker compose exec app mkdir -p storage/logs
    docker compose exec app chmod -R 777 storage/logs
    docker compose exec app chown -R www-data:www-data storage/logs

    echo "Clearing Laravel caches..."
    docker compose exec app php artisan config:clear
    docker compose exec app php artisan cache:clear
    docker compose exec app php artisan route:clear
    docker compose exec app php artisan view:clear

    echo "Restarting containers..."
    docker compose restart app nginx

    echo "✅ Permissions fixed! Docker containers restarted."

# Local environment
else
    echo "Local environment detected"

    echo "Setting permissions for storage and bootstrap/cache..."
    chmod -R 775 storage bootstrap/cache

    echo "Setting permissions for logs directory..."
    mkdir -p storage/logs
    chmod -R 777 storage/logs

    # Detect web server user
    if [ -d /etc/apache2 ]; then
        WEB_USER="www-data"
    elif [ -d /etc/nginx ]; then
        WEB_USER="www-data"
    elif [ "$(uname)" == "Darwin" ]; then
        WEB_USER="_www"
    else
        WEB_USER="www-data"
    fi

    echo "Setting owner to $WEB_USER..."
    sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache 2>/dev/null || echo "Note: Could not change ownership (may need sudo)"

    echo "Clearing Laravel caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear

    echo "✅ Permissions fixed!"
fi

echo ""
echo "If you still have issues, see INSTALL.md troubleshooting section."

