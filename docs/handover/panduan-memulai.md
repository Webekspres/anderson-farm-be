# Panduan Memulai — Backend Anderson Farm

## Prasyarat

| Tool | Versi | Fungsi |
|------|-------|--------|
| PHP | 8.3+ | Runtime |
| Composer | Latest | Dependency manager |
| MySQL | 8+ | Database |
| Node.js | 18+ | Vite assets (opsional untuk `npm run dev`) |

---

## Setup Lokal

```bash
cd anderson-farm-be

# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — edit DB_* di .env, lalu:
php artisan migrate --seed
```

Seeder default: **MinimalDemo** — dataset deterministik untuk smoke/UAT.

```bash
# Ulang dari awal (destructive):
php artisan migrate:fresh --seed

# Atau via composer:
composer run seed:fresh
```

---

## Akun Demo

Password semua user: **`password123`**

| Username | Role | Modul smoke |
|----------|------|-------------|
| `admin` | admin | Setup user/lokasi, unbind device |
| `manager` | manager | Periode, kontrak, monitoring, close |
| `finance` | finance | Expense/income, approval, RHPP, export |
| `pic` | pic | Operasi harian, HBE, kontrak accept |
| `abk` | abk | Operasi harian, cleaning/foto |
| `investor` | investor | Laporan portofolio |

Detail dataset: [database/seeders/README.md](../../database/seeders/README.md)

**Jangan gunakan FullDemoSeeder** untuk UAT — data random/volume.

---

## Development dengan Mobile App

Cara termudah — jalankan API + auto-sync URL ke FE:

```bash
composer run serve:mobile
```

Script [`scripts/serve-mobile.sh`](../../scripts/serve-mobile.sh):
- Bind API di `0.0.0.0:8000`
- Deteksi IP LAN
- Update `EXPO_PUBLIC_API_URL` di `../anderson-farm-fe/.env`

Dengan queue worker:

```bash
composer run serve:mobile:queue
```

**URL per target:**

| Target | URL |
|--------|-----|
| Device fisik (Wi-Fi sama) | `http://<IP-LAN>:8000/api/v1` |
| Android emulator | `http://10.0.2.2:8000/api/v1` |
| iOS simulator / lokal | `http://127.0.0.1:8000/api/v1` |
| Health check | `http://<host>:8000/api/check` |

Setelah `.env` FE berubah: `cd ../anderson-farm-fe && bun start -- --clear`

---

## Docker (Testing)

```bash
docker compose up -d
```

Stack: Laravel + MySQL 8 — port 8080 (app) / 3307 (MySQL). Lihat [`docker-compose.yml`](../../docker-compose.yml).

---

## Perintah Berguna

```bash
composer run dev              # serve + queue + vite (full stack dev)
composer test                 # Pest tests
composer run seed:minimal     # Re-seed MinimalDemo saja
./vendor/bin/pint             # Code style (PSR-12)
php artisan route:list        # Lihat semua route
php artisan queue:listen      # Queue worker manual
```

---

## Upload Lokal (tanpa R2)

Di `.env` development:

```env
FILESYSTEM_UPLOAD_DISK=public
```

Production wajib R2 — lihat [panduan-deploy.md](./panduan-deploy.md).

---

## Testing API

1. **OpenAPI:** [docs/apicontract/openapi/](../apicontract/openapi/)
2. **Postman:** Import dari [docs/postman/](../postman/)
3. **Pest:** `php artisan test` — lihat [docs/run_test.md](../run_test.md)

Flow login Postman:
1. `POST /api/v1/auth/login` dengan `email`, `password`, `device_id`
2. Copy `access_token` → header `Authorization: Bearer <token>`

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| 401 pada semua route | Token expired — login ulang |
| Upload gagal lokal | Set `FILESYSTEM_UPLOAD_DISK=public`, `php artisan storage:link` |
| Queue tidak jalan | Jalankan `php artisan queue:listen` atau `serve:mobile:queue` |
| FE tidak connect | Cek firewall port 8000; pastikan IP LAN benar di `.env` FE |
| Migration error | `php artisan migrate:fresh --seed` (hanya dev!) |

---

## Referensi Mobile

Repo FE: [`anderson-farm-fe`](../../../anderson-farm-fe/)

Handover mobile: [docs/handover/](../../../anderson-farm-fe/docs/handover/)
