### 1. Parameter yang Dikirim & Cara Mendapatkannya

Aplikasi _mobile_ mengirimkan dua parameter utama dalam _query string_:

- **`last_sync_timestamp`**: Diambil dari tabel lokal **`sync_trackers`**.
    - _Query di Mobile:_ `SELECT last_sync_at FROM sync_trackers WHERE table_name = 'daily_activities' LIMIT 1;`
    - Jika hasilnya kosong (instalasi baru), kirimkan string kosong atau tanggal tua (misal: `1970-01-01`).
- **`period_id`**: Diambil dari status aplikasi yang sedang aktif (periode mana yang sedang dibuka oleh Anak Kandang).

**Contoh URL:**
`GET /api/v1/sync/daily-activities?last_sync_timestamp=2026-04-20T10:00:00Z&period_id=uuid-123`

---

### 2. Logika Backend & Respons

Di sisi server (Laravel), algoritmanya adalah mencari data yang "lebih baru" dari waktu terakhir klien melakukan sinkronisasi.

**Logika Backend:**

1.  Ambil `last_sync_timestamp` dari request.
2.  Lakukan _query_ ke tabel `daily_activity_headers` dengan kondisi:
    - `period_id` cocok dengan yang diminta.
    - `updated_at_server` > `last_sync_timestamp`.
3.  **Penting:** Gunakan `with()` untuk mengikutsertakan semua tabel anak (Eager Loading) seperti `daily_dynamic_logs`, `ovk_usages`, dan `daily_checklist_logs`.
4.  Ambil waktu server saat ini (`NOW()`) sebagai penanda waktu sinkronisasi yang baru.

**Struktur Respons:**

```json
{
  "success": true,
  "current_server_timestamp": "2026-04-24T10:00:00Z",
  "data": [
    {
      "id": "uuid-header-A",
      "business_status": "APPROVED",
      "mortality_count": 10,
      "daily_checklist_logs": [...],
      "updated_at_server": "2026-04-23T15:00:00Z"
    }
  ]
}
```

---

### 3. Logika Mobile Saat Menerima Respons

Setelah HP menerima JSON di atas, ia tidak boleh langsung menimpa semuanya. Ia harus melakukan **UPSERT** (Update or Insert):

1.  **Iterasi Data:** Lakukan _loop_ pada _array_ `data`.
2.  **Cek Lokal:** Cari di SQLite lokal apakah ID tersebut sudah ada.
    - **Jika Ada:** Perbarui baris tersebut dengan data terbaru dari server (terutama status `business_status`).
    - **Jika Tidak Ada:** Masukkan sebagai baris baru.
3.  **Proses Anak:** Lakukan hal yang sama (Wipe and Replace) untuk tabel-tabel relasi anaknya di tingkat lokal.

---

### 4. Apa yang Harus Dilakukan Setelah Itu? (Finishing)

Setelah semua data aktivitas berhasil disimpan ke SQLite, langkah terakhir yang **wajib** dilakukan adalah memperbarui "buku catatan" sinkronisasi:

- **Update Sync Tracker:**
    ```sql
    UPDATE sync_trackers
    SET last_sync_at = '2026-04-24T10:00:00Z' -- Diambil dari current_server_timestamp respons
    WHERE table_name = 'daily_activities';
    ```

---

### 5. Bagaimana Cara Mengetahui Sync Berhasil?

Kedua belah pihak saling mengonfirmasi dengan cara yang berbeda:

- **Sisi Mobile:** Mengetahui berhasil jika HTTP Status Code adalah `200 OK` dan proses simpan ke SQLite selesai tanpa _error_. Indikator visualnya adalah status di UI berubah dari ikon "awan mendung/pending" menjadi "centang hijau/synced".
- **Sisi Server:** Mengetahui berhasil secara implisit. Server menganggap tugasnya selesai begitu respons dikirimkan.
- **Apakah perlu konfirmasi balik?** Tidak perlu _endpoint_ tambahan. Jika koneksi terputus saat respons dikirim, HP tidak akan memperbarui `sync_trackers`. Maka saat HP melakukan _sync_ berikutnya, ia akan mengirimkan _timestamp_ lama, dan server akan mengirimkan data yang sama lagi (Idempotent).
