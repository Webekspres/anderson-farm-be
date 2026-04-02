## Level 1: Master Utama (Sangat Independen)

Tabel-tabel ini berdiri sendiri, tidak memiliki Foreign Key ke tabel lain, dan menjadi pondasi bagi seluruh sistem.

- users
- farms
- equipment_types
- form_configs
- category_cashflows (Master Kategori Transaksi baru)
- education_articles
- price_references
- report_templates

---

## Level 2: Master Level 2 (Bergantung pada Level 1)

Tabel ini baru bisa dibuat jika data di Level 1 sudah ada.

- coops: Membutuhkan farm_id.
- equipment_type_form_configs: Membutuhkan equipment_type_id dan form_config_id.

---

## Level 3: Operasional Kandang (Bergantung pada Level 2 & 1)

- coop_documents: Membutuhkan coop_id.
- coop_equipments: Membutuhkan coop_id dan equipment_type_id.
- production_periods (Sangat Krusial): Membutuhkan coop_id dan pic_id (dari tabel users). Ini adalah gerbang untuk semua data transaksi.

---

## Level 4: Transaksi Dasar & Relasi Periode (Bergantung pada Level 3)

Semua tabel di bawah ini tidak akan bisa disimpan jika period_id belum ada di database.

- period_investors: Membutuhkan period_id dan user_id (investor).
- daily_activities: Membutuhkan period_id dan user_id.
- harvests: Membutuhkan period_id dan user_id.
- employee_salaries: Membutuhkan period_id dan employee_id.
- rhpps: Laporan final yang membutuhkan period_id.

---

## Level 5: Transaksi Lanjutan Terikat (Bergantung pada Level 4)

- transactions (Arus Kas):
    - Mengapa Level 5? Tabel ini terikat dengan period_id, user_id, dan category_cashflow_id. Namun, tabel ini juga memiliki relasi opsional (nullable) ke harvest_id dan salary_id.
      Artinya, jika ada transaksi pemasukan dari panen, maka data harvests (Level 4) wajib disinkronisasi lebih dulu sebelum transactions masuk. Begitu juga untuk pembayaran gaji ABK (employee_salaries).

---

## Level 6: Observer / Log Akhir (Paling Bergantung)

- activity_logs:

    Mengapa paling akhir? Log ini mencatat aktivitas menggunakan mekanisme polymorphic (entity_type dan entity_id). Agar data audit valid dan logis, entitas aslinya (entah itu Kandang, Panen, atau Transaksi) harus sudah tersimpan dengan aman di server sebelum log aktivitasnya dikirimkan.

---

Panduan API Push untuk Mobile Dev:
