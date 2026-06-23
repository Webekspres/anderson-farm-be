# Memory: Arsitektur & Alur Data Anderson Farm

Dokumen ini mencatat keterkaitan antara **User Journey**, **Endpoint API**, dan **Tabel Database** untuk memastikan konsistensi pengembangan sistem.

---

## FASE 1: Manajemen Master Data (Blueprint)

_Dilakukan oleh Admin Pusat/Purchasing. Bersifat global dan jarang berubah._

### 1.1 Pembuatan Bank Soal (Form Config)

- **Skenario:** Admin membuat standar pertanyaan atau inputan (misal: "Cek Suhu", "Kondisi V-Belt").
- **Endpoint:** `POST /api/v1/form-configs`
- **Tabel Terkait:** `form_configs`
- **Tujuan:** Menyimpan struktur JSON UI untuk inputan di aplikasi mobile.

### 1.2 Katalog Alat (Equipment Type)

- **Skenario:** Admin mendaftarkan merek atau tipe alat yang digunakan perusahaan (misal: "Temptron T604", "Blower 50 Inch").
- **Endpoint:** `POST /api/v1/equipment-types`
- **Tabel Terkait:** `equipment_types`
- **Tujuan:** Menjadi referensi katalog saat akan memasang alat di kandang.

### 1.3 Pemetaan Standar SOP (Equipment Type Form Config)

- **Skenario:** Admin menentukan bahwa setiap alat tipe "Blower" _seharusnya_ memiliki inputan "Cek Suhu" dan "Cek V-Belt".
- **Endpoint:** (Biasanya digabung dalam `POST /api/v1/equipment-types`)
- **Tabel Terkait:** `equipment_type_form_configs`
- **Tujuan:** Sebagai **Template/Rekomendasi**. Saat Manajer memasang Blower di kandang, sistem otomatis menyarankan form yang sesuai.

---

## 🧙 FASE 2: Setup Kandang Baru (The Wizard)

_Dilakukan oleh Manajer Kandang saat pendaftaran kandang baru atau renovasi._

### 2.1 Langkah 1: Profil Fisik Kandang

- **Skenario:** Manajer membuat identitas gedung kandang baru.
- **Endpoint:** `POST /api/v1/coops`
- **Tabel Terkait:** `coops`
- **Tujuan:** Membuat "cangkang" kandang di database.

### 2.2 Langkah 2: Registrasi Alat Fisik (Hardware Registration)

- **Skenario:** Manajer mengambil alat dari katalog (EquipmentType) dan memasangnya secara fisik di kandang tersebut.
- **Endpoint:** `POST /api/v1/coops/{coop_id}/equipments`
- **Tabel Terkait:** `coop_equipments`
- **Tujuan:** Mencatat "Barang Fisik" dengan nomor seri tertentu (`unit_code`) yang terpasang di kandang tersebut.

### 2.3 Langkah 3: Aktivasi Form Dinamis (Form Assignment)

- **Skenario:** Manajer menentukan soal/inputan apa saja yang **aktif** untuk alat fisik yang baru dipasang.
- **Endpoint:** `POST /api/v1/coops/{coop_id}/form-assignments`
- **Tabel Terkait:** `coop_form_assignments`
- **Tujuan:** Sebagai **Saklar (On/Off)**. Menentukan pertanyaan mana yang akan muncul di HP pekerja untuk alat tertentu.

### 2.4 Langkah 4: Penugasan Pekerja

- **Skenario:** Manajer menunjuk siapa saja pekerja (ABK) yang bertanggung jawab atas kandang tersebut.
- **Endpoint:** `POST /api/v1/coops/{coop_id}/user-assignments`
- **Tabel Terkait:** `coop_user_assignments`
- **Tujuan:** Memberikan hak akses (Otorisasi) bagi pekerja untuk melihat data kandang tersebut di aplikasi mobile.

---

## 📱 FASE 3: Rutinitas Harian (Daily Activity)

_Dilakukan oleh Anak Kandang (ABK) setiap hari._

### 3.1 Pengisian Laporan Harian (HBE / Sensor Log)

- **Skenario:** ABK keliling kandang dan mengisi data suhu atau kondisi alat sesuai form yang muncul di aplikasi.
- **Endpoint:** `POST /api/v1/daily-logs` (TBD)
- **Tabel Terkait:** `daily_dynamic_logs`
- **Tujuan:** Menyimpan **jawaban aktual** dari pengecekan harian.

---

## 💡 Ringkasan Kunci Perbedaan Tabel

1.  **`FormConfig`**: "Buku Soal" (Master).
2.  **`EquipmentTypeFormConfig`**: "Kunci Jawaban/Template SOP" (Master).
3.  **`CoopFormAssignment`**: "Lembar Soal yang dibagikan ke murid tertentu" (Aktivasi/Setup).
4.  **`DailyDynamicLog`**: "Kertas Jawaban murid" (Transaksi/Isi Laporan).

---

_Generated on: 2026-04-19_
