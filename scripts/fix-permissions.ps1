# Fix permissions script for User Management API (Windows/PowerShell)
# Run this script if you encounter permission issues

Write-Host "Fixing permissions for User Management API..." -ForegroundColor Green

# Check if Docker is available
$dockerAvailable = Get-Command docker -ErrorAction SilentlyContinue

if ($dockerAvailable -and (Test-Path "docker-compose.yml")) {
    Write-Host "Docker environment detected" -ForegroundColor Cyan

    Write-Host "Setting permissions for storage and bootstrap/cache..."
    docker compose exec app chmod -R 775 storage bootstrap/cache
    docker compose exec app chown -R www-data:www-data storage bootstrap/cache

    Write-Host "Setting permissions for logs directory..."
    docker compose exec app mkdir -p storage/logs
    docker compose exec app chmod -R 777 storage/logs
    docker compose exec app chown -R www-data:www-data storage/logs

    Write-Host "Clearing Laravel caches..."
    docker compose exec app php artisan config:clear
    docker compose exec app php artisan cache:clear
    docker compose exec app php artisan route:clear
    docker compose exec app php artisan view:clear

    Write-Host "Restarting containers..."
    docker compose restart app nginx

    Write-Host "`n✅ Permissions fixed! Docker containers restarted." -ForegroundColor Green

} else {
    Write-Host "Local environment detected" -ForegroundColor Cyan

    # Check if PHP is available
    $phpAvailable = Get-Command php -ErrorAction SilentlyContinue

    if (-not $phpAvailable) {
        Write-Host "❌ PHP not found. Please install PHP or start Docker." -ForegroundColor Red
        exit 1
    }

    Write-Host "Creating logs directory if it doesn't exist..."
    New-Item -ItemType Directory -Force -Path "storage\logs" | Out-Null

    Write-Host "Note: On Windows, file permissions are handled differently."
    Write-Host "If you're using WSL, run the Linux version of this script instead."

    Write-Host "Clearing Laravel caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear

    Write-Host "`n✅ Caches cleared!" -ForegroundColor Green
    Write-Host "If you're using Docker, make sure Docker Desktop is running and try again." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "If you still have issues, see INSTALL.md troubleshooting section." -ForegroundColor Cyan

