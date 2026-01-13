# Manual Testing Guide - Postman

Complete guide for testing User Management API using Postman.

---

## 🔐 Postman Sign-In

### Do you need to sign in?

**NO** - You can use Postman without signing in!

If Postman asks you to sign in:
1. Click **"Skip and continue without an account"** (bottom of the window)
2. Or click **"X"** to close the sign-in dialog
3. You can use all features for testing API without account

### Alternative: Use Postman Desktop

If browser version requires sign-in:
1. Download Postman Desktop: https://www.postman.com/downloads/
2. Install and open Postman Desktop
3. Use offline mode (no sign-in required)

### Alternative Testing Tools

If you prefer not to use Postman at all:

**Option 1: Use Swagger UI (easiest)**
- Open: https://nextstep.chat/api/documentation
- Test all endpoints directly in browser
- Built-in authorization
- No installation needed

**Option 2: Use cURL (command line)**
- See examples in this guide (each test has cURL equivalent)
- Works on any system

**Option 3: Use other tools**
- Insomnia (free, no login required)
- HTTPie (command line)
- Thunder Client (VS Code extension)

---

## 📦 Import Postman Collection

### Step 1: Import Files

1. **Open Postman**
2. **Click "Import"** (button in top-left corner)
3. **Drag and drop** or select files:
   - `postman/User-Management-API.postman_collection.json`
   - `postman/Production.postman_environment.json`
   - `postman/Local.postman_environment.json`
4. **Click "Import"**

### Step 2: Select Environment

In top-right corner, select:
- **Production** - for testing on https://nextstep.chat
- **Local** - for local testing (http://localhost:8000)

---

## 🎯 Test Scenarios

### Scenario 1: Basic User Flow (Happy Path)

**Goal:** Test complete user lifecycle

#### Test 1.1: Health Check
```
Request: GET /api/health
Expected result: 200 OK
```

**Steps:**
1. Open folder **"Health & Info"**
2. Click **"Health Check"**
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Response contains: `"status": "ok"`
- ✅ Response contains timestamp
- ✅ Postman Tests: 2/2 passed

---

#### Test 1.2: Register New User
```
Request: POST /api/register
Expected result: 201 Created + token
```

**Steps:**
1. Open folder **"Authentication"**
2. Click **"Register User"**
3. Check body - email is unique (uses `{{$timestamp}}`)
4. Click **"Send"**

**Expected results:**
- ✅ Status: `201 Created`
- ✅ Response contains `token`
- ✅ Response contains `user` with ID
- ✅ Token automatically saved in `{{token}}` variable
- ✅ Postman Tests: 3/3 passed

**Sample response:**
```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "first_name": "Jan",
        "last_name": "Kowalski",
        "email": "jan.kowalski@example.com"
    },
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ",
    "token_type": "Bearer"
}
```

---

#### Test 1.3: Get Authenticated User Data
```
Request: GET /api/me
Expected result: 200 OK + user data
```

**Steps:**
1. Click **"Get Current User"**
2. Check **"Authorization"** tab has token (Auto-Bearer)
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Response contains user data
- ✅ User ID matches registration
- ✅ Postman Tests: 2/2 passed

---

#### Test 1.4: List Users
```
Request: GET /api/users
Expected result: 200 OK + paginated list
```

**Steps:**
1. Open folder **"Users"**
2. Click **"List Users"**
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Response contains `data` (array)
- ✅ Response contains `meta` (pagination)
- ✅ At least 1 user in list
- ✅ Postman Tests: 3/3 passed

---

#### Test 1.5: Create User with Emails
```
Request: POST /api/users
Expected result: 201 Created + user with emails
```

**Steps:**
1. Click **"Create User"**
2. Check body - contains 2 emails (one primary)
3. Click **"Send"**

**Expected results:**
- ✅ Status: `201 Created`
- ✅ User has ID (saved in `{{user_id}}`)
- ✅ User has 2 emails
- ✅ One email has `is_primary: true`
- ✅ Postman Tests: 3/3 passed

---

#### Test 1.6: Get User Details
```
Request: GET /api/users/{{user_id}}
Expected result: 200 OK + full user data
```

**Steps:**
1. Click **"Get User by ID"**
2. Check URL - uses `{{user_id}}` from previous test
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ User data matches created user
- ✅ Emails list included in response
- ✅ Postman Tests: 1/1 passed

---

#### Test 1.7: Update User
```
Request: PUT /api/users/{{user_id}}
Expected result: 200 OK + updated user
```

**Steps:**
1. Click **"Update User"**
2. Modify data in body (optional)
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Data updated
- ✅ Postman Tests: 1/1 passed

---

#### Test 1.8: Send Welcome Email
```
Request: POST /api/users/{{user_id}}/welcome
Expected result: 202 Accepted
```

**Steps:**
1. Click **"Send Welcome Email"**
2. Click **"Send"**

**Expected results:**
- ✅ Status: `202 Accepted`
- ✅ Message: "Welcome emails queued successfully"
- ✅ Postman Tests: 2/2 passed

---

#### Test 1.9: Delete User
```
Request: DELETE /api/users/{{user_id}}
Expected result: 204 No Content
```

**Steps:**
1. Click **"Delete User"**
2. Click **"Send"**

**Expected results:**
- ✅ Status: `204 No Content`
- ✅ No body in response
- ✅ User soft-deleted
- ✅ Postman Tests: 1/1 passed

---

#### Test 1.10: Logout
```
Request: POST /api/logout
Expected result: 200 OK
```

**Steps:**
1. Return to folder **"Authentication"**
2. Click **"Logout"**
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Message: "Logged out successfully"
- ✅ Token invalidated
- ✅ Postman Tests: 2/2 passed

---

### Scenario 2: Email Management

**Goal:** Test CRUD operations on emails

**Preparation:**
1. Run Test 1.2 (Register User) - get token
2. Run Test 1.5 (Create User) - get user_id

#### Test 2.1: List User Emails
```
Request: GET /api/users/{{user_id}}/emails
Expected result: 200 OK + emails list
```

**Steps:**
1. Open folder **"User Emails"**
2. Click **"List User Emails"**
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Array with emails (2 emails from Test 1.5)
- ✅ First email_id saved in `{{email_id}}`
- ✅ Postman Tests: 2/2 passed

---

#### Test 2.2: Add New Email
```
Request: POST /api/users/{{user_id}}/emails
Expected result: 201 Created + new email
```

**Steps:**
1. Click **"Add Email to User"**
2. Email in body is unique (uses `{{$timestamp}}`)
3. Click **"Send"**

**Expected results:**
- ✅ Status: `201 Created`
- ✅ New email has ID
- ✅ Email_id saved in variable
- ✅ Postman Tests: 2/2 passed

---

#### Test 2.3: Get Email Details
```
Request: GET /api/users/{{user_id}}/emails/{{email_id}}
Expected result: 200 OK + email details
```

**Steps:**
1. Click **"Get Email by ID"**
2. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Email has all fields (id, email, is_primary)
- ✅ Postman Tests: 1/1 passed

---

#### Test 2.4: Update Email
```
Request: PUT /api/users/{{user_id}}/emails/{{email_id}}
Expected result: 200 OK + updated email
```

**Steps:**
1. Click **"Update Email"**
2. Change email address in body
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Email updated
- ✅ Postman Tests: 1/1 passed

---

#### Test 2.5: Delete Email
```
Request: DELETE /api/users/{{user_id}}/emails/{{email_id}}
Expected result: 204 No Content
```

**Steps:**
1. Click **"Delete Email"**
2. Click **"Send"**

**Expected results:**
- ✅ Status: `204 No Content`
- ✅ Email deleted
- ✅ Postman Tests: 1/1 passed

---

### Scenario 3: Validation and Error Handling

**Goal:** Verify API validates data correctly

#### Test 3.1: Register Without Required Fields
```
Request: POST /api/register (incomplete data)
Expected result: 422 Unprocessable Entity
```

**Steps:**
1. Open folder **"Validation Tests"**
2. Click **"Register - Missing Fields"**
3. Click **"Send"**

**Expected results:**
- ✅ Status: `422 Unprocessable Entity`
- ✅ Response contains `errors` object
- ✅ Errors for: last_name, email, password
- ✅ Postman Tests: 2/2 passed

**Sample response:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "last_name": ["The last name field is required."],
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

---

#### Test 3.2: Create User with Invalid Email
```
Request: POST /api/users (invalid email format)
Expected result: 422 Unprocessable Entity
```

**Steps:**
1. Click **"Create User - Invalid Email"**
2. Check body - email = "invalid-email"
3. Click **"Send"**

**Expected results:**
- ✅ Status: `422 Unprocessable Entity`
- ✅ Validation error for `emails.0.email`
- ✅ Postman Tests: 2/2 passed

---

#### Test 3.3: Access Without Authentication Token
```
Request: GET /api/users (no token)
Expected result: 401 Unauthorized
```

**Steps:**
1. Click **"Access Protected Route Without Token"**
2. Check Authorization = "No Auth"
3. Click **"Send"**

**Expected results:**
- ✅ Status: `401 Unauthorized`
- ✅ Message: "Unauthenticated"
- ✅ Postman Tests: 2/2 passed

---

#### Test 3.4: Access Non-Existent User
```
Request: GET /api/users/99999
Expected result: 404 Not Found
```

**Steps:**
1. In folder **"Users"** open **"Get User by ID"**
2. Change `{{user_id}}` to `99999` in URL
3. Click **"Send"**

**Expected results:**
- ✅ Status: `404 Not Found`
- ✅ Message about missing user

---

### Scenario 4: Pagination and Search

**Goal:** Test pagination and filtering features

#### Test 4.1: Pagination - First Page
```
Request: GET /api/users?per_page=5&page=1
Expected result: 200 OK + 5 users max
```

**Steps:**
1. Open **"List Users"**
2. Add query parameters:
   - `per_page` = `5`
   - `page` = `1`
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Maximum 5 users in `data`
- ✅ Meta contains: `total`, `per_page`, `current_page`

---

#### Test 4.2: Search Users
```
Request: GET /api/users?search=Jan
Expected result: 200 OK + filtered results
```

**Steps:**
1. Open **"List Users"**
2. Enable `search` parameter and set to `Jan`
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Results contain "Jan" in first or last name
- ✅ Empty list if no matches

---

#### Test 4.3: Sorting
```
Request: GET /api/users?sort=first_name&order=asc
Expected result: 200 OK + sorted results
```

**Steps:**
1. Open **"List Users"**
2. Enable parameters:
   - `sort` = `first_name`
   - `order` = `asc`
3. Click **"Send"**

**Expected results:**
- ✅ Status: `200 OK`
- ✅ Users sorted alphabetically by first name

---

## 🔄 Automated Tests (Test Scripts)

Collection includes automated tests for each endpoint:

### How to run all tests at once:

1. **Right-click on collection** "User Management API"
2. **Select "Run collection"**
3. **Click "Run User Management API"**
4. Postman will execute all requests in order and show results

### Example automated tests:

```javascript
// Test 1: Check status code
pm.test('Status code is 200', function () {
    pm.response.to.have.status(200);
});

// Test 2: Check response structure
pm.test('Response has token', function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('token');
});

// Test 3: Save variables for further use
pm.collectionVariables.set('token', jsonData.token);
```

---

## 🎨 Environment Variables

Collection uses variables for easier testing:

| Variable | Description | Example |
|---------|------|----------|
| `{{base_url}}` | API URL | https://nextstep.chat |
| `{{token}}` | Authentication token | Auto-saved after login |
| `{{user_id}}` | Last created user ID | Auto-saved |
| `{{email_id}}` | Last created email ID | Auto-saved |
| `{{$timestamp}}` | Timestamp (Postman built-in) | 1673625600 |

### How to check variables:

1. Click **eye icon** (👁️) in top-right corner
2. Check **"Collection Variables"** or **"Environment"** section
3. You can manually edit values

---

## 📊 Reporting

### Console (logs):
1. Click **View → Show Postman Console** (Alt+Ctrl+C)
2. See detailed logs of each request

### Export results:
1. After running Collection Runner
2. Click **"Export Results"**
3. Choose format: JSON or CSV

---

## 🔧 Troubleshooting

### Problem: 401 Unauthorized on protected endpoints

**Solution:**
1. Check if you ran **"Register User"** or **"Login"**
2. Check **"Authorization"** tab - should be "Bearer Token"
3. Check if `{{token}}` variable has value (click 👁️)

### Problem: 404 Not Found on /api/users/{{user_id}}

**Solution:**
1. Check if you ran **"Create User"** before this test
2. Check `{{user_id}}` variable - should have numeric value
3. You can manually set value in variables

### Problem: 422 Validation Error on "Create User"

**Solution:**
1. Check if all required fields are in body
2. Check email format - must be valid
3. Check if email is unique (uses `{{$timestamp}}`)

### Problem: Environment not working

**Solution:**
1. Check if you selected environment (Production or Local)
2. Check `base_url` in environment variables
3. Make sure server is running (test Health Check)

---

## 📝 Manual Testing Checklist

Use this list during full testing:

### Basic functions:
- [ ] Health check works
- [ ] Register new user
- [ ] Login
- [ ] Get authenticated user data
- [ ] Logout

### Users (CRUD):
- [ ] List users
- [ ] Create user
- [ ] User details
- [ ] Update user
- [ ] Delete user

### User emails:
- [ ] List emails
- [ ] Add email
- [ ] Email details
- [ ] Update email
- [ ] Delete email

### Additional features:
- [ ] Send welcome email
- [ ] Pagination (different per_page)
- [ ] Search
- [ ] Sorting

### Validation:
- [ ] Register without fields (422)
- [ ] Invalid email format (422)
- [ ] Access without token (401)
- [ ] Non-existent resource (404)

### Security:
- [ ] Protected endpoints require token
- [ ] Token expires after logout
- [ ] All inputs validated

---

## 🚀 Quick Start

**Fastest way to test API:**

1. **Import collection** (3 JSON files)
2. **Select environment** (Production or Local)
3. **Run scenario 1** (tests 1.1 - 1.10)
4. **Check results** - all tests should pass ✅

**Execution time:** ~5 minutes

---

## 💻 Alternative: cURL Examples (No Postman Required)

If you prefer command line or can't use Postman:

### Test 1: Health Check
```bash
curl https://nextstep.chat/api/health
```

### Test 2: Register User
```bash
curl -X POST https://nextstep.chat/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Jan",
    "last_name": "Kowalski",
    "phone_number": "+48123456789",
    "email": "jan.test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Save the token from response!

### Test 3: Get Current User (use your token)
```bash
curl https://nextstep.chat/api/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test 4: List Users
```bash
curl https://nextstep.chat/api/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test 5: Create User
```bash
curl -X POST https://nextstep.chat/api/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Anna",
    "last_name": "Nowak",
    "phone_number": "+48987654321",
    "emails": [
      {
        "email": "anna.nowak@example.com",
        "is_primary": true
      }
    ]
  }'
```

### Windows PowerShell Alternative

If you're on Windows and cURL doesn't work:

```powershell
# Health Check
Invoke-RestMethod -Uri "https://nextstep.chat/api/health" -Method GET

# Register User
$body = @{
    first_name = "Jan"
    last_name = "Kowalski"
    phone_number = "+48123456789"
    email = "jan.test@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "https://nextstep.chat/api/register" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body

# Save token
$token = $response.token

# List Users
Invoke-RestMethod -Uri "https://nextstep.chat/api/users" `
    -Method GET `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }
```

---

## 📚 Additional Resources

- **Swagger UI:** https://nextstep.chat/api/documentation
- **API Documentation:** docs/API.md
- **Authentication Guide:** docs/AUTHENTICATION.md

---

**Good luck with testing! 🎉**

