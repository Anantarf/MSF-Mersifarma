# Checklist README Final

File ini hanya checklist. README utama tetap harus mengikuti kondisi proyek yang benar-benar berjalan.

## Identitas Proyek

- Nama proyek dan tujuan singkat.
- Scope yang selesai: P0 wajib, P1 yang ikut dikerjakan, dan batasan yang masih ada.
- Stack aktual: PHP, Laravel, database, Node/Vite, dan browser yang dipakai untuk demo.
- Link ringkas ke dokumen teknis utama.

## Menjalankan Lokal

README perlu memuat langkah yang sudah diuji:

1. Install dependency backend dan frontend.
2. Salin `.env.example` ke `.env`.
3. Atur database demo.
4. Generate `APP_KEY`.
5. Jalankan migrate/seed hanya pada database disposable.
6. Build asset atau jalankan Vite.
7. Jalankan server Laravel.
8. Login memakai akun demo sintetis.

Jangan menaruh secret, password asli, atau `.env` di repository.

## Data Demo

Cantumkan akun sintetis minimal:

- MR utama dan MR pembanding.
- DM yang menjadi atasan langsung MR.
- DM/RSM/MM untuk skenario approval negatif dan monitoring.
- Dokter MCL dan produk demo.

## API

README cukup menautkan `docs/API_CONTRACT.md`, lalu tulis status singkat:

- endpoint wajib yang sudah tersedia,
- endpoint pendukung yang dibuat untuk UI,
- fitur P1 jika ada,
- batasan yang belum diverifikasi di environment nyata.

## Aturan Bisnis dan Asumsi

Tuliskan asumsi yang dipakai, terutama:

- Call List satu pemilik per bulan.
- Approval memakai atasan langsung, bukan hanya role.
- Call Plan memakai Approved List.
- Direct Actual tanpa Plan tetap valid.
- Duplicate Actual dokter/tanggal dicegah global.
- Manual time tidak membuktikan kehadiran fisik.
- Tidak ada GPS, offline mode, insentif, atau verifikasi foto asli.

## Bukti Pengujian

README final harus memisahkan:

- automated test yang benar-benar dijalankan,
- manual test PDF-01 sampai PDF-05,
- screenshot atau screen recording yang tersedia,
- test yang belum dijalankan atau masih perlu environment tertentu.

Jangan mengubah `NOT RUN` menjadi `PASS` tanpa eksekusi nyata.

## Refleksi Tools

Tulis singkat, konkret, dan berbasis bukti:

- tool yang dipakai,
- output yang diterima setelah diverifikasi,
- output yang ditolak atau dikoreksi,
- command/test yang membuktikan hasilnya.

## Submission

- Link repository.
- Link deployment jika ada.
- Panduan demo 30 menit.
- Catatan kerahasiaan: jangan commit PDF soal asli, data nyata, secret, atau materi sensitif.
