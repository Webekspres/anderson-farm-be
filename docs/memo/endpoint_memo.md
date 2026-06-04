# Rekapitulasi Final Endpoint API Anderson Farm (v3.0 Final)

**Arsitektur:** 100% Mobile-Only | **Pola Sinkronisasi:** Offline-First Aggregate & Online RESTful

Dokumen ini memuat daftar _endpoint_ API Anderson Farm. **Sumber kebenaran implementasi:** [`routes/api.php`](../../routes/api.php). Konvensi update resource: **`PATCH` only** (tidak ada `PUT`).

Arsitektur API dibagi menjadi dua kelompok utama: **Kelompok Online-Only** (untuk manajemen data kritis, setup awal, dan pelaporan) dan **Kelompok Sync** (untuk operasional lapangan tanpa sinyal internet).

---

## 0. Infrastruktur (di luar v1)

| Path        | Method | Deskripsi Singkat                          |
| :---------- | :----: | :----------------------------------------- |
| `/api/check` | `GET`  | Health check server (monitoring / devops). |

---

## A. KELOMPOK ONLINE-ONLY (RESTFUL & FILES)

Seluruh aksi di bawah ini wajib dilakukan saat perangkat memiliki koneksi internet aktif demi menjaga integritas _database_ pusat dan mencegah duplikasi/konflik data.

### 1. Autentikasi & Keamanan

| Path                           | Method | Deskripsi Singkat                                               | Route |
| :----------------------------- | :----: | :-------------------------------------------------------------- | :---: |
| `/api/v1/auth/login`           | `POST` | Login & _Device Binding_ (mengunci akun ke 1 HP).               |   ✅   |
| `/api/v1/auth/me`              | `GET`  | Cek sesi aktif & tarik hak akses (_role_ & _coop_assignments_). |   ✅   |
| `/api/v1/auth/logout`          | `POST` | Menghapus (_revoke_) token dari server.                         |   ✅   |
| `/api/v1/auth/forgot-password` | `POST` | Publik. Minta OTP/Link _reset password_ (fase berikutnya).     |   📋   |
| `/api/v1/auth/reset-password`  | `POST` | Publik. `method=old_password`: ganti password sendiri (username + password lama). `method=admin_reset`: Bearer admin + `user_id` reset akun lain. `method=otp`: belum tersedia. |   ✅   |
| `/api/v1/auth/fcm-token`       | `POST` | Simpan token Firebase untuk menerima _Push Notification_.       |   ✅   |

### 2. Manajemen Pengguna & Profil (Admin/Self)

| Path                                  |   Method    | Deskripsi Singkat                                                       | Route |
| :------------------------------------ | :---------: | :---------------------------------------------------------------------- | :---: |
| `/api/v1/users`                       | `GET, POST` | Tarik daftar pekerja (paginasi) & Buat pekerja baru.                    |   ✅   |
| `/api/v1/users/{id}`                  |   `PATCH`   | Edit profil pekerja (parsial atau lengkap).                              |   ✅   |
| `/api/v1/users/{id}`                  |  `DELETE`   | Hapus akun (_Soft-delete_).                                             |   ✅   |

### 3. Master Data & Edukasi (Admin/System)

_Operasi CRUD untuk tabel referensi sistem. Update resource memakai `PATCH`._

| Path                                        |      Method       | Deskripsi Singkat                                                                         | Route |
| :------------------------------------------ | :---------------: | :---------------------------------------------------------------------------------------- | :---: |
| `/api/v1/areas`                             |    `GET, POST`    | Tarik daftar & Kelola wilayah operasional.                                                |   ✅   |
| `/api/v1/areas/{id}`                        | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus area.                                                      |   ✅   |
| `/api/v1/farms`                             |    `GET, POST`    | Tarik daftar & Kelola _farm_ di bawah suatu area.                                         |   ✅   |
| `/api/v1/farms/{id}`                        | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus farm.                                                      |   ✅   |
| `/api/v1/coops`                             |    `GET, POST`    | Tarik daftar & Kelola bangunan fisik kandang, kapasitas, & `coop_type`.                   |   ✅   |
| `/api/v1/coops/{id}`                        | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus kandang.                                                     |   ✅   |
| `/api/v1/coop-floors`                       |    `GET, POST`    | Tarik daftar & Kelola lantai kandang.                                                     |   ✅   |
| `/api/v1/coop-floors/{id}`                  | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus lantai.                                                    |   ✅   |
| `/api/v1/form-configs`                      |    `GET, POST`    | Tarik daftar & Buat format JSON untuk UI _Dynamic Form_.                                  |   ✅   |
| `/api/v1/form-configs/{id}`                 | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus konfigurasi form.                                          |   ✅   |
| `/api/v1/equipment-types`                   |    `GET, POST`    | Tarik daftar & Kelola jenis alat/sensor fisik.                                            |   ✅   |
| `/api/v1/equipment-types/{id}`              | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus jenis alat.                                                |   ✅   |
| `/api/v1/equipment-types/{id}/form-configs` |    `GET, POST`    | `GET` cetak biru form dinamis per tipe alat; `POST` sinkron pemetaan (`form_config_ids`). |   ✅   |
| `/api/v1/transaction-categories`            |    `GET, POST`    | Tarik daftar & Kelola kode akun arus kas.                                                 |   ✅   |
| `/api/v1/transaction-categories/{id}`     | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus kategori transaksi.                                        |   ✅   |
| `/api/v1/ovk-items`                         |    `GET, POST`    | Tarik daftar & Kelola master data jenis Obat, Vaksin, Kimia.                              |   ✅   |
| `/api/v1/ovk-items/{id}`                    | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus OVK item.                                                  |   ✅   |
| `/api/v1/education-articles`                |      `POST`       | Buat artikel panduan/edukasi ABK (admin).                                                 |   ✅   |
| `/api/v1/education-articles/{id}`           |  `PATCH, DELETE`  | Ubah atau hapus artikel edukasi.                                                          |   ✅   |
| `/api/v1/price-references`                  |      `POST`       | Buat referensi harga komoditas.                                                           |   ✅   |
| `/api/v1/price-references/{id}`             |  `PATCH, DELETE`  | Ubah atau hapus referensi harga.                                                          |   ✅   |
| `/api/v1/report-templates`                  |    `GET, POST`    | Tarik daftar & Kelola _template_ auto-teks laporan.                                       |   ✅   |
| `/api/v1/report-templates/{id}`             | `GET, PATCH, DEL` | Ambil detail, ubah, atau hapus report template.                                           |   ✅   |

> **Catatan:** `education-articles` dan `price-references` hanya **write-only** lewat REST (`POST`, `PATCH`, `DELETE`). Pembacaan daftar & detail hanya via `GET /api/v1/sync/education` — tidak ada versi online `GET` REST.

### 4. Setup Kandang & Periode Ternak (Krusial)

*Membangun kerangka struktural (*Setup*) wajib secara online agar aman dari konflik. Menggunakan pola Deferred Upload untuk dokumen pendukung.*

| Path                                    |        Method        | Deskripsi Singkat                                                                                                                     | Route |
| :-------------------------------------- | :------------------: | :------------------------------------------------------------------------------------------------------------------------------------ | :---: |
| `/api/v1/coops/{id}/documents`          | `GET`, `POST`, `DEL` | Menyimpan JSON URL untuk file SOP/Panduan fisik kandang.                                                                              |   ✅   |
| `/api/v1/coops/{id}/user-assignments`   |        `POST`        | Menugaskan pekerja ke kandang tertentu (Bulk).                                                                                        |   ✅   |
| `/api/v1/coops/{id}/equipments`         |     `GET, POST, DEL` | `GET` daftar alat; `POST` registrasi; `DELETE` cabut alat (`unit_code`) di kandang.                                                   |   ✅   |
| `/api/v1/coops/{id}/form-assignments`   |        `POST`        | Mengaktifkan master form spesifik ke kandang tertentu.                                                                                |   ✅   |
| `/api/v1/periods`                       |        `POST`        | Inisiasi Periode Ternak baru (`initial_stock`, dll).                                                                                  |   ✅   |
| `/api/v1/periods/{id}`                  |       `PATCH`        | Edit kerangka periode (`initial_stock`, tanggal, dll).                                                                                |   ✅   |
| `/api/v1/periods/{id}/investors`        |        `POST`        | Menugaskan persentase bagi hasil Investor di periode.                                                                                 |   ✅   |
| `/api/v1/periods/{id}/form-assignments` |    `GET`, `POST`     | `GET` menarik daftar penugasan form HBE aktif pada periode; `POST` sinkronisasi (tulis ulang) struktur form.                          |   ✅   |
| `/api/v1/periods/{id}/checklist-tasks`  |    `GET`, `POST`     | `GET` menarik daftar tugas SOP/checklist yang sudah dibuat; `POST` _generate_/sinkronisasi tugas (_Boolean/Text_) khusus periode ini. |   ✅   |
| `/api/v1/periods/{id}/contracts`        |     `GET`,`POST`     | Menyimpan JSON URL kontrak bagi hasil untuk disetujui ABK.                                                                            |   ✅   |
| `/api/v1/contracts/{id}`              | `GET`, `POST`, `DEL` | Detail kontrak; `POST` persetujuan ABK; `DELETE` hapus kontrak.                                                                       |   ✅   |
| `/api/v1/periods/{id}/documents`        |    `GET`, `POST`     | Menyimpan JSON URL file jadwal/dokumen khusus periode tersebut.                                                                       |   ✅   |

### 5. Ekspor, Impor & Upload File (Storage & Compute)

| Path                             |  Method  | Deskripsi Singkat                                                                                                   | Route |
| :------------------------------- | :------: | :------------------------------------------------------------------------------------------------------------------ | :---: |
| `/api/v1/uploads`                |  `POST`  | **Storage Endpoint.** Unggah _file_ fisik (PDF/JPG) secara _multipart_. Mengembalikan _response_ berupa `file_url`. |   ✅   |
| `/api/v1/uploads`                | `DELETE` | Menghapus _file_ (_orphaned file_) jika _user_ batal klik simpan di aplikasi.                                       |   ✅   |
| `/api/v1/export/rhpp`            |  `GET`   | _Generate_ Excel/PDF performa teknis & laba rugi final per periode.                                                 |   📋   |
| `/api/v1/export/harvests`        |  `GET`   | _Generate_ Excel rekapitulasi panen parsial/bertahap (HarvestEntry).                                                |   📋   |
| `/api/v1/export/evaluations`     |  `GET`   | _Generate_ Excel evaluasi performa teknis (BW, FCR, Deplesi).                                                       |   📋   |
| `/api/v1/export/ovk-usages`      |  `GET`   | Rekap detail pemakaian obat, vaksin, dan kimia.                                                                     |   ✅   |
| `/api/v1/export/bop-details`     |  `GET`   | Excel rincian Biaya Operasional Kandang per periode untuk audit keuangan.                                           |   ✅   |
| `/api/v1/export/template-salary` |  `GET`   | Template Excel gaji ABK per periode aktif (Finance/Admin).                                                          |   ✅   |
| `/api/v1/import/salary`          |  `POST`  | Upload template gaji ABK (all-or-nothing, bulk error). Finance/Admin.                                               |   ✅   |

> **Catatan:** `RhppExportController` sudah ada; route `GET /export/rhpp` belum didaftarkan (**📋**).

### 6. Logika Bisnis & Portal Eksternal (RPC)

| Path                                  | Method | Deskripsi Singkat                                                                                             | Route |
| :------------------------------------ | :----: | :------------------------------------------------------------------------------------------------------------ | :---: |
| `/api/v1/periods/{id}/close`          | `POST` | **Tutup Periode.** Validasi _backend_ otomatis untuk memastikan tak ada log _pending_ sebelum siklus dikunci. |   ✅   |
| `/api/v1/periods/{id}/rhpp-documents` | `POST` | Menyimpan JSON URL dari file PDF RHPP Final yang dilampirkan Admin.                                           |   ✅   |
| `/api/v1/rhpps/{period_id}/publish`   | `POST` | **Ketok Palu RHPP.** Mengubah `publish_status` menjadi `PUBLISHED` dan mengunci laporan untuk Investor/PIC.   |   ✅   |
| `/api/v1/investor/dashboard`          | `GET`  | **Portal Investor.** Rekap ROI super ringan tanpa SQLite.                                                     |   📋   |

### 6b. Approval Manager (Online-Only)

Modul persetujuan laporan harian ABK. **Tidak ada offline queue** — Manager wajib online saat approve/reject. ABK menerima status final via `GET /api/v1/sync/daily-activities`.

| Path                                         | Method | Deskripsi Singkat                                                                                      | Route |
| :------------------------------------------- | :----: | :----------------------------------------------------------------------------------------------------- | :---: |
| `/api/v1/approvals/daily-activities`         |  `GET` | Daftar laporan menunggu approval (default `business_status=SUBMITTED`). Filter: periode, kandang, tanggal. RBAC: Admin/Manager. |   ✅   |
| `/api/v1/approvals/daily-activities/{id}`    |  `GET` | Detail laporan harian lengkap (header + relasi anak) untuk layar review Manager.                       |   ✅   |
| `/api/v1/approvals/daily-activities/{id}`    | `POST` | Approve atau reject laporan. Body: `{ "action": "approve" }` atau `{ "action": "reject", "rejection_reason": "..." }`. Kirim notifikasi push ke ABK. |   ✅   |

### 7. Sistem & Notifikasi

| Path                              | Method  | Deskripsi Singkat                                                                  | Route |
| :-------------------------------- | :-----: | :--------------------------------------------------------------------------------- | :---: |
| `/api/v1/system/check-version`    |  `GET`  | Tembak saat _Splash Screen_. Query: `platform`, `current_version`. Respon: `FORCE_UPDATE` / `OPTIONAL_UPDATE` / `NO_UPDATE_NEEDED`. |   ✅   |
| `/api/v1/notifications`           |  `GET`  | Tarik riwayat pesan masuk (_Inbox_ aplikasi).                                      |   ✅   |
| `/api/v1/notifications/read-all`  | `PATCH` | Tandai **semua** notifikasi unread milik user login sebagai dibaca (mass update).  |   ✅   |
| `/api/v1/notifications/{id}/read` | `PATCH` | Tandai satu notifikasi telah dibaca.                                               |   ✅   |

**Legenda kolom Route:** ✅ terdaftar di `routes/api.php` · 📋 rencana / belum di-route

---

## B. KELOMPOK SYNC (OFFLINE-FIRST AGGREGATE)

Kelompok ini menjadi denyut nadi operasional lapangan yang _blank-spot_. Wajib menggunakan parameter `last_sync_server_id` dan `last_sync_timestamp`. ABK hanya boleh _push_ status operasional (`DRAFT`, `SUBMITTED`, `NEEDS_REVIEW`) via sync; status `APPROVED`/`REJECTED` hanya di-set Manager melalui endpoint online `/api/v1/approvals/daily-activities/{id}`.

| Path                            |   Method    | Deskripsi Singkat                                                                                                              | Route |
| :------------------------------ | :---------: | :----------------------------------------------------------------------------------------------------------------------------- | :---: |
| `/api/v1/sync/master-data`      |    `GET`    | Mengunduh _cache_ referensi dasar. (Termasuk daftar `ovk_items` dan `coop_documents`) .                                        |   ✅   |
| `/api/v1/sync/periods`          | `GET, POST` | `GET` untuk menarik detail periode aktif. `POST` HANYA untuk mengirim jejak persetujuan digital `ContractAcceptance` dari ABK. |   ✅   |
| `/api/v1/sync/daily-activities` | `GET, POST` | **Beban Terberat.** Sinkronisasi satu _Header_ beserta relasi anaknya (HBE, Foto, Panen, `ovk_usages`, & Checklist).           |   ✅   |
| `/api/v1/sync/finances`         | `GET, POST` | mengelola sinkronisasi data transaksi pengeluaran operasional dan penarikan status gaji dengan pembatasan peran                |   ✅   |
| `/api/v1/sync/maintenances`     | `GET, POST` | Sinkronisasi pelaporan kerusakan dan status pemeliharaan fasilitas (bangunan dan mesin) berdasarkan spesifik lantai kandang    |   ✅   |
| `/api/v1/sync/rhpps`            |    `GET`    | Unduh rekap _read-only_ laba bersih akhir dan dokumen PDF-nya untuk dilihat ABK/PIC.                                           |   ✅   |
| `/api/v1/sync/education`        |    `GET`    | Unduh artikel edukasi & referensi harga secara _offline_.                                                                      |   ✅   |
| `/api/v1/sync/activity-logs`    |   `POST`    | _Push-only._ Bulk sync audit trail (`logs[]`, idempotent by UUID). Sanctum wajib.                                             |   ✅   |
