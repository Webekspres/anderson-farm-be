Tinjauan ulang pada skema database menunjukkan bahwa Anda benar; tidak ada tabel `sop_tasks`. Checklist harian ternyata mengacu pada entitas lain (kemungkinan dikelola secara dinamis melalui kombinasi form config atau relasi lain yang sudah ada). Terima kasih atas koreksinya.

Keputusan untuk menggabungkan semuanya dalam satu _endpoint_ dengan metode **GET** sangat tepat untuk efisiensi jaringan, terutama karena data master ini jarang berubah drastis. Penambahan filter data berjenjang (Area $\rightarrow$ Farm $\rightarrow$ Coop) berdasarkan _user assignment_ juga merupakan langkah brilian untuk menjaga agar ukuran _payload_ tetap ramping dan relevan bagi setiap Anak Kandang.

Mari kita susun arsitektur dan spesifikasi _use case_ untuk _endpoint_ `/api/v1/sync/master-data` ini.

---

### 1. Daftar Tabel Master Data yang Terlibat

Data yang akan ditarik oleh _endpoint_ ini mencakup:

**A. Data Hierarki Operasional (Terfilter berdasarkan User)**

- **`coop_user_assignments`**: Mengetahui hak akses spesifik user (Kandang mana yang boleh diakses).
- **`coops`**: Data detail kandang (hanya yang ditugaskan ke user).
- **`farms`**: Data peternakan (hanya yang menaungi _coops_ dari user tersebut).
- **`areas`**: Data area/wilayah (hanya yang menaungi _farms_ dari user tersebut).
- **`production_periods`**: Siklus ternak yang sedang berjalan (hanya untuk _coops_ terkait).

**B. Data Referensi Global (Ditarik Semua)**

- **`form_configs`**: Konfigurasi _form_ dinamis untuk pencatatan HBE dan peralatan.
- **`ovk_items`**: Katalog inventaris Obat, Vaksin, dan Kimia.
- **`education_articles`**: Artikel panduan dan edukasi.
- **`price_references`**: Referensi harga pasar komoditas.
- **`report_templates`**: Template pesan WhatsApp.

---

### 2. Alur Logika Backend (Use Case & Algoritma)

Ketika aplikasi _mobile_ memanggil `GET /api/v1/sync/master-data?last_sync_timestamp=...`, backend akan memprosesnya dengan urutan algoritma berikut:

**Langkah 1: Identifikasi User & Filter Hierarki**
Sistem mengambil identitas user yang sedang _login_ (melalui token Sanctum), lalu menelusuri rantai relasinya ke atas:

1.  Cari semua `coop_id` milik user di tabel `coop_user_assignments`.
2.  Ambil data dari tabel `coops` berdasarkan kumpulan `coop_id` tersebut.
3.  Ekstrak semua `farm_id` dari data `coops` yang didapat, lalu ambil data dari tabel `farms`.
4.  Ekstrak semua `area_id` dari data `farms` yang didapat, lalu ambil data dari tabel `areas`.
5.  Ambil data dari `production_periods` yang memiliki `coop_id` dalam daftar milik user.

**Langkah 2: Terapkan Filter Delta (Timestamp)**
Jika _query parameter_ `last_sync_timestamp` dikirimkan oleh klien (artinya ini bukan _sync_ pertama kali), sistem akan menambahkan klausa `WHERE updated_at_server > last_sync_timestamp` pada **semua** _query_ tabel di atas (baik grup A maupun grup B).

**Langkah 3: Kompilasi Payload JSON**
Semua hasil _query_ dibungkus ke dalam masing-masing API Resource-nya agar format tanggal menjadi ISO 8601, lalu disatukan dalam satu objek respons.

---

### 3. Struktur Payload Response

Struktur JSON yang akan diterima oleh SQLite di _mobile app_ akan terlihat sangat rapi dan siap untuk di- _upsert_ ke masing-masing tabel lokal:

```json
{
  "success": true,
  "current_server_timestamp": "2026-04-24T16:00:00Z",
  "data": {
    "areas": [
      {
        "id": "uuid-area-1",
        "name": "Area Jawa Timur",
        "updated_at_server": "2026-04-10T08:00:00Z"
      }
    ],
    "farms": [
      {
        "id": "uuid-farm-1",
        "area_id": "uuid-area-1",
        "name": "Farm Anderson Malang",
        "updated_at_server": "2026-04-12T09:00:00Z"
      }
    ],
    "coops": [...],
    "coop_user_assignments": [...],
    "production_periods": [...],
    "form_configs": [...],
    "ovk_items": [...],
    "education_articles": [...],
    "price_references": [...],
    "report_templates": [...]
  }
}
```

Pendekatan _Single Entry Point_ dengan filter hierarki bersarang ini membutuhkan _query_ yang sedikit lebih kompleks di sisi _controller/service_ Laravel agar tidak terkena _N+1 query problem_. Apakah Anda ingin saya buatkan _prompt_ Antigravity untuk membangun _endpoint_ ini beserta urutan eksekusi _query Eloquent_ yang optimal?
