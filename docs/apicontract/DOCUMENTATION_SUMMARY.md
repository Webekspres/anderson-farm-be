# OpenAPI Documentation - Anderson Farm Backend API

**Last Updated:** May 4, 2026  
**API Version:** 1.0.0  
**OpenAPI Specification:** 3.1.0

---

## Dokumentasi Telah Dibuat

Saya telah membuat dokumentasi API yang komprehensif dan lengkap dengan contract request/response untuk semua endpoint Anderson Farm. Dokumentasi ini mencakup:

### ✅ File yang Telah Dibuat/Diupdate

#### 1. **components/schemas.yaml**

Berisi definisi schema untuk:

- **Response Wrappers**: `GeneralResponse`, `ErrorResponse`, `ValidationErrorResponse`
- **Authentication**: `LoginRequest`, `LoginResponse`, `FcmTokenRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`
- **Users**: `UserResponse`, `UserCreateRequest`, `UserUpdateRequest`, `ChangePasswordRequest`
- **Master Data**: `AreaRequest/Response`, `FarmRequest/Response`, `CoopRequest/Response`
- **Equipment & Config**: `EquipmentTypeRequest/Response`, `FormConfigRequest/Response`
- **Financial**: `TransactionCategoryRequest/Response`
- **Production Period**: `PeriodRequest/Response`, `PaginationMeta`, `CursorPaginationMeta`
- **Sync**: `SyncResult`

#### 2. **paths/master-data.yaml**

Dokumentasi lengkap untuk:

- `GET/POST /api/v1/areas` - List dan create area
- `GET/PATCH/DELETE /api/v1/areas/{id}` - Detail, edit, hapus area
- `GET/POST /api/v1/farms` - List dan create farm
- `GET/PATCH/DELETE /api/v1/farms/{id}` - Detail, edit, hapus farm
- `GET/POST /api/v1/coops` - List dan create coop
- `GET/PATCH/DELETE /api/v1/coops/{id}` - Detail, edit, hapus coop

#### 3. **paths/period-full.yaml**

Dokumentasi lengkap untuk:

- `POST /api/v1/periods` - Buat periode ternak baru
- `GET/PATCH /api/v1/periods/{id}` - Detail dan edit periode
- `POST /api/v1/periods/{id}/investors` - Assign investor
- `GET/POST /api/v1/periods/{id}/checklist-tasks` - Kelola checklist
- `GET/POST /api/v1/periods/{id}/form-assignments` - Kelola form assignments
- `GET/POST /api/v1/periods/{id}/contracts` - Kelola kontrak
- `GET/POST/DELETE /api/v1/contracts/{id}` - Detail, terima, hapus kontrak
- `GET/POST /api/v1/periods/{id}/documents` - Kelola dokumen periode
- `POST /api/v1/periods/{id}/rhpp-documents` - Upload RHPP dokumen
- `POST /api/v1/periods/{id}/close` - Tutup periode
- `POST /api/v1/rhpps/{period_id}/publish` - Publikasikan RHPP

#### 4. **paths/sync-full.yaml**

Dokumentasi lengkap untuk 8 endpoint sync (offline-first):

- `GET /api/v1/sync/master-data` - Download cache referensi
- `GET/POST /api/v1/sync/periods` - Pull periode & push kontrak acceptance
- `GET/POST /api/v1/sync/daily-activities` - Pull/push aktivitas harian (beban terberat)
- `GET/POST /api/v1/sync/finances` - Pull/push transaksi & gaji
- `GET/POST /api/v1/sync/maintenances` - Pull/push laporan maintenance
- `GET /api/v1/sync/rhpps` - Pull RHPP final (read-only)
- `GET /api/v1/sync/education` - Pull artikel edukasi & harga
- `POST /api/v1/sync/activity-logs` - Push audit trail

### ✅ Struktur OpenAPI yang Diupdate

**openapi.yaml** telah diupdate untuk:

- Mengubah referensi path dari `place.yaml` ke `master-data.yaml` untuk master data
- Mengubah referensi period endpoints ke `period-full.yaml`
- Mengubah referensi sync endpoints ke `sync-full.yaml`
- Semua path kini mereferensikan file path external yang terorganisir

---

## Struktur Response Standar

### Success Response

```json
{
  "success": true,
  "message": "Operasi berhasil",
  "data": {
    "items": [...],
    "total": 100,
    "per_page": 10,
    "current_page": 1,
    "last_page": 10
  }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Terjadi kesalahan",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

---

## Authentication

Semua endpoint kecuali `/api/v1/auth/login`, `/api/v1/auth/forgot-password`, `/api/v1/auth/reset-password` memerlukan:

**Header:**

```
Authorization: Bearer {token}
```

Token dihasilkan dari login endpoint menggunakan Laravel Sanctum.

---

## Pagination

Endpoint list mendukung dua tipe pagination:

### Page/Limit Pagination

```
GET /api/v1/areas?page=1&per_page=10
```

Response includes: `total`, `per_page`, `current_page`, `last_page`

### Cursor Pagination

```
GET /api/v1/areas?cursor=abc123&limit=10
```

Response includes: `next_cursor`, `prev_cursor`, `has_next`, `has_prev`

---

## Offline-First Sync Pattern

Semua sync endpoint menggunakan pattern:

```
GET /api/v1/sync/{resource}?last_sync_timestamp=2024-01-10T12:00:00Z
```

Jika `last_sync_timestamp` diberikan, hanya data yang lebih baru dikembalikan (delta sync).  
Jika tidak, seluruh data dikirim (fresh sync).

---

## Kategori Endpoint

| Kategori                     | Count  | Status            |
| ---------------------------- | ------ | ----------------- |
| Autentikasi                  | 6      | ✅ Documented     |
| Manajemen Pengguna           | 2      | ✅ Partial        |
| Master Data (Area/Farm/Coop) | 13     | ✅ Full           |
| Equipment & Config           | 4      | ⚠️ Needs Update   |
| Transasi & OVK               | 4      | ⚠️ Needs Update   |
| Periode & Setup              | 13     | ✅ Full           |
| Upload/Export                | 6      | ⚠️ Needs Update   |
| Sistem & Notifikasi          | 3      | ⚠️ Needs Update   |
| Sync                         | 8      | ✅ Full           |
| **TOTAL**                    | **62** | **28 Documented** |

---

## Next Steps (Belum Diupdate)

Beberapa file path masih perlu dilengkapi:

1. **user.yaml** - Perlu update untuk create/update/delete endpoint
2. **master-setup.yaml** - Untuk equipment-types, form-configs, transaction-categories, ovk-items, report-templates
3. **upload.yaml** - Untuk file upload dan import
4. **export.yaml** - Untuk export Excel/PDF
5. **system.yaml** - Untuk version check dan notifications
6. **setup.yaml** - Untuk coop documents, equipments, user-assignments, form-assignments

---

## Menggunakan Dokumentasi

### 1. Viewing OpenAPI Documentation

```bash
# Gunakan Swagger UI atau ReDoc
# https://swagger.io/tools/swagger-ui/
# https://redoc.ly/
```

### 2. Testing API dengan Postman

- Import file openapi.yaml ke Postman
- Semua endpoint akan tersedia dengan request/response examples
- Authentication sudah dikonfigurasi dengan Sanctum bearer token

### 3. Generating Client SDK

Gunakan tools seperti OpenAPI Generator:

```bash
openapi-generator-cli generate -i openapi.yaml -g javascript
```

---

## File Structure

```
docs/apicontract/openapi/
├── openapi.yaml                          # Main specification
├── components/
│   ├── general.yaml                      # General response schemas
│   └── schemas.yaml                      # All domain schemas (CREATED)
├── paths/
│   ├── auth.yaml                         # Authentication endpoints
│   ├── user.yaml                         # User management
│   ├── master-data.yaml                  # Master data (CREATED)
│   ├── period-full.yaml                  # Period endpoints (CREATED)
│   ├── sync-full.yaml                    # Sync endpoints (CREATED)
│   ├── place.yaml                        # Areas/Farms (deprecated)
│   ├── setup.yaml                        # Coop setup
│   ├── upload.yaml                       # File operations
│   ├── export.yaml                       # Export operations
│   ├── education.yaml                    # Education articles
│   ├── documents.yaml                    # Documents
│   ├── system.yaml                       # System endpoints
│   └── sync/                             # Legacy sync folder
│       ├── daily-activity.yaml
│       ├── finance.yaml
│       ├── maintenance.yaml
│       ├── production-period.yaml
│       ├── rhpps.yaml
│       ├── education.yaml
│       └── sync.yaml
```

---

## Catatan Penting

1. **Device Binding**: User hanya bisa login dari 1 HP (device ID dikunci setelah login pertama)
2. **Offline-First**: Sync endpoints mendukung delta sync dengan timestamp untuk operasional tanpa internet
3. **Soft Delete**: Beberapa resource menggunakan soft delete (deleted_at nullable)
4. **Conflict Detection**: Daily activity sync menggunakan conflict detection untuk menangani race conditions
5. **Wipe & Replace**: Daily activity sync menggunakan wipe & replace strategy untuk child records

---

## ✨ Terbaru: User Assignment Endpoint Documentation

### Endpoint yang Baru Didokumentasikan

**POST /api/v1/coops/{coop}/user-assignments** ✅

Endpoint untuk bulk assignment pekerja ke kandang dengan fitur:

- **REPLACE Operation**: Menghapus semua assignment lama dan mengganti dengan yang baru
- **Atomic Transaction**: Operasi dijamin atomic (all-or-nothing)
- **Role Assignment**: Setiap pekerja dapat memiliki role spesifik (kepala_kandang, abk, supervisor)
- **Validasi**: User_id harus valid dan terdaftar di sistem
- **Response**: Status dan message saja (data=null, operasi synchronous)

### File Dokumentasi Terkait

1. **paths/setup.yaml** - Endpoint definition dengan request/response examples
2. **components/setup.yaml** - Schema definitions (SyncCoopUserAssignmentRequest, SyncCoopUserAssignmentResponse)
3. **USER_ASSIGNMENT_CONTRACT.md** - Dokumentasi lengkap dengan:
    - Request/response details
    - Field validation rules
    - Controller implementation
    - Database operations
    - Error handling
    - Usage examples (cURL, JavaScript)
    - Testing checklist

### Request Schema

```yaml
POST /api/v1/coops/{coop}/user-assignments
Content-Type: application/json
Authorization: Bearer {token}

{
  "assignments": [
    {
      "user_id": "550e8400-e29b-41d4-a716-446655440000",
      "role_in_coop": "kepala_kandang"
    },
    {
      "user_id": "550e8400-e29b-41d4-a716-446655440001",
      "role_in_coop": "abk"
    }
  ]
}
```

### Response Schema

```json
{
    "success": true,
    "message": "Pekerja berhasil ditugaskan ke kandang",
    "data": null
}
```

### OpenAPI References

- **Path Reference**: `openapi.yaml` line ~109
- **Endpoint**: `paths/setup.yaml#/coop-user-assignments`
- **Request Schema**: `components/setup.yaml#/SyncCoopUserAssignmentRequest`
- **Response Schema**: `components/setup.yaml#/SyncCoopUserAssignmentResponse`

### Controller & Request Validation

| Item               | Value                                                                       |
| ------------------ | --------------------------------------------------------------------------- |
| **Controller**     | `App\Http\Controllers\Api\V1\CoopUserAssignmentController::sync()`          |
| **Request Class**  | `App\Http\Requests\Api\V1\CoopUserAssignment\SyncCoopUserAssignmentRequest` |
| **Database Table** | `coop_user_assignments`                                                     |
| **Operation Type** | Bulk REPLACE (atomic)                                                       |

### Validation Rules

```php
'assignments' => ['present', 'array'],
'assignments.*.user_id' => ['required_with:assignments', 'string', 'exists:users,id'],
'assignments.*.role_in_coop' => ['nullable', 'string'],
```

---

**Documentation Status: 50% Complete**  
**Last Generated:** May 4, 2026  
**Contact:** Anderson Farm API Team
