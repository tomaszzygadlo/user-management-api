# API Documentation

## Overview

**Base URL:** `http://localhost:8000/api`  
**Version:** 1.0.0  
**Content-Type:** `application/json`  
**Authentication:** None (can be added with Laravel Sanctum)

## Response Format

All responses follow a consistent structure:

### Success Response
```json
{
  "data": { ... },
  "meta": { ... }
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 202 | Accepted - Request accepted for processing |
| 204 | No Content - Request successful, no content returned |
| 400 | Bad Request - Invalid request syntax |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error - Server error |

## Endpoints

### Health Check

#### GET /api/health

Check API health status.

**Response: 200 OK**
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00Z",
  "service": "User Management API",
  "version": "1.0.0"
}
```

---

## User Endpoints

### List Users

#### GET /api/users

Retrieve a paginated list of users with their email addresses.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page (max: 100) |
| `search` | string | - | Search by name or phone |
| `sort` | string | created_at | Sort field |
| `order` | string | desc | Sort order (asc/desc) |

**Example Request:**
```bash
GET /api/users?page=1&per_page=10&search=Jan&sort=first_name&order=asc
```

**Response: 200 OK**
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "Jan",
      "last_name": "Kowalski",
      "full_name": "Jan Kowalski",
      "phone_number": "+48123456789",
      "emails": [
        {
          "id": 1,
          "email": "jan@example.com",
          "is_primary": true,
          "is_verified": true,
          "verified_at": "2024-01-15T10:30:00Z",
          "created_at": "2024-01-15T10:30:00Z"
        },
        {
          "id": 2,
          "email": "jan.work@company.com",
          "is_primary": false,
          "is_verified": false,
          "verified_at": null,
          "created_at": "2024-01-15T10:30:00Z"
        }
      ],
      "emails_count": 2,
      "primary_email": "jan@example.com",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "total": 25,
    "per_page": 10,
    "current_page": 1,
    "last_page": 3,
    "from": 1,
    "to": 10
  },
  "links": {
    "first": "http://localhost:8000/api/users?page=1",
    "last": "http://localhost:8000/api/users?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/users?page=2"
  },
  "api_version": "1.0.0"
}
```

---

### Create User

#### POST /api/users

Create a new user with one or more email addresses.

**Request Body:**
```json
{
  "first_name": "Jan",
  "last_name": "Kowalski",
  "phone_number": "+48123456789",
  "emails": [
    {
      "email": "jan@example.com",
      "is_primary": true
    },
    {
      "email": "jan.work@company.com",
      "is_primary": false
    }
  ]
}
```

**Validation Rules:**

| Field | Rules | Description |
|-------|-------|-------------|
| `first_name` | required, string, max:255 | User's first name |
| `last_name` | required, string, max:255 | User's last name |
| `phone_number` | required, string, regex, max:20 | Valid phone number |
| `emails` | required, array, min:1, max:10 | Array of email objects |
| `emails.*.email` | required, email:rfc,dns, distinct | Valid email address |
| `emails.*.is_primary` | boolean | Mark as primary (optional) |

**Notes:**
- At least one email is required
- Only one email can be marked as primary
- If no email is marked primary, first email becomes primary
- Duplicate emails in request are not allowed
- Email addresses must be unique per user

**Response: 201 Created**
```json
{
  "data": {
    "id": 1,
    "first_name": "Jan",
    "last_name": "Kowalski",
    "full_name": "Jan Kowalski",
    "phone_number": "+48123456789",
    "emails": [
      {
        "id": 1,
        "email": "jan@example.com",
        "is_primary": true,
        "is_verified": false,
        "verified_at": null,
        "created_at": "2024-01-15T10:30:00Z"
      },
      {
        "id": 2,
        "email": "jan.work@company.com",
        "is_primary": false,
        "is_verified": false,
        "verified_at": null,
        "created_at": "2024-01-15T10:30:00Z"
      }
    ],
    "emails_count": 2,
    "primary_email": "jan@example.com",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "meta": {
    "version": "1.0.0"
  }
}
```

**Error Response: 422 Unprocessable Entity**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "first_name": ["First name is required."],
    "emails.0.email": ["Invalid email address format."],
    "emails": ["Only one email can be marked as primary."]
  }
}
```

---

### Get Single User

#### GET /api/users/{id}

Retrieve a specific user by ID with all email addresses.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | User ID |

**Example Request:**
```bash
GET /api/users/1
```

**Response: 200 OK**
```json
{
  "data": {
    "id": 1,
    "first_name": "Jan",
    "last_name": "Kowalski",
    "full_name": "Jan Kowalski",
    "phone_number": "+48123456789",
    "emails": [...],
    "emails_count": 2,
    "primary_email": "jan@example.com",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "meta": {
    "version": "1.0.0"
  }
}
```

**Error Response: 404 Not Found**
```json
{
  "message": "User not found"
}
```

---

### Update User

#### PUT /api/users/{id}

Update an existing user and optionally manage their email addresses.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | User ID |

**Request Body (Full Update):**
```json
{
  "first_name": "Jan",
  "last_name": "Nowak",
  "phone_number": "+48987654321",
  "emails": [
    {
      "id": 1,
      "email": "jan.updated@example.com",
      "is_primary": true
    },
    {
      "email": "jan.new@example.com",
      "is_primary": false
    },
    {
      "id": 2,
      "delete": true
    }
  ]
}
```

**Request Body (Partial Update):**
```json
{
  "first_name": "Jan",
  "last_name": "Nowak"
}
```

**Email Management:**
- **Update existing email:** Include `id` field with new email value
- **Add new email:** Omit `id` field
- **Delete email:** Include `id` and `delete: true`

**Validation Rules:**

| Field | Rules | Description |
|-------|-------|-------------|
| `first_name` | sometimes, required, string, max:255 | User's first name |
| `last_name` | sometimes, required, string, max:255 | User's last name |
| `phone_number` | sometimes, required, string, regex, max:20 | Valid phone number |
| `emails` | sometimes, array, min:1, max:10 | Array of email objects |
| `emails.*.id` | sometimes, integer, exists | Existing email ID |
| `emails.*.email` | required, email:rfc,dns, distinct | Valid email address |
| `emails.*.is_primary` | boolean | Mark as primary |
| `emails.*.delete` | boolean | Mark for deletion |

**Notes:**
- All fields are optional (partial update)
- At least one email must remain after deletions
- At least one email must be primary
- Only one email can be primary

**Response: 200 OK**
```json
{
  "data": {
    "id": 1,
    "first_name": "Jan",
    "last_name": "Nowak",
    "full_name": "Jan Nowak",
    "phone_number": "+48987654321",
    "emails": [...],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T11:45:00Z"
  }
}
```

**Error Response: 422 Unprocessable Entity**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "emails": ["At least one email must remain."],
    "emails.0.email": ["Invalid email address format."]
  }
}
```

---

### Delete User

#### DELETE /api/users/{id}

Soft delete a user. The user's emails will be cascade deleted (hard delete).

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | User ID |

**Example Request:**
```bash
DELETE /api/users/1
```

**Response: 204 No Content**

No response body.

**Error Response: 404 Not Found**
```json
{
  "message": "User not found"
}
```

**Notes:**
- User is soft deleted (deleted_at timestamp set)
- All associated emails are permanently deleted (cascade)
- Soft deleted users can be restored if needed

---

### Send Welcome Emails

#### POST /api/users/{id}/welcome

Queue welcome emails to be sent to **all** email addresses associated with the user.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | User ID |

**Example Request:**
```bash
POST /api/users/1/welcome
```

**Response: 202 Accepted**
```json
{
  "message": "Welcome emails queued successfully",
  "user_id": 1
}
```

**Email Content:**
- **Subject:** "Witamy!"
- **Body:** "Witamy użytkownika {first_name} {last_name}"
- **Queue:** High priority
- **Delivery:** Asynchronous via queue worker

**Notes:**
- Email is queued, not sent immediately
- Returns 202 Accepted (not 200 OK) to indicate async processing
- One notification sent to all email addresses simultaneously
- Queue worker must be running to process emails
- Check logs for delivery status

**Error Response: 404 Not Found**
```json
{
  "message": "User not found"
}
```

---

## Common Error Responses

### 400 Bad Request
```json
{
  "message": "Invalid request format"
}
```

### 422 Unprocessable Entity (Validation Failed)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

### 500 Internal Server Error
```json
{
  "message": "Failed to create user",
  "error": "Database connection failed"
}
```

---

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **Default:** 60 requests per minute per IP
- **Headers:**
  - `X-RateLimit-Limit`: Total requests allowed
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Time until limit resets (Unix timestamp)

**Example:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1705318200
```

**Response when rate limit exceeded: 429 Too Many Requests**
```json
{
  "message": "Too Many Requests"
}
```

---

## CORS Configuration

Cross-Origin Resource Sharing (CORS) is enabled for all origins in development.

**Headers:**
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With`

For production, configure specific allowed origins in `config/cors.php`.

---

## Postman Collection

Import the following collection into Postman:

```json
{
  "info": {
    "name": "User Management API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Health Check",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/health"
      }
    },
    {
      "name": "List Users",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/users?page=1&per_page=10"
      }
    },
    {
      "name": "Create User",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/users",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"first_name\": \"Jan\",\n  \"last_name\": \"Kowalski\",\n  \"phone_number\": \"+48123456789\",\n  \"emails\": [\n    {\n      \"email\": \"jan@example.com\",\n      \"is_primary\": true\n    }\n  ]\n}"
        }
      }
    },
    {
      "name": "Get User",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/users/1"
      }
    },
    {
      "name": "Update User",
      "request": {
        "method": "PUT",
        "url": "{{base_url}}/users/1",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"first_name\": \"Jan\",\n  \"last_name\": \"Nowak\"\n}"
        }
      }
    },
    {
      "name": "Delete User",
      "request": {
        "method": "DELETE",
        "url": "{{base_url}}/users/1"
      }
    },
    {
      "name": "Send Welcome Email",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/users/1/welcome"
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/api"
    }
  ]
}
```

---

## cURL Examples

### Create User
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Jan",
    "last_name": "Kowalski",
    "phone_number": "+48123456789",
    "emails": [
      {"email": "jan@example.com", "is_primary": true},
      {"email": "jan.work@company.com", "is_primary": false}
    ]
  }'
```

### List Users with Search
```bash
curl -X GET "http://localhost:8000/api/users?search=Jan&per_page=10&sort=first_name&order=asc" \
  -H "Accept: application/json"
```

### Get User
```bash
curl -X GET http://localhost:8000/api/users/1 \
  -H "Accept: application/json"
```

### Update User
```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Jan",
    "last_name": "Nowak",
    "phone_number": "+48987654321"
  }'
```

### Delete User
```bash
curl -X DELETE http://localhost:8000/api/users/1 \
  -H "Accept: application/json"
```

### Send Welcome Email
```bash
curl -X POST http://localhost:8000/api/users/1/welcome \
  -H "Accept: application/json"
```

---

## Changelog

### Version 1.0.0 (2024-01-15)
- Initial API release
- CRUD operations for users
- Email management
- Welcome email notification
- Search and pagination
- Full test coverage

---

**API Version:** 1.0.0  
**Last Updated:** January 2026  
**Maintained by:** Senior Laravel Developer
