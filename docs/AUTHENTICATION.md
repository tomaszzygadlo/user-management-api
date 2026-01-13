# Autentykacja API - Laravel Sanctum

## Wprowadzenie

Aplikacja User Management API używa Laravel Sanctum do autentykacji. Sanctum zapewnia prosty system tokenów API dla aplikacji SPA, mobilnych i prostych API opartych na tokenach.

## Endpoints Autentykacji

### 1. Rejestracja Użytkownika

**Endpoint:** `POST /api/register`

**Request:**
```json
{
    "first_name": "Jan",
    "last_name": "Kowalski",
    "phone_number": "+48123456789",
    "email": "jan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201 Created):**
```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "first_name": "Jan",
        "last_name": "Kowalski",
        "phone_number": "+48123456789",
        "email": "jan@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-13T10:00:00.000000Z",
        "updated_at": "2026-01-13T10:00:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "token_type": "Bearer"
}
```

### 2. Logowanie

**Endpoint:** `POST /api/login`

**Request:**
```json
{
    "email": "jan@example.com",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "first_name": "Jan",
        "last_name": "Kowalski",
        "phone_number": "+48123456789",
        "email": "jan@example.com",
        "created_at": "2026-01-13T10:00:00.000000Z",
        "updated_at": "2026-01-13T10:00:00.000000Z"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz1234567890",
    "token_type": "Bearer"
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": [
            "The provided credentials are incorrect."
        ]
    }
}
```

### 3. Pobierz Dane Zalogowanego Użytkownika

**Endpoint:** `GET /api/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "user": {
        "id": 1,
        "first_name": "Jan",
        "last_name": "Kowalski",
        "phone_number": "+48123456789",
        "email": "jan@example.com",
        "created_at": "2026-01-13T10:00:00.000000Z",
        "updated_at": "2026-01-13T10:00:00.000000Z",
        "emails": [
            {
                "id": 1,
                "user_id": 1,
                "email": "jan.primary@example.com",
                "is_primary": true,
                "verified_at": null,
                "created_at": "2026-01-13T10:00:00.000000Z",
                "updated_at": "2026-01-13T10:00:00.000000Z"
            }
        ]
    }
}
```

### 4. Wylogowanie

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "message": "Logged out successfully"
}
```

## Użycie Tokenu

Po zalogowaniu lub rejestracji otrzymujesz token, który należy dołączać do każdego chronionego żądania w nagłówku `Authorization`:

```bash
Authorization: Bearer {token}
```

### Przykład z cURL

```bash
# Rejestracja
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Jan",
    "last_name": "Kowalski",
    "phone_number": "+48123456789",
    "email": "jan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Logowanie
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jan@example.com",
    "password": "password123"
  }'

# Użycie tokenu
curl -X GET http://localhost:8000/api/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"

# Wylogowanie
curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

### Przykład z JavaScript (Fetch API)

```javascript
// Rejestracja
const registerResponse = await fetch('http://localhost:8000/api/register', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        first_name: 'Jan',
        last_name: 'Kowalski',
        phone_number: '+48123456789',
        email: 'jan@example.com',
        password: 'password123',
        password_confirmation: 'password123'
    })
});

const { token } = await registerResponse.json();

// Użycie tokenu
const usersResponse = await fetch('http://localhost:8000/api/users', {
    headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
    }
});

const users = await usersResponse.json();
```

## Chronione Endpointy

Wszystkie poniższe endpointy wymagają tokenu autentykacji:

- `GET /api/me` - Pobierz dane zalogowanego użytkownika
- `POST /api/logout` - Wylogowanie
- `GET /api/users` - Lista użytkowników
- `POST /api/users` - Utwórz użytkownika
- `GET /api/users/{id}` - Pobierz użytkownika
- `PUT /api/users/{id}` - Zaktualizuj użytkownika
- `DELETE /api/users/{id}` - Usuń użytkownika
- `POST /api/users/{id}/welcome` - Wyślij email powitalny
- `GET /api/users/{userId}/emails` - Lista emaili użytkownika
- `POST /api/users/{userId}/emails` - Dodaj email do użytkownika
- `GET /api/users/{userId}/emails/{emailId}` - Pobierz email
- `PUT /api/users/{userId}/emails/{emailId}` - Zaktualizuj email
- `DELETE /api/users/{userId}/emails/{emailId}` - Usuń email

## Publiczne Endpointy

Poniższe endpointy nie wymagają autentykacji:

- `GET /api/health` - Status API
- `POST /api/register` - Rejestracja
- `POST /api/login` - Logowanie

## Konfiguracja

### Czas Wygaśnięcia Tokenu

Domyślnie tokeny nie wygasają. Możesz skonfigurować czas wygaśnięcia w pliku `config/sanctum.php`:

```php
'expiration' => 525600, // 1 rok w minutach
```

### Stateful Domains

Dla aplikacji SPA możesz skonfigurować domeny, które będą używać sesji zamiast tokenów:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),
```

## Bezpieczeństwo

1. **HTTPS**: W środowisku produkcyjnym zawsze używaj HTTPS
2. **Rate Limiting**: API ma domyślnie limit 60 żądań/minutę
3. **Token Storage**: Nigdy nie przechowuj tokenów w localStorage, używaj httpOnly cookies dla SPA
4. **Token Revocation**: Tokeny można unieważnić przez wylogowanie lub usunięcie z bazy danych

## Obsługa Błędów

### 401 Unauthorized
Brak tokenu lub token nieprawidłowy:
```json
{
    "message": "Unauthenticated."
}
```

### 422 Validation Error
Błędy walidacji:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### 429 Too Many Requests
Przekroczono limit żądań:
```json
{
    "message": "Too Many Attempts."
}
```

## Testowanie

### Testy Jednostkowe

```php
public function test_user_can_register()
{
    $response = $this->postJson('/api/register', [
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'jan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user',
            'token',
            'token_type'
        ]);
}

public function test_user_can_login()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user',
            'token',
            'token_type'
        ]);
}
```

## Swagger UI

Dokumentacja interaktywna dostępna jest pod adresem:
- Development: http://localhost:8000/api/documentation
- Production: https://yourdomain.com/api/documentation

W Swagger UI możesz:
1. Kliknąć przycisk "Authorize" u góry
2. Wpisać token w formacie: `Bearer {token}`
3. Kliknąć "Authorize"
4. Testować chronione endpointy

