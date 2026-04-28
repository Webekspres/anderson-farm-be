### 1. Fase Setup Infrastruktur & Master Data (Admin/Manager)

Fase ini memetakan bagaimana sistem mengenali dunia nyata sebelum ayam masuk ke kandang.

- **Pembuatan Hierarki Lokasi:** Alur mendaftarkan Area baru, Farm di dalam Area, Kandang (Bangunan) di dalam Farm, hingga memecah Kandang menjadi Lantai 1 dan Lantai 2 beserta kapasitasnya.
- **Registrasi Master Mesin & Template Sensor:** Alur Admin mendefinisikan tipe mesin (misal: Temptron) dan merelasikannya dengan template _form_ (misal: Suhu, Kipas). Termasuk alur _upload_ Excel untuk _auto-generate form_.
- **Pemasangan & Kustomisasi Mesin di Lantai:** Alur Manager Farm mengalokasikan mesin spesifik ke Lantai tertentu, dilanjutkan dengan mematikan/menyalakan sensor spesifik untuk Lantai tersebut (menggunakan `CoopFormAssignment`).
- **Penugasan Personel:** Alur menetapkan Anak Kandang (ABK) dan PIC ke kandang tertentu agar hak akses data mereka terisolasi dengan aman.

### 2. Fase Inisiasi Siklus Ternak (Manager & ABK)

Fase ini mencakup persiapan administratif sebelum operasional harian dimulai.

- **Pembukaan Periode Produksi Baru:** Alur Manager menetapkan populasi awal, target FCR/Mortalitas, mendaftarkan Investor (`PeriodInvestor`), menetapkan struktur Gaji/Bonus (`EmployeeSalary`), dan mengunggah dokumen SOP.
- **Persetujuan Kontrak Digital (The Handshake):** Alur sistem mengunci menu "Input Harian" di HP ABK sampai ABK menarik data periode, membaca `ContractAbk`, dan menekan tombol setuju (`ContractAcceptance`) secara _offline_, lalu mengirimkannya ke _server_.

### 3. Fase Operasional Harian (Anak Kandang)

Fase ini adalah jantung dari aplikasi _mobile_, memetakan interaksi _user_ di area tanpa sinyal.

- **Rendering Form Dinamis:** Alur aplikasi _mobile_ membaca Lantai yang dipilih ABK, mengecek tipe mesin yang terpasang, dan memunculkan kolom _input_ (Suhu, Fan, Humidity) secara otomatis sesuai konfigurasi mesin tersebut.
- **Perekaman Aktivitas Harian (Mode Offline):** Alur ABK menginput tingkat kematian, _culling_, pemakaian Obat/Vaksin (OVK), mencentang SOP harian, hingga mengambil foto bukti, lalu menyimpannya ke SQLite dengan status `PENDING_SYNC`.
- **Perekaman Panen (Harvesting):** Alur ABK mencatat aktivitas penimbangan dan pengeluaran ayam saat masa panen tiba.

### 4. Fase Sinkronisasi Data (Sistem/Backend)

Fase ini memetakan pergerakan data antara SQLite dan MySQL. Skenario teknis ini sangat penting bagi _developer_.

- **Alur PULL Sync (Delta Sync):** Mekanisme HP meminta pembaruan Master Data dan Periode berdasarkan `last_sync_timestamp`, dan bagaimana HP menimpa/memperbarui data di SQLite-nya.
- **Alur PUSH Sync (Bulk Sync & Conflict Resolution):** Mekanisme HP mengirim data operasional ke _server_, bagaimana _server_ melakukan validasi status periode, menyelesaikan konflik data (jika _Manager_ sudah mengubah data di _Web_), dan mengeksekusi strategi _Wipe and Replace_ untuk tabel detail (OVK, Foto, Sensor).

### 5. Fase Peninjauan & Penutupan (Manager/Finance)

Fase ini memetakan bagaimana data dari lapangan diverifikasi dan ditutup.

- **Approval Laporan Harian:** Alur Manager di _Web Dashboard_ meninjau laporan harian yang baru masuk, melakukan _Approve_ atau _Reject_ (dengan alasan), dan bagaimana status _Reject_ ini kembali ke HP ABK saat _sync_ berikutnya.
- **Tutup Periode & Generasi RHPP:** Alur sistem mengakumulasikan total ayam mati, total pakan, total panen, menghitung performa (FCR, IP), hingga mencetak laporan final (RHPP) dan mendistribusikan notifikasi via integrasi WhatsApp (`ReportTemplate`).
