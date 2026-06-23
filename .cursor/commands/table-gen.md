# table-gen

---

## description: Gunakan workflow ini saat Anda memberikan cuplikan skema Prisma untuk diterjemahkan ke Laravel.

# Workflow: Prisma to Laravel Ecosystem

## Trigger

Dijalankan ketika pengguna memberikan cuplikan skema Prisma.

## Langkah-langkah

1. **Analisis Prisma**: Identifikasi nama tabel (`@@map`), primary keys (UUID), nullability, unique constraints, dan relasi.
2. **Drafting**: Rencanakan pembuatan 5 file: Migration, Model, Factory, Seeder, dan API Resource.
3. **Execution**: Gunakan satu perintah artisan untuk inisialisasi:
   `php artisan make:model ModelName -m -f -s` (Resource dibuat terpisah di folder Api/V1).
4. **Implementasi**:
    - Migration: Terjemahkan tipe data Prisma ke Blueprint Laravel secara presisi.
    - Model: Tambahkan trait `HasUuids`, set `$keyType = 'string'`, dan definisikan relasi Eloquent.
    - Factory: Gunakan `fake()` yang relevan.
    - Seeder: Panggil factory dengan count(5).
    - Resource: Map kolom DB ke API contract sesuai standar Global Rules.
