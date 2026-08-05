[//]: # "Memo Manajemen Dokumen Anderson Farm"

# Memo Manajemen Dokumen Anderson Farm

[cite_start]Berdasarkan dokumen Spesifikasi Flow [cite: 381, 651], Proposal [cite: 549-559], dan ERD [cite: 841-845], kebutuhan manajemen dokumen (baik yang diunggah secara fisik maupun _report_ yang di-_generate_ secara sistem) terbagi menjadi beberapa kategori utama.

Jika disepakati bahwa proses _Export_ dan _Import_ (yang memanipulasi _database_ secara masif seperti Excel) dilakukan secara **Online Only**, maka pengelolaan dokumen pada sistem Anderson Farm dapat dirangkum sebagai berikut:

---

## A. Dokumen Input (Upload Fisik via Multipart)

File-file fisik (PDF, JPG, PNG) yang diunggah ke sistem melalui endpoint terpusat (`POST /api/v1/uploads`) dengan parameter `type` sesuai kebutuhan.

### 1. Daftar Kontrak ABK (`ContractAbk`)

- **Deskripsi:** File kontrak kerja/SOP yang diunggah PIC/Manager dari aplikasi mobile untuk periode ternak (`POST /uploads` lalu `POST /periods/{id}/contracts`). ABK/PIC wajib menyetujui (_Contract Acceptance_) sebelum mengisi data harian.
- **Jenis File:** PDF, JPG, PNG.

### 2. Bukti Pengeluaran/Nota (`Expense Receipt`)

- **Deskripsi:** Foto nota/kuitansi untuk setiap pengeluaran kas (obat, pakan, BOP).
- **Jenis File:** JPG/PNG. _Sistem wajib kompresi di mobile sebelum upload._

### 3. Bukti Pemasukan (`Income Receipt`)

- **Deskripsi:** Foto bukti transfer/nota penjualan pupuk, bangkai, sortiran.
- **Jenis File:** JPG/PNG (opsional).

### 4. Bukti Foto Kondisi Harian (`PhotoEvidence`)

- **Deskripsi:** Foto yang dilampirkan ABK/PIC saat laporan harian (kandang bersih, ayam mati, alat rusak).
- **Jenis File:** JPG/PNG.

### 5. Template/Dokumen Kandang (`CoopDocument`/`PeriodDocument`)

- **Deskripsi:** File panduan (ARV, OVK, Cleaning List) di-upload Admin/Manager dari **aplikasi mobile** (Siapkan sistem / detail periode), lalu di-download/view ABK di lapangan (view-only).
- **Jenis File:** PDF/JPG.

### 6. Gambar/Thumbnail Edukasi & Referensi Harga

- **Deskripsi:** Pada Modul 10 (Laporan & Investor), artikel edukasi dan harga komoditi (tabel `EducationArticle` dan `PriceReference` di ERD) memiliki kolom `image_url`. Gambar diunggah oleh Admin dari **aplikasi mobile** (Siapkan sistem) lewat `POST /api/v1/uploads`.
- **Jenis File:** JPG/PNG.
- **API:** Menumpang di endpoint `POST /api/v1/uploads` dengan parameter `type=article`.

---

## B. Dokumen Export (Download dari Server - Online Only)

Laporan yang di-_generate_ real-time oleh server Laravel dari data yang terkumpul. Endpoint harus spesifik (misal: `GET /api/v1/export/rhpp`).

### 1. Laporan Keuangan & RHPP (Rekapitulasi Hasil Pemeliharaan Periode)

- **Deskripsi:** Laporan akhir periode berisi kalkulasi pemasukan, pengeluaran, laba bersih, FCR, IP, deplesi, bagi hasil investor.
- **Format Export:** Excel/PDF.
- **Aktor:** Manager, Finance, Investor (view-only).

### 2. Laporan Panen & Nilai Penjualan

- **Deskripsi:** Rekap data `HarvestEntry` (tanggal panen, ritase, jumlah ekor, berat total, pendapatan kotor per kandang/periode).
- **Format Export:** Excel.

### 3. Laporan Evaluasi & Performa Teknis

- **Deskripsi:** Rekapan grafik/data harian (deplesi, suhu, konsumsi pakan vs ARV) untuk evaluasi SOP oleh Manager.
- **Format Export:** Excel/PDF.

### 4. Format Template Excel Kosong

- **Deskripsi:** Untuk fitur Import Gaji dan Import RHPP, sistem harus menyediakan file Excel kosong dengan header standar agar Finance tidak salah format saat mengisi data.
- **API:** Backend Laravel menyediakan endpoint download, misal `GET /api/v1/export/template-salary` untuk file `template_gaji_abk.xlsx`.

---

## C. Dokumen Import (Upload Data via Excel - Online Only)

Fitur di mana Finance/Manager dapat mengunggah file Excel yang sudah disiapkan, menembak endpoint spesifik (misal: `POST /api/v1/import/salary`).

### 1. Import Komponen RHPP & Bagi Hasil Investor

- **Deskripsi:** Perhitungan RHPP final kadang melibatkan komponen eksternal (pajak, potongan khusus). Finance dapat download template Excel, edit offline, lalu import untuk menghasilkan `RhppStatus::PUBLISHED`.

### 2. Import Gaji ABK (`EmployeeSalary`)

- **Deskripsi:** Daftar upah borongan/bonus panen untuk semua ABK. Lebih efisien daripada input manual per ABK.

---

## D. Dokumen Auto-Generate Teks (Client-Side)

### 1. Generator Laporan WhatsApp

- **Deskripsi:** Aplikasi (tanpa perlu ke server jika data sudah lengkap) mengambil template teks, menyisipkan data harian (misal: "Deplesi hari ini: 10 ekor"), dan menyalin ke clipboard HP agar bisa langsung dikirim via WhatsApp oleh PIC ke grup manajemen.

---

Dengan pemetaan ini, pembagian tugas jelas: **backend Laravel** menyediakan API upload/storage, validasi, import/export Excel, dan sync; **aplikasi mobile** adalah satu-satunya permukaan user (upload foto/file multipart, accept kontrak, generator WA, Siapkan sistem). Produk Anderson Farm adalah mobile apps saja.
