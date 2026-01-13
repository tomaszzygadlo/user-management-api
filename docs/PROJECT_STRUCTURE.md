# Project Structure

## Overview

This document describes the organization of the User Management API project.

## Root Directory

```
user-management-api/
├── docs/                    # 📚 All project documentation
├── scripts/                 # 🔧 Utility scripts
├── app/                     # 🎯 Application code
├── config/                  # ⚙️ Configuration files
├── database/                # 🗄️ Database files
├── routes/                  # 🛣️ Route definitions
├── tests/                   # 🧪 Test files
├── docker/                  # 🐳 Docker configuration
├── storage/                 # 💾 Application storage
├── public/                  # 🌐 Public web files
├── resources/               # 🎨 Views, assets
└── vendor/                  # 📦 Dependencies
```

## Documentation (`docs/`)

All project documentation is organized here:

- **API.md** - Complete API endpoints reference
- **ARCHITECTURE.md** - Project structure and design decisions
- **CHANGELOG.md** - Version history and changes
- **CONTRIBUTING.md** - Guidelines for contributors
- **DEPLOYMENT.md** - Production deployment guide
- **INSTALL.md** - Detailed installation instructions
- **PROJECT_STRUCTURE.md** - This file
- **RECRUITMENT_RESPONSE.md** - Original recruitment task response

## Scripts (`scripts/`)

Utility scripts for maintenance and deployment:

- **fix-permissions.sh** - Fix Laravel storage/cache permissions (Linux/macOS)
- **fix-permissions.ps1** - Fix Laravel storage/cache permissions (Windows)
- **deploy.sh** - Deployment script for production
- **nextstep-worker.service** - Systemd service for queue worker
- **nginx_nextstep.conf** - Nginx configuration example

## Application (`app/`)

Laravel application code:

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── UserController.php    # API endpoints
│   ├── Requests/
│   │   ├── StoreUserRequest.php      # Validation for creating users
│   │   └── UpdateUserRequest.php     # Validation for updating users
│   └── Resources/
│       ├── UserResource.php          # JSON transformation for users
│       └── EmailResource.php         # JSON transformation for emails
├── Models/
│   ├── User.php                      # User model
│   └── Email.php                     # Email model
├── Services/
│   └── UserService.php               # Business logic
├── Notifications/
│   └── WelcomeUserNotification.php   # Welcome email
└── Providers/
    └── AppServiceProvider.php        # Service providers
```

## Database (`database/`)

```
database/
├── migrations/
│   ├── 2024_01_01_000001_create_users_table.php
│   ├── 2024_01_01_000002_create_emails_table.php
│   └── 2024_01_01_000003_create_jobs_table.php
├── factories/
│   ├── UserFactory.php               # User test data factory
│   └── EmailFactory.php              # Email test data factory
├── seeders/
│   └── DatabaseSeeder.php            # Database seeding
├── database.sqlite                   # SQLite database (dev)
└── testing.sqlite                    # SQLite database (testing)
```

## Tests (`tests/`)

```
tests/
├── Feature/
│   ├── UserCrudTest.php              # API endpoints tests
│   └── WelcomeEmailTest.php          # Email sending tests
├── Unit/
│   └── UserServiceTest.php           # Service layer tests
├── TestCase.php                      # Base test case
└── CreatesApplication.php            # Application creation for tests
```

## Docker (`docker/`)

```
docker/
└── nginx/
    └── default.conf                  # Nginx configuration for Docker
```

## Configuration Files (Root)

- **composer.json** - PHP dependencies
- **package.json** - Node.js dependencies (if any)
- **phpunit.xml** - PHPUnit configuration
- **phpstan.neon** - PHPStan static analysis config
- **pint.json** - Laravel Pint code style config
- **Dockerfile** - Docker image definition
- **docker-compose.yml** - Docker Compose for development
- **docker-compose-prod.yml** - Docker Compose for production
- **.env.example** - Environment variables template
- **artisan** - Laravel command-line interface
- **README.md** - Main project documentation

## Storage (`storage/`)

```
storage/
├── app/
│   ├── public/                       # Publicly accessible files
│   └── private/                      # Private files
├── framework/
│   ├── cache/                        # Framework cache files
│   ├── sessions/                     # Session files
│   └── views/                        # Compiled Blade views
├── logs/                             # Application logs
└── api-docs/
    └── api-docs.json                 # Generated OpenAPI/Swagger docs
```

## Key Design Decisions

1. **Separation of Concerns**: Business logic in `UserService.php`, validation in Form Requests
2. **API Resources**: JSON transformation separated from models
3. **Documentation Centralized**: All docs in `docs/` folder for easy access
4. **Scripts Organized**: Utility scripts in `scripts/` folder
5. **Docker Ready**: Complete Docker setup for development and production
6. **Test Coverage**: Feature and unit tests organized by type

## Quick Navigation

- Need to add a feature? Start in `app/Http/Controllers/Api/`
- Need to change DB structure? Check `database/migrations/`
- Need to deploy? See `docs/DEPLOYMENT.md`
- Need to fix permissions? Run scripts in `scripts/`
- Need API reference? See `docs/API.md`

