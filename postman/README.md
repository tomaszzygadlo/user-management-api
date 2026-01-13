# Postman Collection - User Management API

This folder contains Postman collection and environments for testing the User Management API.

## 📁 Files

- **User-Management-API.postman_collection.json** - Main collection with all API endpoints
- **Production.postman_environment.json** - Environment for production (https://nextstep.chat)
- **Local.postman_environment.json** - Environment for local development (http://localhost:8000)

## 🚀 Quick Start

### 1. Import to Postman

1. Open Postman
2. Click **"Import"** button
3. Drag and drop all 3 JSON files from this folder
4. Click **"Import"**

### 2. Select Environment

In top-right corner, select:
- **Production** - for testing on https://nextstep.chat
- **Local** - for testing locally

### 3. Start Testing

**Quick test sequence:**
1. Open folder **"Authentication"**
2. Run **"Register User"** (creates user + saves token)
3. Run **"Get Current User"** (uses saved token)
4. Run **"List Users"** (shows all users)

All tests should pass ✅

## 📖 Documentation

Full testing guide with scenarios: **[docs/MANUAL_TESTING.md](../docs/MANUAL_TESTING.md)**

## 🎯 What's Included

### Endpoints Covered:

- ✅ **Health Check** - API status
- ✅ **Authentication** - Register, Login, Logout, Me
- ✅ **Users CRUD** - Create, Read, Update, Delete
- ✅ **User Emails** - Nested resource management
- ✅ **Welcome Email** - Send welcome notifications
- ✅ **Validation Tests** - Error handling
- ✅ **Pagination & Search** - Filtering and sorting

### Features:

- ✅ **Auto-save tokens** - After login/register
- ✅ **Auto-save IDs** - For chained requests
- ✅ **Built-in tests** - Automatic validation
- ✅ **Unique data** - Uses `{{$timestamp}}` for emails
- ✅ **Environment variables** - Easy switching

## 🔄 Running All Tests

1. Right-click on collection **"User Management API"**
2. Select **"Run collection"**
3. Click **"Run User Management API"**
4. View results (all tests should pass)

## 📊 Variables

| Variable | Description | Auto-saved |
|----------|-------------|------------|
| `base_url` | API base URL | No (set in environment) |
| `token` | Auth token | Yes (after register/login) |
| `user_id` | Last created user ID | Yes (after create user) |
| `email_id` | Last created email ID | Yes (after add email) |

## 🎨 Environments

### Production
```json
{
  "base_url": "https://nextstep.chat",
  "token": "",
  "user_id": "",
  "email_id": ""
}
```

### Local
```json
{
  "base_url": "http://localhost:8000",
  "token": "",
  "user_id": "",
  "email_id": ""
}
```

## 📝 Test Scenarios

See **[docs/MANUAL_TESTING.md](../docs/MANUAL_TESTING.md)** for complete test scenarios:

1. **Scenario 1:** Basic user flow (Happy Path)
2. **Scenario 2:** Email management
3. **Scenario 3:** Validation and error handling
4. **Scenario 4:** Pagination and search

## 🔧 Troubleshooting

### Token not working?
- Make sure you ran **"Register User"** or **"Login"** first
- Check if `{{token}}` variable has value (click 👁️ icon)

### 404 errors?
- Check if you have correct `user_id` or `email_id`
- Make sure you created user before accessing it

### Environment issues?
- Verify environment is selected (top-right)
- Check `base_url` value in environment variables

## 🌐 API URLs

- **Production:** https://nextstep.chat
- **Production Swagger:** https://nextstep.chat/api/documentation
- **Local:** http://localhost:8000
- **Local Swagger:** http://localhost:8000/api/documentation

## 📚 Related Documentation

- [Full Testing Guide](../docs/MANUAL_TESTING.md)
- [API Documentation](../docs/API.md)
- [Authentication Guide](../docs/AUTHENTICATION.md)

---

**Happy Testing! 🎉**

