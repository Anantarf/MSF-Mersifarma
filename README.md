# Mobile Sales Force (MSF) — PT Mersifarma

> **Studi Kasus:** Junior Software Engineer — Mobile Sales Force Application  
> **Baseline Desain:** v1.5 (Dokumentasi lengkap di folder [`docs/`](docs/))  
> **Status Proyek:** **Fase 0 & Fase 1 Selesai & Terverifikasi** (Setup Monolith, Database Schema 11 Tabel, Model Eloquent, Seeder Sintetis & Skenario Demo, Session Auth, dan Helper API CSRF).

---

## 1. Ringkasan Proyek & Arsitektur

Mobile Sales Force (MSF) adalah aplikasi web *mobile-first* yang dirancang untuk mendukung operasional lapangan **Medical Representative (MR)** dan alur persetujuan oleh **District Manager (DM)**, **Regional Sales Manager (RSM)**, hingga **Medical/Marketing Manager (MM)**.

Aplikasi ini mencakup 3 modul inti:
1. **Call List:** Perencanaan target dokter bulanan dari Master Customer List (MCL) dengan alur persetujuan *single-level direct manager*.
2. **Call Plan:** Penjadwalan tanggal kunjungan harian berdasarkan Call List yang telah disetujui (*Approved*).
3. **Call Actual:** Realisasi kunjungan dengan bukti foto, tanda tangan digital, dan detail produk promosi, dilengkapi proteksi anti-duplikasi kunjungan global (*1 Actual per dokter per hari*).

### Teknologi yang Digunakan
* **Backend:** PHP 8.2.12+, Laravel Framework 11.57.0
* **Database:** MySQL 8.0.16+ / MariaDB 10.4.32+ (InnoDB, Foreign Keys, Stored Generated Columns)
* **Frontend:** Laravel Blade, CSS Modern (Mobile-First, Plus Jakarta Sans), Vanilla JavaScript
* **Autentikasi & Keamanan:** Session Auth Same-Origin, CSRF Protection, Password Bcrypt Hashing

---

## 2. Cara Menjalankan Secara Lokal

### Prasyarat
* PHP >= 8.2 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`
* Composer 2.x
* MySQL 8.0+ atau MariaDB 10.4+ (misal via XAMPP)

### Langkah Instalasi
1. **Clone Repository:**
   ```bash
   git clone https://github.com/Anantarf/MSF-Mersifarma.git
   cd MSF-Mersifarma
   ```

2. **Install Dependensi PHP:**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment:**
   Salin file template `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   Pastikan pengaturan koneksi database di `.env` sesuai dengan server database lokal Anda:
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
   > ⚠️ **Perhatian:** Perintah `migrate:fresh` akan menghapus tabel lama di database `mersi_msf`. Gunakan hanya pada database pengembangan.
   ```bash
   php artisan migrate:fresh --seed
   ```
   *(Opsional)* Untuk mengisikan contoh transaksi Call List yang sudah disetujui (*Approved*) dan draft untuk demo cepat:
   ```bash
   php artisan db:seed --class=DemoScenarioSeeder
   ```

6. **Jalankan Web Server Lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser melalui: `http://localhost:8000`

---

## 3. Akun Demo Pengujian (Data Sintetis)

Seluruh akun demo telah dibuat secara sintetis di dalam database dengan kata sandi default: **`password`**. Form login web juga menyediakan tombol **1-Klik Isi Akun Demo** untuk kemudahan pengujian.

| Role | Nama Pengguna | Email | Atasan Langsung (`manager_id`) | Keterangan |
|---|---|---|---|---|
| **MM** | Budi Santoso | `mm@mersi.com` | *- (Puncak Hierarki)* | Medical / Marketing Manager |
| **RSM** | Siti Rahma | `rsm@mersi.com` | Budi Santoso (`mm`) | Regional Sales Manager |
| **DM** | Joko Widodo | `dm@mersi.com` | Siti Rahma (`rsm`) | District Manager (Atasan MR Rudi) |
| **MR** | Rudi Hartono | `mr@mersi.com` | Joko Widodo (`dm`) | Medical Representative Utama |
| **MR 2** | Ahmad Dahlan | `mr2@mersi.com` | Joko Widodo (`dm`) | MR pembanding (Uji negatif kepemilikan) |
| **DM 2** | Hendra Gunawan | `dm2@mersi.com` | Siti Rahma (`rsm`) | DM pembanding (Uji negatif bukan direct manager) |

---

## 4. Status Implementasi Endpoint API (P0)

Sesuai kontrak [docs/API_CONTRACT.md](docs/API_CONTRACT.md), sistem menyediakan 8 endpoint wajib (P0) dan endpoint pembantu:

| Method | Endpoint | Fungsi | Status |
|---|---|---|:---:|
| `GET` | `/api/csrf-token` | Helper pengambilan token CSRF untuk Postman/cURL | ✅ **Siap** |
| `POST` | `/api/auth/login` | Login session via JSON API | ✅ **Siap** |
| `POST` | `/api/auth/logout` | Logout session via JSON API | ✅ **Siap** |
| `GET` | `/api/me` | Informasi profil pengguna yang sedang login | ✅ **Siap** |
| `GET` | `/api/doctors` | Mengambil seluruh daftar dokter Master Customer List (MCL) | ✅ **Siap** |
| `POST` | `/api/call-lists` | Membuat draft Call List bulanan | ✅ **Siap** |
| `GET` | `/api/call-lists` | Mengambil daftar Call List milik sendiri / antrean review | ✅ **Siap** |
| `GET` | `/api/call-lists/{id}` | Detail Call List dan log histori persetujuan | ✅ **Siap** |
| `PUT` | `/api/call-lists/{id}` | Memperbarui dokter sasaran draft Call List | ✅ **Siap** |
| `POST` | `/api/call-lists/{id}/submit` | Mengajukan Call List ke direct manager untuk approval | ✅ **Siap** |
| `PATCH` | `/api/call-lists/{id}/approve` | Persetujuan (Approve/Reject) oleh direct manager | ✅ **Siap** |
| `POST` | `/api/call-plans` | Menjadwalkan Call Plan dari Call List Approved | ⏳ *Fase 3* |
| `GET` | `/api/call-plans` | Mengambil daftar jadwal Call Plan milik MR | ⏳ *Fase 3* |
| `POST` | `/api/call-actuals` | Mencatat realisasi kunjungan (Planned / Without Plan) | ⏳ *Fase 4* |
| `GET` | `/api/call-actuals` | Mengambil daftar riwayat kunjungan milik MR | ⏳ *Fase 4* |

### Panduan Pengujian API via Postman / cURL
Karena aplikasi menerapkan proteksi CSRF same-origin:
1. Panggil `GET http://localhost:8000/api/csrf-token` untuk mendapatkan token dan cookie session.
2. Sertakan header `X-CSRF-TOKEN: <token>` dan `Accept: application/json` pada request `POST`/`PATCH`.

---

## 5. Indeks Dokumentasi Teknis

Seluruh dokumen spesifikasi arsitektur disimpan dalam folder [`docs/`](docs/):
* [`docs/DOCS_INDEX.md`](docs/DOCS_INDEX.md) — Indeks dan hierarki kebenaran dokumen
* [`docs/DESIGN.md`](docs/DESIGN.md) — Dokumen induk rancangan sistem & alur operasional
* [`docs/PRD.md`](docs/PRD.md) — Persyaratan bisnis & spesifikasi UX
* [`docs/API_CONTRACT.md`](docs/API_CONTRACT.md) — Spesifikasi request/response 8 endpoint API
* [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) — Skema 11 tabel, foreign keys, dan constraint InnoDB
* [`docs/DECISIONS.md`](docs/DECISIONS.md) — Architecture Decision Records (ADR-001 s.d. ADR-027)
* [`docs/TDD.md`](docs/TDD.md) — Spesifikasi workflow, state transitions, dan locking strategy
* [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md) — Rencana pengujian manual PDF dan skenario negatif
* [`docs/IMPLEMENTATION_PLAN.md`](docs/IMPLEMENTATION_PLAN.md) — Roadmap tahapan pengembangan

---

## 6. Verifikasi & Pengujian Otomatis

Aplikasi dilengkapi unit dan feature test otomatis untuk memvalidasi integritas autentikasi, model hierarki, dan skema database. Jalankan dengan perintah:

```bash
php artisan test
```

**Hasil Pengujian Terkini:**
```text
PASS  Tests\Unit\ExampleTest
✓ that true is true

PASS  Tests\Feature\AuthAndDatabaseTest
✓ login page is accessible
✓ login with valid credentials
✓ api login returns json envelope
✓ api me returns authenticated user
✓ api me returns 401 when unauthenticated
✓ login with invalid credentials
✓ logout clears session
✓ database hierarchy and seeds integrity
✓ api csrf token endpoint returns token
✓ demo scenario seeder creates approved call list with audit logs

PASS  Tests\Feature\ExampleTest
✓ the application returns a successful response

Tests:    12 passed (42 assertions)
Duration: 1.26s
```
