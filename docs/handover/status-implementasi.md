# Status Implementasi — Backend Anderson Farm

> **Terakhir dinilai:** 1 September 2026  
> **Keseluruhan:** ~95% selesai (RC prep done); UAT tertahan smoke test mobile

---

## Tahap Bisnis

| Tahap | Status |
|-------|--------|
| Analisis & Desain | Selesai |
| Development & QC | ~95% — RC prep selesai |
| UAT & Revisi | Tertahan — smoke retest mobile ([runbook FE](../../../anderson-farm-fe/docs/handover/smoke-device-runbook.md)) |
| Go-Live | Belum dimulai |

---

## Perubahan Fase 14 (Agustus 2026)

- Enum `transactions.business_status` diselaraskan ke `BusinessStatus` (`SUBMITTED`, `NEEDS_REVIEW`) + migration MySQL
- `FinanceApprovalController` + `/approvals/finances` — antrian persetujuan transaksi keuangan
- `CashBalanceController` + `GET /finances/cash-balance` — saldo kas otoritatif server
- Education articles: field `content_html`, `category`, `author_name`
- Monitoring deviation acknowledgements (BE + FE)
- Version gate: `1.0.0` / `MIN_APP_VERSION=0.1.0`

**Verifikasi terakhir:** Pest (monitoring, auth, investor, version, approvals, finance sync) + Pint.

---

## Modul API — Status

### Selesai & Teruji

| Area | Controller / Service | Catatan |
|------|---------------------|---------|
| Auth & device binding | `AuthController` | Login, logout, forgot/reset password |
| Version gate | `SystemController` | Force/optional update |
| Master data CRUD | `AreaController`, `FarmController`, `CoopController`, `UserController`, dll. | UUID, soft delete where applicable |
| Period lifecycle | `PeriodController`, `PeriodActionController` | Create, update, close, investors |
| Sync engine | `*SyncController` (master, daily, finance, maintenance, period, RHPP, education) | Offline-first pull/push |
| Approvals | `DailyActivityApprovalController`, `FinanceApprovalController` | Daily + finance queues |
| Finance | `FinanceSyncController`, `CashBalanceController` | Expense/income push, saldo kas |
| Monitoring | `MonitoringController`, `MonitoringService` | KPI, deviasi, acknowledgement |
| RHPP | `RhppActionController`, `RhppSyncController`, `RhppDocumentController` | Generate, publish, sync PUBLISHED only |
| Export | `*ExportController` | Harvest, OVK, BOP, evaluasi, gaji, RHPP Excel |
| Upload | `UploadController` | R2 upload (foto, receipt, PDF) |
| Education | `EducationArticleController`, `PriceReferenceController` | Artikel + referensi harga |
| Investor | `InvestorDashboardController`, `InvestorPeriodController` | View-only scoped |
| Notifications | `NotificationController` | List, mark-read |
| Import gaji | `SalaryImportController` | Excel import template |

### Backlog / Deferred (BE-side)

| Item | Status |
|------|--------|
| OTP gateway (email/WhatsApp) | Belum di-wire — OTP cached server-side |
| RHPP COGS v2 | Formula komponen Excel import/revisi penuh |
| Opening balance carryover | `opening_balance = 0` — belum ada model carryover per periode |
| ARV PDF parsing engine | Parsed standards / IoT suhu |

Detail: [masalah-dan-backlog.md](./masalah-dan-backlog.md)

---

## Testing

```bash
php artisan test
composer test
```

Coverage utama (Feature tests):
- Auth, version check, investor scope
- Finance sync, approvals
- Monitoring KPI & deviations
- Period actions, journey setup

Referensi: [docs/run_test.md](../run_test.md)

---

## Versi & Gatekeeper

| Sumber | Versi |
|--------|-------|
| `anderson-farm-fe/app.json` | `1.0.0` / versionCode 3 |
| `config/app_version.php` | `latest: 1.0.0`, `min: 0.1.0` |
| Production `.env` | `LATEST_APP_VERSION=1.0.0`, `MIN_APP_VERSION=0.1.0` |

**Penting:** Setiap release app baru, update `LATEST_APP_VERSION` di production sebelum distribusi APK/AAB.

---

## Yang Berfungsi End-to-End

1. Login → device binding → token Sanctum
2. Master sync pull → period sync → daily activity push/pull
3. Finance push (expense/income) → approval queue → approve/reject
4. Photo/receipt upload ke R2 saat sync flush
5. RHPP generate → publish → sync ke mobile (PUBLISHED only)
6. Export Excel (harvest, OVK, BOP, dll.)
7. Version check → force/optional update di mobile
