# Database seeders — Anderson Farm

## Default (testing / Spesifikasi Flow)

Use the deterministic **MinimalDemo** dataset — not the random factory chain.

```bash
php artisan db:seed
# same as:
php artisan db:seed --class=MinimalDemoSeeder

# clean slate (destructive):
php artisan migrate:fresh --seed
```

Idempotent: safe to re-run `MinimalDemoSeeder` (uses `updateOrCreate`).

### Login matrix

Password for all demo users: **`password123`**

| Username   | Role     | Typical modules / smoke |
|------------|----------|-------------------------|
| `admin`    | admin    | Modul 1–2 setup, user/lokasi, unbind |
| `manager`  | manager  | Periode, kontrak upload, monitoring, close |
| `finance`  | finance  | Expense/income, approval, RHPP, export |
| `pic`      | pic      | Operasi harian, HBE, kontrak accept |
| `abk`      | abk      | Operasi harian, cleaning/foto, kontrak accept |
| `investor` | investor | Laporan / portofolio (period attach) |

### What MinimalDemo already seeds

- 1 Area → 1 Farm → 1 Kandang (`Kandang A`) → 2 lantai
- Active period `DEMO-001` on Lantai 1 (PIC = `pic`)
- Coop assignments: PIC (`kepala_kandang`) + ABK
- Contract PDF stub + **acceptances for PIC and ABK** (gate Modul 3)
- `period_investors`: `investor` @ 100% share, initial investment 50jt
- Period docs stubs: `CARE_TEMPLATE`, `OVK`, `ARV` (placeholder URLs)
- Catalog: transaction categories, WA report template, 2 equipment types, 2 OVK items

### What you still test in the app

Daily activities, panen, expense/income, approvals, RHPP generate/publish, sync conflicts, photo upload, device binding — create via mobile after login.

---

## Opt-in only: FullDemo (random / volume)

```bash
php artisan db:seed --class=FullDemoSeeder
```

**Do not use for UAT / smoke against Spesifikasi Flow.** This runs factory-based seeders (many farms, random users, bulk transactions). Prefer MinimalDemo for FE and staging smoke.
