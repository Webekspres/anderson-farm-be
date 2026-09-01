# Gambaran Proyek — Backend Anderson Farm

## Konteks Bisnis

**Anderson Farm** adalah peternakan ayam broiler profesional dengan kompleksitas operasional tinggi. Sistem ini mendigitalkan operasi harian lapangan hingga pelaporan keuangan untuk manajemen dan investor.

Repo ini adalah **server-side API** — bukan aplikasi mobile. Frontend Expo/React Native mengonsumsi API ini secara offline-first dengan sinkronisasi background.

---

## Peran API dalam Sistem

```text
┌─────────────────────────────────────┐
│   Mobile App (anderson-farm-fe)     │
│   • UI, SQLite lokal, sync queue    │
└──────────────┬──────────────────────┘
               │ REST API /api/v1
               ▼
┌─────────────────────────────────────┐
│   Backend API (repo ini)            │
│   • Auth & device binding (Sanctum) │
│   • Master & transaksi (MySQL)      │
│   • Sync pull/push + conflict       │
│   • RHPP & perhitungan keuangan     │
│   • Upload R2, export Excel/PDF     │
│   • FCM push notification           │
└─────────────────────────────────────┘
```

---

## Modul API Utama

| Modul | Endpoint group | Fungsi |
|-------|---------------|--------|
| **Auth** | `/auth/*` | Login, logout, forgot/reset password, FCM token |
| **System** | `/system/check-version` | Version gate (force/optional update) |
| **Master Data** | `/areas`, `/farms`, `/coops`, `/users`, dll. | CRUD master + assignment |
| **Period** | `/periods/*` | Siklus periode, kontrak, investor, dokumen |
| **Sync** | `/sync/*` | Pull/push offline-first (master, daily, finance, maintenance, RHPP) |
| **Approvals** | `/approvals/*` | Persetujuan aktivitas harian & transaksi keuangan |
| **Finance** | `/finances/cash-balance`, sync finance | Saldo kas, expense/income |
| **Monitoring** | `/monitoring/*` | KPI, deviasi ARV, acknowledgement |
| **RHPP** | `/rhpps/*`, `/sync/rhpps` | Generate, publish, dokumen RHPP |
| **Export** | `/export/*` | Excel harvest, OVK, BOP, evaluasi, gaji |
| **Upload** | `/uploads` | Foto/receipt ke Cloudflare R2 |
| **Education** | `/education-articles`, `/price-references` | Artikel & referensi harga |
| **Investor** | `/investor/*` | Dashboard portofolio view-only |

Definisi lengkap: [docs/apicontract/openapi/](../apicontract/openapi/openapi.yaml)

---

## Konvensi Teknis

| Aspek | Aturan |
|-------|--------|
| Primary key | UUID (`HasUuids`) |
| Response shape | `{ success, message, data }` |
| Validasi | Form Request di `app/Http/Requests/Api/V1/` |
| Transformasi | API Resource di `app/Http/Resources/Api/V1/` |
| Business logic | Service classes di `app/Services/` |
| Auth | Bearer token Sanctum — header `Authorization` |
| Timestamps | ISO 8601 |

---

## Peran Pengguna (RBAC)

| Role | Akses API utama |
|------|----------------|
| **admin** | Semua master data, user, konfigurasi |
| **manager** | Periode, monitoring, close periode, approval |
| **finance** | Transaksi, approval keuangan, RHPP, export |
| **pic** | Operasi harian, periode assigned, expense |
| **abk** | Operasi harian assigned coop |
| **investor** | Read-only laporan & portofolio |

Matriks detail: [Spesifikasi Flow Anderson](../../../anderson-farm-fe/docs/project-related/Spesifikasi%20Flow%20Anderson.md)

---

## Yang Bukan Tanggung Jawab Backend

- UI/UX mobile (repo FE)
- SQLite lokal & sync queue di device
- Offline presentation states (`LOCAL_SAVED`, dll.) — FE concern
- Build & distribusi APK/AAB — EAS/Play Console

---

## Glosarium

| Istilah | Arti |
|---------|------|
| **Kandang** | Coop / house |
| **Periode** | Siklus pemeliharaan ayam di satu kandang |
| **RHPP** | Rekap Hasil Produksi Panen — laporan keuangan periode |
| **ARV** | Analytical Reference Value — benchmark performa |
| **OVK** | Obat/vitamin/kimia |
| **HBE** | Health Behavior & Environment checklist |
| **BOP** | Biaya Operasional Peternakan |
