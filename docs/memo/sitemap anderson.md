# Sitemap Anderson Farm — Mobile App

> **⚠️ DOKUMEN HISTORIS** — Dibuat saat fase perencanaan awal. TODO inline di dokumen ini **sudah diimplementasi** di kode saat ini. Untuk status aktual, gunakan [handover FE](../../../anderson-farm-fe/docs/handover/) dan [handover BE](../handover/).

> **Platform:** 100% Mobile-Only (Expo Router / React Native)
> **Backend:** Laravel API (`anderson-farm-be`)
> **Spesifikasi:** Flow v0.6.1 + Proposal Pengembangan Aplikasi

---

## Konvensi

| Simbol     | Keterangan                                                  |
| ---------- | ----------------------------------------------------------- |
| `[id]`     | Dynamic route parameter                                     |
| `📴`       | Offline-first (baca SQLite dulu, sync bila online)          |
| `🌐`       | Online-only (wajib koneksi)                                 |
| `👷`       | Requires login — ABK / PIC (operasional lapangan)           |
| `👤`       | Requires login — semua role                                 |
| `🔑`       | Requires login — Manager / Admin                            |
| `💰`       | Requires login — Finance / Admin                            |
| `📊`       | Requires login — Investor (view-only)                       |
| `✅`       | Status: Done — layar berfungsi penuh                        |
| `🔶`       | Status: Partial — UI ada, data masih mock / belum terhubung |
| `🔴`       | Status: Placeholder — file ada tapi hanya stub kosong       |
| `📋`       | Status: Planned — ada di spec, belum ada file layar         |
| `[FASE 2]` | Di luar scope saat ini                                      |

---

## Ringkasan Status

| Status         | Jumlah |
| -------------- | ------ |
| ✅ Done        | 15     |
| 🔶 Partial     | 8      |
| 🔴 Placeholder | 1      |
| 📋 Planned     | 21     |
| **Total**      | **45** |

---

## 0. Entry Point

```
/
├── /                          → Splash / Auth Gate                     ✅ 🌐
│   ├── Cek token lokal di SQLite
│   ├── Jika token valid → redirect ke /(tabs)
│   └── Jika tidak → redirect ke /login
│
└── /login                     → Login                                  ✅ 🌐
    ├── Form: username + password
    ├── Device Binding: mengunci akun ke 1 HP setelah login pertama
    ├── Simpan token ke SQLite (users table)
    └── API: POST /api/v1/auth/login
```

---

## 1. Tab Bar — Bottom Navigation 👤

> Visibility tiap tab dikontrol RBAC via `useRole().hasPermission()`.
> File layout: `app/(tabs)/_layout.tsx`

```
/(tabs)
├── Tab 1 — Dashboard          → Beranda KPI Kandang                    🔶 📴
├── Tab 2 — Aktivitas          → Input Harian ABK (4 kartu)             🔶 📴
├── Tab 3 — Input Harian       → Form Wizard HBE                        🔴 📴
├── Tab 4 — Laporan            → Performa Periode                       🔶 📴
├── Tab 5 — Kelola             → Admin Back-Office Hub                  ✅ 🌐
└── Tab 6 — Akun               → Profil & Pengaturan                    ✅ 🌐
```

---

## 2. Dashboard 👷 📴

```
/(tabs)
└── /                          → Dashboard Utama                        🔶
    ├── Header: nama kandang, farm, sync status badge, notif bell
    ├── Section: Floor Switcher (Lantai 1 / Lantai 2)                   🔶 (dicomment)
    ├── Section: KPI Grid — 6 kartu                                     🔶 (data mock)
    │   ├── Populasi aktif
    │   ├── Kematian hari ini
    │   ├── Deplesi (%)
    │   ├── FCR sementara
    │   ├── Umur ayam (hari)
    │   └── Stok pakan (sak)
    ├── Section: ARV Alert Banner                                        🔶
    │   └── Muncul otomatis jika deplesi > threshold ARV
    ├── Section: Daily Task Tracker (SOP Checklist)                     🔶
    │   └── TODO: connect ke daily_checklist_logs SQLite
    └── Section: 7-Day Mortality Trend Chart                            🔶
        └── TODO: connect ke daily_dynamic_logs SQLite
```

---

## 3. Aktivitas — Input Harian ABK 👷 📴

```
/(tabs)
└── /aktivitas                 → Aktivitas Harian                       🔶
    ├── Header: label kandang + lantai, tanggal, sync status, progress bar
    ├── Floor Switcher (Lantai 1 / Lantai 2)
    ├── Kartu A — Pertumbuhan Ayam                                      🔶
    │   ├── Tampil: populasi, mati, culling, bobot rata-rata
    │   └── Tap → GrowthModal (Form)
    │       ├── Jumlah mati, jumlah culling
    │       ├── Bobot rata-rata, stok pakan sak
    │       └── TODO: upsert daily_dynamic_logs (HBE)
    ├── Kartu B — Lingkungan & Alat Sensor                              🔶
    │   ├── Tampil: suhu, kelembaban, ammonia, status mesin
    │   └── Tap → EnvironmentModal (Form)
    │       ├── Dynamic field dari FormConfig server (suhu, fan, humidity, dll)
    │       └── TODO: render dari coop_form_assignments + upsert daily_dynamic_logs
    ├── Kartu C — OVK & SOP Checklist                                   🔶
    │   ├── Tampil: checklist SOP, ringkasan OVK terpakai
    │   └── Tap → OVKModal (Form)
    │       ├── Checklist SOP boolean/teks
    │       ├── OVK usage per item (qty × unit)
    │       ├── Upload foto bukti (camera/gallery)
    │       └── TODO: upsert daily_checklist_logs + ovk_usages + photo_evidence
    └── Kartu D — Panen (conditional: muncul saat umur ≥ 20 hari)      🔶
        ├── Tampil: jumlah ekor panen, berat total
        └── Tap → HarvestModal (Form)
            ├── Jumlah ekor, berat total (kg), harga/kg
            ├── Ritase ke-N, foto surat jalan (DO)
            └── TODO: upsert harvest_entries
```

---

## 4. Input Harian — Form Wizard HBE 👷 📴

```
/(tabs)
└── /input-harian              → Form Wizard Input Cepat                🔴 STUB
    ├── ⛔ Contract Gate: terkunci sampai ABK tandatangan kontrak       📋
    ├── Form HBE: mortalitas, culling, konsumsi pakan, bobot rata2      📋
    ├── Dynamic SOP Checklist dari checklist_tasks                      📋
    │   ├── DAILY tasks (muncul setiap hari)
    │   └── ONE_TIME tasks (muncul sekali pada umur ayam tertentu)
    ├── Multi-item OVK usage (qty per item dari master ovk_items)       📋
    ├── Upload foto bukti → POST /api/v1/uploads                       📋
    └── Simpan ke SQLite (sync_status=PENDING_SYNC) → sync saat online  📋
```

---

## 5. Laporan — Performa Periode 👤 📴

```
/(tabs)
└── /laporan                   → Laporan Performa Periode               🔶
    ├── Header: konteks kandang, tanggal mulai, hari ke-N, status AKTIF/SELESAI
    ├── Section: Period Info Banner
    ├── Section: KPI Teknis Kumulatif                                   🔶 (data mock)
    │   ├── FCR (Feed Conversion Ratio)
    │   ├── IP (Index Performance)
    │   ├── Total deplesi kumulatif
    │   └── Total panen (ekor + kg)
    ├── Section: Tren Kematian 7 Hari Terakhir                          🔶 (data mock)
    ├── Section: Rekapitulasi Panen (semua ritase)                      🔶 (data mock)
    └── Section: Ekspor Laporan
        ├── Ekspor RHPP → GET /api/v1/export/rhpp                      📋
        ├── Ekspor Data Panen → GET /api/v1/export/harvests             📋
        ├── Ekspor Evaluasi Teknis → GET /api/v1/export/evaluations     📋
        └── Generator Laporan WhatsApp (client-side, dari ReportTemplate) 📋
```

---

## 6. Kelola — Admin Back-Office Hub 🔑 🌐

> Menu Kelola adalah **online-only by design**. Tidak ada offline queue di sini.
> RBAC gate: hanya tampil untuk role yang punya `access_kelola`.

```
/(tabs)
└── /kelola                    → Hub Admin Mobile                       ✅
    ├── Group: Master Data
    │   ├── Kelola User        → /kelola/users
    │   ├── Kelola Peternakan  → /kelola/farms
    │   ├── Setup Alat & Form  → /kelola/equipment-form
    │   └── Periode Ternak     → /kelola/periods
    ├── Group: Keuangan
    │   ├── Input Pengeluaran  → /kelola/finance/expenses               📋
    │   ├── Input Pemasukan    → /kelola/finance/income                 📋
    │   ├── Manajemen Investor → /kelola/finance/investors              📋
    │   └── Kategori Transaksi → /kelola/finance/categories             📋
    └── Group: Inventaris & Referensi
        ├── Master OVK         → /kelola/ovk                           📋
        ├── Artikel Edukasi    → /kelola/education                     📋
        ├── Referensi Harga    → /kelola/price-references              📋
        └── Template Laporan   → /kelola/report-templates              📋 [SOON]
```

---

## 7. Kelola — Master Data 🔑 🌐

### 7a. Kelola User

```
/kelola
└── /users                     → Daftar User                           ✅
    ├── Search bar
    ├── Filter pill per role (Admin, Manager, Finance, PIC, ABK, Investor)
    ├── Tap kartu → UserDetailSheet
    │   ├── Edit profil → PATCH /api/v1/users/{id}
    │   ├── Reset device binding → PATCH /api/v1/users/{id}
    │   ├── Reset password (by admin) → POST /api/v1/users/{id}/reset-password
    │   └── Hapus akun (soft-delete) → DELETE /api/v1/users/{id}
    └── FAB → /users/form
        └── /users/form        → Form Buat / Edit User                 ✅
            ├── Field: nama, username, email, phone, role, password
            └── API: POST /api/v1/users (create) | PATCH (edit)
```

### 7b. Kelola Peternakan (Hirarki)

```
/kelola
└── /farms                     → Daftar Area                           ✅
    ├── Search bar
    ├── Tap → list farm dalam area itu
    ├── Long press → AreaDetailSheet (edit/delete area)
    ├── FarmsSyncButton → GET /api/v1/sync/master-data
    └── FAB → /farms/form (type=area)
        │
        └── /farms/[areaId]    → Daftar Farm dalam Area                ✅
            ├── Tap → list kandang dalam farm itu
            └── FAB → /farms/form (type=farm)
                │
                └── /farms/[areaId]/[farmId] → Daftar Kandang         ✅
                    ├── Tap → CoopDetailSheet
                    │   ├── Lihat/edit detail kandang
                    │   ├── Kelola lantai (CoopFloor setup)           ✅
                    │   ├── Assign pekerja ke kandang                  ✅
                    │   │   └── POST /api/v1/coops/{id}/user-assignments
                    │   ├── Assign form ke kandang                     ✅
                    │   │   └── POST /api/v1/coops/{id}/form-assignments
                    │   └── Upload dokumen SOP kandang                 ✅
                    │       └── POST /api/v1/coops/{id}/documents
                    └── FAB → /farms/form (type=coop)

/kelola/farms
├── /form                      → Form Buat/Edit Area|Farm|Kandang      ✅
│   └── type=area|farm|coop via route params
├── /assignments               → Penugasan Pekerja ke Kandang          ✅
│   └── Bulk assign: POST /api/v1/coops/{id}/user-assignments
└── /floors                    → Setup Lantai Kandang                  ✅
    └── CoopFloor (lantai 1, lantai 2) + kapasitas
```

### 7c. Setup Alat & Form Config

```
/kelola
└── /equipment-form            → Daftar Tipe Alat                      ✅
    ├── List EquipmentType (Temptron, Punos, dll)
    ├── Tap → detail + form config terhubung
    └── FAB → /equipment-form/form
        └── /equipment-form/form → Form EquipmentType                 ✅
            ├── CRUD EquipmentType → POST/PATCH /api/v1/equipment-types
            ├── Sync FormConfig JSON ke tipe alat
            │   └── POST /api/v1/equipment-types/{id}/form-configs
            └── Assign form ke kandang
                └── POST /api/v1/coops/{id}/form-assignments

/kelola/form-configs           → Kelola Form Config (JSON fields)      📋
└── CRUD FormConfig → GET/POST/PATCH/DELETE /api/v1/form-configs
```

---

## 8. Kelola — Periode Ternak 🔑 🌐

```
/kelola
└── /periods                   → Daftar Periode                        ✅
    ├── Segmented tabs: Aktif / Selesai
    ├── Search bar
    ├── Tap → PeriodDetailSheet
    │   ├── Step 1: Alokasi Investor                                    ✅
    │   │   └── POST /api/v1/periods/{id}/investors
    │   ├── Step 2: Setup Form HBE (laporan biologis wajib)             ✅
    │   │   └── GET + POST /api/v1/periods/{id}/form-assignments
    │   ├── Step 3: Setup SOP Checklist                                 ✅
    │   │   └── GET + POST /api/v1/periods/{id}/checklist-tasks
    │   │       ├── DAILY: tugas rutin setiap hari
    │   │       └── ONE_TIME: tugas spesifik pada umur ayam tertentu
    │   ├── Step 4: Upload Kontrak PDF untuk ABK                        ✅
    │   │   └── POST /uploads → POST /periods/{id}/contracts
    │   ├── Step 5: Upload Dokumen Pendukung (jadwal, SOP)              ✅
    │   │   └── POST /api/v1/periods/{id}/documents
    │   ├── Tutup Periode                                               ✅
    │   │   └── POST /api/v1/periods/{id}/close
    │   ├── Upload RHPP Final (PDF)                                     ✅
    │   │   └── POST /api/v1/periods/{id}/rhpp-documents
    │   └── Publish RHPP (ketok palu)                                   ✅
    │       └── POST /api/v1/rhpps/{period_id}/publish
    └── FAB → /periods/form
        └── /periods/form      → Form Buka Periode Baru                ✅
            ├── Field: floor_id, initial_stock, start_date, pic_user_id
            ├── floor_id di-resolve dari coop_floors_master (bukan coop_id)
            ├── created_at_client wajib dikirim (audit trail)
            └── API: POST /api/v1/periods
```

---

## 9. Kelola — Keuangan 💰 🌐

```
/kelola/finance
├── /expenses                  → Input Pengeluaran                     📋 📴
│   ├── Transaction type=EXPENSE: pakan, sekam, gas, listrik, BOP
│   ├── Field: category_id, amount, description, date
│   ├── Upload foto nota/kuitansi → POST /api/v1/uploads
│   └── Simpan ke SQLite (PENDING_SYNC) → sync via POST /api/v1/sync/finances
│
├── /income                    → Input Pemasukan                       📋 📴
│   ├── Transaction type=INCOME: penjualan ayam sortir, pupuk, bangkai
│   └── Sync via POST /api/v1/sync/finances
│
├── /investors                 → Manajemen Investor                    📋
│   ├── Setup user dengan role=investor
│   ├── Assign investor ke periode + persentase bagi hasil
│   └── POST /api/v1/periods/{id}/investors
│
├── /categories                → Kategori Transaksi                    📋
│   └── CRUD kode akun arus kas → /api/v1/transaction-categories
│
└── /salary                    → Import Gaji ABK (Excel)               📋
    ├── Download template kosong → GET /api/v1/export/template-salary
    └── Upload Excel → POST /api/v1/import/salary
```

---

## 10. Kelola — Inventaris & Referensi 🔑 🌐

```
/kelola
├── /ovk                       → Master OVK                            📋
│   ├── CRUD Obat, Vaksin, Kimia kandang
│   ├── Kategori: pakan | obat | vaksin | kimia
│   └── API: CRUD /api/v1/ovk-items
│
├── /education                 → Artikel Edukasi                       📋
│   ├── CRUD artikel panduan ABK + thumbnail
│   └── API: POST/PATCH/DELETE /api/v1/education-articles
│       (GET via /sync/education — tersedia offline)
│
├── /price-references          → Referensi Harga Komoditas             📋
│   ├── Update harga ayam broiler dan pakan
│   └── API: POST/PATCH/DELETE /api/v1/price-references
│       (GET via /sync/education — tersedia offline)
│
└── /report-templates          → Template Laporan WA                   📋 [SOON]
    ├── CRUD template auto-teks laporan WhatsApp
    └── Digunakan oleh WA Generator di tab Laporan
```

---

## 11. Akun & Profil 👤

```
/(tabs)
└── /akun                      → Profil Saya                           ✅
    ├── IdentityCard: nama, username, role badge
    ├── ContractStatusCard: status kontrak per coop assignment
    ├── DeviceInfoCard: device_id, last_validated_at
    ├── KeamananAkunCard
    │   └── Tap → ChangePasswordSheet
    │       ├── Online-only (validasi ke server)
    │       └── API: POST /api/v1/profile/change-password
    ├── SyncStatusCard: status sync terakhir                            🔶 (mock)
    └── Tombol Logout
        ├── POST /api/v1/auth/logout
        ├── Clear token dari SQLite
        └── Redirect ke /login
```

---

## 12. Notifikasi 👤 🌐

```
/notification                  → Inbox Notifikasi                      🔶
├── Bell icon dari DashboardHeader → push ke /notification
├── List notifikasi masuk dari server
├── API: GET /api/v1/notifications                                      📋
├── Tap item → PATCH /api/v1/notifications/{id}/read                   📋
├── Tombol Baca Semua → PATCH /api/v1/notifications/read-all           📋
└── FCM Push Notification                                               ✅
    ├── Token disimpan via POST /api/v1/auth/fcm-token
    └── Trigger event: periode baru, RHPP published, laporan ditolak
```

---

## 13. Kontrak ABK — Acceptance Flow 👷 📴

```
/contracts                     → Daftar Kontrak Saya                   📋
├── ⛔ Gate: Input Harian TERKUNCI sampai semua kontrak aktif ditandatangani
├── Data dari: GET /api/v1/sync/periods (field: contracts)
└── Tap → /contracts/[id]

/contracts/[id]                → Detail & Tanda Tangan Kontrak         📋
├── Tampilkan PDF kontrak (WebView / external link)
├── Tombol "Saya Setuju"
│   ├── Simpan ContractAcceptance ke SQLite
│   └── Sync via POST /api/v1/sync/periods saat online
└── API online: POST /api/v1/contracts/{id}/accept (bila langsung online)
```

---

## 14. Pemeliharaan Fasilitas 👷 📴

```
/maintenance                   → Laporan Kerusakan                     📋
├── List maintenance log per lantai kandang
├── GET /api/v1/sync/maintenances
└── FAB → /maintenance/form

/maintenance/form              → Form Laporan Kerusakan                📋
├── Field: floor_id, equipment_type, deskripsi kerusakan
├── Upload foto bukti (camera)
├── Simpan ke SQLite maintenance_logs (PENDING_SYNC)
├── Sync: POST /api/v1/sync/maintenances saat online
└── Manager dapat update status: pending → in_progress → resolved
```

---

## 15. Sinkronisasi Data 👷 📴

```
/(tabs)
└── /sync                      → Manajemen Sinkronisasi                🔶
    ├── Overview Card: pending count, failed count, progress bar
    ├── Filter Tabs: Antrean | Riwayat
    ├── Sync Now button → trigger manual push ke server
    ├── Per-item: Retry (SYNC_FAILED) | Lihat Konflik (CONFLICT)
    └── Conflict Resolution Modal
        ├── "Gunakan Data HP Saya" (USE_LOCAL: force-push ke server)
        └── "Gunakan Data Server" (USE_SERVER: discard lokal, pull server)

Sync Services:
├── Master Data  → GET /api/v1/sync/master-data                        🔶
│   └── areas, farms, coops, floors, users, ovk_items, form_configs
├── Periods      → GET /api/v1/sync/periods (pull)                     🔶
│                  POST /api/v1/sync/periods (push ContractAcceptance)
├── Daily Acts   → GET|POST /api/v1/sync/daily-activities              📋 ⚠️ KRITIKAL
│   └── Butuh tabel: daily_activity_headers, daily_dynamic_logs,
│       daily_checklist_logs, harvest_entries, ovk_usages, photo_evidence
├── Finances     → GET|POST /api/v1/sync/finances                      📋
│   └── Butuh tabel: transactions, employee_salaries
├── Maintenances → GET|POST /api/v1/sync/maintenances                  📋
│   └── Butuh tabel: maintenance_logs
├── RHPP         → GET /api/v1/sync/rhpps (read-only)                  📋
└── Education    → GET /api/v1/sync/education (read-only)              📋
    └── articles + price_references
```

---

## 16. Portal Investor 📊 🌐

```
/investor/dashboard
├── 📊 RINGKASAN INVESTASI AKTIF (Multi-Lantai)
│   ├── Nama Kandang & Lantai (e.g., Kandang A - Lantai 1)
│   ├── Umur Ayam Aktif: X Hari (Countdown Panen: Y Hari Lagi)
│   ├── Populasi Ayam Hidup: X Ekor (Deplesi: X%) -> [Sinyal Kesehatan Kandang]
│   ├── Persentase Bagi Hasil: X% (Sesuai Kontrak PeriodInvestor)
│   └── Realisasi BOP Berjalan: Rp X.XXX.XXX
│
├── 📜 RIWAYAT SIKLUS & KEUANGAN (Closed Period)
│   ├── Status Laporan Akhir: RHPP (PUBLISHED / DRAFT)
│   ├── Rekap Realisasi ROI Final: Rp X.XXX.XXX
│   └── Status Transfer Payout: (PAID / PENDING)
└── API: GET /api/v1/investor/dashboard
```

---

## 17. Edukasi & Referensi 👷 📴

```
/education                     → Artikel Edukasi                       📋
├── List artikel dari SQLite cache (offline)
└── /education/[id]            → Detail Artikel                        📋
    └── Konten + gambar thumbnail, read-only

/price-references              → Referensi Harga Komoditas             📋
├── Tabel harga ayam broiler + pakan
└── Berguna PIC saat negosiasi di lapangan (offline-accessible)
```

---

## 18. Approval Manager 🔑 🌐

```
/kelola/approvals              → Approval Laporan Harian               📋
├── List daily_activity_headers yang masuk, status pending_approval
├── Tap → detail laporan harian ABK
├── Approve → update business_status = APPROVED
│   └── ABK menerima notifikasi push: laporan disetujui
└── Reject (dengan alasan) → business_status = REJECTED
    └── ABK menerima notifikasi + bisa koreksi di lapangan
```

---
