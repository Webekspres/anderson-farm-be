### 🟢 FASE 1: Selesai GET (Kondisi Siap Tempur)

_Pagi hari, Anak Kandang (ABK) terhubung ke WiFi mes, menekan tombol "Tarik Data Server"._

1. **Apa yang terjadi:** HP memanggil `GET /api/v1/sync/daily-activities`.
2. **Posisi Data:** Data terbaru dari MySQL (Cloud Server) turun dan **ditulis/disimpan ke dalam SQLite** (Database Lokal di HP).
3. **Status Akhir Fase 1:** HP sekarang memiliki salinan data server. Tabel `sync_trackers` di HP diperbarui dengan waktu server (`current_server_timestamp`). Anak Kandang kini membawa HP ke kandang yang **tidak ada sinyal internet sama sekali**.

### 🟡 FASE 2: Mode Offline (Perekaman Data)

_Siang hari, ABK mencatat ada 5 ayam mati, memberi pakan, dan memvaksin ayam._

1. **Interaksi User:** ABK membuka aplikasi, mengisi form "Laporan Harian", menekan "Simpan".
2. **Generasi ID (Krusial):** Karena sedang _offline_, HP tidak bisa meminta ID (Auto Increment) ke Server. Maka, aplikasi _mobile_ **men-generate UUID sendiri** (misal: `uuid-header-999` dan `uuid-detail-ovk-1`).
3. **Ditulis ke Mana?** Data **HANYA ditulis ke tabel SQLite lokal di HP**.
4. **Penandaan (Flagging):** HP menyimpan data tersebut ke tabel `daily_activity_headers` lokal dengan menyematkan:
    - `sync_status` = `'PENDING_SYNC'` (Ini sangat penting).
    - `created_at_client` = `2026-04-24 13:00:00` (Waktu asli saat tombol diklik).

### 🟠 FASE 3: Persiapan PUSH (Kembali ke Area Bersinyal)

_Sore hari, ABK kembali ke mes, terhubung ke WiFi, lalu menekan tombol "Kirim Data"._

1. **Query Lokal:** Sebelum mengirim API, aplikasi _mobile_ menyapu database SQLite-nya sendiri dengan _query_:
   `SELECT * FROM daily_activity_headers WHERE sync_status IN ('PENDING_SYNC', 'CONFLICT');`
2. **Perakitan Payload:** HP menemukan `uuid-header-999`. Ia lalu merakit JSON super besar (Header beserta seluruh relasi anaknya: OVK, Kematian, Foto, dll).
3. **Pengiriman:** HP memanggil `POST /api/v1/sync/daily-activities` dengan membawa JSON tersebut.

### 🔴 FASE 4: Server Memproses (Wipe & Replace)

_Data JSON sampai ke Laravel Backend (di awan)._

1. **Gatekeeping:** Server mengecek apakah Periode tersebut masih aktif.
2. **Operasi Induk (Header):** Server menulis (UPSERT) `uuid-header-999` ke dalam tabel `daily_activity_headers` di **MySQL Server**. Server menyisipkan waktu `updated_at_server = NOW()` dan mengubah `sync_status = 'SYNCED'`.
3. **Operasi Anak (Detail):** Server mencari apakah ada data anak lama milik `uuid-header-999`. Jika ada, dihapus semua (_Wipe_). Lalu server menulis ulang secara massal (Bulk Insert / _Replace_) semua data OVK, kematian, dan checklist ke **MySQL Server**.
4. **Respons Dibuat:** Server membalas ke HP: _"Sukses! `uuid-header-999` sudah saya simpan. Ini `server_id` urutannya: 450."_

### 🔵 FASE 5: Selesai POST (Kerapian Lokal)

_Respons sukses dari Server diterima oleh HP._

1. **Finalisasi:** Aplikasi _mobile_ mencari baris `uuid-header-999` di dalam **SQLite lokalnya**.
2. **Pembaruan Status:** HP memperbarui baris tersebut:
    - Mengubah `sync_status` dari `'PENDING_SYNC'` menjadi `'SYNCED'`.
    - Memasukkan nomor urut `server_id = 450` (Penting untuk pengurutan tampilan jika diperlukan).
3. **Selesai:** Ikon di HP berubah dari "Panah Berputar" menjadi "Centang Hijau". Siklus sinkronisasi selesai 100%.

**Ringkasan Golden Rule (Aturan Emas):**
Dalam _offline-first_, Server adalah pemegang kebenaran mutlak untuk _waktu_ (`updated_at_server`) dan _struktur id_ (`server_id`), sedangkan HP Klien adalah pelapor kejadian yang bertanggung jawab menciptakan `UUID` dan merekam waktu kejadian aslinya (`created_at_client`). Server hanya menyalin dan memvalidasi apa yang terjadi di HP.
