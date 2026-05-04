# User Assignment API Contract - Quick Reference

## 🎯 Endpoint Overview

```
Method: POST
Path:   /api/v1/coops/{coop}/user-assignments
Auth:   Bearer Token (Sanctum)
Version: v1
```

---

## 📥 REQUEST

### URL

```
POST /api/v1/coops/550e8400-e29b-41d4-a716-446655440000/user-assignments
```

### Headers

```
Authorization: Bearer 2|AbCdEfGhIjKlMnOpQrStUvWxYz
Content-Type: application/json
```

### Body Structure

```typescript
{
    assignments: Array<{
        user_id: UUID; // Required, must exist in users table
        role_in_coop?: string; // Optional: kepala_kandang | abk | supervisor
    }>;
}
```

### Examples

#### Example 1: Assign multiple workers with roles

```json
{
    "assignments": [
        {
            "user_id": "550e8400-e29b-41d4-a716-446655440000",
            "role_in_coop": "kepala_kandang"
        },
        {
            "user_id": "550e8400-e29b-41d4-a716-446655440001",
            "role_in_coop": "abk"
        },
        {
            "user_id": "550e8400-e29b-41d4-a716-446655440002",
            "role_in_coop": "abk"
        }
    ]
}
```

#### Example 2: Assign workers without roles

```json
{
    "assignments": [
        {
            "user_id": "550e8400-e29b-41d4-a716-446655440000",
            "role_in_coop": null
        }
    ]
}
```

#### Example 3: Unbind all workers (empty assignments)

```json
{
    "assignments": []
}
```

---

## 📤 RESPONSE

### Success (200 OK)

```json
{
    "success": true,
    "message": "Pekerja berhasil ditugaskan ke kandang",
    "data": null
}
```

### Errors

#### 400 Bad Request - User not found

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "assignments.0.user_id": ["User tidak ditemukan di sistem"]
    }
}
```

#### 401 Unauthorized - Invalid/expired token

```json
{
    "success": false,
    "message": "Unauthenticated",
    "errors": {}
}
```

#### 403 Forbidden - Insufficient permissions

```json
{
    "success": false,
    "message": "Unauthorized action",
    "errors": {}
}
```

#### 404 Not Found - Coop not found

```json
{
    "success": false,
    "message": "Kandang tidak ditemukan",
    "errors": {}
}
```

---

## 🔍 Validation Rules

| Field                        | Rule                            | Error Message               |
| ---------------------------- | ------------------------------- | --------------------------- |
| `assignments`                | array, present                  | Must be an array            |
| `assignments[]`              | array items                     | Each item must be an object |
| `assignments[].user_id`      | required, uuid, exists:users.id | User tidak ditemukan        |
| `assignments[].role_in_coop` | nullable, string                | Invalid role                |

---

## 💾 Database Impact

### Operation Flow

1. **Start Transaction**
2. **Soft Delete**: All existing assignments for this coop
    ```sql
    UPDATE coop_user_assignments
    SET deleted_at = NOW()
    WHERE coop_id = '{coop_id}'
    ```
3. **Bulk Insert**: New assignments
    ```sql
    INSERT INTO coop_user_assignments (
      coop_id, user_id, assigned_at, role_in_coop,
      sync_status, created_at_client, updated_at_client
    ) VALUES (...)
    ```
4. **Commit Transaction**

### Created Fields

```
id                  -> UUID (generated)
server_id          -> UUID (null, set by server)
version            -> 0
user_id            -> From request
coop_id            -> From URL parameter
assigned_at        -> NOW()
role_in_coop       -> From request (nullable)
sync_status        -> 'PENDING_SYNC'
created_at_client  -> NOW()
updated_at_client  -> NOW()
created_at_server  -> NULL
updated_at_server  -> NULL
deleted_at         -> NULL
```

---

## 🔐 Security

✅ **Requires Authentication**: Yes (Bearer token)  
✅ **Rate Limited**: No (depends on middleware)  
✅ **CORS**: Yes (if configured)  
✅ **Data Validation**: Strong (UUID, exists check)  
⚠️ **Authorization**: Check if user can manage this coop

---

## 📊 Status Codes Summary

| Code | Meaning      | Action                |
| ---- | ------------ | --------------------- |
| 200  | Success      | Assignment completed  |
| 400  | Bad Request  | Fix validation errors |
| 401  | Unauthorized | Provide valid token   |
| 403  | Forbidden    | Check permissions     |
| 404  | Not Found    | Verify coop ID        |
| 500  | Server Error | Contact support       |

---

## 🧪 Testing Examples

### Using cURL

```bash
curl -X POST http://localhost:8000/api/v1/coops/550e8400-e29b-41d4-a716-446655440000/user-assignments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assignments": [
      {"user_id": "550e8400-e29b-41d4-a716-446655440001", "role_in_coop": "kepala_kandang"},
      {"user_id": "550e8400-e29b-41d4-a716-446655440002", "role_in_coop": "abk"}
    ]
  }'
```

### Using JavaScript/Axios

```javascript
const coopId = "550e8400-e29b-41d4-a716-446655440000";
const token = "YOUR_TOKEN";

const response = await axios.post(
    `/api/v1/coops/${coopId}/user-assignments`,
    {
        assignments: [
            {
                user_id: "550e8400-e29b-41d4-a716-446655440001",
                role_in_coop: "kepala_kandang",
            },
            {
                user_id: "550e8400-e29b-41d4-a716-446655440002",
                role_in_coop: "abk",
            },
        ],
    },
    {
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
    },
);

if (response.data.success) {
    console.log("Assignment successful!");
} else {
    console.error("Errors:", response.data.errors);
}
```

### Using Postman

1. Import openapi.yaml into Postman
2. Find "POST /coops/{coop}/user-assignments" under Coop Setup
3. Set {{coop}} variable to a valid coop UUID
4. Set Authorization header with {{token}}
5. Fill request body with assignments array
6. Send request

---

## 📝 Implementation Files

| File                                | Type           | Content                                                                                        |
| ----------------------------------- | -------------- | ---------------------------------------------------------------------------------------------- |
| `routes/api.php`                    | Route          | `Route::post('/coops/{coop}/user-assignments', [CoopUserAssignmentController::class, 'sync'])` |
| `CoopUserAssignmentController.php`  | Controller     | `sync(SyncCoopUserAssignmentRequest $request, Coop $coop)`                                     |
| `SyncCoopUserAssignmentRequest.php` | FormRequest    | Validation rules                                                                               |
| `CoopUserAssignment.php`            | Model          | Database model                                                                                 |
| `setup.yaml`                        | OpenAPI Path   | Endpoint definition                                                                            |
| `components/setup.yaml`             | OpenAPI Schema | Request/response schemas                                                                       |

---

## ⚠️ Important Notes

1. **REPLACE Operation**: This is NOT an append operation
    - All old assignments are deleted first
    - New assignments are then inserted
    - If assignments array is empty, worker is completely unbound

2. **Atomic Transaction**: Either all assignments succeed or all fail
    - No partial updates
    - Database consistency guaranteed

3. **Sync Status**: All created assignments have `sync_status = 'PENDING_SYNC'`
    - Client should sync with server after this operation

4. **Response Data**: Always returns `data: null`
    - Use GET endpoint if you need detailed assignment data
    - Response only confirms operation success

5. **User Validation**: User ID must exist in users table
    - Invalid UUID format → 400 error
    - Non-existent user → 400 error

---

## 📚 Related Documentation

- **Full Contract**: `docs/apicontract/USER_ASSIGNMENT_CONTRACT.md`
- **OpenAPI Spec**: `docs/apicontract/openapi.yaml`
- **Path Definition**: `docs/apicontract/openapi/paths/setup.yaml`
- **Schemas**: `docs/apicontract/openapi/components/setup.yaml`

---

**Version**: 1.0  
**Last Updated**: May 4, 2026  
**Status**: ✅ Production Ready
