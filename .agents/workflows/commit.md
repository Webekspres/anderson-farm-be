---
description: Gunakan workflow ini khusus untuk menghasilkan pesan commit yang standar
---

# Workflow: Conventional Commit Generator

## Trigger

Dijalankan setelah perubahan kode selesai dilakukan untuk membuat pesan git commit.

## Aturan

- Gunakan spesifikasi **Conventional Commits**: `<type>(<scope>): <subject>`.
- Type yang valid: `feat`, `fix`, `refactor`, `style`, `chore`, `docs`.
- **Subject**: Gunakan kalimat perintah (imperative), huruf kecil di awal, dan tanpa titik di akhir (maks 50 karakter).
- **Body**: Jelaskan "MENGAPA" dan "BAGAIMANA" jika perubahan bersifat kompleks.
- **Bahasa**: Selalu gunakan Bahasa Indonesia yang profesional.

## Output

Hasilkan pesan commit langsung di dalam blok kode markdown agar mudah disalin.
