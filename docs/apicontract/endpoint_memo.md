# Rekapitulasi Final Endpoint API Anderson Farm (v3.0 Final)

**Arsitektur:** 100% Mobile-Only | **Pola Sinkronisasi:** Offline-First Aggregate & Online RESTful

Dokumen ini memuat daftar _endpoint_ API yang digunakan oleh aplikasi _mobile_ Anderson Farm. Arsitektur API dibagi menjadi dua kelompok utama: **Kelompok Online-Only** (untuk manajemen data kritis, setup awal, dan pelaporan) dan **Kelompok Sync** (untuk operasional lapangan tanpa sinyal internet).

---

## A. KELOMPOK ONLINE-ONLY (RESTFUL & FILES)

Seluruh aksi di bawah ini wajib dilakukan saat perangkat memiliki koneksi internet aktif demi menjaga integritas _database_ pusat dan mencegah duplikasi/konflik data.

### 1. Autentikasi & Keamanan

| Path                           | Method | Deskripsi Singkat                                               |
| :----------------------------- | :----: | :-------------------------------------------------------------- |
| `/api/v1/auth/login`           | `POST` | Login & _Device Binding_ (mengunci akun ke 1 HP)[cite: 7, 11].  |
| `/api/v1/auth/me`              | `GET`  | Cek sesi aktif & tarik hak akses (_role_ & _coop_assignments_). |
| `/api/v1/auth/logout`          | `POST` | Menghapus (_revoke_) token dari server.                         |
| `/api/v1/auth/fcm-token`       | `POST` | Simpan token Firebase untuk menerima _Push Notification_.       |
| `/api/v1/auth/forgot-password` | `POST` | Publik. Minta OTP/Link _reset password_.                        |
| `/api/v1/auth/reset-password`  | `POST` | Publik. Submit _password_ baru dengan OTP.                      |

### 2. Manajemen Pengguna & Profil (Admin/Self)

| Path                              |   Method    | Deskripsi Singkat                                                       |
| :-------------------------------- | :---------: | :---------------------------------------------------------------------- |
| `/api/v1/users`                   | `GET, POST` | Tarik daftar pekerja (paginasi) & Buat pekerja baru[cite: 10, 11].      |
| `/api/v1/users/{id}`              |    `PUT`    | Edit profil pekerja sepenuhnya.                                         |
| `/api/v1/users/{id}`              |   `PATCH`   | Edit parsial (Contoh: Reset/Unbind `device_id` pekerja jika HP hilang). |
| `/api/v1/users/{id}`              |  `DELETE`   | Hapus akun (_Soft-delete_)[cite: 14].                                   |
| `/api/v1/profile/change-password` |   `POST`    | Ubah _password_ mandiri bagi _user_ yang sedang _login_.                |

### 3. Master Data & Edukasi (Admin/System)

_Operasi CRUD (Create, Read, Update, Delete) untuk tabel referensi sistem._

| Path                             |      Method      | Deskripsi Singkat                                                        |
| :------------------------------- | :--------------: | :----------------------------------------------------------------------- |
| `/api/v1/areas`                  | `POST, PUT, DEL` | Kelola wilayah operasional[cite: 25].                                    |
| `/api/v1/farms`                  | `POST, PUT, DEL` | Kelola _farm_ di bawah suatu area[cite: 29].                             |
| `/api/v1/coops`                  | `POST, PUT, DEL` | Kelola bangunan fisik kandang, kapasitas, & `coop_type`[cite: 34].       |
| `/api/v1/form-configs`           | `POST, PUT, DEL` | Buat format JSON untuk UI _Dynamic Form_ (Sensor/HBE)[cite: 58].         |
| `/api/v1/equipment-types`        | `POST, PUT, DEL` | Daftar jenis alat/sensor fisik (Kipas, Heater)[cite: 52].                |
| `/api/v1/transaction-categories` | `POST, PUT, DEL` | Daftar kode akun arus kas (INCOME/EXPENSE)[cite: 145].                   |
| `/api/v1/ovk-items`              | `POST, PUT, DEL` | Master data jenis Obat, Vaksin, dan Kimia berserta satuannya[cite: 128]. |
| `/api/v1/education-articles`     | `POST, PUT, DEL` | Kelola artikel panduan/edukasi ABK[cite: 180].                           |
| `/api/v1/price-references`       | `POST, PUT, DEL` | Kelola referensi harga komoditas (ayam, pakan)[cite: 184].               |
| `/api/v1/report-templates`       | `POST, PUT, DEL` | Kelola _template_ auto-teks laporan WhatsApp[cite: 188].                 |

### 4. Setup Kandang & Periode Ternak (Krusial)

*Membangun kerangka struktural (*Setup*) wajib secara online agar aman dari konflik. Menggunakan pola Deferred Upload untuk dokumen pendukung.*

| Path                                    |   Method    | Deskripsi Singkat                                                              |
| :-------------------------------------- | :---------: | :----------------------------------------------------------------------------- |
| `/api/v1/coops/{id}/documents`          |   `POST`    | Menyimpan JSON URL untuk file SOP/Panduan fisik kandang[cite: 44].             |
| `/api/v1/coops/{id}/form-assignments`   |   `POST`    | Mengaktifkan master form spesifik ke kandang tertentu[cite: 65].               |
| `/api/v1/coops/{id}/equipments`         | `POST, DEL` | Meregistrasi/mencabut alat fisik (`unit_code`) di kandang[cite: 75].           |
| `/api/v1/periods`                       | `POST, PUT` | Inisiasi & edit kerangka Periode Ternak baru (`initial_stock`, dll)[cite: 80]. |
| `/api/v1/periods/{id}/investors`        |   `POST`    | Menugaskan persentase bagi hasil Investor di periode[cite: 101].               |
| `/api/v1/periods/{id}/checklist-tasks`  |   `POST`    | _Generate_ daftar SOP/tugas (_Boolean/Text_) khusus periode ini[cite: 91].     |
| `/api/v1/periods/{id}/form-assignments` |   `POST`    | Mengaitkan pertanyaan HBE spesifik khusus periode ini[cite: 61].               |
| `/api/v1/periods/{id}/contracts`        |   `POST`    | Menyimpan JSON URL kontrak bagi hasil untuk disetujui ABK[cite: 138].          |
| `/api/v1/periods/{id}/documents`        |   `POST`    | Menyimpan JSON URL file jadwal/dokumen khusus periode tersebut[cite: 88].      |
| `/api/v1/periods/{id}/rhpp-documents`   |   `POST`    | Menyimpan JSON URL dari file PDF RHPP Final yang dilampirkan Admin[cite: 174]. |

### 5. Ekspor, Impor & Upload File (Storage & Compute)

| Path                             |  Method  | Deskripsi Singkat                                                                                                   |
| :------------------------------- | :------: | :------------------------------------------------------------------------------------------------------------------ |
| `/api/v1/uploads`                |  `POST`  | **Storage Endpoint.** Unggah _file_ fisik (PDF/JPG) secara _multipart_. Mengembalikan _response_ berupa `file_url`. |
| `/api/v1/uploads`                | `DELETE` | Menghapus _file_ (_orphaned file_) jika _user_ batal klik simpan di aplikasi.                                       |
| `/api/v1/export/rhpp`            |  `GET`   | _Generate_ Excel/PDF perhitungan detail periode sebelum difinalisasi.                                               |
| `/api/v1/export/harvests`        |  `GET`   | _Generate_ Excel rekap panen bertahap (`HarvestEntry`)[cite: 119].                                                  |
| `/api/v1/export/evaluations`     |  `GET`   | _Generate_ Excel evaluasi teknis harian.                                                                            |
| `/api/v1/export/template-salary` |  `GET`   | Download format Excel kosong untuk persiapan _import_ gaji.                                                         |
| `/api/v1/import/salary`          |  `POST`  | Unggah data tagihan upah ABK massal via Excel (`EmployeeSalary`)[cite: 166].                                        |

### 6. Logika Bisnis & Portal Eksternal (RPC)

| Path                                | Method | Deskripsi Singkat                                                                                                       |
| :---------------------------------- | :----: | :---------------------------------------------------------------------------------------------------------------------- |
| `/api/v1/periods/{id}/close`        | `POST` | **Tutup Periode.** Validasi _backend_ otomatis untuk memastikan tak ada log _pending_ sebelum siklus dikunci[cite: 81]. |
| `/api/v1/rhpps/{period_id}/publish` | `POST` | **Ketok Palu RHPP.** Mengubah `publish_status` menjadi `PUBLISHED` dan mengunci laporan untuk Investor/PIC[cite: 171].  |
| `/api/v1/investor/dashboard`        | `GET`  | **Portal Investor.** Rekap ROI super ringan tanpa SQLite.                                                               |

### 7. Sistem & Notifikasi

| Path                              | Method  | Deskripsi Singkat                                                                  |
| :-------------------------------- | :-----: | :--------------------------------------------------------------------------------- |
| `/api/v1/system/check-version`    |  `GET`  | Tembak saat _Splash Screen_. Paksa _update_ ke PlayStore jika aplikasi _outdated_. |
| `/api/v1/notifications`           |  `GET`  | Tarik riwayat pesan masuk (_Inbox_ aplikasi).                                      |
| `/api/v1/notifications/{id}/read` | `PATCH` | Tandai notifikasi telah dibaca.                                                    |

---

## B. KELOMPOK SYNC (OFFLINE-FIRST AGGREGATE)

Kelompok ini menjadi denyut nadi operasional lapangan yang _blank-spot_. Wajib menggunakan parameter `last_sync_server_id` dan `last_sync_timestamp`. **Persetujuan (Approval) Manager dilakukan secara offline** dengan cara mengubah kolom `business_status` [cite: 110, 156] lalu me- _push_-nya ke _endpoint_ `daily-activities` atau `finances`.

| Path                            |   Method    | Deskripsi Singkat                                                                                                                         |
| :------------------------------ | :---------: | :---------------------------------------------------------------------------------------------------------------------------------------- |
| `/api/v1/sync/master-data`      |    `GET`    | Mengunduh _cache_ referensi dasar. (Termasuk daftar `ovk_items` dan `coop_documents`) [cite: 44, 128].                                    |
| `/api/v1/sync/periods`          | `GET, POST` | `GET` untuk menarik detail periode aktif. `POST` HANYA untuk mengirim jejak persetujuan digital `ContractAcceptance` dari ABK[cite: 141]. |
| `/api/v1/sync/daily-activities` | `GET, POST` | **Beban Terberat.** Sinkronisasi satu _Header_ beserta relasi anaknya (HBE, Foto, Panen, `ovk_usages`, & Checklist)[cite: 108, 114].      |
| `/api/v1/sync/finances`         | `GET, POST` | Sinkronisasi Arus Kas (Transaksi pembelian Pakan/DOC, operasional) dan status pembayaran gaji[cite: 150, 166].                            |
| `/api/v1/sync/maintenances`     | `GET, POST` | Sinkronisasi perbaikan fisik bangunan kandang (`MaintenanceLog`)[cite: 48].                                                               |
| `/api/v1/sync/rhpps`            |    `GET`    | Unduh rekap _read-only_ laba bersih akhir dan dokumen PDF-nya untuk dilihat ABK/PIC[cite: 171, 174].                                      |
| `/api/v1/sync/education`        |    `GET`    | Unduh artikel edukasi & referensi harga secara _offline_[cite: 180, 184].                                                                 |
| `/api/v1/sync/activity-logs`    |   `POST`    | _Push-only._ Kirim rekam jejak (_audit trail_) tombol aplikasi yang diklik _user_[cite: 18].                                              |
