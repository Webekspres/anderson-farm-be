# Masalah & Backlog — Backend Anderson Farm

> Sinkron dengan backlog mobile: [known-issues-and-backlog.md](../../../anderson-farm-fe/docs/handover/known-issues-and-backlog.md)

---

## Blocker Utama (Lintas FE + BE)

**UAT tertahan** menunggu smoke test perangkat fisik. Item berikut sudah diperbaiki di kode tetapi belum diverifikasi ulang di device:

| ID | Skenario | Perbaikan BE terkait |
|----|----------|---------------------|
| **B5** | Context persist setelah kill app | — (FE SecureStore) |
| **C4** | Upload foto cleaning online | Upload R2 via `POST /uploads` |
| **D1b** | Income create tetap INCOME | Push INCOME + pull `type` dari kategori |
| **D2** | Approval daily approve/reject | Authorize finance di approval controller |
| **D3** | Antrian persetujuan keuangan | `/approvals/finances` + `FinanceApprovalService` |
| **D4** | Edit expense/income | Re-push by id; block jika APPROVED |
| **D5** | Saldo kas server | `GET /finances/cash-balance` |

Runbook: [smoke-device-runbook.md](../../../anderson-farm-fe/docs/handover/smoke-device-runbook.md)

---

## Backlog Ditunda

Jangan mulai sebelum smoke P0 hijau.

| Item | Deskripsi BE |
|------|-------------|
| **OTP gateway** | Email/WhatsApp OTP delivery belum di-wire |
| **RHPP COGS v2** | Import/revisi formula komponen Excel penuh |
| **Opening balance carryover** | `CashBalanceService` mengembalikan `opening_balance = 0` |
| **ARV PDF engine** | Parsed standards, benchmark lintas periode |
| **Crash monitoring** | Tidak ada SDK server-side; deferred di FE juga |

---

## Caveat Arsitektur

| Caveat | Detail |
|--------|--------|
| **RHPP sync PUBLISHED-only** | `GET /sync/rhpps` hanya mengembalikan RHPP published; draft hanya via generate on-demand |
| **Queue worker wajib di prod** | `QUEUE_CONNECTION=database` — tanpa worker, job async tertunda |
| **R2 wajib di prod** | `FILESYSTEM_UPLOAD_DISK=r2` — upload foto/receipt gagal tanpa kredensial R2 |
| **Period mutations online-only** | Create/edit periode butuh network; daily activity yang offline-first |
| **Kontrak API = SSOT** | Jangan ubah shape response tanpa update OpenAPI + koordinasi FE |
| **Memo lama** | `docs/memo/sitemap anderson.md` berisi TODO historis — sudah diimplementasi |

---

## Bug P0 yang Sudah Ditutup

| Bug | Tanggal | Fix |
|-----|---------|-----|
| `finance-missing-canApprove` | 2026-07-28 | Authorize finance di approval |
| Income type salah setelah sync | 2026-07-28 | Push INCOME + kategori type |
| `business_status` enum mismatch | 2026-08-27 | Migration align ke `SUBMITTED`, `NEEDS_REVIEW` |

---

## Artefak Release Belum Selesai

- [x] Version gate env (`LATEST_APP_VERSION=1.0.0`)
- [x] Migration transactions business_status + education/ack tables
- [ ] `APP_UPDATE_URL_IOS` — placeholder sampai App Store submit
- [ ] Crash monitoring (deferred — keputusan produk)

---

## Yang Jangan Disentuh Sembarangan

| Area | Alasan |
|------|--------|
| Sync controllers core | Logika conflict resolution kompleks & teruji |
| OpenAPI contract | SSOT integrasi FE — ubah dengan koordinasi |
| Migration yang sudah di production | Jalankan hanya migration pending; jangan rollback sembarangan |
