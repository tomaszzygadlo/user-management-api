# Hotfix - Brak tabeli cache na produkcji

## Problem
Po wdrożeniu na produkcję endpoint `/api/health` zwraca błąd:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'user_management.cache' doesn't exist
```

## Przyczyna
Aplikacja na produkcji używa cache drivera `database`, ale brakuje migracji tworzących tabele `cache` i `cache_locks`.

## Rozwiązanie

### Opcja 1: Aktualizacja przez SSH (zalecane)

1. **Połącz się z serwerem:**
   ```bash
   ssh user@nextstep.chat
   ```

2. **Przejdź do katalogu aplikacji:**
   ```bash
   cd /var/www/nextstep
   # lub inna ścieżka gdzie zainstalowana jest aplikacja
   ```

3. **Pobierz najnowsze zmiany:**
   ```bash
   git pull origin master
   ```

4. **Uruchom migracje:**
   ```bash
   php artisan migrate --force
   ```

5. **Wyczyść cache (opcjonalnie):**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

6. **Zrestartuj aplikację (jeśli używasz Docker):**
   ```bash
   docker compose -f docker-compose-prod.yml restart
   ```

7. **Sprawdź czy działa:**
   ```bash
   curl https://nextstep.chat/api/health
   ```

### Opcja 2: Ręczne utworzenie tabel w bazie danych

Jeśli nie masz dostępu do artisan, możesz ręcznie utworzyć tabele w MySQL:

```sql
USE user_management;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Opcja 3: Zmiana cache drivera na plik (tymczasowe)

Jeśli nie możesz natychmiast uruchomić migracji, zmień cache driver w pliku `.env` na produkcji:

```bash
# W /var/www/nextstep/.env zmień:
CACHE_DRIVER=database
# na:
CACHE_DRIVER=file
```

Następnie wyczyść cache konfiguracji:
```bash
php artisan config:clear
```

**Uwaga:** To jest rozwiązanie tymczasowe. Dla lepszej wydajności zaleca się użycie cache database lub redis.

## Weryfikacja

Po wdrożeniu poprawki sprawdź:

1. **Health endpoint:**
   ```bash
   curl https://nextstep.chat/api/health
   ```
   
   Oczekiwana odpowiedź:
   ```json
   {
     "status": "healthy",
     "timestamp": "2026-01-13T15:53:07+00:00"
   }
   ```

2. **Sprawdź tabele w bazie:**
   ```bash
   mysql -u nextstep -p -e "USE user_management; SHOW TABLES LIKE 'cache%';"
   ```
   
   Powinny pokazać się tabele:
   - cache
   - cache_locks

3. **Sprawdź logi:**
   ```bash
   tail -f storage/logs/laravel.log
   # lub dla Docker:
   docker compose -f docker-compose-prod.yml logs -f app
   ```

## Zapobieganie w przyszłości

1. Zawsze uruchamiaj `php artisan migrate --force` podczas wdrażania
2. Dodaj migracje do procesu CI/CD
3. Testuj na środowisku staging przed wdrożeniem na produkcję
4. Dokumentuj wszystkie zależności środowiskowe

## Pliki zmienione w tym hotfixie

- `database/migrations/2026_01_13_155307_create_cache_table.php` (nowy plik)

## Commit
```
commit: ee75bc7
message: Add cache table migration for production database driver
```

