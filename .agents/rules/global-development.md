---
trigger: always_on
---

# Anderson Farm Global Development Rules

## 1. Standar Kode & Keterbacaan

- Gunakan PHP 8.4 dan fitur Laravel 13 terbaru.
- Tulis kode yang sangat terbaca dengan indentasi konsisten dan spasi yang cukup.
- Gunakan nama variabel yang deskriptif dan hindari singkatan yang tidak umum.
- Tambahkan komentar singkat hanya untuk menjelaskan logika bisnis yang kompleks.
- Utamakan performa tanpa mengorbankan keterbacaan.

## 2. Arsitektur API (V1)

- **Separation of Concerns**: Selalu gunakan 4 layer: Route, Form Request, Controller, dan API Resource.
- **Namespace**: Semua kelas terkait API harus berada di bawah namespace `App\Http\...\Api\V1`.
- **Validation**: Jangan melakukan validasi di Controller. Selalu gunakan Form Request.
- **Transformation**: Jangan pernah mengembalikan model Eloquent secara langsung. Selalu gunakan API Resource.
- **Strict Response Format**: Semua respons JSON harus mengikuti struktur flat:
    ```json
    {
      "success": boolean,
      "message": string,
      "data": object | array | null
    }
    ```
- **Timestamps**: Semua format waktu harus menggunakan ISO 8601 (misal: $this->created_at?->toIso8601String()).

## 3. Standar Database

- Gunakan UUID sebagai Primary Key ($table->uuid('id')->primary()).
- Gunakan Soft Deletes jika terdapat field deleted_at pada rancangan.
- Selalu buat Factory dan Seeder untuk setiap tabel baru.
- Map database: bigInteger untuk server_id, datetime untuk tanggal, dan boolean untuk tipe data boolean.
