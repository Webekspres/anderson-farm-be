# 🧠 Memo: User Journey & Lifecycle Production Period (v4.0)

Dokumen ini mencatat alur hidup satu siklus ternak, mulai dari persiapan gedung hingga pembagian keuntungan ke investor, beserta arsitektur pemisahan pelaporan.

---

### 🏢 FASE 1: Wizard "Buka Siklus Baru" (Web Dashboard)

**Aktor (Role):** Manajer Area atau Admin Kemitraan
**Konteks:** Dilakukan beberapa hari sebelum, atau tepat pada hari saat anak ayam (DOC) tiba di kandang.

| Urutan                       | Aksi yang Dilakukan User                                                                                                                                        | Endpoint yang Di-Hit                         | Tabel yang Ditulis        |
| :--------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------- | :------------------------ |
| **1. Inisiasi & Populasi**   | Mengisi tanggal mulai, memilih Kandang Alpha, memilih PIC, dan memasukkan jumlah ayam masuk (Misal: 20.000 ekor).                                               | `POST /api/v1/periods`                       | `production_periods`      |
| **2. Alokasi Investor**      | Memilih pemodal dari daftar dan menentukan persentase pembagian keuntungan (Misal: PT A 60%, PT B 40%).                                                         | `POST /api/v1/periods/{id}/investors`        | `period_investors`        |
| **3. Setup Laporan (HBE)**   | Menyalakan kewajiban lapor angka biologis (Mortalitas, Pakan) yang merujuk pada Master Data Global. Wajib diisi setiap hari.                                    | `POST /api/v1/periods/{id}/form-assignments` | `period_form_assignments` |
| **4. Setup Checklist (SOP)** | Me-_generate_ tugas SOP (Ceklis/Teks). Bisa diatur rutin setiap hari (`DAILY`) atau spesifik 1x jalan pada umur ayam tertentu (`ONE_TIME` + `target_age_days`). | `POST /api/v1/periods/{id}/checklist-tasks`  | `checklist_tasks`         |
| **5. Upload Kontrak**        | Mengunggah PDF dokumen target FCR dan harga kesepakatan kemitraan.                                                                                              | `POST /api/v1/periods/{id}/contracts`        | `contract_abks`           |

_(Setelah Langkah 5, sistem mengunci konfigurasi dan status periode menjadi `ACTIVE`. Wizard selesai.)_

---

### 📱 FASE 2: Operasional Harian (Mobile App / Offline-First)

**Aktor (Role):** Anak Kandang (ABK) dan PIC Kandang
**Konteks:** Dilakukan setiap hari selama masa pemeliharaan ayam. Karena menggunakan sistem _offline-first_, _endpoint_ yang di-hit biasanya berupa _bulk sync_ saat HP mendapatkan sinyal internet.

| Urutan                            | Aksi yang Dilakukan User                                                                                                                                                             | Endpoint yang Di-Hit                  | Tabel yang Ditulis                                                     |
| :-------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------ | :--------------------------------------------------------------------- |
| **1. Tanda Tangan Kontrak**       | Hari pertama kerja, ABK membuka HP, membaca PDF kontrak dari Manajer, dan menekan tombol "Setuju".                                                                                   | `POST /api/v1/contracts/{id}/accept`  | `contract_acceptances`                                                 |
| **2. Laporan Kedatangan OVK**     | Truk logistik (pakan/vaksin) datang. PIC memfoto Surat Jalan / DO lewat HP.                                                                                                          | `POST /api/v1/periods/{id}/documents` | `period_documents`                                                     |
| **3. Laporan Harian (HBE & SOP)** | Setiap sore, ABK berkeliling kandang mengetik angka kematian (HBE) dan menceklis tugas persiapan/harian. _Tugas yang muncul di layar akan otomatis menyesuaikan umur ayam hari itu._ | `POST /api/v1/sync/daily-activities`  | `daily_activity_headers`, `daily_dynamic_logs`, `daily_checklist_logs` |

---

### 💡 Ringkasan Logika Arsitektur & Pelaporan

- **Pemisahan Tegas Fase:** Manajer mengatur "Aturan Main" di Fase 1 (Kantor), sedangkan ABK hanya "Mengeksekusi" aturan tersebut di Fase 2 (Lapangan).
- **Relasi Pekerja Tidak Berubah:** Penugasan pekerja (`coop_user_assignments`) tidak ada di tahap ini karena pekerja sudah ditugaskan secara permanen ke bangunan Kandang. Saat periode baru dibuat di gedung mereka, aplikasi mereka otomatis menerima tugasnya.
- **Filosofi Pelaporan (Rapor vs Jadwal Piket):**
    - **`PeriodFormAssignment` (Form HBE):** Bagaikan _Rapor Nilai_. Mengumpulkan data **Kuantitatif (Angka)** yang ketat dan standar se-perusahaan. Datanya masuk ke server untuk dihitung ke dalam rumus **Uang, RHPP, dan FCR**.
    - **`ChecklistTask` (SOP):** Bagaikan _Jadwal Piket_. Mengumpulkan data **Kualitatif (Sudah/Belum atau Teks)** yang fleksibel. Digunakan oleh Manager hanya untuk memantau **Kedisiplinan/Kepatuhan Kerja** Anak Kandang, dan tidak akan merusak rumus hitungan performa finansial.
