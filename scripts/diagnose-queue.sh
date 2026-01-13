#!/bin/bash

# Queue Diagnostics Script
# Diagnoses why queue worker is not processing jobs

echo "=========================================="
echo "   Queue Diagnostics"
echo "=========================================="
echo ""

cd /var/www/user-management-api

# Check if using Docker
if [ -f "docker-compose-prod.yml" ]; then
    COMPOSE_FILE="docker-compose-prod.yml"
elif [ -f "docker-compose.yml" ]; then
    COMPOSE_FILE="docker-compose.yml"
fi

echo "[1/8] Checking queue configuration..."
docker compose -f $COMPOSE_FILE exec -T app cat .env | grep -E "QUEUE_CONNECTION|REDIS_"
echo ""

echo "[2/8] Checking Laravel queue config..."
docker compose -f $COMPOSE_FILE exec -T app php artisan tinker --execute="
echo 'Queue Driver: ' . config('queue.default') . \"\n\";
echo 'Redis Connection: ' . config('queue.connections.redis.connection') . \"\n\";
echo 'Redis Queue: ' . config('queue.connections.redis.queue') . \"\n\";
echo 'Redis Host: ' . config('database.redis.default.host') . \"\n\";
echo 'Redis Prefix: ' . config('database.redis.options.prefix') . \"\n\";
"
echo ""

echo "[3/8] Testing Redis connection..."
docker compose -f $COMPOSE_FILE exec -T app php artisan tinker --execute="
try {
    \$pong = Redis::ping();
    echo 'Redis Connection: SUCCESS (PONG)\n';
} catch (\Exception \$e) {
    echo 'Redis Connection: FAILED - ' . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "[4/8] Checking Redis keys..."
docker compose -f $COMPOSE_FILE exec -T redis redis-cli KEYS "*queue*" | head -20
echo ""

echo "[5/8] Checking default queue length..."
docker compose -f $COMPOSE_FILE exec -T redis redis-cli LLEN queues:default
echo ""

echo "[6/8] Checking jobs in queue..."
QUEUE_JOBS=$(docker compose -f $COMPOSE_FILE exec -T redis redis-cli LRANGE queues:default 0 2)
if [ -z "$QUEUE_JOBS" ]; then
    echo "No jobs in queue"
else
    echo "$QUEUE_JOBS"
fi
echo ""

echo "[7/8] Test queueing a job..."
docker compose -f $COMPOSE_FILE exec -T app php artisan tinker --execute="
Queue::push(function() {
    logger('TEST JOB FROM DIAGNOSTICS');
});
echo 'Test job queued\n';
"
echo ""

echo "[8/8] Check if job was added to Redis..."
sleep 1
docker compose -f $COMPOSE_FILE exec -T redis redis-cli LLEN queues:default
echo ""

echo "=========================================="
echo "   Diagnostic Summary"
echo "=========================================="
echo ""
echo "If queue length increased after test job:"
echo "  ✓ Jobs ARE being queued to Redis"
echo "  ✗ Worker is NOT processing them"
echo ""
echo "Solution:"
echo "  1. Stop worker: sudo systemctl stop laravel-queue-worker"
echo "  2. Run manually: docker compose -f $COMPOSE_FILE exec app php artisan queue:work redis --queue=default --verbose"
echo "  3. In another terminal, send test email"
echo "  4. Watch worker output - should see 'Processing:' message"
echo ""
echo "If worker processes the test job:"
echo "  → Reinstall systemd service with fixed command"
echo "  → Run: sudo ./scripts/setup-queue-worker.sh"
echo ""

