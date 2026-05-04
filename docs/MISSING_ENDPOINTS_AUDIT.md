# 📊 MISSING ENDPOINTS AUDIT REPORT

**Tanggal**: 2026-05-04  
**Sumber Perbandingan**:

- Master List: `docs/memo/endpoint_memo.md` (62 endpoints total)
- Implementation: `routes/api.php` (actual routes)

**Metodologi**:

- ✅ `Route::apiResource('resource')` = 5 methods (index, show, store, update, destroy)
- ✅ `Route::apiResource(...)->only(['method1', 'method2'])` = limited methods
- ✅ `Route::apiResource(...)->except(['method'])` = excludes specific methods

---

## 📋 RINGKASAN EKSEKUTIF

| Kategori                    | Total Endpoints | Implemented | Missing | % Complete |
| --------------------------- | --------------- | ----------- | ------- | ---------- |
| **Autentikasi & Keamanan**  | 6               | 3           | **3**   | 50%        |
| **Manajemen Pengguna**      | 5               | 4           | **1**   | 80%        |
| **Master Data**             | 22              | 20          | **2**   | 91%        |
| **Setup Kandang & Periode** | 13              | 12          | **1**   | 92%        |
| **Export/Import & Upload**  | 6               | 3           | **3**   | 50%        |
| **Logika Bisnis & Portal**  | 4               | 3           | **1**   | 75%        |
| **Sistem & Notifikasi**     | 3               | 0           | **3**   | 0%         |
| **Sync (Offline-First)**    | 8               | 7           | **1**   | 88%        |
| **TOTAL**                   | **67**          | **52**      | **15**  | **78%**    |

---

## 🔴 MISSING ENDPOINTS (15 TOTAL)

### A. AUTENTIKASI & KEAMANAN (3 MISSING)

#### ❌ 1. `/api/v1/auth/forgot-password` - POST

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Publik. Minta OTP/Link _reset password_."
- **Why needed**: User lupa password perlu endpoint publik (no auth required)
- **Controller needed**: `AuthController@forgotPassword()` (publik route, tidak perlu middleware sanctum)
- **Request**: `{ "username": "...", "via": "email|wa" }`
- **Response**: `{ "success": true, "message": "OTP telah dikirim ke email/WA" }`

#### ❌ 2. `/api/v1/auth/reset-password` - POST

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Publik. Submit _password_ baru dengan OTP."
- **Why needed**: User menerima OTP dan ingin reset password
- **Controller needed**: `AuthController@resetPassword()` (publik route)
- **Request**: `{ "username": "...", "otp": "...", "new_password": "..." }`
- **Response**: `{ "success": true, "message": "Password berhasil direset" }`

#### ❌ 3. `/api/v1/auth/fcm-token` - POST

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Simpan token Firebase untuk menerima _Push Notification_."
- **Why needed**: Client perlu register FCM token untuk push notifications
- **Controller needed**: `AuthController@setFcmToken()` (protected, perlu auth)
- **Request**: `{ "fcm_token": "...", "device_name": "..." }`
- **Response**: `{ "success": true, "message": "FCM token berhasil disimpan" }`

---

### B. MANAJEMEN PENGGUNA & PROFIL (1 MISSING)

#### ❌ 4. `/api/v1/users/{id}` - GET (Detail User)

- **Status**: ❌ NOT IMPLEMENTED
- **Current state**: `Route::apiResource('users', UserController::class)->except(['show']);`
- **Issue**: `.except(['show'])` menghilangkan GET detail endpoint
- **Why needed**: Admin/PIC perlu melihat detail user tertentu (email, phone, role, assignments)
- **Fix**: Ubah ke `->except([])` atau tambah manual route
- **Expected Response**: `{ "success": true, "data": { "id": "...", "username": "...", "name": "...", "role": "...", "email": "...", "phone": "...", "coop_assignments": [...] } }`

---

### C. MASTER DATA (2 MISSING)

#### ❌ 5. `/api/v1/education-articles` - GET (List Articles)

- **Status**: ❌ NOT IMPLEMENTED
- **Current state**: `Route::apiResource('education-articles', ...)->only(['store', 'update', 'destroy']);`
- **Issue**: `.only(...)` hanya allows POST, PUT, DELETE → missing GET list
- **Why needed**: Client perlu pull daftar semua artikel untuk edukasi ABK
- **Fix**: Tambah `'index'` ke only array: `->only(['index', 'show', 'store', 'update', 'destroy'])`
- **Expected Response**: `{ "success": true, "data": [{ "id": "...", "title": "...", "content": "...", "created_at": "..." }, ...] }`

#### ❌ 6. `/api/v1/education-articles/{id}` - GET (Detail Article)

- **Status**: ❌ NOT IMPLEMENTED
- **Current state**: Same as above `.only(['store', 'update', 'destroy'])`
- **Issue**: Missing 'show' method
- **Fix**: Tambah 'show' ke only array
- **Expected**: Single article detail dengan full content

#### ❌ 7. `/api/v1/price-references` - GET (List Prices)

- **Status**: ❌ NOT IMPLEMENTED
- **Current state**: `Route::apiResource('price-references', ...)->only(['store', 'update', 'destroy']);`
- **Issue**: Missing GET list
- **Why needed**: Client perlu pull daftar referensi harga untuk perhitungan
- **Fix**: Ubah to `->only(['index', 'show', 'store', 'update', 'destroy'])`
- **Expected Response**: Array of price references with item name and current price

#### ❌ 8. `/api/v1/price-references/{id}` - GET (Detail Price)

- **Status**: ❌ NOT IMPLEMENTED
- **Issue**: Same as #7
- **Fix**: Include 'show' in only array

---

### D. SETUP KANDANG & PERIODE (1 MISSING)

#### ❌ 9. `/api/v1/sync/periods` - POST (Push Contract Acceptance)

- **Status**: ❌ NOT IMPLEMENTED
- **Current state**: `Route::get('/periods', ...);` ← only GET
- **Expected in endpoint_memo.md**: "`GET` untuk menarik detail periode aktif. `POST` HANYA untuk mengirim jejak persetujuan digital `ContractAcceptance` dari ABK."
- **Why needed**: ABK offline menerima contract, approval offline, lalu POST saat online untuk sync jejak tanda tangan
- **Fix**: Tambah `Route::post('/periods', [...SyncController@storeAcceptance]);`
- **Controller needed**: Logic untuk menerima & menyimpan `ContractAcceptance` records

---

### E. EXPORT, IMPORT & UPLOAD (3 MISSING)

#### ❌ 10. `/api/v1/export/rhpp` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "_Generate_ Excel/PDF perhitungan detail periode sebelum difinalisasi."
- **Why needed**: Admin perlu preview RHPP sebelum publikasi
- **Query params**: `?period_id=...&format=excel|pdf`
- **Controller needed**: Export controller dengan logic generate RHPP Excel/PDF
- **Expected Response**: File download (PDF/Excel) atau URL ke file

#### ❌ 11. `/api/v1/export/harvests` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "_Generate_ Excel rekap panen bertahap (`HarvestEntry`)."
- **Why needed**: Report panen per periode
- **Query params**: `?period_id=...&format=excel|pdf`
- **Expected Response**: File download dengan tabel panen by tanggal

#### ❌ 12. `/api/v1/export/evaluations` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "_Generate_ Excel evaluasi teknis harian."
- **Why needed**: Report evaluasi per periode
- **Expected Response**: File download dengan detail evaluasi

#### ❌ 13. `/api/v1/export/template-salary` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Download format Excel kosong untuk persiapan _import_ gaji."
- **Why needed**: Admin perlu template Excel untuk bulk import gaji
- **Expected Response**: Excel file template dengan headers (user_id, amount, notes, dll)
- **Related**: POST `/api/v1/import/salary` - untuk upload file Excel yang sudah diisi

#### ❌ 14. `/api/v1/import/salary` - POST

- **Status**: ❌ NOT IMPLEMENTED (separately, not part of upload)
- **Expected in endpoint_memo.md**: "Unggah data tagihan upah ABK massal via Excel (`EmployeeSalary`)."
- **Current state**: Upload endpoint exists tapi tidak ada dedicated import/salary endpoint
- **Why needed**: Bulk import gaji dari Excel file (berbeda dari file upload biasa, ada business logic)
- **Controller needed**: `SalaryImportController@import()` dengan parsing Excel & validasi
- **Request**: Multipart form data dengan file Excel
- **Response**: `{ "success": true, "message": "Gaji 25 pekerja berhasil diimpor", "data": { "imported_count": 25 } }`

---

### F. LOGIKA BISNIS & PORTAL (1 MISSING)

#### ❌ 15. `/api/v1/investor/dashboard` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "**Portal Investor.** Rekap ROI super ringan tanpa SQLite."
- **Why needed**: Investor portal untuk melihat ROI ringkas periode
- **Query params**: `?period_id=...`
- **Controller needed**: `InvestorController@dashboard()`
- **Expected Response**:
    ```json
    {
        "success": true,
        "data": {
            "period_id": "...",
            "investment_amount": 50000000,
            "profit_percentage": 12.5,
            "profit_amount": 6250000,
            "roi_status": "active|closed"
        }
    }
    ```

---

### G. SISTEM & NOTIFIKASI (3 MISSING)

#### ❌ 16. `/api/v1/system/check-version` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Tembak saat _Splash Screen_. Paksa _update_ ke PlayStore jika aplikasi _outdated_."
- **Why needed**: Version control untuk mobile app (force update check)
- **Query params**: `?app_version=1.2.3&platform=android|ios`
- **Controller needed**: `SystemController@checkVersion()`
- **Expected Response**:
    ```json
    {
        "success": true,
        "data": {
            "latest_version": "1.3.0",
            "current_version": "1.2.3",
            "force_update": false,
            "update_message": "New features available",
            "download_url": "https://play.google.com/..."
        }
    }
    ```

#### ❌ 17. `/api/v1/notifications` - GET

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Tarik riwayat pesan masuk (_Inbox_ aplikasi)."
- **Why needed**: App inbox untuk melihat semua notifikasi (push notifications history)
- **Query params**: `?page=1&per_page=20`
- **Controller needed**: `NotificationController@index()`
- **Expected Response**:
    ```json
    {
        "success": true,
        "data": [
            {
                "id": "...",
                "title": "Period ABC ditutup",
                "message": "Periode ABC telah ditutup oleh manager",
                "type": "info|warning|success",
                "read_at": "2026-05-04T10:00:00Z",
                "created_at": "2026-05-04T09:30:00Z"
            }
        ],
        "pagination": { "total": 45, "per_page": 20, "current_page": 1 }
    }
    ```

#### ❌ 18. `/api/v1/notifications/{id}/read` - PATCH

- **Status**: ❌ NOT IMPLEMENTED
- **Expected in endpoint_memo.md**: "Tandai notifikasi telah dibaca."
- **Why needed**: Mark as read functionality untuk inbox
- **Controller needed**: `NotificationController@markAsRead()`
- **Request**: Empty body (atau `{ "read": true }`)
- **Expected Response**: `{ "success": true, "message": "Notifikasi ditandai sebagai dibaca" }`

---

## 📈 IMPLEMENTATION PRIORITY

### 🔥 HIGH PRIORITY (Blocking mobile app functionality)

1. **Auth endpoints** (3 endpoints)
    - Forgot/Reset password (publik, user-facing)
    - FCM token (required untuk push notifications)
    - Effort: ~4-6 jam (depends on FCM setup)

2. **System check-version** (1 endpoint)
    - Required di splash screen
    - Effort: ~1-2 jam

3. **Notifications endpoints** (3 endpoints)
    - Required untuk app inbox
    - Effort: ~3-4 jam

### 🟡 MEDIUM PRIORITY (Important but not blocking)

4. **Export endpoints** (4 endpoints)
    - Report generation untuk admin
    - Effort: ~8-12 jam (depends on Excel library & complexity)

5. **Import salary** (1 endpoint)
    - Bulk import feature
    - Effort: ~4-6 jam

6. **User GET detail** (1 endpoint)
    - Admin feature untuk manage users
    - Effort: ~1 jam (just fix the except clause)

### 🟢 LOW PRIORITY (Can be added later)

7. **Education articles GET** (2 endpoints)
    - Read-only for users, can read from sync/education
    - Effort: ~1 jam

8. **Price references GET** (2 endpoints)
    - Can read from sync/education
    - Effort: ~1 jam

9. **Investor dashboard** (1 endpoint)
    - Portal eksternal, nice-to-have
    - Effort: ~2-3 jam

10. **Sync periods POST** (1 endpoint)
    - Contract acceptance sync
    - Effort: ~3-4 jam

---

## 📊 DETAILED MISSING ENDPOINTS TABLE

| #   | Endpoint                   | Method | Category | Priority  | Estimated Effort |
| --- | -------------------------- | ------ | -------- | --------- | ---------------- |
| 1   | `/auth/forgot-password`    | POST   | Auth     | 🔥 HIGH   | 2h               |
| 2   | `/auth/reset-password`     | POST   | Auth     | 🔥 HIGH   | 2h               |
| 3   | `/auth/fcm-token`          | POST   | Auth     | 🔥 HIGH   | 2h               |
| 4   | `/users/{id}`              | GET    | Users    | 🟡 MEDIUM | 1h               |
| 5   | `/education-articles`      | GET    | Master   | 🟢 LOW    | 1h               |
| 6   | `/education-articles/{id}` | GET    | Master   | 🟢 LOW    | 0.5h             |
| 7   | `/price-references`        | GET    | Master   | 🟢 LOW    | 1h               |
| 8   | `/price-references/{id}`   | GET    | Master   | 🟢 LOW    | 0.5h             |
| 9   | `/sync/periods`            | POST   | Sync     | 🟢 LOW    | 4h               |
| 10  | `/export/rhpp`             | GET    | Export   | 🟡 MEDIUM | 4h               |
| 11  | `/export/harvests`         | GET    | Export   | 🟡 MEDIUM | 3h               |
| 12  | `/export/evaluations`      | GET    | Export   | 🟡 MEDIUM | 3h               |
| 13  | `/export/template-salary`  | GET    | Export   | 🟡 MEDIUM | 2h               |
| 14  | `/import/salary`           | POST   | Import   | 🟡 MEDIUM | 4h               |
| 15  | `/investor/dashboard`      | GET    | Portal   | 🟢 LOW    | 3h               |
| 16  | `/system/check-version`    | GET    | System   | 🔥 HIGH   | 2h               |
| 17  | `/notifications`           | GET    | Notify   | 🔥 HIGH   | 3h               |
| 18  | `/notifications/{id}/read` | PATCH  | Notify   | 🔥 HIGH   | 1h               |

**Total Estimated Effort**: ~40-50 jam

---

## 🔧 QUICK FIXES (Can do in <2 hours)

Endpoints yang bisa diperbaiki dengan minimal change:

```php
// FIX 1: Restore user show endpoint (line 44)
// BEFORE:
Route::apiResource('users', UserController::class)->except(['show']);
// AFTER:
Route::apiResource('users', UserController::class);

// FIX 2: Add education-articles GET (line 71)
// BEFORE:
Route::apiResource('education-articles', App\Http\Controllers\Api\V1\EducationArticleController::class)->only(['store', 'update', 'destroy']);
// AFTER:
Route::apiResource('education-articles', App\Http\Controllers\Api\V1\EducationArticleController::class);

// FIX 3: Add price-references GET (line 74)
// BEFORE:
Route::apiResource('price-references', App\Http\Controllers\Api\V1\PriceReferenceController::class)->only(['store', 'update', 'destroy']);
// AFTER:
Route::apiResource('price-references', App\Http\Controllers\Api\V1\PriceReferenceController::class);

// FIX 4: Add areas show endpoint (line 67)
// BEFORE:
Route::apiResource('areas', App\Http\Controllers\Api\V1\AreaController::class)->except(['show']);
// AFTER:
Route::apiResource('areas', App\Http\Controllers\Api\V1\AreaController::class);
```

---

## 📝 NOTES

1. **apiResource behavior**:
    - Full: `GET index`, `GET show`, `POST store`, `PUT/PATCH update`, `DELETE destroy`
    - `.except(['show'])` removes show endpoint
    - `.only(['store', 'destroy'])` allows ONLY store and destroy

2. **Missing endpoints are NOT in routes/api.php** - beberapa mungkin ada di file routes lain atau belum dibuat controller-nya

3. **Sync/periods POST** sudah di endpoint_memo tapi belum di routes, ini untuk handle offline contract acceptance dari ABK

4. **Export endpoints** membutuhkan library Excel (seperti Laravel Excel/PhpOffice)

5. **Priority didasarkan pada**:
    - Mobile app splash screen & core functionality (system, auth, notifications)
    - Data retrieval untuk offline sync (education, prices)
    - Admin features (exports, reports)

---

**Status Report**: 52/67 endpoints implemented (78% complete)  
**Blocking Issues**: 6 (Auth 3, System 1, Notifications 2)  
**Recommended Next**: Implement HIGH PRIORITY endpoints first (~8 hours)
