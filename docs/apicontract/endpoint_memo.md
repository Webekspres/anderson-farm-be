# Rekapitulasi Final Endpoint API Anderson Farm (v3.0 Final)

**Arsitektur:** 100% Mobile-Only | **Pola Sinkronisasi:** Offline-First Aggregate & Online RESTful

Dokumen ini memuat daftar _endpoint_ API yang digunakan oleh aplikasi _mobile_ Anderson Farm. Arsitektur API dibagi menjadi dua kelompok utama: **Kelompok Online-Only** (untuk manajemen data kritis, setup awal, dan pelaporan) dan **Kelompok Sync** (untuk operasional lapangan tanpa sinyal internet).

---

## A. KELOMPOK ONLINE-ONLY (RESTFUL & FILES)

Seluruh aksi di bawah ini wajib dilakukan saat perangkat memiliki koneksi internet aktif demi menjaga integritas _database_ pusat dan mencegah duplikasi/konflik data.

### 1. Autentikasi & Keamanan

| Path                           | Method | Deskripsi Singkat                                               |
| :----------------------------- | :----: | :-------------------------------------------------------------- |
| `/api/v1/auth/login`           | `POST` | Login & _Device Binding_ (mengunci akun ke 1 HP).               |
| `/api/v1/auth/me`              | `GET`  | Cek sesi aktif & tarik hak akses (_role_ & _coop_assignments_). |
| `/api/v1/auth/logout`          | `POST` | Menghapus (_revoke_) token dari server.                         |
| `/api/v1/auth/forgot-password` | `POST` | Publik. Minta OTP/Link _reset password_.                        |
| `/api/v1/auth/reset-password`  | `POST` | Publik. Submit _password_ baru dengan OTP.                      |
| `/api/v1/auth/fcm-token`       | `POST` | Simpan token Firebase untuk menerima _Push Notification_.       |

### 2. Manajemen Pengguna & Profil (Admin/Self)

| Path                              |   Method    | Deskripsi Singkat                                                       |
| :-------------------------------- | :---------: | :---------------------------------------------------------------------- |
| `/api/v1/users`                   | `GET, POST` | Tarik daftar pekerja (paginasi) & Buat pekerja baru.                    |
| `/api/v1/users/{id}`              |    `PUT`    | Edit profil pekerja sepenuhnya.                                         |
| `/api/v1/users/{id}`              |   `PATCH`   | Edit parsial (Contoh: Reset/Unbind `device_id` pekerja jika HP hilang). |
| `/api/v1/users/{id}`              |  `DELETE`   | Hapus akun (_Soft-delete_).                                             |
| `/api/v1/profile/change-password` |   `POST`    | Ubah _password_ mandiri bagi _user_ yang sedang _login_.                |

### 3. Master Data & Edukasi (Admin/System)

_Operasi CRUD (Create, Read, Update, Delete) untuk tabel referensi sistem._

| Path                             |      Method      | Deskripsi Singkat                                             |
| :------------------------------- | :--------------: | :------------------------------------------------------------ |
| `/api/v1/areas`                  | `POST, PATCH, DEL` | Kelola wilayah operasional.                                   |
| `/api/v1/farms`                  | `POST, PATCH, DEL` | Kelola _farm_ di bawah suatu area.                            |
| `/api/v1/coops`                  | `POST, PATCH, DEL` | Kelola bangunan fisik kandang, kapasitas, & `coop_type`.      |
| `/api/v1/form-configs`           | `POST, PATCH, DEL` | Buat format JSON untuk UI _Dynamic Form_ (Sensor/HBE).        |
| `/api/v1/equipment-types`        | `POST, PATCH, DEL` | Daftar jenis alat/sensor fisik (Kipas, Heater).               |
| `/api/v1/transaction-categories` | `POST, PATCH, DEL` | Daftar kode akun arus kas (INCOME/EXPENSE).                   |
| `/api/v1/ovk-items`              | `POST, PATCH, DEL` | Master data jenis Obat, Vaksin, dan Kimia berserta satuannya. |
| `/api/v1/education-articles`     | `POST, PATCH, DEL` | Kelola artikel panduan/edukasi ABK.                           |
| `/api/v1/price-references`       | `POST, PATCH, DEL` | Kelola referensi harga komoditas (ayam, pakan).               |
| `/api/v1/report-templates`       | `POST, PATCH, DEL` | Kelola _template_ auto-teks laporan WhatsApp.                 |

### 4. Setup Kandang & Periode Ternak (Krusial)

*Membangun kerangka struktural (*Setup*) wajib secara online agar aman dari konflik. Menggunakan pola Deferred Upload untuk dokumen pendukung.*

| Path                                    |   Method    | Deskripsi Singkat                                                    |
| :-------------------------------------- | :---------: | :------------------------------------------------------------------- |
| `/api/v1/coops/{id}/documents`          |   `POST`    | Menyimpan JSON URL untuk file SOP/Panduan fisik kandang.             |
| `/api/v1/coops/{id}/form-assignments`   |   `POST`    | Mengaktifkan master form spesifik ke kandang tertentu.               |
| `/api/v1/coops/{id}/equipments`         | `POST, DEL` | Meregistrasi/mencabut alat fisik (`unit_code`) di kandang.           |
| `/api/v1/periods`                       | `POST, PUT` | Inisiasi & edit kerangka Periode Ternak baru (`initial_stock`, dll). |
| `/api/v1/periods/{id}/investors`        |   `POST`    | Menugaskan persentase bagi hasil Investor di periode.                |
| `/api/v1/periods/{id}/checklist-tasks`  |   `POST`    | _Generate_ daftar SOP/tugas (_Boolean/Text_) khusus periode ini.     |
| `/api/v1/periods/{id}/form-assignments` |   `POST`    | Mengaitkan pertanyaan HBE spesifik khusus periode ini.               |
| `/api/v1/periods/{id}/contracts`        |   `POST`    | Menyimpan JSON URL kontrak bagi hasil untuk disetujui ABK.           |
| `/api/v1/periods/{id}/documents`        |   `POST`    | Menyimpan JSON URL file jadwal/dokumen khusus periode tersebut.      |
| `/api/v1/periods/{id}/rhpp-documents`   |   `POST`    | Menyimpan JSON URL dari file PDF RHPP Final yang dilampirkan Admin.  |

### 5. Ekspor, Impor & Upload File (Storage & Compute)

| Path                             |  Method  | Deskripsi Singkat                                                                                                   |
| :------------------------------- | :------: | :------------------------------------------------------------------------------------------------------------------ |
| `/api/v1/uploads`                |  `POST`  | **Storage Endpoint.** Unggah _file_ fisik (PDF/JPG) secara _multipart_. Mengembalikan _response_ berupa `file_url`. |
| `/api/v1/uploads`                | `DELETE` | Menghapus _file_ (_orphaned file_) jika _user_ batal klik simpan di aplikasi.                                       |
| `/api/v1/export/rhpp`            |  `GET`   | _Generate_ Excel/PDF perhitungan detail periode sebelum difinalisasi.                                               |
| `/api/v1/export/harvests`        |  `GET`   | _Generate_ Excel rekap panen bertahap (`HarvestEntry`).                                                             |
| `/api/v1/export/evaluations`     |  `GET`   | _Generate_ Excel evaluasi teknis harian.                                                                            |
| `/api/v1/export/template-salary` |  `GET`   | Download format Excel kosong untuk persiapan _import_ gaji.                                                         |
| `/api/v1/import/salary`          |  `POST`  | Unggah data tagihan upah ABK massal via Excel (`EmployeeSalary`).                                                   |

### 6. Logika Bisnis & Portal Eksternal (RPC)

| Path                                | Method | Deskripsi Singkat                                                                                             |
| :---------------------------------- | :----: | :------------------------------------------------------------------------------------------------------------ |
| `/api/v1/periods/{id}/close`        | `POST` | **Tutup Periode.** Validasi _backend_ otomatis untuk memastikan tak ada log _pending_ sebelum siklus dikunci. |
| `/api/v1/rhpps/{period_id}/publish` | `POST` | **Ketok Palu RHPP.** Mengubah `publish_status` menjadi `PUBLISHED` dan mengunci laporan untuk Investor/PIC.   |
| `/api/v1/investor/dashboard`        | `GET`  | **Portal Investor.** Rekap ROI super ringan tanpa SQLite.                                                     |

### 7. Sistem & Notifikasi

| Path                              | Method  | Deskripsi Singkat                                                                  |
| :-------------------------------- | :-----: | :--------------------------------------------------------------------------------- |
| `/api/v1/system/check-version`    |  `GET`  | Tembak saat _Splash Screen_. Paksa _update_ ke PlayStore jika aplikasi _outdated_. |
| `/api/v1/notifications`           |  `GET`  | Tarik riwayat pesan masuk (_Inbox_ aplikasi).                                      |
| `/api/v1/notifications/{id}/read` | `PATCH` | Tandai notifikasi telah dibaca.                                                    |

---

## B. KELOMPOK SYNC (OFFLINE-FIRST AGGREGATE)

Kelompok ini menjadi denyut nadi operasional lapangan yang _blank-spot_. Wajib menggunakan parameter `last_sync_server_id` dan `last_sync_timestamp`. **Persetujuan (Approval) Manager dilakukan secara offline** dengan cara mengubah kolom `business_status` inances`.

| Path                            |   Method    | Deskripsi Singkat                                                                                                              |
| :------------------------------ | :---------: | :----------------------------------------------------------------------------------------------------------------------------- |
| `/api/v1/sync/master-data`      |    `GET`    | Mengunduh _cache_ referensi dasar. (Termasuk daftar `ovk_items` dan `coop_documents`) .                                        |
| `/api/v1/sync/periods`          | `GET, POST` | `GET` untuk menarik detail periode aktif. `POST` HANYA untuk mengirim jejak persetujuan digital `ContractAcceptance` dari ABK. |
| `/api/v1/sync/daily-activities` | `GET, POST` | **Beban Terberat.** Sinkronisasi satu _Header_ beserta relasi anaknya (HBE, Foto, Panen, `ovk_usages`, & Checklist).           |
| `/api/v1/sync/finances`         | `GET, POST` | Sinkronisasi Arus Kas (Transaksi pembelian Pakan/DOC, operasional) dan status pembayaran gaji.                                 |
| `/api/v1/sync/maintenances`     | `GET, POST` | Sinkronisasi perbaikan fisik bangunan kandang (`MaintenanceLog`).                                                              |
| `/api/v1/sync/rhpps`            |    `GET`    | Unduh rekap _read-only_ laba bersih akhir dan dokumen PDF-nya untuk dilihat ABK/PIC.                                           |
| `/api/v1/sync/education`        |    `GET`    | Unduh artikel edukasi & referensi harga secara _offline_.                                                                      |
| `/api/v1/sync/activity-logs`    |   `POST`    | _Push-only._ Kirim rekam jejak (_audit trail_) tombol aplikasi yang diklik _user_.                                             |
