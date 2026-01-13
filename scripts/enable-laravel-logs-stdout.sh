#!/bin/bash

# Enable Laravel logs to Docker stdout
# This makes Laravel logs visible in 'docker compose logs'
# Works with daily log files

echo "=========================================="
echo "   Enable Laravel Logs to stdout"
echo "=========================================="
echo ""

cd /var/www/user-management-api

if [ -f "docker-compose-prod.yml" ]; then
    COMPOSE_FILE="docker-compose-prod.yml"
elif [ -f "docker-compose.yml" ]; then
    COMPOSE_FILE="docker-compose.yml"
else
    echo "Error: No docker-compose file found"
    exit 1
fi

TODAYS_LOG="laravel-$(date +%Y-%m-%d).log"

echo "Today's log file: $TODAYS_LOG"
echo ""

echo "Backing up current logs..."
docker compose -f $COMPOSE_FILE exec app bash -c "
if [ -f storage/logs/$TODAYS_LOG ]; then
    cp storage/logs/$TODAYS_LOG storage/logs/$TODAYS_LOG.backup
    echo 'Backup created: storage/logs/$TODAYS_LOG.backup'
fi
"

echo "Creating symlink to /dev/stdout for today's log..."
docker compose -f $COMPOSE_FILE exec app bash -c "
rm -f storage/logs/$TODAYS_LOG
ln -s /dev/stdout storage/logs/$TODAYS_LOG
chown www-data:www-data storage/logs/$TODAYS_LOG
echo 'Symlink created: storage/logs/$TODAYS_LOG -> /dev/stdout'
"

echo ""
echo "=========================================="
echo "   Done!"
echo "=========================================="
echo ""
echo "Today's Laravel logs ($TODAYS_LOG) are now visible in:"
echo "  docker compose -f $COMPOSE_FILE logs -f app"
echo ""
echo "NOTE: Daily logs rotate at midnight."
echo "Tomorrow you'll need to run this script again for the new day's log."
echo ""
echo "To make this permanent, add a cron job:"
echo "  0 0 * * * cd /var/www/user-management-api && ./scripts/enable-laravel-logs-stdout.sh"
echo ""
echo "To test:"
echo "  1. docker compose -f $COMPOSE_FILE logs -f app"
echo "  2. In another terminal, call your API endpoint"
echo "  3. You should see Laravel logs in real-time!"
echo ""



