### feat(export): tambah export harvest & evaluasi performa
Implementasi GET /export/harvests dan GET /export/evaluations
dengan RBAC admin/manager/investor, algoritma stok/FCR/deplesi
harian, serta tes Pest lengkap.

Tambahkan feed_consumption_kg pada DailyActivityHeader agar
konsumsi pakan tersinkron via sync dan dipakai RhppCalculationService.
Perbarui kontrak OpenAPI, Postman, endpoint_memo, dan tes sync/approval.

---

### feat(api): infrastruktur, approval, gaji, dan konsolidasi auth
Tambahkan fondasi offline-first dan modul operasional yang belum ada di
branch dev, sekaligus merapikan kontrak API dan autentikasi password.

- Infrastruktur: GET /system/check-version (publik, app_version dari .env)
  dan POST /sync/activity-logs (Sanctum, bulk idempotent updateOrCreate)
- Auth: gabung reset password ke POST /auth/reset-password
  (old_password publik + admin_reset dengan token); hapus
  /profile/change-password dan /users/{id}/reset-password
- Migrasi route update ke PATCH-only; tes HttpMethodPolicy + penyesuaian
  FormConfig/TransactionCategory/UpdateUser
- Approval: modul /approvals/daily-activities + service, job notifikasi ABK
- Finances: export template gaji dan import gaji (all-or-nothing)
- Refactor DailyActivitySyncController ke DailyActivitySyncService;
  enum BusinessStatus; perbaikan seeder ContractAbk & kategori gaji
- Dokumentasi: endpoint_memo, OpenAPI put→patch, Postman gaji, run_test.md
- Forgot-password dan OTP email sengaja tidak diimplementasikan

---

### feat(api): endpoint operasional, selaraskan erd, dan perbaiki sinkron
Tambahkan modul API untuk mobile v3.0: ekspor OVK/BOP, CRUD lantai
kandang, blueprint form periode (GET + ui_metadata), checklist SOP
periode, pemetaan EquipmentType–FormConfig (GET/POST sync), serta
PATCH notifications/read-all mass update.

Selaraskan skema dengan erd.prisma: server_id BigInt pada banyak
tabel, coop_type di Coop (bukan CoopFloor), migrasi/factory/seeder,
dan penyesuaian model serta API Resource.

Refactor sinkron finans & aktivitas harian; ganti controller form-config
alat ke EquipmentTypeFormConfigController; perbarui endpoint_memo,
urgent_plan, sitemap, postman, dan tes Pest terkait.

Hapus SyncEquipmentTypeFormConfigController lama dan tes export RHPP
yang digantikan struktur baru.

---

### issue export rhpp

---


Berikut adalah draf rekapitulasi progres yang lebih singkat, padat, dan hanya menggunakan satu level daftar poin agar lebih praktis dibaca di grup *chat*:

---

*Laporan Progres Mingguan Backend API & Sinkronisasi*

Seminggu ini telah diselesaikan beberapa pembaruan fungsional, penyelarasan database, dan optimasi arsitektur berikut:

* *Ekspor & Impor Finansial:* Mengimplementasikan rute `GET /export/harvests` untuk rekapitulasi panen parsial, `GET /export/evaluations` untuk tabel rekap teknis harian (Deplesi, BW, FCR Berjalan), serta fitur ekspor template dan impor data total gaji tim Finance dengan prinsip aman *All-or-Nothing*.
* *Infrastruktur Offline-First & Sinkronisasi:* Membuat endpoint publik `GET /system/check-version` sebagai gerbang filter versi aplikasi mobile via `.env` dan endpoint `POST /sync/activity-logs` terproteksi untuk setoran massal log jejak audit secara *idempotent* (`updateOrCreate`).
* *Penyelarasan Skema Database (ERD Prisma):* Menambahkan field murni `feed_consumption_kg` langsung pada induk tabel `DailyActivityHeader` untuk kalkulasi pakan harian, mengubah tipe data `server_id` menjadi `BigInt` pada banyak tabel utama, dan memindahkan properti `coop_type` ke level `Coop`.
* *Modul Operasional & Route PATCH-only:* Menambahkan API pendukung mobile v3.0 (Ekspor OVK/BOP, CRUD master lantai kandang, blueprint form periode, dan checklist SOP), memetakan relasi `EquipmentType`–`FormConfig`, serta memigrasikan seluruh *route update* data menjadi `PATCH-only` (termasuk fitur *read-all* notifikasi massal).
* *Alur Persetujuan (Approval):* Membangun modul persetujuan log harian melalui rute `/approvals/daily-activities` lengkap dengan layanan otomatis pengiriman notifikasi balik ke perangkat gawai milik ABK.
* *Konsolidasi Fitur Auth:* Menyederhanakan dan merapikan pintu perubahan kata sandi dengan menyatukan seluruh logika ke dalam satu rute pintu `POST /auth/reset-password` (mendukung *old_password* publik maupun *admin_reset* via token), serta menghapus rute-rute lama yang redundan.
* *Pembaruan Dokumentasi & Testing:* Melakukan penyesuaian menyeluruh pada kontrak OpenAPI (mengubah metode PUT ke PATCH), Postman Collection terbaru, berkas `endpoint_memo`, serta memastikan seluruh fungsional di atas lolos pengujian otomatis menggunakan Pest PHP.

---