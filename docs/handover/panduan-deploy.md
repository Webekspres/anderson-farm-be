# Panduan Deploy — Backend Anderson Farm

> **Environment production:** `https://anderson-farm.webekspres.web.id`  
> **API base:** `https://anderson-farm.webekspres.web.id/api/v1`

---

## Ringkasan Infrastruktur

| Komponen | Platform |
|----------|----------|
| Web server | cPanel shared hosting |
| Database | MySQL (cPanel) |
| File storage | Cloudflare R2 |
| Queue | Laravel database queue (cron/worker) |
| Deploy config | [`.cpanel.yml`](../../.cpanel.yml) |

Path deploy (dari `.cpanel.yml`):

```
public_html/anderson-farm.webekspres.web.id/
```

---

## Checklist Deploy Baru

### 1. Kode

```bash
# Di server atau via CI — pull branch yang disetujui (dev-kris / main)
git pull origin <branch>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Environment (`.env` production)

**Jangan commit `.env` ke Git.** Set di server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://anderson-farm.webekspres.web.id

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database

FILESYSTEM_UPLOAD_DISK=r2
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=anderson-farm
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_URL=https://<public-r2-or-custom-domain>
R2_USE_PATH_STYLE_ENDPOINT=true

LATEST_APP_VERSION=1.0.0
MIN_APP_VERSION=0.1.0
APP_UPDATE_URL_ANDROID=https://play.google.com/store/apps/details?id=com.andersonfarm.app
APP_UPDATE_URL_IOS=
```

Referensi lengkap: [`.env.example`](../../.env.example)

### 3. Queue Worker

Job async (notifikasi, dll.) membutuhkan worker:

```bash
php artisan queue:work --tries=3
```

Di cPanel, set **cron** setiap menit:

```cron
* * * * * cd /path/to/anderson-farm-be && php artisan schedule:run >> /dev/null 2>&1
```

Atau gunakan Supervisor jika tersedia. Tanpa worker, job menumpuk di tabel `jobs`.

### 4. PHP-FPM

Setelah deploy env/migration, restart PHP-FPM dari cPanel.

### 5. Verifikasi Post-Deploy

```bash
curl https://anderson-farm.webekspres.web.id/api/check
curl "https://anderson-farm.webekspres.web.id/api/v1/system/check-version?platform=android&version=1.0.0"
```

Login smoke via Postman: [docs/postman/](../postman/)

---

## Cloudflare R2

- Bucket: `anderson-farm`
- Upload endpoint: `POST /api/v1/uploads` (authenticated)
- **Wajib** enable public access atau custom domain pada bucket agar `file_url` bisa dibuka di app

---

## Version Gate (Mobile)

Setiap release APK/AAB baru:

1. Bump `version` di `anderson-farm-fe/app.json`
2. Build via EAS
3. Update di production `.env`:
   - `LATEST_APP_VERSION` = versi baru
   - `MIN_APP_VERSION` = versi minimum yang masih boleh jalan (biasanya 1 minor di bawah latest)
4. Restart PHP-FPM

| Versi app | Efek |
|-----------|------|
| `< MIN_APP_VERSION` | Force update (blocker screen) |
| `>= MIN` dan `< LATEST` | Optional update prompt |
| `>= LATEST` | Normal |

---

## Rollback

1. Checkout/tag commit sebelumnya
2. `composer install --no-dev`
3. **Hati-hati rollback migration** — hanya jika migration baru belum di production atau ada down() yang aman
4. `php artisan config:cache`
5. Restart PHP-FPM + queue worker

---

## Yang Tidak Boleh di-Commit

- File `.env` production
- Kredensial R2, DB, FCM
- `storage/logs/` berisi data sensitif

---

## Deploy Mobile (Cross-Reference)

Build APK/AAB: [eas-build.md](../../../anderson-farm-fe/docs/eas-build.md)

Smoke setelah deploy BE: [smoke-device-runbook.md](../../../anderson-farm-fe/docs/handover/smoke-device-runbook.md)

---

## Troubleshooting Production

| Gejala | Kemungkinan penyebab |
|--------|----------------------|
| Upload foto gagal | R2 credentials / bucket public access |
| App force update semua user | `MIN_APP_VERSION` terlalu tinggi |
| Job tidak jalan | Queue worker/cron tidak aktif |
| 500 setelah deploy | `php artisan config:clear` lalu cache ulang; cek `storage/logs/laravel.log` |
| CORS (jika web) | Cek middleware — mobile pakai bearer token, bukan cookie |
