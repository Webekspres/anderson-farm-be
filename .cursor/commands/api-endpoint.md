# api-endpoint

---

## description: Gunakan workflow ini saat ingin membuat fitur API baru atau melakukan pengujian.

# Workflow: API Endpoint & Quality Assurance

## Trigger

Dijalankan ketika pengguna meminta pembuatan, pembaruan, atau pengujian endpoint API.

## Langkah-langkah

1. **Implementation Plan**: Buat outline langkah demi langkah, detail file yang akan diubah, dan alur logikanya.
2. **Self-Review**: Periksa potensi edge cases dan pelanggaran prinsip DRY.
3. **Execution**:
    - Buat Route di `api.php`.
    - Buat Form Request dengan aturan validasi yang ketat.
    - Buat Controller tipis (Thin Controller).
    - Buat API Resource.
4. **Automated Testing (Pest PHP)**:
    - Lokasi: `tests/Feature/Api/V1/...`.
    - Skenario: Minimal 1 Test Happy-path (Success) dan 1 Test Edge-case (Failure/Unauthorized).
    - Gunakan `RefreshDatabase` dan Factory untuk setup state.
