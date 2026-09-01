# Anderson Farm — Backend API

**Repositori backend** untuk sistem terintegrasi manajemen operasional dan keuangan peternakan ayam broiler **Anderson Farm**.

API Laravel 13 ini menyediakan autentikasi (Sanctum), master data, sinkronisasi offline-first, perhitungan keuangan (RHPP), ekspor Excel/PDF, upload file ke Cloudflare R2, dan notifikasi push (FCM).

Aplikasi mobile (Expo/React Native) ada di repo terpisah: [`anderson-farm-fe`](../anderson-farm-fe/).

**Dikembangkan oleh:** [PT Webekspres Teknologi Indonesia](https://webekspres.id)

---

## Status Proyek

| Item | Detail |
|------|--------|
| Framework | Laravel 13, PHP 8.3+ |
| Status development | ~95% selesai (RC prep done) |
| Branch aktif | `dev-kris` |
| API production | `https://anderson-farm.webekspres.web.id/api/v1` |
| Versi app gatekeeper | `LATEST_APP_VERSION=1.0.0`, `MIN_APP_VERSION=0.1.0` |

**Blocker:** UAT tertahan menunggu smoke test mobile. Lihat [handover FE](../anderson-farm-fe/docs/handover/).

---

## Dokumentasi Serah Terima

> Developer baru: mulai dari [Handover Backend](./docs/handover/).

| Dokumen | Deskripsi |
|---------|-----------|
| [Index Handover](./docs/handover/README.md) | Navigasi dokumen serah terima |
| [Gambaran Proyek](./docs/handover/gambaran-proyek.md) | Konteks bisnis & arsitektur API |
| [Status Implementasi](./docs/handover/status-implementasi.md) | Modul/endpoint selesai, fase terakhir |
| [Masalah & Backlog](./docs/handover/masalah-dan-backlog.md) | Blocker, backlog, caveat |
| [Panduan Memulai](./docs/handover/panduan-memulai.md) | Setup lokal, Docker, seeders |
| [Panduan Deploy](./docs/handover/panduan-deploy.md) | cPanel, queue, R2, production |

---

## Quick Start

### Prasyarat

- PHP 8.3+
- Composer
- MySQL 8+
- Node.js 18+ (untuk Vite assets)

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### Jalankan untuk development mobile

```bash
composer run serve:mobile
```

Script ini menjalankan API di `0.0.0.0:8000` dan otomatis mengisi `EXPO_PUBLIC_API_URL` di repo FE (`../anderson-farm-fe/.env`).

Dengan queue worker:

```bash
composer run serve:mobile:queue
```

### Akun demo

Password semua user: **`password123`**

| Username | Role |
|----------|------|
| `admin` | admin |
| `manager` | manager |
| `finance` | finance |
| `pic` | pic |
| `abk` | abk |
| `investor` | investor |

Detail dataset: [database/seeders/README.md](./database/seeders/README.md)

---

## Testing

```bash
php artisan test
# atau
composer test
```

Referensi perintah: [docs/run_test.md](./docs/run_test.md)

---

## Dokumentasi API

| Sumber | Lokasi |
|--------|--------|
| OpenAPI 3.1 | [docs/apicontract/openapi/](./docs/apicontract/openapi/) |
| Ringkasan kontrak | [docs/apicontract/DOCUMENTATION_SUMMARY.md](./docs/apicontract/DOCUMENTATION_SUMMARY.md) |
| Postman collections | [docs/postman/](./docs/postman/) |
| Memo internal (flows) | [docs/memo/](./docs/memo/) |

**Konvensi response:** `{ success, message, data }` — UUID primary keys, ISO 8601 timestamps.

---

## Environment Penting

Salin dari [`.env.example`](./.env.example):

| Variabel | Fungsi |
|----------|--------|
| `DB_*` | MySQL |
| `R2_*` | Cloudflare R2 (upload foto/receipt production) |
| `FILESYSTEM_UPLOAD_DISK` | `r2` (prod) / `public` (lokal) |
| `QUEUE_CONNECTION` | `database` — butuh worker di production |
| `LATEST_APP_VERSION` | Versi terbaru app (sinkron `app.json` FE) |
| `MIN_APP_VERSION` | Versi minimum — di bawah ini force update |
| `APP_UPDATE_URL_ANDROID` | URL Play Store |

---

## Deploy Production

- Host: cPanel — konfigurasi di [`.cpanel.yml`](./.cpanel.yml)
- Path deploy: `public_html/anderson-farm.webekspres.web.id/`
- Panduan lengkap: [docs/handover/panduan-deploy.md](./docs/handover/panduan-deploy.md)

---

## Struktur Penting

```
app/Http/Controllers/Api/V1/   # Controller API
app/Http/Requests/Api/V1/      # Form Request validasi
app/Http/Resources/Api/V1/     # API Resources
app/Services/                  # Business logic
database/migrations/           # Schema
database/seeders/              # Demo data
docs/apicontract/openapi/      # Kontrak API
routes/api.php                 # Route definitions
scripts/serve-mobile.sh        # Dev server + sync FE .env
```

Panduan AI/developer: [AGENTS.md](./AGENTS.md)

---

## Lisensi

Privat — Anderson Farm / PT Webekspres Teknologi Indonesia.
