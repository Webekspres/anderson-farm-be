# Anderson Farm Backend — Dokumentasi Serah Terima

> **Terakhir diperbarui:** 1 September 2026  
> **Pengembang:** [PT Webekspres Teknologi Indonesia](https://webekspres.id)  
> **Status:** ~95% selesai — RC prep done; smoke test mobile pending

---

## Apa Repo Ini?

Repositori `anderson-farm-be` adalah **REST API Laravel 13** untuk Anderson Farm. Menangani autentikasi, master data, sinkronisasi offline-first, keuangan, RHPP, ekspor, upload R2, dan push notification.

Aplikasi mobile ada di repo terpisah: [`anderson-farm-fe`](../../../anderson-farm-fe/).

---

## Mulai dari Mana?

| Jika Anda... | Baca ini dulu |
|--------------|---------------|
| **Developer backend** baru | [Gambaran Proyek](./gambaran-proyek.md) → [Panduan Memulai](./panduan-memulai.md) |
| **Developer mobile** (integrasi API) | [Kontrak OpenAPI](../apicontract/openapi/) → [Status Implementasi](./status-implementasi.md) |
| **QA / tester** | [Handover FE](../../../anderson-farm-fe/docs/handover/) → [Runbook Smoke](../../../anderson-farm-fe/docs/handover/smoke-device-runbook.md) |
| **PM / manajemen** | [Ringkasan Serah Terima](../../../anderson-farm-fe/docs/handover/ringkasan-serah-terima.md) |
| **DevOps** | [Panduan Deploy](./panduan-deploy.md) |

---

## Daftar Dokumen

| Dokumen | Deskripsi |
|---------|-----------|
| [Gambaran Proyek](./gambaran-proyek.md) | Konteks bisnis, peran API, diagram arsitektur |
| [Status Implementasi](./status-implementasi.md) | Modul/endpoint selesai, perubahan fase 14 |
| [Masalah & Backlog](./masalah-dan-backlog.md) | Blocker, backlog BE, caveat arsitektur |
| [Panduan Memulai](./panduan-memulai.md) | Setup lokal, Docker, seeders, serve-mobile |
| [Panduan Deploy](./panduan-deploy.md) | cPanel, queue, R2, env production |

---

## Fakta Cepat

| Item | Detail |
|------|--------|
| Framework | Laravel 13, PHP 8.3+ |
| Database | MySQL (UUID PK) |
| Auth | Laravel Sanctum (bearer token) |
| File storage | Cloudflare R2 (production) |
| Testing | Pest |
| Branch aktif | `dev-kris` |
| API production | `https://anderson-farm.webekspres.web.id/api/v1` |

---

## Referensi Lain

| Dokumen | Lokasi |
|---------|--------|
| README proyek | [../../README.md](../../README.md) |
| Seeders & akun demo | [../../database/seeders/README.md](../../database/seeders/README.md) |
| OpenAPI | [../apicontract/openapi/](../apicontract/openapi/) |
| Postman | [../postman/](../postman/) |
| Panduan AI | [../../AGENTS.md](../../AGENTS.md) |
| Handover mobile | [../../../anderson-farm-fe/docs/handover/](../../../anderson-farm-fe/docs/handover/) |
| Workspace root | [../../../README.md](../../../README.md) |
