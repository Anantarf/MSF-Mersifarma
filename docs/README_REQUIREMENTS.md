# README Requirements — untuk repository MSF yang sudah diimplementasikan

> **Bukan README final aplikasi.** IDE harus membuat `README.md` yang sesuai *hasil implementasi sebenarnya*; jangan menyalin status PASS/demo dari dokumen ini sebelum benar-benar ada bukti.

## 1. Identitas proyek

- Nama proyek dan tujuan singkat.
- Scope dikerjakan: P0 mana sudah lengkap; P1 mana jika ada.
- Stack aktual dan versi PHP, Laravel, MySQL, serta kebutuhan browser.
- Arsitektur ringkas dengan tautan ke `DESIGN.md`.

## 2. Menjalankan secara lokal

Tulis command yang benar-benar sudah diuji:

1. Clone repository.
2. `composer install` dan langkah instalasi asset frontend yang sesuai (jika ada).
3. Copy `.env.example` menjadi `.env` dan atur koneksi DB.
4. Buat APP_KEY.
5. `php artisan migrate:fresh --seed` (**hanya untuk database demo**, dengan peringatan menghapus data).
6. Jalankan Laravel server/asset bundler.
7. Akses URL lokal dan login dengan **akun demo sintetis**.
8. Cara mengambil cookie/CSRF bila menguji API via Postman/cURL; atau berikan petunjuk browser session.

Jangan menaruh password asli atau `.env` pada repo.

## 3. Skenario login dan demo

- Seed actor MR-A, MR-B, DM-A, DM-B, RSM, MM, dan relasi manager langsung.
- Dokter MCL dan produk sintetis.
- Demo happy path MR List→DM approve→Plan→Actual; reject/resubmit; duplicate protection; Without Plan; negative role.

## 4. Daftar API

Link ke `API_CONTRACT.md`. Tandai endpoint **sudah dikerjakan**, **belum dikerjakan**, dan optional P1 secara jujur.

## 5. Business assumptions & limitations

Tuliskan asumsi yang benar-benar dipakai, minimal mengacu pada `DECISIONS.md`: status List 3 aktif+event, global Plan uniqueness, owner/month List, DM/RSM personal List, classification as-of Check-in, schedule deviation, UI reason vs optional API, manual time tidak membuktikan kehadiran, P1 nondependency.

## 6. Manual test evidence

Sediakan tabel PDF-01..PDF-05 dengan: Steps, Expected, Actual, PASS/FAIL, bukti screenshot atau screen recording. Jangan mengubah `NOT RUN` menjadi PASS hanya karena kode tampak benar.

## 7. Tools reflection

Untuk setiap alat yang dipakai (AI coding assistant, coding tools, linter, DB tools, test runner): tujuan, contoh output diterima dan ditolak, alasan, serta bagaimana kebenarannya diverifikasi sebelum digabung ke source code.

## 8. Submission

- Link repository dan jika tersedia live deployment (opsional).
- Petunjuk presentasi 30 menit dan test/demo.
- **Confidentiality:** jangan commit PDF soal asli, data nyata, secret, atau materi sensitif. Gunakan seed dummy. Jika ketentuan public repo vs kerahasiaan bahan tidak jelas, minta klarifikasi penyelenggara.
