# MSF — Documentation Index / IDE Handoff

> **Package:** MSF Design Baseline **v1.5 + Terminologi UI v1.6 + Operational UX v1.7** (10 Oktober 2026).  
> **Project:** Mobile Sales Force — PT Mersifarma.  
> **Status:** **DESIGN READY; CODE NOT STARTED; TESTS NOT RUN.**  
> Semua dokumen `.md` ini adalah spesifikasi untuk IDE, bukan kode aplikasi.

## 1. File di paket

| File | Fungsi / kapan dibaca |
|---|---|
| [`AGENTS.md`](AGENTS.md) | Aturan ringkas untuk AI IDE agar tidak keluar scope |
| [`DESIGN.md`](DESIGN.md) | **Dokumen induk** arsitektur, ERD, business logic, end-to-end operational flow |
| [`PRD.md`](PRD.md) | Persyaratan bisnis, aktor, UX, acceptance, P0/P1 |
| [`TDD.md`](TDD.md) | Services, transaction flows, approval/Plan/Actual/Session states |
| [`API_CONTRACT.md`](API_CONTRACT.md) | **8 API wajib**, request/response, supporting APIs, errors |
| [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md) | 11 tabel, composite FKs, unique/check, lock strategy, migrations |
| [`TEST_PLAN.md`](TEST_PLAN.md) | 5 mandatory PDF tests + skenario negatif dan acceptance gates |
| [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) | Prioritas implementasi Laravel dari setup hingga evidence |
| [`DECISIONS.md`](DECISIONS.md) | Asumsi, decisions, trade-offs dan keterbatasan |
| [`README_REQUIREMENTS.md`](README_REQUIREMENTS.md) | Persyaratan README repository sesudah aplikasi selesai |

## 2. Instruksi singkat untuk IDE

> Baca seluruh dokumen, prioritaskan aturan PDF dan P0. Implementasikan Laravel + Blade + MySQL dengan delapan endpoint wajib dan tiga halaman. Mulai dari migration/seed, Call List approval, Call Plan, lalu **Direct Call Actual** Planned dan `plan_id=null` tanpa Visit Session. Setelah P0 berfungsi, kerjakan UI mobile-first dan tes wajib. Visit Session, recovery, cancel Plan adalah P1 opsional. Jangan membuat PASS/evidence palsu. Jika ada konflik spesifikasi, jelaskan sebelum mengubah requirement.

### Hierarki referensi

- **Ketentuan wajib PDF asli** mendahului setiap asumsi engineering.
- **`API_CONTRACT.md`** utama untuk kontrak HTTP.
- **`DATABASE_SCHEMA.md`** utama untuk struktur dan constraint DB.
- **`PRD.md`** utama untuk aturan bisnis dan UX.
- **`TDD.md`** utama untuk service, locking, workflow.
- **`DESIGN.md`** adalah overview terintegrasi/rujukan desain.
- **`DECISIONS.md`** mencatat asumsi dan perubahan yang diizinkan.

Jika terdapat ketidakcocokan, **jangan diam-diam memilih**. Gunakan ketentuan PDF dan catat trade-off, kemudian minta keputusan pengguna bila mengubah kewajiban atau business rule.

## 3. Non-negotiable quick reference

1. Single-level hierarchy MR→DM, DM→RSM, RSM→MM; verify **direct manager** identity.
2. Call Plan hanya dari Approved List, dokter anggota List, dan tanggal dalam periode.
3. Global doctor/date uniqueness untuk final Actual.
4. Actual photo URL, signature URL, product list minimal 1.
5. `plan_id` nullable: Without Plan tetap bisa mencatat dokter MCL.
6. UI Planned-first dengan satu opsi Di Luar Rencana. Heading tiga halaman menampilkan **Target Dokter** (Call List Bulanan), **Jadwal Kunjungan** (Call Plan), dan **Pelaksanaan Kunjungan** (Call Actual); subtitle di heading, tidak wajib dalam mobile menu.
7. **P0**: Isi Laporan Kunjungan + Waktu Mulai/Selesai manual + Simpan Laporan. **P1**: Mulai/Akhiri Kunjungan dengan server timestamps; tidak boleh memalsukan Check-in server pada P0.
8. Label Call List **Perlu Revisi** hanya untuk Draft setelah reject; pending/approved mengikuti status terkini, bukan histori reject lama.
9. Session-based flows P1 **tidak boleh menjadi syarat** untuk 8 endpoint mandatory P0.
10. Jangan mengklaim fitur dibangun atau dites sebelum benar-benar dilakukan.

## 4. Letak dokumen dalam repo

**Disarankan:** Letakkan dokumen di root repo (`DESIGN.md`, `PRD.md`, dan seterusnya) bersama source Laravel agar coding assistant membaca semuanya. `README.md` aplikasi dibuat ketika project telah berjalan; lihat `README_REQUIREMENTS.md`.

Dokumen dapat disimpan dalam `/docs` jika IDE/agent diperintahkan membaca `/docs/DOCS_INDEX.md`; tetapi tempatkan `AGENTS.md` di root karena sejumlah coding assistant akan membaca file instruksi itu secara otomatis.

## 5. Checklist penyerahan dan kejujuran status

- [x] Business/technical baseline v1.5 + terminology v1.6 + operational UX v1.7 tersusun.
- [x] HTTP contract, DB, dan test plan tersusun.
- [x] Laravel project dibuat.
- [x] MySQL migration dijalankan.
- [x] 8 API wajib diimplementasikan.
- [x] 3 halaman berfungsi secara responsif.
- [x] 5 manual test PDF + 60 automated tests dijalankan, 100% PASS dan evidence dicatat.
- [x] README aplikasi final + panduan demo disiapkan.
- [x] Proposal Konsolidasi v1.8 (Peran Manajerial, Wilayah, Tata Kelola Kunjungan) diadopsi ke ADR-031 hingga ADR-034.

**Catatan:** Baseline P0 telah terverifikasi penuh dan stabil. Proposal v1.8 memberikan penyelarasan peran manajerial dan alur lanjutan (P1) tanpa merusak 8 endpoint wajib PDF dan invariant sistem yang telah berjalan.
