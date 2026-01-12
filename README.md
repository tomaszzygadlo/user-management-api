# User Management API

Simple REST API for managing users with multiple emails. Built with Laravel 12.

## What it does

- CRUD operations for users
- Each user can have multiple emails (one marked as primary)
- Welcome email sent to all user's addresses (queued)
- Soft delete users (cascade delete emails)
- Search & pagination

## Tech Stack

- Laravel 12
- PHP 8.3+
- MySQL 8.0+
- Redis (optional, for queue)

## Project Structure

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/UserController.php
│   ├── Requests/StoreUserRequest.php, UpdateUserRequest.php
│   └── Resources/UserResource.php, EmailResource.php
├── Models/User.php, Email.php
├── Services/UserService.php
└── Notifications/WelcomeUserNotification.php

database/migrations/
├── create_users_table
├── create_emails_table
└── create_jobs_table

tests/
├── Feature/UserCrudTest.php, WelcomeEmailTest.php
└── Unit/UserServiceTest.php
```

## Quick Setup

## Quick Setup

```bash
# Install
composer install
cp .env.example .env
php artisan key:generate

# Configure DB in .env then:
php artisan migrate --seed

# Run
php artisan serve
php artisan queue:work  # separate terminal
```

API available at: `http://localhost:8000/api`

## Docker Setup (alternative)

## Docker Setup (alternative)

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan queue:work
```

**Access points:**
- API: `http://localhost:8000/api`
- Swagger UI: `http://localhost:8000/api/documentation`
- Mailpit (email testing): `http://localhost:8025`

## API Documentation

Interactive API documentation is available via **Swagger UI**:

- 🔗 **Swagger UI**: `http://localhost:8000/api/documentation`
- 📄 **OpenAPI JSON**: `http://localhost:8000/docs/api-docs.json`
- 🏠 **Home Page**: `http://localhost:8000/` - quick overview with links

The Swagger UI provides:
- ✅ Interactive API testing directly from your browser
- 📋 Complete request/response examples
- 🔍 Schema definitions
- 🚀 Try-it-out functionality

## API Endpoints

## API Endpoints

```
GET    /api/health                 - health check
GET    /api/users                  - list users (pagination, search)
POST   /api/users                  - create user with emails
GET    /api/users/{id}             - get user details
PUT    /api/users/{id}             - update user/emails
DELETE /api/users/{id}             - soft delete user
POST   /api/users/{id}/welcome     - send welcome email (queued)
```

## Example Requests
## Example Requests

### Create user
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jan",
    "last_name": "Kowalski",
    "phone_number": "+48123456789",
    "emails": [
      {"email": "jan@example.com", "is_primary": true}
    ]
  }'
```

### Update user
```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"first_name": "Jan", "last_name": "Nowak"}'
```

### Send welcome email
```bash
curl -X POST http://localhost:8000/api/users/1/welcome
```

See `API.md` for complete documentation.

## Testing
## Testing

```bash
php artisan test                    # all tests
php artisan test --coverage         # with coverage
php artisan test --testsuite=Feature
```

Tests:
- `tests/Feature/UserCrudTest.php` - API endpoints
- `tests/Feature/WelcomeEmailTest.php` - email sending
- `tests/Unit/UserServiceTest.php` - service layer

## Database Schema

## Database Schema

```sql
users:
  - id, first_name, last_name, phone_number
  - timestamps, soft_deletes
  - indexes on name, phone

emails:
  - id, user_id (FK), email, is_primary
  - verified_at, timestamps
  - unique(user_id, email)
  - cascade delete on user deletion
```

## Configuration Notes

**Queue**: Use `sync` for dev, `redis` for prod. Start worker: `php artisan queue:work`

**Mail**: Use `log` driver for dev, SMTP for prod. Check `.env.example` for config.

**Validation**: 
- Phone: regex validated
- Email: RFC + DNS validation
- Only one primary email per user
- Email unique per user

## Troubleshooting

**Quick Fix**: Run the permission fix script:
```bash
# Linux/macOS
bash fix-permissions.sh

# Windows PowerShell
.\fix-permissions.ps1
```

**Permission denied (storage/logs)**: 
```bash
# Docker
docker compose exec app chmod -R 777 storage/logs
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose restart app

# Local
chmod -R 775 storage bootstrap/cache
```

**Rate limiter [api] is not defined**: Run `php artisan config:clear` and restart

**Missing APP_KEY**: Run `php artisan key:generate` (or in Docker: `docker compose exec app php artisan key:generate`)

**DB connection failed**: Check `.env` credentials, run `php artisan config:clear`

**Queue not processing**: Ensure `php artisan queue:work` is running

**Port in use**: Run `php artisan serve --port=8001`

See `INSTALL.md` for detailed troubleshooting guide.

## Architecture Notes

- Service Layer for business logic
- Form Requests for validation
- API Resources for JSON transformation
- Queued notifications for emails
- Soft deletes with cascade

See `ARCHITECTURE.md` for detailed design decisions.

## Code Quality

```bash
./vendor/bin/pint              # fix code style
./vendor/bin/phpstan analyse   # static analysis
php artisan l5-swagger:generate # regenerate API documentation
```

**Note**: Swagger documentation is automatically generated from OpenAPI annotations in controllers.

## License

MIT
