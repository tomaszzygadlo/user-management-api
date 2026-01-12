# Contributing

## Setup

```bash
git clone <repo>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

## Pull Requests

1. Fork repo
2. Create branch (`feature/thing`)
3. Make changes
4. Run tests + pint
5. Commit (`feat: add thing`)
6. Push and open PR

## Standards

- PSR-12 code style (`./vendor/bin/pint`)
- PHPStan level 5 (`./vendor/bin/phpstan analyse`)
- Tests for new features
- Type hints everywhere

## Commit Format

Use conventional commits:
- `feat:` new feature
- `fix:` bug fix
- `docs:` documentation
- `test:` tests
- `refactor:` refactoring

Example: `feat: add email verification`
