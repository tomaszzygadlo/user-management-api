#!/bin/bash

# Email Diagnostics Script for User Management API
# This script helps diagnose email sending issues in production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if running in Docker or native
USING_DOCKER=false
COMPOSE_FILE=""

# Check for docker-compose files and Docker availability
if command -v docker &> /dev/null && command -v docker compose &> /dev/null; then
    if [ -f "docker-compose-prod.yml" ]; then
        USING_DOCKER=true
        COMPOSE_FILE="docker-compose-prod.yml"
    elif [ -f "docker-compose.yml" ]; then
        USING_DOCKER=true
        COMPOSE_FILE="docker-compose.yml"
    fi
fi

# Helper function to run commands
run_cmd() {
    if [ "$USING_DOCKER" = true ]; then
        docker compose -f $COMPOSE_FILE exec -T app "$@"
    else
        "$@"
    fi
}

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   Email Diagnostics Tool${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

if [ "$USING_DOCKER" = true ]; then
    echo -e "${GREEN}✓ Detected: Docker environment${NC}"
else
    echo -e "${GREEN}✓ Detected: Native PHP environment${NC}"
fi
echo ""

# Step 1: Check User
echo -e "${YELLOW}[1/7] Checking user ID...${NC}"
if [ -z "$1" ]; then
    USER_ID=4
    echo -e "${YELLOW}No user ID provided, using default: $USER_ID${NC}"
else
    USER_ID=$1
fi

# Debug: Check database connection first
DB_TEST=$(run_cmd php artisan tinker --execute="try { echo \DB::connection()->getPdo() ? 'OK' : 'FAIL'; } catch (\Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); }" 2>&1)

if echo "$DB_TEST" | grep -qi "error\|fail"; then
    echo -e "${RED}✗ Database connection error!${NC}"
    echo -e "${RED}  $DB_TEST${NC}"
    echo ""
    echo -e "${YELLOW}Check your .env database configuration:${NC}"
    run_cmd cat .env | grep "^DB_"
    exit 1
fi

# Try to find user with better error handling
USER_CHECK=$(run_cmd php artisan tinker --execute="
try {
    \$user = \App\Models\User::with('emails')->find($USER_ID);
    if (\$user) {
        echo json_encode(\$user);
    } else {
        echo 'null';
    }
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

# Check for errors
if echo "$USER_CHECK" | grep -q "ERROR:"; then
    echo -e "${RED}✗ Error checking user: $USER_CHECK${NC}"
    exit 1
fi

# Check if user exists
if echo "$USER_CHECK" | grep -q '"id"'; then
    echo -e "${GREEN}✓ User $USER_ID exists${NC}"

    EMAIL_COUNT=$(echo "$USER_CHECK" | grep -o '"email"' | wc -l)
    if [ "$EMAIL_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓ User has $EMAIL_COUNT email address(es)${NC}"
        echo "$USER_CHECK" | grep -o '"email":"[^"]*"' | sed 's/"email":"/  - /g' | sed 's/"//g'
    else
        echo -e "${RED}✗ User has NO email addresses!${NC}"
        echo -e "${RED}  This is the problem - user needs at least one email${NC}"
        echo ""
        echo -e "${YELLOW}Add email to user:${NC}"
        echo -e "${YELLOW}  php artisan tinker${NC}"
        echo -e "${YELLOW}  \$user = User::find($USER_ID);${NC}"
        echo -e "${YELLOW}  \$user->emails()->create(['email' => 'user@example.com', 'is_primary' => true]);${NC}"
        exit 1
    fi
elif echo "$USER_CHECK" | grep -q "null"; then
    echo -e "${RED}✗ User $USER_ID not found${NC}"
    echo ""
    echo -e "${YELLOW}Let me check what users exist in the database...${NC}"

    USERS_LIST=$(run_cmd php artisan tinker --execute="
try {
    \$users = \App\Models\User::select('id', 'first_name', 'last_name')->limit(10)->get();
    if (\$users->count() > 0) {
        echo json_encode(\$users);
    } else {
        echo '[]';
    }
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

    if echo "$USERS_LIST" | grep -q "ERROR:"; then
        echo -e "${RED}✗ Error listing users: $USERS_LIST${NC}"
        exit 1
    fi

    if echo "$USERS_LIST" | grep -q '"id"'; then
        echo -e "${GREEN}Available users:${NC}"
        echo "$USERS_LIST" | python3 -m json.tool 2>/dev/null | grep -E '"id"|"first_name"|"last_name"' | sed 's/^/  /' || \
        echo "$USERS_LIST" | grep -o '"id":[0-9]*' | sed 's/"id":/  User ID: /g'
        echo ""
        echo -e "${YELLOW}Usage: $0 <user_id>${NC}"
        echo -e "${YELLOW}Example: $0 1${NC}"
    else
        echo -e "${YELLOW}No users found in database. Create a user first:${NC}"
        echo -e "${YELLOW}  php artisan tinker${NC}"
        echo -e "${YELLOW}  \$user = User::create(['first_name' => 'Test', 'last_name' => 'User', 'phone_number' => '+1234567890']);${NC}"
        echo -e "${YELLOW}  \$user->emails()->create(['email' => 'test@example.com', 'is_primary' => true]);${NC}"
    fi
    exit 1
else
    echo -e "${RED}✗ Unexpected response when checking user${NC}"
    echo -e "${YELLOW}Raw response: $USER_CHECK${NC}"
    exit 1
fi
echo ""

# Step 2: Check SMTP Configuration
echo -e "${YELLOW}[2/7] Checking SMTP configuration...${NC}"
MAIL_CONFIG=$(run_cmd cat .env | grep "^MAIL_" || echo "")
if [ -z "$MAIL_CONFIG" ]; then
    echo -e "${RED}✗ No MAIL_ configuration found in .env${NC}"
else
    echo -e "${GREEN}✓ MAIL configuration found:${NC}"
    echo "$MAIL_CONFIG" | sed 's/MAIL_PASSWORD=.*/MAIL_PASSWORD=***HIDDEN***/' | sed 's/^/  /'
fi
echo ""

# Step 3: Check Queue Configuration
echo -e "${YELLOW}[3/7] Checking queue configuration...${NC}"
QUEUE_CONNECTION=$(run_cmd cat .env | grep "^QUEUE_CONNECTION=" | cut -d'=' -f2)
if [ -z "$QUEUE_CONNECTION" ]; then
    QUEUE_CONNECTION="database"
fi
echo -e "${GREEN}✓ Queue connection: $QUEUE_CONNECTION${NC}"

if [ "$QUEUE_CONNECTION" = "redis" ]; then
    echo "  Testing Redis connection..."
    REDIS_CHECK=$(run_cmd php artisan tinker --execute="try { \Illuminate\Support\Facades\Redis::ping(); echo 'PONG'; } catch (\Exception \$e) { echo 'ERROR: ' . \$e->getMessage(); }" 2>/dev/null || echo "ERROR")

    if echo "$REDIS_CHECK" | grep -q "PONG"; then
        echo -e "${GREEN}  ✓ Redis is working${NC}"
    else
        echo -e "${RED}  ✗ Redis connection failed: $REDIS_CHECK${NC}"
        echo -e "${YELLOW}  Consider changing QUEUE_CONNECTION to 'database' or 'sync'${NC}"
    fi
fi
echo ""

# Step 4: Check Queue Worker
echo -e "${YELLOW}[4/7] Checking if queue worker is running...${NC}"

WORKER_RUNNING=""
WORKER_TYPE=""

# Check systemd service first (if exists)
if systemctl list-units --all 2>/dev/null | grep -q "laravel-queue-worker"; then
    if systemctl is-active --quiet laravel-queue-worker 2>/dev/null; then
        WORKER_RUNNING="systemd"
        WORKER_TYPE="systemd service (laravel-queue-worker)"
    fi
fi

# If not found via systemd, check inside container
if [ -z "$WORKER_RUNNING" ]; then
    if [ "$USING_DOCKER" = true ]; then
        # For Docker, check if queue:work is running in the container
        WORKER_CHECK=$(docker compose -f $COMPOSE_FILE exec -T app ps aux 2>/dev/null | grep "queue:work" | grep -v grep || echo "")
        if [ -n "$WORKER_CHECK" ]; then
            WORKER_RUNNING="docker"
            WORKER_TYPE="inside Docker container"
        fi
    else
        # For native, check ps
        WORKER_CHECK=$(ps aux | grep "queue:work" | grep -v grep || echo "")
        if [ -n "$WORKER_CHECK" ]; then
            WORKER_RUNNING="native"
            WORKER_TYPE="native process"
        fi
    fi
fi

if [ -n "$WORKER_RUNNING" ]; then
    echo -e "${GREEN}✓ Queue worker is running${NC}"
    echo -e "${GREEN}  Type: $WORKER_TYPE${NC}"

    if [ "$WORKER_RUNNING" = "systemd" ]; then
        echo -e "${GREEN}  Status: $(systemctl is-active laravel-queue-worker)${NC}"
        echo -e "${GREEN}  Manage: sudo systemctl status|restart|stop laravel-queue-worker${NC}"
    fi
else
    echo -e "${RED}✗ Queue worker is NOT running${NC}"
    echo -e "${YELLOW}  This is likely the problem!${NC}"

    if [ "$USING_DOCKER" = true ]; then
        echo -e "${YELLOW}  Start it with:${NC}"
        echo -e "${YELLOW}    Option A (systemd): sudo systemctl start laravel-queue-worker${NC}"
        echo -e "${YELLOW}    Option B (manual): docker compose -f $COMPOSE_FILE exec -d app php artisan queue:work --daemon${NC}"
    else
        echo -e "${YELLOW}  Start it with: php artisan queue:work${NC}"
    fi

    if [ "$QUEUE_CONNECTION" != "sync" ]; then
        echo ""
        echo -e "${YELLOW}  Quick fix: Change to sync queue (emails send immediately)${NC}"
        echo -e "${YELLOW}  Add to .env: QUEUE_CONNECTION=sync${NC}"
    fi
fi
echo ""

# Step 5: Check Failed Jobs
echo -e "${YELLOW}[5/7] Checking for failed jobs...${NC}"
if [ "$QUEUE_CONNECTION" = "database" ] || [ "$QUEUE_CONNECTION" = "redis" ]; then
    FAILED_JOBS=$(run_cmd php artisan tinker --execute="echo \DB::table('failed_jobs')->count();" 2>/dev/null || echo "0")

    if [ "$FAILED_JOBS" -gt 0 ]; then
        echo -e "${RED}✗ Found $FAILED_JOBS failed job(s)${NC}"
        echo -e "${YELLOW}  View them with: php artisan queue:failed${NC}"
        echo -e "${YELLOW}  Retry all: php artisan queue:retry all${NC}"

        # Show last failed job
        LAST_FAILED=$(run_cmd php artisan tinker --execute="echo json_encode(\DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first());" 2>/dev/null || echo "{}")
        if echo "$LAST_FAILED" | grep -q "exception"; then
            echo ""
            echo -e "${YELLOW}  Last failure reason:${NC}"
            echo "$LAST_FAILED" | grep -o '"exception":"[^"]*"' | cut -d'"' -f4 | head -c 200 | sed 's/^/    /'
        fi
    else
        echo -e "${GREEN}✓ No failed jobs${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Skipped (sync queue doesn't use job table)${NC}"
fi
echo ""

# Step 6: Check Recent Logs
echo -e "${YELLOW}[6/7] Checking recent logs...${NC}"
if [ "$USING_DOCKER" = true ]; then
    RECENT_LOGS=$(docker compose -f $COMPOSE_FILE exec -T app tail -n 20 storage/logs/laravel.log 2>/dev/null || echo "")
else
    RECENT_LOGS=$(tail -n 20 storage/logs/laravel.log 2>/dev/null || echo "")
fi

if [ -n "$RECENT_LOGS" ]; then
    # Check for errors
    ERROR_COUNT=$(echo "$RECENT_LOGS" | grep -i "ERROR" | wc -l)
    WARNING_COUNT=$(echo "$RECENT_LOGS" | grep -i "WARNING" | wc -l)

    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo -e "${RED}✗ Found $ERROR_COUNT error(s) in recent logs${NC}"
        echo ""
        echo -e "${RED}Recent errors:${NC}"
        echo "$RECENT_LOGS" | grep -i "ERROR" | tail -n 5 | sed 's/^/  /'
    elif [ "$WARNING_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}⚠ Found $WARNING_COUNT warning(s) in recent logs${NC}"
        echo "$RECENT_LOGS" | grep -i "WARNING" | tail -n 3 | sed 's/^/  /'
    else
        echo -e "${GREEN}✓ No errors in recent logs${NC}"
    fi

    # Check for welcome email logs
    if echo "$RECENT_LOGS" | grep -qi "welcome"; then
        echo ""
        echo -e "${BLUE}Welcome email related logs:${NC}"
        echo "$RECENT_LOGS" | grep -i "welcome" | tail -n 3 | sed 's/^/  /'
    fi
else
    echo -e "${YELLOW}⚠ Could not read logs${NC}"
fi
echo ""

# Step 7: Test Email Sending
echo -e "${YELLOW}[7/7] Testing email configuration...${NC}"
echo -e "${YELLOW}Testing if emails can be queued...${NC}"

# Get user's first email for realistic test
USER_EMAIL=$(echo "$USER_CHECK" | grep -o '"email":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$USER_EMAIL" ]; then
    echo -e "${YELLOW}⚠ Skipping email test (user has no email)${NC}"
else
    # Test queueing without actually sending
    TEST_RESULT=$(run_cmd php artisan tinker --execute="
try {
    // Just verify Mail facade works and SMTP config is loaded
    \$mailer = \Illuminate\Support\Facades\Mail::mailer();
    \$config = config('mail');
    if (\$config['mailers']['smtp']['host']) {
        echo 'SUCCESS: SMTP configured (' . \$config['mailers']['smtp']['host'] . ')';
    } else {
        echo 'ERROR: SMTP not configured';
    }
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1 || echo "ERROR: Command failed")

    if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
        echo -e "${GREEN}✓ SMTP configuration is valid${NC}"
        echo -e "${GREEN}  $(echo "$TEST_RESULT" | grep "SUCCESS:" | cut -d':' -f2-)${NC}"
        echo ""
        echo -e "${YELLOW}To test actual email sending:${NC}"
        echo -e "${YELLOW}  1. Start queue worker (see fix above)${NC}"
        echo -e "${YELLOW}  2. Send welcome email: POST /api/users/$USER_ID/welcome${NC}"
        echo -e "${YELLOW}  3. Check mailbox: $USER_EMAIL${NC}"
    else
        echo -e "${RED}✗ SMTP configuration error${NC}"
        echo -e "${RED}Error: $(echo "$TEST_RESULT" | grep "ERROR:" || echo "$TEST_RESULT")${NC}"
    fi
fi
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   Diagnostic Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

if [ "$EMAIL_COUNT" -eq 0 ]; then
    echo -e "${RED}❌ Problem: User has no email addresses${NC}"
    echo -e "${YELLOW}   Fix: Add email to user in database${NC}"
elif [ -z "$WORKER_RUNNING" ] && [ "$QUEUE_CONNECTION" != "sync" ]; then
    echo -e "${RED}❌ Problem: Queue worker is not running${NC}"
    echo -e "${YELLOW}   Fix: Start queue worker with:${NC}"
    if [ "$USING_DOCKER" = true ]; then
        # Check if systemd service exists but is stopped
        if systemctl list-units --all 2>/dev/null | grep -q "laravel-queue-worker"; then
            echo -e "${YELLOW}   sudo systemctl start laravel-queue-worker${NC}"
        else
            echo -e "${YELLOW}   docker compose -f $COMPOSE_FILE exec -d app php artisan queue:work --daemon${NC}"
            echo -e "${YELLOW}   Or install permanent service: sudo ./scripts/setup-queue-worker.sh${NC}"
        fi
    else
        echo -e "${YELLOW}   php artisan queue:work${NC}"
    fi
elif echo "$TEST_RESULT" | grep -q "ERROR"; then
    echo -e "${RED}❌ Problem: SMTP configuration error${NC}"
    echo -e "${YELLOW}   Fix: Check MAIL_ settings in .env${NC}"
    echo -e "${YELLOW}   See: docs/MAIL_CONFIGURATION.md${NC}"
elif [ "$FAILED_JOBS" -gt 0 ]; then
    echo -e "${YELLOW}⚠ Warning: There are failed jobs${NC}"
    echo -e "${YELLOW}   Fix: Review and retry with: php artisan queue:retry all${NC}"
elif [ -n "$WORKER_RUNNING" ]; then
    echo -e "${GREEN}✅ All checks passed!${NC}"
    echo -e "${GREEN}   Queue worker is running ($WORKER_TYPE)${NC}"
    echo -e "${GREEN}   Email system is ready!${NC}"
    echo ""
    echo -e "${YELLOW}To send welcome email:${NC}"
    if [ "$USING_DOCKER" = true ]; then
        echo -e "${YELLOW}   curl -X POST http://localhost/api/users/$USER_ID/welcome${NC}"
        echo -e "${YELLOW}   Or via Tinker:${NC}"
        echo -e "${YELLOW}   docker compose -f $COMPOSE_FILE exec app php artisan tinker --execute=\"${NC}"
        echo -e "${YELLOW}     User::find($USER_ID)->notify(new \App\Notifications\WelcomeUserNotification(User::find($USER_ID)));\"${NC}"
    else
        echo -e "${YELLOW}   curl -X POST http://localhost/api/users/$USER_ID/welcome${NC}"
    fi
    echo ""
    echo -e "${YELLOW}Check mailbox: $USER_EMAIL${NC}"
else
    echo -e "${GREEN}✅ All checks passed!${NC}"
    echo -e "${GREEN}   If emails still not arriving, check:${NC}"
    echo -e "${GREEN}   1. Spam folder${NC}"
    echo -e "${GREEN}   2. SMTP server logs${NC}"
    echo -e "${GREEN}   3. Email provider settings${NC}"
fi
echo ""

echo -e "${BLUE}For detailed troubleshooting guide, see: docs/EMAIL_TROUBLESHOOTING.md${NC}"

