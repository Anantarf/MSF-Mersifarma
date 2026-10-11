# Mobile Sales Force (MSF) — PT Mersifarma

> **Studi Kasus:** Junior Software Engineer — Mobile Sales Force Application  
> **Baseline Desain:** v1.5 (Dokumentasi lengkap di folder [`docs/`](docs/))  
> **Status Delivery:** **P0 + P1 Lengkap & Terverifikasi 100%** (8 Endpoint API Wajib + 6 Endpoint P1 Operasional, 3 Halaman Web Mobile-First, Proteksi Anti-Duplikasi MySQL InnoDB, Auto-Expire Sesi Basi, Widget Monitoring Manajerial, dan 58 Skenario Uji Otomatis Lolos).

---

## 1. Ringkasan Proyek & Arsitektur

Mobile Sales Force (MSF) adalah aplikasi web terintegrasi yang dirancang untuk mendukung kegiatan operasional lapangan **Medical Representative (MR)** serta alur persetujuan bertingkat tunggal (*single-level direct manager*) oleh **District Manager (DM)**, **Regional Sales Manager (RSM)**, dan **Medical/Marketing Manager (MM)**.

Sistem mengimplementasikan tiga modul utama:
1. **Target Dokter (Call List):** Pengusulan target dokter bulanan dari Master Customer List (MCL). Alur persetujuan ditujukan kepada atasan langsung. Penolakan mengembalikan status ke Draft dengan catatan revisi tersimpan di jejak audit. Dilengkapi **Widget Ringkasan Monitoring Wilayah (Read-Only)** untuk atasan (DM/RSM/MM).
2. **Jadwal Kunjungan (Call Plan):** Penjadwalan tanggal dan jam kunjungan harian. Wajib bersumber dari Call List yang telah disetujui (*Approved*), dokter anggota list, dan tanggal dalam bulan periode list. Dilengkapi proteksi bentrok jadwal dokter/hari secara global, fitur pembatalan jadwal ber-alasan, dan live availability checker.
3. **Realisasi Kunjungan (Call Actual):** Pelaporan bukti kunjungan lapangan dengan bukti foto, tanda tangan digital dokter, dan detailing produk farmasi. Mendukung kunjungan terencana (*Planned*) dan kunjungan di luar rencana (*Without Plan* yang otomatis diklasifikasikan backend menjadi *Unplanned* atau *Non Target*). Mendukung alur Persisted Visit Session (Server Check-in/Check-out) dengan fitur auto-expire sesi basi yang ramah lapangan.

### Spesifikasi Teknologi Aktual
* **Backend:** PHP 8.2.12, Laravel Framework 11.57.0 (Arsitektur Service Layer, Form Requests, Strict Exceptions)
* **Database:** MySQL 8.0.16+ / MariaDB 10.4.32+ (InnoDB, Composite Foreign Keys, Stored Generated Columns `active_doctor_id`, Row-Level Locking `FOR UPDATE`)
* **Frontend:** Laravel Blade, Vanilla CSS (Identitas Medis Farmasi `#0f766e`, Standar Sentuhan 44px, Aksesibilitas Keyboard `Escape`, Zero-Slop Alert Banners), Vanilla JavaScript Fetch API
* **Autentikasi & Keamanan:** Session Auth Same-Origin, Proteksi CSRF, Scoped Privacy (MR tidak dapat mengintip bukti atau data MR lain)

Arsitektur detail dapat dipelajari pada [docs/DESIGN.md](docs/DESIGN.md) dan [docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md).

---

## 2. Cara Menjalankan Secara Lokal

### Prasyarat Sistem
* PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`)
* Composer 2.x
* MySQL 8.0+ atau MariaDB 10.4+ (misal via XAMPP) pada port default 3306
* Git

### Langkah Instalasi
1. **Clone Repository:**
   ```bash
   git clone https://github.com/Anantarf/MSF-Mersifarma.git
   cd MSF-Mersifarma
   ```

2. **Install Dependensi Composer:**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment:**
   Salin file konfigurasi contoh `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   Pastikan konfigurasi database di file `.env` mengarah ke database MySQL/MariaDB lokal Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mersi_msf
   DB_USERNAME=root
   DB_PASSWORD=
   DB_COLLATION=utf8mb4_unicode_ci
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Migrasi Database & Seeding Data Sintetis:**
   > ⚠️ **Catatan:** Perintah `migrate:fresh` akan menghapus tabel lama di database `mersi_msf`. Gunakan hanya pada database lokal pengujian.
   ```bash
   php artisan migrate:fresh --seed
   ```
   Seluruh data demo transaksi (Call List approved/pending/revision, Call Plans terjadwal/selesai/dibatalkan, dan riwayat Call Actual planned/unplanned/non-target) sudah terisi otomatis melalui `DemoScenarioSeeder`.

6. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Buka peramban (browser) di alamat: **`http://localhost:8000`**

---

## 3. Akun Demo Pengujian (Data Sintetis)

Seluruh akun pengujian menggunakan data sintetis dengan kata sandi default: **`password`**. Halaman login menyediakan tombol **1-Klik Isi Cepat** untuk kemudahan penguji berganti peran.

| Peran | Nama Pengguna | Alamat Email | Atasan Langsung (`manager_id`) | Keterangan Uji & Cakupan Wilayah |
|---|---|---|---|---|
| **MM** | Budi Santoso | `mm@mersi.com` | *- (Puncak Hierarki)* | Marketing Manager Nasional (Review level tertinggi) |
| **RSM** | Siti Rahma | `rsm@mersi.com` | Budi Santoso (`mm`) | Regional Sales Manager (DKI Jakarta) |
| **DM** | Wisnu Santhika | `dm@mersi.com` | Siti Rahma (`rsm`) | District Manager (Jakarta Selatan - Atasan langsung MR Rudi) |
| **MR** | Rudi Hartono | `mr@mersi.com` | Wisnu Santhika (`dm`) | Medical Representative Utama (Jakarta Selatan 1) |
| **MR 2** | Ahmad Dahlan | `mr2@mersi.com` | Wisnu Santhika (`dm`) | MR Pembanding (Jakarta Selatan 2 - Uji negatif kepemilikan/privasi) |
| **DM 2** | Hendra Gunawan | `dm2@mersi.com` | Siti Rahma (`rsm`) | DM Pembanding (Jakarta Timur - Uji negatif bukan atasan langsung) |

---

## 4. Status Implementasi Endpoint API (P0 & P1)

### A. 8 Endpoint API Wajib (P0)
Sesuai dokumen kontrak [docs/API_CONTRACT.md](docs/API_CONTRACT.md), seluruh 8 endpoint wajib telah selesai diimplementasikan dan diverifikasi:

| No | Method | Endpoint | Fungsi Domain | Status Implementasi |
|:--:|---|---|---|:---:|
| 1 | `POST` | `/api/call-lists` | Membuat draft Call List bulanan | ✅ **SELESAI (PASS)** |
| 2 | `GET` | `/api/call-lists` | Mengambil daftar Call List pemilik atau antrean approval manager | ✅ **SELESAI (PASS)** |
| 3 | `GET` | `/api/call-lists/{id}` | Mengambil detail Call List beserta daftar dokter dan log audit | ✅ **SELESAI (PASS)** |
| 4 | `PATCH` | `/api/call-lists/{id}/approve` | Keputusan persetujuan (Approve/Reject) oleh atasan langsung | ✅ **SELESAI (PASS)** |
| 5 | `POST` | `/api/call-plans` | Menjadwalkan rencana kunjungan dari Call List yang disetujui | ✅ **SELESAI (PASS)** |
| 6 | `GET` | `/api/call-plans` | Mengambil jadwal rencana kunjungan milik MR (filter tanggal/status) | ✅ **SELESAI (PASS)** |
| 7 | `POST` | `/api/call-actuals` | Mencatat realisasi kunjungan (Planned maupun Without Plan) | ✅ **SELESAI (PASS)** |
| 8 | `GET` | `/api/call-actuals` | Mengambil riwayat realisasi kunjungan milik MR login | ✅ **SELESAI (PASS)** |

### B. Endpoint P1 & Operasional Pendukung
* `POST /api/visit-sessions` atau `/api/visit-sessions/start` &mdash; Memulai sesi kunjungan (server check-in timestamp).
* `GET /api/visit-sessions/active` &mdash; Mengambil sesi kunjungan MR yang sedang berlangsung (dengan auto-expire sesi basi).
* `POST /api/visit-sessions/{id}/checkout` &mdash; Mencatat waktu selesai kunjungan (server check-out timestamp).
* `POST /api/visit-sessions/{id}/cancel` &mdash; Membatalkan sesi kunjungan aktif dengan alasan pembatalan.
* `PATCH /api/call-plans/{id}/cancel` &mdash; Membatalkan jadwal kunjungan dengan alasan dan membebaskan slot dokter.
* `GET /api/doctors/{id}/availability` &mdash; Mengecek kalender ketersediaan dokter tanpa membocorkan privasi rekan kerja.
* `GET /api/call-lists/summary` &mdash; Widget ringkasan monitoring manajerial (read-only) untuk atasan (DM/RSM/MM).
* `GET /api/csrf-token` &mdash; Helper pengambilan token CSRF untuk Postman / cURL.
* `GET /api/doctors` &mdash; Master Customer List (MCL) picker dokter.
* `GET /api/products` &mdash; Master produk farmasi untuk detailing (Mersibion 5000, Mersikol, Mersidryl, dll).
* `POST /api/call-lists/{id}/submit` &mdash; Mengajukan draft Call List ke atasan langsung.

---

## 5. Matriks Hasil Pengujian Manual PDF (5 Skenario Wajib)

Pengujian manual kasus uji wajib dari PDF studi kasus telah dieksekusi secara nyata dan diotomatisasi melalui rangkaian test suite PHPUnit:

| Kode Skenario | Langkah Pengujian & Prakondisi | Hasil yang Diharapkan | Hasil Observasi Nyata | Status | Bukti Uji Otomatis |
|---|---|---|---|:---:|---|
| **PDF-01** | Login MR Rudi (`mr@mersi.com`). Buat Call List November, Submit. Login DM Joko (`dm@mersi.com`), Approve Call List. Login MR Rudi, buat Call Plan dokter D1 tanggal 12 Nov. Catat Actual Planned dengan foto, TTD, dan produk. | List disetujui; Plan berubah dari `scheduled` ke `completed`; Actual tercatat `completed` dengan produk detailing. | Endpoint merespons `201 Created`. Status Call Plan otomatis terupdate menjadi `completed`. Record Actual dan pivot produk tersimpan atomik. | **PASS** | `CallActualTest::test_pdf_01_planned_call_actual_completion` |
| **PDF-02** | MR mencoba membuat Call Plan dari Call List yang masih berstatus Draft (belum disetujui). | Ditolak dengan status HTTP 422 dan kode error `CALL_LIST_NOT_APPROVED`; tidak ada Plan yang tersimpan. | Request ditolak HTTP 422 dengan pesan `"Call Plan hanya dapat dibuat dari Call List yang telah disetujui"`. Database bersih tanpa record Plan baru. | **PASS** | `CallPlanTest::test_pdf_02_create_call_plan_from_non_approved_list_is_rejected` |
| **PDF-03** | MR Rudi mencatat Actual untuk Dokter 1 pada tanggal X. Kemudian MR Rudi atau MR Ahmad mencoba mencatat Actual kedua untuk Dokter 1 pada tanggal yang sama. | Ditolak dengan HTTP 409 `VISIT_ALREADY_EXISTS`; jumlah Actual untuk dokter/tanggal tersebut tetap 1. Privasi data terjaga. | Request kedua ditolak HTTP 409. Database constraint `uq_actual_doctor_day` dan lock transaksi menolak duplikasi. MR lain tidak melihat data pribadi pemilik record. | **PASS** | `CallActualTest::test_pdf_03_duplicate_actual_for_same_doctor_and_date_is_rejected` |
| **PDF-04** | Pencatatan Actual dikirim dengan kolom `photo_url` kosong atau hilang. | Ditolak HTTP 422 `PHOTO_REQUIRED`; tidak ada record Actual atau pivot produk parsial yang tersimpan. | Request ditolak HTTP 422 dengan pesan `"Foto bukti kunjungan wajib disertakan"`. Transaksi DB bersih tanpa data parsial. | **PASS** | `CallActualTest::test_pdf_04_actual_without_photo_is_rejected_without_partial_writes` |
| **PDF-05** | RSM Siti Rahma atau MM Budi Santoso mencoba melakukan Approve terhadap Call List MR Rudi yang ditugaskan ke DM Joko. | Ditolak HTTP 403 `APPROVAL_NOT_ALLOWED`; status Call List tidak berubah dan tetap menunggu DM Joko. | Permintaan ditolak HTTP 403 karena hierarki direct manager tidak cocok. Call List tetap `pending_approval`. | **PASS** | `CallListTest::test_pdf_05_unassigned_manager_or_higher_level_cannot_approve` |

### Rekapitulasi Rangkaian Tes Otomatis (PHPUnit)
Jalankan pengujian seluruh sistem:
```bash
php artisan test
```
**Hasil Eksekusi:**
```text
   PASS  Tests\Unit\ExampleTest (1 test)
   PASS  Tests\Feature\AuthAndDatabaseTest (13 tests)
   PASS  Tests\Feature\CallListTest (9 tests)
   PASS  Tests\Feature\CallPlanTest (14 tests)
   PASS  Tests\Feature\CallActualTest (10 tests)
   PASS  Tests\Feature\VisitSessionTest (10 tests)
   PASS  Tests\Feature\ExampleTest (1 test)

  Tests:    58 passed (205 assertions)
  Duration: 4.72s
```

---

## 6. Asumsi Bisnis & Batasan Desain

Berdasarkan dokumen arsitektur [docs/DECISIONS.md](docs/DECISIONS.md):
1. **Single-Level Direct Approval vs. Managerial Monitoring:** Persetujuan Call List bersifat satu tingkat (*single-level final approval*: `MR -> DM`, `DM -> RSM`, `RSM -> MM`). Begitu DM menyetujui Call List MR, keputusan tersebut sudah final dan MR langsung berhak membuat Call Plan tanpa perlu approval berantai ke RSM/MM. Untuk atasan tingkat lebih tinggi, disediakan **Widget Ringkasan Monitoring Wilayah (Read-Only)** untuk memantau capaian tim tanpa membebani proses operasional (ADR-004).
2. **Rejection to Draft:** Penolakan mengembalikan status Call List ke `draft` agar bawahan dapat merevisi dan mengajukan kembali, dengan seluruh catatan alasan tersimpan dalam riwayat audit (ADR-005).
3. **Pencatatan Tanpa Rencana (Without Plan):** Direct API mendukung `plan_id = null` tanpa mewajibkan alasan kunjungan. Backend secara otomatis menentukan tipe kunjungan: `unplanned` jika dokter terdaftar pada Call List yang sudah disetujui sebelum check-in, dan `non_target` jika dokter tidak terdaftar pada Call List sasaran (ADR-011).
4. **Anti-Duplikasi Kunjungan:** Enforced pada level database via unique composite index `(doctor_id, visit_date)` pada tabel `call_actuals` dan row-level locking `FOR UPDATE` pada tabel `master_customers` untuk mencegah *race condition* (ADR-010).
5. **KISS Auto-Expire Sesi Basi:** Sesi kunjungan aktif yang tertinggal dari hari kemarin otomatis kedaluwarsa dan ditutup saat MR berinteraksi dengan sistem di hari baru, mencegah MR terkunci tanpa membutuhkan daemon background cron eksternal.
6. **Privasi Antar-MR:** Pada respons penolakan duplikasi (409) dan availability check, sistem tidak membocorkan nama MR lain, foto, produk, maupun tanda tangan digital rekan kerja (ADR-014).

---

## 7. Refleksi Penggunaan AI & Tooling (Anti-Slop Audit)

Dalam proses rekayasa perangkat lunak ini, asisten coding AI digunakan secara terukur dan disiplin di bawah panduan filter anti-slop ([anti-slop/audit-001-2026-10-10.md](anti-slop/audit-001-2026-10-10.md)):

| Kategori | Keputusan Diterima & Diterapkan | Usulan AI yang Ditolak / Dikoreksi | Alasan Disiplin Rekayasa |
|---|---|---|---|
| **Palet Warna UI** | Menggunakan palet Medis Farmasi Mersifarma Teal (`#0f766e`, `#115e59`). | Ditolak: Warna default generic AI blue (`#0284c7`). | Memperkuat identitas farmasi medis profesional PT Mersifarma. |
| **Penanganan Error UI** | Menggunakan banner pesan in-page contextual (`#statusAlert`, modal error element). | Ditolak: Dialog popup bawaan browser `alert()` dan `confirm()`. | Dialog native memblokir thread JavaScript dan merusak UX mobile. |
| **Aksesibilitas** | Standar target sentuh minimum 44px dan penutup modal via tombol `Escape`. | Ditolak: Tombol kecil tanpa padding dan modal tanpa keyboard handler. | Memenuhi standar WCAG 2.1 untuk kenyamanan MR di perangkat seluler lapangan. |
| **Karakter Tipografi** | Menggunakan tanda baca baku titik dua `:` atau tanda minus `-`. | Ditolak: Karakter em-dash AI generic (`—`) di judul antarmuka. | Menghilangkan artifak khas LLM agar UI natural dan berstandar enterprise. |
| **Integritas Pengujian** | Setiap klaim kelulusan skenario wajib dieksekusi langsung pada database MySQL lokal. | Ditolak: Klaim kelulusan berbasis asumsi kode tanpa verifikasi terminal. | Mematuhi prinsip kejujuran Junior Software Engineer: tidak pernah memalsukan log bukti uji. |

---

## 8. Panduan Presentasi 30 Menit

Untuk demonstrasi teknis di hadapan tim penilai:
1. **Menit 0–5:** Paparan ringkas arsitektur monolith Laravel + MySQL InnoDB, hierarki peran MR-DM-RSM-MM, dan prinsip P0 sebelum P1.
2. **Menit 5–12 (Live Demo Alur Happy Path PDF-01):**
   * Login sebagai Rudi Hartono (MR) &rarr; Buat Call List baru &rarr; Ajukan ke atasan.
   * Login sebagai Wisnu Santhika (DM) &rarr; Buka tab Persetujuan Masuk &rarr; Setujui Call List.
   * Kembali ke MR &rarr; Buat Call Plan &rarr; Eksekusi Call Actual (foto, TTD, detailing produk).
3. **Menit 12–20 (Live Demo Proteksi Bisnis & Skenario Negatif):**
   * Demo **PDF-02:** Menolak pembuatan Plan dari Call List Draft.
   * Demo **PDF-03:** Menolak pencatatan kunjungan kedua untuk dokter & tanggal yang sama.
   * Demo **PDF-04:** Menolak pelaporan kunjungan tanpa foto bukti.
   * Demo **PDF-05:** Membuktikan RSM/MM tidak bisa menyetujui pengajuan bawahan langsung DM.
4. **Menit 20–25:** Tinjauan kode bersih (Service layer, lock concurrency, stored generated column, composite foreign keys).
5. **Menit 25–30:** Tanya jawab dan kesimpulan.
