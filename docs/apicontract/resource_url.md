# 1. Master Data Utama (Independen)

Data ini menjadi pondasi. Untuk aplikasi mobile Anak Kandang, biasanya mereka hanya melakukan GET, tapi kita tetap sediakan POST jika ada fitur input dari mobile untuk level Manager.

**Users & Auth**

- /api/users (GET, POST)

**Farm & Infrastruktur**

- /api/farms (GET, POST)
- /api/coops (GET, POST)
- /api/coop-documents (GET, POST)

**Engine Form & Kategori**

- /api/equipment-types (GET)
- /api/form-configs (GET)
- /api/category-cashflows (GET)

---

# 2. Operasional Kandang (Inti Aplikasi)

Grup ini adalah endpoint yang paling sering diakses (Read & Write) oleh Anak Kandang setiap harinya.

**Siklus Ternak**

- /api/production-periods (GET, POST)
- /api/period-investors (GET, POST) — Pivot tabel yang diekspos agar mobile tahu persentase investor.

**Aktivitas & Hasil**

- /api/daily-activities (GET, POST)
- /api/harvests (GET, POST)
- /api/employee-salaries (GET, POST)

---

# 3. Keuangan & Laporan

Sesuai dengan kesepakatan penggabungan tabel, Income dan Expense melebur di sini.

**Arus Kas Tunggal**

- /api/transactions (GET, POST) — Payload di sini sudah membawa category_id (pakan, panen, gaji, dll).

**Finalisasi (Tugas Server)**

- /api/rhpps (GET) — Mobile menarik data hasil laporan.

Khusus RHPP, disarankan tetap ada endpoint action seperti /api/rhpps/{period_id}/generate karena kalkulasinya berat.

---

# 4. Sistem Log & Edukasi

**Audit Trail**

- /api/activity-logs (POST) — Mobile hanya mengirim log, jarang perlu menarik log.

**Konten Eksternal (Biasanya Read-Only untuk Mobile)**

- /api/education-articles (GET)
- /api/price-references (GET)
- /api/report-templates (GET)
