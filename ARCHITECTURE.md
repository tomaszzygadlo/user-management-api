# Architecture Documentation

## Overview

Standard Laravel clean architecture with service layer pattern. Nothing fancy, just proper separation of concerns.

## Layers

```
Controllers (HTTP) → Services (Business Logic) → Models (Data)
```

- **Controllers**: Thin, handle HTTP requests/responses only
- **Services**: Business logic, transactions, orchestration
- **Models**: Eloquent, relationships, scopes
- **Form Requests**: Validation logic
- **Resources**: JSON transformation

## Design Patterns Used

### Service Layer
Business logic separated from controllers. Makes testing easier and code reusable.

Example:
```php
class UserService {
    public function createUser(array $data): User {
        return DB::transaction(function() use ($data) {
            $user = User::create([...]);
            $this->syncEmails($user, $data['emails']);
            return $user;
        });
    }
}
```

Why: Controllers stay thin, logic is testable without HTTP, transactions in one place.

### Form Requests
Validation + authorization in dedicated classes.

```php
class StoreUserRequest extends FormRequest {
    public function rules(): array { ... }
    public function withValidator($validator): void { ... }
}
```

Why: Keeps controllers clean, validation reusable, custom rules in one place.

### API Resources
JSON transformation layer.

```php
class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'emails' => EmailResource::collection($this->whenLoaded('emails')),
        ];
    }
}
```

Why: Consistent responses, easy versioning, decouples DB from API structure.

### Repository Pattern?
Not used. Eloquent is enough for this scale. Would add unnecessary abstraction.
