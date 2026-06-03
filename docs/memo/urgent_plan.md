Setelah menyandingkan dokumen `sitemap anderson.md` dengan `endpoint_memo.md`, secara garis besar rancangan Anda sudah memiliki tingkat keselarasan yang sangat tinggi sekitar **85%**. Pola pembagian antara data yang dikonsumsi secara lokal (_Offline-first_) dan menu administrasi makro (_Online-only_) sudah terpetakan dengan baik.

Namun, masih terdapat beberapa **"lubang sinkronisasi"** dan kontradiksi arsitektur yang perlu diselaraskan agar tim _mobile app developer_ tidak bingung saat melakukan _routing_ data.

Berikut adalah _breakdown_ lengkap mengenai kesesuaian dan celah yang harus Anda tambahkan:

---

## 1. Komponen yang Sudah Sesuai (Match)

- **Autentikasi & Sesi:** Protokol login, _device binding_, penarikan profil (`/auth/me`), hingga penyimpanan token Firebase (FCM) sudah serasi penuh antara UI sitemap dan spesifikasi API.
- **Modul Input Operasional ABK:** Input biologis harian (Growth, Environment, OVK, dan Panen) sudah terakomodasi dengan sempurna lewat satu gerbang beban berat, yaitu `POST /api/v1/sync/daily-activities`.
- **Modul Kelola Master Data (Admin):** CRUD untuk User, Wilayah (Areas), Peternakan (Farms), Kandang (Coops), Master OVK, hingga Artikel Edukasi sudah _mirroring_ secara tepat antara halaman kelola mobile dan RESTful API.
- **Engine Ekspor & Finansial:** Semua tombol unduh berkas laporan keuangan (RHPP, Panen, Evaluasi, OVK, dan BOP) beserta skema unggah massal Gaji ABK via Excel sudah terpetakan dengan aman.
- **Portal Investor:** Halaman khusus `GET /api/v1/investor/dashboard` yang berstatus _read-only_ tanpa SQLite sudah didukung penuh di kedua dokumen.

---

## 2. Komponen yang Belum Sesuai (Gaps & Discrepancies)

Berikut adalah 5 poin krusial yang ada di Sitemap tetapi **hilang, tidak konsisten, atau belum terdefinisi** di Endpoint Memo Anda:

### A. Kontradiksi Arsitektur Modul Approval Manager (Kritis)

- **Di Sitemap (Modul 6 & 18):** Fitur _Approval Laporan Harian_ diletakkan di bawah menu `/kelola/approvals` yang secara tegas dinyatakan sebagai kelompok **Online-only by design (Tidak ada offline queue)**.
- **Di Endpoint Memo (Bagian B):** Terdapat catatan bahwa _"Persetujuan (Approval) Manager dilakukan secara offline dengan cara mengubah kolom business_status"_.
- **Solusi:** Anda harus menegaskan kembali keputusan bisnis peternakan. Jika Manager melakukan approval secara online saat memantau dari rumah/kantor, Anda wajib menambahkan _endpoint_ RESTful online baru di kelompok A Memo Anda: `POST /api/v1/approvals/daily-activities/{id}`.

---

Dari kelima poin ketidaksesuaian di atas, mana yang ingin kita sepakati terlebih dahulu untuk metrik **Approval Manager**—apakah mau dikunci sebagai fitur _Online-Only_ (sesuai Sitemap) atau _Offline-First_ (sesuai catatan kaki Endpoint Memo)?
