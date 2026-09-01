# Inventaris Akses & Kredensial — Anderson Farm

> **PENTING:** Dokumen ini berisi **lokasi** kredensial, bukan nilai secret.  
> **Jangan** menulis password, API key, atau token langsung di file ini atau commit ke Git.  
> Kolom **Pemilik Akun** dan **Lokasi Kredensial** yang masih `_(isi tim)_` harus dilengkapi tim internal sebelum go-live.

**Terakhir diperbarui:** 1 September 2026  
**Diisi otomatis dari codebase:** URL, package, branch, project ID (bukan secret)

---

## Cara Menggunakan Dokumen Ini

1. Isi baris yang masih `_(isi tim)_` dengan pemilik akun perusahaan
2. Verifikasi akses bisa dibuka minimal 2 orang internal
3. Rotasi kredensial setelah developer outgoing resign

---

## Repositori & Kode

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Repo FE `anderson-farm-fe` | GitHub | _(isi tim)_ | _(isi vault)_ | `https://github.com/Webekspres/anderson-farm-fe` — branch aktif: **`dev`** |
| Repo BE `anderson-farm-be` | GitHub | _(isi tim)_ | _(isi vault)_ | `https://github.com/Webekspres/anderson-farm-be` — branch aktif: **`dev-kris`** |
| Workspace lokal (dua repo) | Developer machine | — | — | Folder: `Anderson Environment (Partial)/` — berisi `README.md` + `AGENT.md` (bukan repo Git terpisah) |

---

## Backend Production

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| API production | cPanel / shared hosting | _(isi tim)_ | _(isi vault)_ | URL: **`https://anderson-farm.webekspres.web.id`** — API: **`/api/v1`** |
| Health check | — | — | — | `GET https://anderson-farm.webekspres.web.id/api/check` |
| cPanel login | cPanel | _(isi tim)_ | _(isi vault)_ | Deploy path: `public_html/anderson-farm.webekspres.web.id/` (lihat `.cpanel.yml`) |
| Database MySQL prod | cPanel MySQL | _(isi tim)_ | _(isi vault)_ + `.env` server | Nama DB/user — **bukan** password di git |
| Laravel `.env` production | Server filesystem | _(isi tim)_ | _(isi vault)_ | Path di server: _(isi tim)_ |
| SSL certificate | cPanel / provider | _(isi tim)_ | _(isi vault)_ | Subdomain: `anderson-farm.webekspres.web.id` |

### Env production (referensi — nilai di server, bukan di git)

| Variabel | Nilai yang diharapkan (non-secret) |
|----------|-----------------------------------|
| `LATEST_APP_VERSION` | `1.0.0` |
| `MIN_APP_VERSION` | `0.1.0` |
| `APP_UPDATE_URL_ANDROID` | `https://play.google.com/store/apps/details?id=com.andersonfarm.app` |
| `APP_UPDATE_URL_IOS` | _(kosong — iOS belum submit)_ |
| `FILESYSTEM_UPLOAD_DISK` | `r2` |
| `R2_BUCKET` | `anderson-farm` |
| `QUEUE_CONNECTION` | `database` |

---

## Cloud Storage

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Cloudflare R2 bucket | Cloudflare | _(isi tim)_ | _(isi vault)_ | Bucket: **`anderson-farm`** |
| R2 API keys | Cloudflare | _(isi tim)_ | _(isi vault)_ + `.env` prod | Env: `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_ENDPOINT`, `R2_URL` |
| Public URL / custom domain R2 | Cloudflare | _(isi tim)_ | _(isi vault)_ | Wajib public access agar `file_url` bisa dibuka di app |

---

## Mobile Build & Distribusi

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Expo / EAS project | expo.dev | _(isi tim)_ | _(isi vault)_ | Slug: **`anderson-farm`** — Project ID: **`d9473795-b2d6-40f9-8d1d-90030073df20`** |
| EAS build profiles | expo.dev | — | — | `development` (APK dev client), `preview` (APK internal), `production` (AAB) — lihat `eas.json` |
| EAS Android keystore | expo.dev | _(isi tim)_ | _(isi vault)_ | Managed by EAS atau upload manual |
| Google Play Console | Google | _(isi tim)_ | _(isi vault)_ | Package: **`com.andersonfarm.app`** |
| Play Store listing | Google Play | _(isi tim)_ | _(isi vault)_ | URL: `https://play.google.com/store/apps/details?id=com.andersonfarm.app` |
| Apple Developer | Apple | — | — | **Out of scope kontrak** — bundle ID sudah diset: `com.andersonfarm.app` |

### Build commands (referensi)

```bash
cd anderson-farm-fe
bun run build:android:preview      # APK QA
bun run build:android:production   # AAB Play Store
```

---

## Push Notification

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Firebase project (FCM) | Firebase Console | _(isi tim)_ | _(isi vault)_ | Paket BE: `devkandil/notifire` |
| `google-services.json` | Firebase | _(isi tim)_ | _(isi vault)_ | Untuk build Android — cek apakah sudah di repo atau EAS secrets |
| FCM server key / service account | Firebase | _(isi tim)_ | _(isi vault)_ + `.env` BE jika ada |

---

## Akun Demo (Development / UAT)

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Demo users | Database seed | — | — | Password semua: **`password123`** — lihat `database/seeders/README.md` |
| Seeder | Laravel | — | — | `php artisan db:seed` → `MinimalDemoSeeder` |

---

## Monitoring & Analytics (Deferred)

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Crash monitoring (Sentry) | — | — | — | **Belum diimplementasi** — deferred |
| Play Console vitals | Google Play | _(isi tim)_ | _(isi vault)_ | Alternatif monitoring crash |

---

## Domain & DNS

| Aset | Platform | Pemilik Akun | Lokasi Kredensial | Catatan |
|------|----------|-------------|-------------------|---------|
| Domain induk | Registrar | _(isi tim)_ | _(isi vault)_ | `webekspres.web.id` |
| Subdomain API | DNS (cPanel/Cloudflare) | _(isi tim)_ | _(isi vault)_ | `anderson-farm.webekspres.web.id` |

---

## Kontak Eskalasi Internal

> Isi dengan kontak **perusahaan**, bukan developer outgoing.

| Peran | Nama | Kontak | Catatan |
|-------|------|--------|---------|
| Lead Developer / Pengganti | _(isi tim)_ | _(isi tim)_ | |
| Project Manager | _(isi tim)_ | _(isi tim)_ | |
| Kontak Klien (Anderson Farm) | _(isi tim)_ | _(isi tim)_ | |
| DevOps / IT Webekspres | _(isi tim)_ | _(isi tim)_ | |

---

## Checklist Serah Terima Akses

- [ ] Semua baris `_(isi tim)_` sudah diisi
- [ ] Minimal 2 orang internal punya akses vault kredensial
- [ ] GitHub org — developer outgoing bukan sole admin
- [ ] cPanel — password dirotasi & diserahkan ke IT
- [ ] Expo/EAS — ownership project ke akun perusahaan (`projectId` di atas)
- [ ] Play Console — admin ke tim internal
- [ ] Cloudflare R2 — API key bisa dirotasi tanpa developer outgoing

---

## Rotasi Kredensial Pasca-Resign

1. Password cPanel / SSH
2. R2 API keys (`R2_*` di `.env` prod)
3. Database password
4. Expo access tokens
5. Firebase service account (jika digunakan)

Setelah rotasi: `php artisan config:cache` → restart PHP-FPM → verifikasi login + upload foto.
