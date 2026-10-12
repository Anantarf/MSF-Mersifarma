# Architecture Decision Records â€” MSF

> **Status:** Catatan keputusan arsitektur dan trade-off yang dipakai proyek. Automated test terakhir: 65 passed, 239 assertions.
> **Perubahan setelah baseline:** tambahkan ADR baru dengan tanggal, alasan, trade-off, dampak API/schema/testing. Jangan diam-diam mengubah aturan PDF.

| ADR | Decision | Rationale | Type |
|---|---|---|---|
| ADR-001 | Laravel monolith, Blade, JS ringan, MySQL 8.0.16+ | Cocok untuk scope dan kecepatan implementasi | Design |
| ADR-002 | P0 meliputi 8 endpoint wajib dan 3 halaman | Deliverable PDF prioritas tertinggi | PDF |
| ADR-003 | Auth via Laravel session + CSRF same-origin | Integrasi Blade dan JSON API sederhana | Design |
| ADR-004 | Approval List satu level direct-manager dengan `manager_id` dan assigned approver | Mencegah approval role-only/lompatan hierarchy | PDF + Design |
| ADR-005 | DM/RSM boleh membuat List pribadi | Demonstrasi chain MRâ†’DMâ†’RSMâ†’MM | Assumption |
| ADR-006 | List current status `draft`,`pending_approval`,`approved`; submitted/rejected di append-only logs | Histori PDF terjaga tanpa transient persistent states | Assumption |
| ADR-007 | Rejected kembali ke Draft; UI menunjukkan *Perlu Revisi* dan reason | Memenuhi revisi + meningkatkan kejelasan | PDF + UX |
| ADR-008 | Satu List per owner/month | Hindari dua target bulanan aktif | Assumption |
| ADR-009 | Satu **non-Cancelled** Plan per doctor/date global | Interpretasi konservatif duplicate Plan | Interpretation |
| ADR-010 | Satu final Actual per doctor/date global, semua mode | Duplicate protection harus lintas MR/jenis | PDF |
| ADR-011 | `plan_id=null` tetap legal tanpa session; MCL membership wajib | Kontrak API dan future bonus | PDF |
| ADR-012 | Unplanned/Non Target server snapshot as-of Check-in vs Approved List | Klasifikasi historis deterministik | Assumption |
| ADR-013 | UI Planned-first; hanya satu entry Kunjungan di Luar Rencana | UX MR sederhana | UX |
| ADR-014 | Frontend exception reason required, Direct API reason optional | Mengurangi kesalahan lapangan tanpa melanggar PDF | UX/Compatibility |
| ADR-015 | Approved List immutable; final Actual immutable | Menjaga integritas histori | Assumption |
| ADR-016 | Planned Actual boleh tanggal bergeser dalam List month dengan reason | Menangani jadwal berubah tanpa membuat Plan baru | Assumption |
| ADR-017 | Plan terkait Completed hanya jika Actual punya `plan_id` itu | Mencegah Planned/Without Plan status tak konsisten | Design |
| ADR-018 | Plan terhalang Actual unrelated tetap Scheduled + computed Conflict | Hindari status otomatis palsu | Design |
| ADR-019 | Visit Session persisten, resume, server time, revision/recovery adalah P1 | Tidak menghalangi P0 Direct API | Priority |
| ADR-020 | Cancel inaccurate Session lalu Direct Manual Actual | Alur recovery dengan sumber waktu jelas | P1 |
| ADR-021 | `visit_session_id=null` berarti manual time; session-backed berarti server time | Audit provenance tanpa kolom baru | Assumption |
| ADR-022 | Actual retry sesudah timeout: GET own Actual, 409 duplicate | Tidak mengklaim full idempotency | Limitation |
| ADR-023 | DB doctor row lock + unique global constraints | Menjaga cross-table concurrency | Design |
| ADR-024 | Tidak ada GPS, verified media, auto-escalation, offline dalam P0 | Fokus deliverable / jangan overengineering | Scope |
| ADR-025 | Timestamps future ditolak dan Direct Actual hari sama | Kesederhanaan integritas waktu | Assumption |
| ADR-026 | Label utama UI: **Target Dokter**, **Jadwal Kunjungan**, **Pelaksanaan Kunjungan**; subtitle PDF wajib pada **heading halaman**, bukan semua item menu; menu mobile boleh diringkas | Mempermudah MR dan menjaga keterlacakan PDF tanpa mengubah API/domain | UX |
| ADR-027 | **P0** memakai **Isi Laporan Kunjungan** + input waktu manual + **Simpan Laporan**; **P1** baru memakai Mulai/Akhiri Kunjungan dengan timestamp server | Cegah status Check-in/Check-out palsu | UX/Priority |
| ADR-028 | Label **Perlu Revisi** hanya ketika current status Draft setelah reject dan belum resubmit; pending/approved mengalahkan histori reject lama | Hindari tampilan status usang | UX/State |
| ADR-029 | Sembunyikan tombol input kunjungan untuk non-MR dan sediakan switch akun demo instan | Mencegah error 403 bagi manajer dan mempermudah evaluasi presentasi | UX/Role |
| ADR-030 | Monitoring tim dan pratinjau bukti audit untuk manajer | Memenuhi fungsi supervisi manajer tanpa endpoint baru | UX/Manager |
| ADR-031 | Penyelarasan peran strategi vs operasi dan one-level approval (v1.8) | Menjaga otonomi MR dan integritas approval PDF | Invariant |
| ADR-032 | Master Customer List terpusat dengan hierarki wilayah (v1.8) | Filter distrik default dengan fallback pencarian global | Design |
| ADR-033 | Pengetatan tanggal Plan dan status Expired/Tidak Terlaksana (v1.8) | Menghilangkan status menggantung dan menjaga kejelasan pelaporan | Operational |
| ADR-034 | Detailing produk murni dan rekomendasi non-blokir (v1.8) | Mencegah pelaporan fiktif; minimal 1 produk riil | Compliance |

## Dokumentasi yang wajib menjelaskan asumsi

1. Representation 3 current states plus event log untuk submitted/rejected.
2. DM/RSM personal Call List untuk demo hierarchy.
3. Unique global non-Cancelled Plan dan satu List per bulan.
4. Actual without Plan tetap legal bahkan tanpa Approved List.
5. Approved-as-of-Checkin untuk klasifikasi.
6. Schedule deviation di bulan yang sama + reason.
7. UI reason wajib vs Direct API opsional.
8. Manual historic time tidak membuktikan attendance; GPS/time verification di luar scope.
9. Session dan recovery adalah P1, bukan dependency P0.
10. 409 duplicate after retry bukan bukti gagal simpan; reconcile via GET own Actual.
11. UI heading berbahasa Indonesia dengan subtitle terminologi PDF; mobile navigation boleh ringkas. P0 menginput waktu manual; hanya P1 memakai tombol Check-in/Check-out server, dan Check-out tetap berbeda dari Complete Actual (Simpan Laporan).
12. Prioritas label status aktif Call List harus mengabaikan histori reject usang setelah resubmit/approval.

## Keterbatasan yang diterima

- Historical Manual Actual berisi waktu dari pengguna dan bisa tidak akurat; simpan timestamp pembuatan dan source untuk audit.
- Session cancel lalu manual entry adalah dua request terpisah sehingga kegagalan di tengah membutuhkan pengguna melanjutkan pengisian.
- Pending approval tua tidak otomatis di-escalate; tampilkan usia dan lakukan follow-up manual.
- Tidak ada offline mode/GPS/photo content verification/insentif calculations.
- Constraint dan transaksi sudah tercakup automated test lokal. Verifikasi concurrency pada MySQL nyata tetap perlu dijalankan jika reviewer meminta bukti database-level.

### ADR-029: Role-Based Supervisory Context & Clean Role Isolation
- Date: 2026-10-11
- Status: Accepted
- Problem: Akun manajer (DM/RSM/MM) masih melihat tombol buat target dokter, jadwal, dan kunjungan yang tidak sesuai peran manajerial di lapangan.
- Decision: Batasi pembuatan Call List, Call Plan, dan Call Actual pada antarmuka (UI) hanya untuk Medical Representative (MR). Untuk manajer (DM, RSM, MM), sembunyikan seluruh tombol buat target/jadwal/kunjungan, buka langsung tab Persetujuan Masuk (Review) secara default, dan pertahankan UI enterprise yang bersih tanpa widget/banner berlebihan. Pilihan akun demo tetap terpusat di halaman Login.
- Source of requirement: Business reality / UX
- API/DB/UX impact: Backend API tetap utuh; Blade UI sepenuhnya mencerminkan peran operasional lapangan tanpa membingungkan manajer.
- Risks/trade-offs: Tidak ada.
- Tests to update: Pertahankan seluruh test terkait role isolation tetap hijau.

### ADR-030: Managerial Monitoring of Team Call Actuals & Audit Evidence
- Date: 2026-10-11
- Status: Accepted
- Problem: Manajer (DM/RSM/MM) belum dapat melihat riwayat kunjungan lapangan yang dilakukan oleh tim bawahannya di halaman Pelaksanaan Kunjungan, sehingga tidak dapat memverifikasi bukti kunjungan (foto dan tanda tangan dokter) yang menjadi dasar perhitungan insentif dan pelaporan manajemen.
- Decision: Perluas endpoint GET /api/call-actuals agar akun manajer secara otomatis memuat kunjungan bawahan sesuai hierarki supervisi (DM memuat MR distriknya, RSM memuat MR regionalnya, MM memuat seluruh MR). Pada antarmuka Pelaksanaan Kunjungan untuk manajer, tampilkan strip metrik kinerja tim (Total, Terencana, Di Luar Rencana), filter anggota tim/periode, serta tabel riwayat kunjungan tim dengan aksi pratinjau bukti audit.
- Source of requirement: Business reality / SFA Standard
- API/DB/UX impact: GET /api/call-actuals kini mendukung scoping manajerial; Blade UI Pelaksanaan Kunjungan menampilkan mode monitoring tim tanpa membuat rute baru.
- Risks/trade-offs: Tidak ada risiko breaking change; akses MR tetap terisolasi pada kunjungannya sendiri.
- Tests to update: Tambahkan unit test untuk scoping kunjungan manajerial.

### ADR-031: Strategy-to-Operations Alignment & Invariant One-Level Approval (v1.8)
- Date: 2026-10-11
- Status: Accepted
- Problem: Terdapat kerancuan batas tanggung jawab antara MM, RSM, DM, dan MR, serta kekhawatiran apakah arahan manajerial akan membatasi otonomi MR atau membentuk rantai persetujuan tiga level untuk satu Call List MR.
- Decision: Formalisasikan pemisahan peran strategis vs operasional:
  1. MM menentukan fokus produk dan spesialisasi kampanye tiga bulanan (bukan kuota MR individu).
  2. RSM menerjemahkan strategi menjadi prioritas dokter regional.
  3. DM menetapkan arahan dokter prioritas bulanan tingkat distrik sebagai arahan non-eksklusif (MR tetap berhak memilih dokter MCL lain dan menentukan jumlah target sendiri).
  4. Approval Call List tetap mutlak Satu Tingkat Langsung (MR -> DM, DM -> RSM, RSM -> MM), tidak pernah menjadi rantai 3 level untuk satu Call List MR.
- Source of requirement: v1.8 Change Proposal / PDF Invariant
- API/DB/UX impact: Tidak merusak 8 endpoint wajib PDF. UI Call List menampilkan arahan prioritas distrik sebagai badge/info tanpa memblokir dokter MCL lain.
- Risks/trade-offs: Call List manajerial (DM dan RSM) diperlakukan sebagai ringkasan target wilayah bulanan, terpisah dari eksekusi visit.
- Tests to update: Skenario otorisasi approval tetap independen antar-tingkat.

### ADR-032: Centralized Master Customer List (MCL) with Territory Hierarchy Context (v1.8)
- Date: 2026-10-11
- Status: Accepted
- Problem: Struktur wilayah belum memiliki hierarki formal antara Regional, Distrik, dan Area, sementara operasional membutuhkan dokter dikelompokkan berdasarkan distrik.
- Decision: Master Customer List (MCL) tetap berada dalam satu tabel master terpusat (`master_customers`), bukan tabel terpisah per wilayah. Hierarki wilayah (Nasional -> Regional -> Distrik -> Area) dilekatkan sebagai konteks data. MR secara default melihat dokter dalam distriknya saat pencarian, namun pencarian luas ke seluruh MCL tetap diizinkan untuk kebutuhan Kunjungan di Luar Rencana (Non Target).
- Source of requirement: v1.8 Change Proposal
- API/DB/UX impact: Tidak memecah skema master dokter. Pencarian dokter di modal Kunjungan di Luar Rencana menyajikan default distrik dengan fallback pencarian global.
- Risks/trade-offs: Penambahan kolom territory tidak boleh merusak backward compatibility query dokter yang sudah ada.
- Tests to update: Pengujian pencarian dokter default distrik dan dokter non-target lintas wilayah.

### ADR-033: Strict Planned Visit Matching & Plan Expiry Status (v1.8)
- Date: 2026-10-11
- Status: Accepted
- Problem: Deviasi tanggal pada kunjungan terencana berpotensi membingungkan pelaporan, dan jadwal yang terlewat hari tanpa realisasi menggantung tanpa kejelasan status.
- Decision:
  1. Planned Actual harus mencocokkan tanggal yang sama persis dengan tanggal Plan (`visit_date == plan_date`). Jika tanggal kunjungan bergeser, MR harus menjadwalkan ulang atau menggunakan jalur Kunjungan di Luar Rencana (`plan_id = null`).
  2. Plan berstatus `scheduled` yang melewati akhir hari (Asia/Jakarta) tanpa record Actual otomatis bertransisi menjadi `expired` (Tidak Terlaksana) melalui scheduled command. Dokter yang plan-nya expired tetap tercatat di histori dan dapat dijadwalkan kembali pada tanggal lain dalam bulan yang sama jika masih ada di Call List Approved.
- Source of requirement: v1.8 Change Proposal / Operational Reality
- API/DB/UX impact: Penambahan status `expired` pada enum Call Plan. Command `msf:expire-plans` idempoten.
- Risks/trade-offs: Indeks unik active plan harus memperlakukan `expired` sebagai state non-aktif sehingga dokter dapat dijadwalkan ulang.
- Tests to update: Test command expire-plans dan validasi pencocokan tanggal Planned visit.

### ADR-034: Genuine Detailing Products & Non-Blocking Specialty Recommendations (v1.8)
- Date: 2026-10-11
- Status: Accepted
- Problem: Ada godaan bisnis untuk meng-auto-fill produk fokus MM ke dalam laporan kunjungan MR, yang berisiko menciptakan laporan fiktif.
- Decision: Produk yang dicatat pada `call_actuals` adalah produk yang benar-benar dipresentasikan MR saat visit (minimal 1 produk master). Rekomendasi produk berbasis spesialisasi dokter bersifat saran non-blokir di UI dan tidak boleh otomatis disuntikkan ke dalam database tanpa pilihan eksplisit MR.
- Source of requirement: PDF Requirement / Compliance Integrity
- API/DB/UX impact: Backend tetap menolak laporan jika array produk kosong (422), dan tidak melakukan auto-fill produk.
- Risks/trade-offs: Tidak ada risiko tambahan yang disengaja; integritas data pelaporan tetap dijaga oleh validasi backend.
- Tests to update: Automated test memastikan laporan tanpa produk ditolak dan produk yang tersimpan sesuai input MR.

## Format ADR baru

```md
### ADR-026 â€” [Decision title]
- Date:
- Status: Proposed / Accepted / Rejected
- Problem:
- Decision:
- Source of requirement: PDF / Assumption / Engineering necessity
- API/DB/UX impact:
- Risks/trade-offs:
- Tests to update:
```
