#!/bin/bash

# Quick Database Check Script
# Use this to debug database connection issues

echo "=========================================="
echo "   Database Connection Test"
echo "=========================================="
echo ""

cd /var/www/user-management-api

# Check if running in Docker or native
USING_DOCKER=false
COMPOSE_FILE=""

if command -v docker &> /dev/null && command -v docker compose &> /dev/null; then
    if [ -f "docker-compose-prod.yml" ]; then
        USING_DOCKER=true
        COMPOSE_FILE="docker-compose-prod.yml"
        echo "✓ Detected: Docker environment (docker-compose-prod.yml)"
    elif [ -f "docker-compose.yml" ]; then
        USING_DOCKER=true
        COMPOSE_FILE="docker-compose.yml"
        echo "✓ Detected: Docker environment (docker-compose.yml)"
    fi
else
    echo "✓ Detected: Native PHP environment"
fi
echo ""

# Helper function to run commands
run_cmd() {
    if [ "$USING_DOCKER" = true ]; then
        docker compose -f $COMPOSE_FILE exec -T app "$@"
    else
        "$@"
    fi
}

echo "[1/5] Checking .env database configuration..."
echo "----------------------------------------"
run_cmd cat .env | grep "^DB_"
echo ""

echo "[2/5] Testing database connection..."
run_cmd php artisan tinker --execute="
try {
    \$pdo = \DB::connection()->getPdo();
    echo 'SUCCESS: Connected to database\n';
    echo 'Driver: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . \"\n\";
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "[3/5] Counting users in database..."
run_cmd php artisan tinker --execute="
try {
    \$count = \App\Models\User::count();
    echo \"Total users: \$count\n\";
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "[4/5] Listing all users..."
run_cmd php artisan tinker --execute="
try {
    \$users = \App\Models\User::with('emails')->get();
    foreach (\$users as \$user) {
        echo \"ID: {\$user->id} | {\$user->first_name} {\$user->last_name} | Emails: \" . \$user->emails->count() . \"\n\";
        foreach (\$user->emails as \$email) {
            echo \"  - {\$email->email}\" . (\$email->is_primary ? ' (primary)' : '') . \"\n\";
        }
    }
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "[5/5] Checking specific user ID 4..."
run_cmd php artisan tinker --execute="
try {
    \$user = \App\Models\User::with('emails')->find(4);
    if (\$user) {
        echo \"✓ User 4 EXISTS\n\";
        echo \"  Name: {\$user->first_name} {\$user->last_name}\n\";
        echo \"  Phone: {\$user->phone_number}\n\";
        echo \"  Emails: \" . \$user->emails->count() . \"\n\";
        foreach (\$user->emails as \$email) {
            echo \"    - {\$email->email}\" . (\$email->is_primary ? ' (primary)' : '') . \"\n\";
        }
    } else {
        echo \"✗ User 4 NOT FOUND\n\";
    }
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "=========================================="
echo "   Test Complete"
echo "=========================================="

