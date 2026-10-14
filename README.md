# Mobile Sales Force (MSF) - PT Mersifarma

Aplikasi studi kasus **Mobile Sales Force** untuk alur kerja Medical Representative: menyusun target dokter bulanan, membuat jadwal kunjungan, dan mencatat realisasi kunjungan dengan bukti foto, tanda tangan, serta produk detailing.

## Status Saat Ini

| Area | Status |
|---|---|
| 8 endpoint wajib PDF | Selesai dan diuji |
| 3 halaman utama Blade | Selesai: Target Dokter, Jadwal Kunjungan, Pelaksanaan Kunjungan |
| Business rules P0 | Selesai: approval, ownership, duplicate protection, planned/without plan actual |
| Fitur tambahan P1 | Tersedia: sesi kunjungan (pencatatan waktu mulai/selesai otomatis), reschedule/cancel plan, rekomendasi dan arahan manajerial |
| Automated tests | `105 passed (375 assertions)` |
| UI smoke evidence | Tersedia di `docs/evidence/ui-smoke/` |
| Skenario manual PDF | Perlu direkam saat demo akhir; automated test bukan penggantinya |

README ini menjelaskan arsitektur, aturan bisnis, dan kondisi implementasi aktual.

## Fitur Utama

### Target Dokter

- MR membuat Call List bulanan dari Master Customer List.
- Draft dapat disimpan, diajukan, ditolak, direvisi, dan diajukan ulang.
- Approval hanya oleh atasan langsung yang ditugaskan.
- Riwayat approval tersimpan sebagai audit trail.
- Manager melihat antrean persetujuan dan ringkasan monitoring bawahan.

### Jadwal Kunjungan

- MR membuat Call Plan hanya dari Call List yang sudah disetujui.
- Dokter harus termasuk dalam Call List sumber.
- Tanggal jadwal harus berada dalam bulan Call List.
- Sistem menolak bentrok dokter/tanggal secara global.
- Jadwal dapat dibatalkan dengan alasan jika belum completed.
- Jadwal lama yang lewat hari dapat di-expire oleh command.

### Pelaksanaan Kunjungan

- MR dapat mencatat Actual dari Plan terjadwal.
- MR juga dapat mencatat kunjungan tanpa Plan (`plan_id = null`).
- Foto, tanda tangan, dan minimal satu produk wajib untuk Actual final.
- Sistem mencegah duplikasi Actual untuk dokter dan tanggal yang sama secara global.
- Backend mengklasifikasikan kunjungan tanpa Plan sebagai `unplanned` atau `non_target`.
- Manager dapat melihat riwayat Actual bawahan sesuai scope hierarki.

## Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.2, Laravel 11 |
| Database | MySQL 8.0+ atau MariaDB 10.4+ dengan InnoDB |
| Frontend | Blade, CSS per halaman, JavaScript per halaman, Vite |
| Auth | Laravel session auth + CSRF same-origin |
| Testing | PHPUnit feature tests |

Struktur frontend sudah dipisah agar Blade tetap ringan:

```text
resources/views/          # Markup Blade
resources/css/pages/      # CSS per halaman utama
resources/js/pages/       # JavaScript per halaman utama
app/Services/             # Business rules backend
app/Http/Requests/        # Validasi request
```

## Instalasi Lokal

### Prasyarat

- PHP 8.2+
- Composer 2.x
- Node.js dan npm
- MySQL 8.0+ atau MariaDB 10.4+

### Langkah Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mersi_msf
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_unicode_ci
```

Jalankan migrasi dan seed data demo:

```bash
php artisan migrate:fresh --seed
```

Catatan: `migrate:fresh` menghapus tabel lama. Gunakan hanya pada database lokal/demo.

Jalankan aplikasi:

```bash
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Akun Demo

Semua akun memakai password: `password`.

| Role | Username | Email | Atasan Langsung | Keterangan |
|---|---|---|---|---|
| MM | `mm` | `mm@mersi.com` | - | Puncak hierarki |
| RSM | `rsm` | `rsm@mersi.com` | MM | Regional manager |
| DM | `dm` | `dm@mersi.com` | RSM | Jakarta Selatan; atasan MR Rudi dan Ahmad |
| DM 2 | `dm2` | `dm2@mersi.com` | RSM | Jakarta Timur; atasan MR Nadia dan Bambang |
| MR | `mr` | `mr@mersi.com` | DM | Akun demo Jakarta Selatan |
| MR 2 | `mr2` | `mr2@mersi.com` | DM | Uji duplicate dan privacy |
| MR 3 | `mr3` | `mr3@mersi.com` | DM 2 | Akun demo Jakarta Timur |
| MR 4 | `mr4` | `mr4@mersi.com` | DM 2 | Pasangan MR kedua Jakarta Timur |

## Endpoint API

### Endpoint Wajib PDF

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/call-lists` | Membuat draft Call List |
| `GET` | `/api/call-lists` | Mengambil Call List sesuai scope user |
| `GET` | `/api/call-lists/{id}` | Detail Call List dan approval history |
| `PATCH` | `/api/call-lists/{id}/approve` | Approve atau reject Call List |
| `POST` | `/api/call-plans` | Membuat Call Plan |
| `GET` | `/api/call-plans` | Mengambil Call Plan milik MR |
| `POST` | `/api/call-actuals` | Mencatat Call Actual planned atau without plan |
| `GET` | `/api/call-actuals` | Mengambil riwayat Actual sesuai scope user |

### Endpoint Pendukung

| Method | Endpoint | Fungsi |
|---|---|---|
| `PATCH/PUT` | `/api/call-lists/{id}` | Edit draft Call List |
| `POST` | `/api/call-lists/{id}/submit` | Submit atau resubmit Call List |
| `POST` | `/api/call-lists/{id}/reject` | Shortcut reject dengan reason |
| `GET` | `/api/call-lists/summary` | Ringkasan monitoring manager |
| `POST` | `/api/visit-sessions` | Mulai atau resume visit session |
| `GET` | `/api/visit-sessions/active` | Ambil session aktif |
| `POST` | `/api/visit-sessions/{id}/checkout` | Mengakhiri sesi kunjungan (catat waktu selesai) |
| `POST/PATCH` | `/api/visit-sessions/{id}/cancel` | Cancel session |
| `PATCH` | `/api/call-plans/{id}/cancel` | Cancel jadwal |
| `PATCH` | `/api/call-plans/{id}/reschedule` | Penjadwalan ulang jadwal kunjungan |
| `POST` | `/api/managerial-campaigns` | Membuat Usulan Distrik (DM) atau Arahan Regional (RSM) |
| `GET` | `/api/managerial-campaigns` | Mengambil program/arahan sesuai hierarki wilayah |
| `GET` | `/api/managerial-campaigns/{id}` | Detail program dan kalkulasi progres kunjungan dokter |
| `PATCH` | `/api/managerial-campaigns/{id}/approve` | Persetujuan usulan distrik oleh RSM |
| `PATCH` | `/api/managerial-campaigns/{id}/reject` | Penolakan usulan distrik dengan alasan revisi |
| `GET` | `/api/managerial-campaigns/summary` | Ringkasan capaian target wilayah |
| `GET` | `/api/managerial-campaigns/recommendations` | Rekomendasi dokter target dan produk fokus untuk MR |
| `GET` | `/api/doctors` | Master dokter |
| `GET` | `/api/products` | Master produk |
| `GET` | `/api/doctors/{id}/availability` | Availability dokter |
| `GET` | `/api/csrf-token` | Helper CSRF untuk API client |

## Business Rules yang Dijaga

- Approval Call List satu tingkat langsung: MR ke DM, DM ke RSM, RSM ke MM.
- Approver harus sesuai `assigned_approver_id` dan relasi `manager_id`.
- Resubmit menjaga approver awal pada siklus pengajuan.
- Call Plan hanya dari Call List approved milik MR.
- Dokter pada Call Plan harus anggota Call List.
- Tanggal Plan harus dalam bulan Call List.
- Satu dokter hanya boleh punya satu Plan aktif per tanggal secara global.
- Satu dokter hanya boleh punya satu Actual final per tanggal secara global.
- `plan_id = null` tetap valid untuk Direct Call Actual.
- Direct API tidak mewajibkan `visit_reason` untuk without plan.
- Foto, tanda tangan, dan produk wajib untuk Actual final.
- Completion Actual bersifat atomik: actual, products, dan status plan/session konsisten.
- Conflict lintas MR tidak membocorkan foto, tanda tangan, produk, atau identitas sensitif MR lain.
- Program manajerial terikat hierarki wilayah dan tidak dapat diakses lintas distrik tanpa izin.

## Hasil Test

Command validasi terakhir yang dicatat di README ini dijalankan pada lingkungan lokal demo pada 14 Oktober 2026.

Automated test:

```bash
php artisan test
```

Hasil terakhir:

```text
Tests: 105 passed (375 assertions)
```

Build frontend:

```bash
npm run build
```

Hasil terakhir: PASS.

Blade cache:

```bash
php artisan view:cache
```

Hasil terakhir: PASS.

## Mapping Skenario Wajib PDF

| ID | Skenario | Status Otomatis | Test Evidence |
|---|---|---|---|
| PDF-01 | MR membuat List, DM approve, MR membuat Plan, MR mencatat Actual | PASS | `CallActualTest::test_mr_can_record_planned_actual_and_complete_only_referenced_plan` |
| PDF-02 | Plan dari List belum approved ditolak | PASS | `CallPlanTest::test_pdf_02_create_call_plan_from_non_approved_list_is_rejected` |
| PDF-03 | Actual kedua untuk dokter/tanggal sama ditolak | PASS | `CallActualTest::test_pdf_03_duplicate_actual_for_same_doctor_and_date_is_rejected_across_mrs` |
| PDF-04 | Actual tanpa foto ditolak dan tidak membuat partial write | PASS | `CallActualTest::test_pdf_04_actual_without_photo_is_rejected_and_creates_no_partial_record` |
| PDF-05 | RSM/MM atau manager bukan approver tidak bisa approve Call List MR | PASS | `CallListTest::test_pdf_05_unassigned_manager_or_higher_level_cannot_approve` |

Catatan: tabel di atas adalah bukti automated feature test. Untuk submission akhir, tetap disarankan merekam manual browser flow PDF-01 sampai PDF-05.

## Browser Smoke Verification

Pengujian fungsionalitas UI pada browser lokal telah diverifikasi pada area berikut. Screenshot tersimpan di `docs/evidence/ui-smoke/`.

| Area | Evidence | Status Verifikasi UI |
|---|---|---|
| Target Dokter | `01-target-dokter.png` | Alur pembuatan draft, submit approval, dan tabel riwayat |
| Jadwal Kunjungan | `02-jadwal-kunjungan.png` | Alur pembuatan jadwal, filter bulan, dan pembatalan/reschedule |
| Pelaksanaan Kunjungan | `03-pelaksanaan-kunjungan.png` | Alur kunjungan terencana, mulai kunjungan, dan form realisasi |
| Modal Kunjungan di Luar Rencana | `04-kunjungan-luar-rencana-modal.png` | Alur kunjungan tanpa plan (unplanned / non-target) |

Verifikasi ini adalah smoke-test UI lokal, bukan pengganti rekaman manual end-to-end PDF.

## Asumsi dan Batasan

- Data demo sepenuhnya sintetis.
- Foto dan tanda tangan memakai URL/mock string; tidak ada upload file produksi.
- Manual Actual memakai waktu yang dikirim pengguna, sehingga tidak membuktikan kehadiran fisik.
- GPS, offline mode, insentif, notifikasi, dan deployment produksi tidak termasuk scope.
- P1 visit session tersedia, tetapi P0 Direct Actual tetap dapat berjalan tanpa session.
- MySQL/MariaDB lokal sudah dipakai untuk pengujian; concurrency produksi tetap perlu diuji lebih lanjut bila sistem dipakai nyata.

## Refleksi Tooling

- Pengembangan dibantu dengan editor, test runner, build tool, dan review bertahap.
- Setiap perubahan yang diterima diverifikasi lewat test, build, atau pengecekan source sebelum dicatat sebagai selesai.
- Bagian yang terlalu generik dikoreksi: copy UI disederhanakan, inline handler dihapus, CSS/JS dipisah dari Blade, dan native `confirm()` diganti modal internal.
- Klaim PASS hanya ditulis setelah command benar-benar dijalankan.

## Panduan Demo 30 Menit

1. Jelaskan role dan business flow: MR, DM, RSM, MM.
2. Demo PDF-01: MR buat target, DM approve, MR buat jadwal, MR catat actual.
3. Demo PDF-02: Plan dari draft ditolak.
4. Demo PDF-03: duplicate actual dokter/tanggal ditolak.
5. Demo PDF-04: actual tanpa foto ditolak.
6. Demo PDF-05: RSM/MM atau manager lain tidak bisa approve Call List MR.
7. Tunjukkan service layer dan test suite.
8. Tutup dengan batasan scope dan asumsi desain.

## Catatan Kerahasiaan

Jangan commit PDF asli studi kasus, `.env`, credential pribadi, data nyata, atau materi internal perusahaan. Repository ini memakai data sintetis untuk kebutuhan demo dan penilaian.
