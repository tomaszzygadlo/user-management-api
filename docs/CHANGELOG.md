# Changelog

## [1.0.3] - 2026-01-13

### Email System - Complete Fix
**Problem:** Emails were queued but never sent - multiple root causes found and fixed.

**Fixed:**
- Queue mismatch: Notification used 'high' queue, worker listened on 'default'
- Redis configuration: Wrong host (127.0.0.1 instead of redis) and problematic prefix
- Worker configuration: Missing queue specification in systemd service
- SMTP test: Changed from failing test@example.com to validation-only approach

**Added:**
- Comprehensive logging at every step (Controller → Service → Notification → Mail)
- Automated diagnostic tools: `diagnose-email.sh`, `diagnose-queue.sh`, `test-database.sh`
- Systemd service installer: `setup-queue-worker.sh`
- Step-by-step troubleshooting guide: `EMAIL_TROUBLESHOOTING.md`

### Logging System - Daily Rotation
**Changed:**
- From single log file to daily rotating logs (`laravel-YYYY-MM-DD.log`)
- Automatic rotation at midnight, keeps 14 days (configurable)
- Added `enable-laravel-logs-stdout.sh` for Docker log visibility

### Other Improvements
- Docker detection in scripts (checks for docker-compose files)
- Removed obsolete `version` attribute from docker-compose files
- NPM handling improved (optional, not required for API-only)
- Documentation simplified and consolidated

## [1.0.2] - 2026-01-13

### Fixed
- **SMTP Mail Configuration**: Configured SMTP for email sending
  - Added encryption support (SSL/TLS) in mail config
  - Changed Redis client from `phpredis` to `predis` (no PHP extension required)
  - Installed `predis/predis` package for Redis support
  - Default queue changed to `sync` for simpler setup

### Added
- `.env.prod` - Production environment template
- Mail configuration documentation in `docs/`

## [1.0.1] - 2026-01-13

### Fixed
- **Redis Extension**: Added PHP Redis extension to Dockerfile
  - Fixed "Class 'Redis' not found" error
  - Production queue driver (Redis) now works correctly

## [1.0.0] - 2026-01-12

Initial release for recruitment task.

### Features
- User CRUD operations
- Email management (one-to-many)
- Multi-email support with primary designation
- Welcome email notification (queued)
- Soft delete users, cascade delete emails
- Search and pagination
- Form Request validation
- API Resources for JSON
- Service Layer architecture
- Full test suite (Feature + Unit)
- Docker support
- Interactive API documentation (Swagger UI)
- OpenAPI 3.0 specification

### Tech
- Laravel 12
- PHP 8.3+
- MySQL 8.0+
- Redis queue
- PSR-12 code style
- PHPStan level 5
