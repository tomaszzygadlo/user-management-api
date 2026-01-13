#!/bin/bash

# Queue Worker Setup Script
# Creates systemd service for Laravel queue worker with Docker

echo "=========================================="
echo "   Queue Worker Setup (Docker)"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (use sudo)"
    exit 1
fi

# Get project directory
PROJECT_DIR="/var/www/user-management-api"
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: Project directory not found: $PROJECT_DIR"
    exit 1
fi

# Get docker compose file
if [ -f "$PROJECT_DIR/docker-compose-prod.yml" ]; then
    COMPOSE_FILE="docker-compose-prod.yml"
elif [ -f "$PROJECT_DIR/docker-compose.yml" ]; then
    COMPOSE_FILE="docker-compose.yml"
else
    echo "Error: No docker-compose file found"
    exit 1
fi

echo "Creating systemd service for queue worker..."
echo "Project: $PROJECT_DIR"
echo "Compose: $COMPOSE_FILE"
echo ""

# Create systemd service file
cat > /etc/systemd/system/laravel-queue-worker.service << 'EOF'
[Unit]
Description=Laravel Queue Worker (Docker)
After=docker.service
Requires=docker.service

[Service]
Type=simple
User=root
WorkingDirectory=/var/www/user-management-api
Restart=always
RestartSec=10

# Start queue worker in Docker
# Explicitly specify redis connection and default queue
ExecStart=/usr/bin/docker compose -f docker-compose-prod.yml exec -T app php artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600

# Stop gracefully
ExecStop=/usr/bin/docker compose -f docker-compose-prod.yml exec -T app php artisan queue:restart

# Restart on failure
StartLimitInterval=180
StartLimitBurst=30

[Install]
WantedBy=multi-user.target
EOF

echo "✓ Service file created: /etc/systemd/system/laravel-queue-worker.service"
echo ""

# Reload systemd
echo "Reloading systemd..."
systemctl daemon-reload
echo "✓ Done"
echo ""

# Enable service
echo "Enabling service (autostart on boot)..."
systemctl enable laravel-queue-worker.service
echo "✓ Done"
echo ""

# Start service
echo "Starting service..."
systemctl start laravel-queue-worker.service
echo "✓ Done"
echo ""

# Check status
echo "Checking status..."
systemctl status laravel-queue-worker.service --no-pager
echo ""

echo "=========================================="
echo "   Setup Complete!"
echo "=========================================="
echo ""
echo "Useful commands:"
echo "  sudo systemctl status laravel-queue-worker    # Check status"
echo "  sudo systemctl restart laravel-queue-worker   # Restart worker"
echo "  sudo systemctl stop laravel-queue-worker      # Stop worker"
echo "  sudo journalctl -u laravel-queue-worker -f    # View logs"
echo ""

