# User Assignment API Contract Documentation

## Endpoint: POST /api/v1/coops/{coop}/user-assignments

### Overview

Endpoint untuk melakukan bulk assignment (penugasan massal) pekerja ke kandang tertentu. Operasi ini bersifat **REPLACE** (menghapus semua assignment lama dan mengganti dengan yang baru), bukan **APPEND**.

### Request Details

#### URL Path

```
POST /api/v1/coops/{coop}/user-assignments
```

#### URL Parameter

- `coop` (required, UUID): UUID dari kandang yang akan di-update

#### Authentication

- **Type**: Bearer Token (Laravel Sanctum)
- **Header**: `Authorization: Bearer {token}`

#### Request Body Schema

```yaml
Content-Type: application/json

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
      "role_in_coop": null
    }
  ]
}
```

#### Field Validation

| Field                        | Type        | Required | Rules                    | Example            |
| ---------------------------- | ----------- | -------- | ------------------------ | ------------------ |
| `assignments`                | Array       | Yes      | Min 0 items              | `[...]`            |
| `assignments[].user_id`      | UUID        | Yes\*    | Must exist in `users.id` | `"550e8400..."`    |
| `assignments[].role_in_coop` | String/Null | No       | Enum or null             | `"kepala_kandang"` |

\*Required if `assignments` array has items

#### Supported Roles

- `kepala_kandang` - Kepala Kandang / Supervisor kandang
- `abk` - Anak Buah Kandang (pekerja harian)
- `supervisor` - Supervisor/Pengawas
- `null` - Tanpa role spesifik

---

### Response Details

#### Success Response (200 OK)

```json
{
    "success": true,
    "message": "Pekerja berhasil ditugaskan ke kandang",
    "data": null
}
```

#### Error Responses

**400 Bad Request** - Validasi Gagal

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "assignments.0.user_id": ["User tidak ditemukan di sistem"]
    }
}
```

**401 Unauthorized** - Token tidak valid atau expired

```json
{
    "success": false,
    "message": "Unauthenticated",
    "errors": {}
}
```

**403 Forbidden** - User tidak memiliki permission

```json
{
    "success": false,
    "message": "Unauthorized action",
    "errors": {}
}
```

**404 Not Found** - Kandang tidak ditemukan

```json
{
    "success": false,
    "message": "Kandang tidak ditemukan",
    "errors": {}
}
```

---

### Implementation Details

#### Controller: `CoopUserAssignmentController::sync()`

- **Namespace**: `App\Http\Controllers\Api\V1`
- **Request Class**: `App\Http\Requests\Api\V1\CoopUserAssignment\SyncCoopUserAssignmentRequest`
- **Database Transaction**: Yes (atomic operation)

#### Database Operation

1. **Delete**: Soft delete semua existing assignments untuk coop ini
2. **Insert**: Bulk insert new assignments dengan data:
    - `coop_id`
    - `user_id`
    - `assigned_at` (current timestamp)
    - `role_in_coop`
    - `sync_status` = 'PENDING_SYNC'
    - `created_at_client`, `updated_at_client`

#### Response Model

- **CoopUserAssignmentResource**: Tidak digunakan untuk bulk response (response data=null)
- **Database Table**: `coop_user_assignments`

#### Model Fields

```php
CoopUserAssignment {
  id: UUID,
  server_id: UUID,
  version: Integer,
  user_id: UUID,
  coop_id: UUID,
  assigned_at: DateTime,
  role_in_coop: String|Null,
  sync_status: String,
  created_at_client: DateTime,
  created_at_server: DateTime,
  updated_at_client: DateTime,
  updated_at_server: DateTime,
  deleted_at: DateTime|Null
}
```

---

### Important Notes

⚠️ **REPLACE Operation**

- Operasi ini menghapus SEMUA assignment lama sebelum insert yang baru
- Jika `assignments` array kosong, semua pekerja akan di-unbind dari kandang
- Ini adalah operasi atomic dalam satu transaksi database

✅ **Success Indicators**

- Status code: 200 OK
- `success` field: true
- `message` field: berisi pesan sukses

❌ **Error Handling**

- User yang tidak ditemukan akan reject dengan 400
- Invalid UUID format akan reject dengan 400
- Token expired/invalid akan reject dengan 401
- Kandang tidak ditemukan akan reject dengan 404

🔄 **Offline-First Sync**

- Setiap assignment dicatat dengan `sync_status` = 'PENDING_SYNC'
- Client dapat query `GET /sync/...` untuk sync data yang berubah

---

### Example Usage

#### cURL

```bash
curl -X POST "http://localhost:8000/api/v1/coops/550e8400-e29b-41d4-a716-446655440000/user-assignments" \
  -H "Authorization: Bearer 2|AbCdEfGhIjKlMnOpQrStUvWxYz" \
  -H "Content-Type: application/json" \
  -d '{
    "assignments": [
      {
        "user_id": "550e8400-e29b-41d4-a716-446655440001",
        "role_in_coop": "kepala_kandang"
      },
      {
        "user_id": "550e8400-e29b-41d4-a716-446655440002",
        "role_in_coop": "abk"
      }
    ]
  }'
```

#### JavaScript/Axios

```javascript
const response = await axios.post(
    "/api/v1/coops/550e8400-e29b-41d4-a716-446655440000/user-assignments",
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
        headers: { Authorization: `Bearer ${token}` },
    },
);

console.log(response.data);
```

---

### OpenAPI Specification Reference

**File**: `docs/apicontract/openapi/paths/setup.yaml`
**Component**: `#/coop-user-assignments`

**Schemas Used**:

- Request: `components/setup.yaml#/SyncCoopUserAssignmentRequest`
- Response: `components/setup.yaml#/SyncCoopUserAssignmentResponse`

---

### Testing Checklist

- [ ] POST dengan valid user_ids → 200 OK
- [ ] POST dengan user_id yang tidak ada → 400 (validation error)
- [ ] POST dengan invalid UUID format → 400 (validation error)
- [ ] POST tanpa token → 401 Unauthorized
- [ ] POST dengan token expired → 401 Unauthorized
- [ ] POST ke coop yang tidak ada → 404 Not Found
- [ ] Verify all existing assignments deleted before insert
- [ ] Verify `sync_status` = 'PENDING_SYNC' untuk semua record baru
- [ ] Verify response always has `data: null` (not actual assignment data)
- [ ] Test dengan assignment array kosong → semua pekerja unbind dari coop

---

**Last Updated**: 2026-05-04
**Documentation Version**: 1.0
**API Version**: v1
