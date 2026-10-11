# Architecture Decision Records — MSF

> **Design baseline:** v1.5 + UX label v1.6 + operational clarity v1.7. **Status:** keputusan desain diterima, belum diuji pada runtime.  
> **Perubahan setelah baseline:** tambahkan ADR baru dengan tanggal, alasan, trade-off, dampak API/schema/testing. Jangan diam-diam mengubah aturan PDF.

| ADR | Decision | Rationale | Type |
|---|---|---|---|
| ADR-001 | Laravel monolith, Blade, JS ringan, MySQL 8.0.16+ | Cocok untuk scope dan kecepatan implementasi | Design |
| ADR-002 | P0 meliputi 8 endpoint wajib dan 3 halaman | Deliverable PDF prioritas tertinggi | PDF |
| ADR-003 | Auth via Laravel session + CSRF same-origin | Integrasi Blade dan JSON API sederhana | Design |
| ADR-004 | Approval List satu level direct-manager dengan `manager_id` dan assigned approver | Mencegah approval role-only/lompatan hierarchy | PDF + Design |
| ADR-005 | DM/RSM boleh membuat List pribadi | Demonstrasi chain MR→DM→RSM→MM | Assumption |
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
- Generated indexes, composite FK, CHECK dan transaksi **belum diverifikasi di MySQL** sampai implementasi.

### ADR-029: Role-Based Supervisory Context & Clean Role Isolation
- Date: 2026-10-11
- Status: Accepted
- Problem: Akun manajer (DM/RSM/MM) masih melihat tombol buat target dokter, jadwal, dan kunjungan yang tidak sesuai peran manajerial di lapangan.
- Decision: Batasi pembuatan Call List, Call Plan, dan Call Actual pada antarmuka (UI) hanya untuk Medical Representative (MR). Untuk manajer (DM, RSM, MM), sembunyikan seluruh tombol buat target/jadwal/kunjungan, buka langsung tab Persetujuan Masuk (Review) secara default, dan pertahankan UI enterprise yang bersih tanpa widget/banner berlebihan. Pilihan akun demo tetap terpusat di halaman Login.
- Source of requirement: Business reality / UX
- API/DB/UX impact: Backend API tetap utuh; Blade UI sepenuhnya mencerminkan peran operasional lapangan tanpa membingungkan manajer.
- Risks/trade-offs: Tidak ada.
- Tests to update: Mempertahankan 100% kelulusan tes PHPUnit.

## Format ADR baru

```md
### ADR-026 — [Decision title]
- Date:
- Status: Proposed / Accepted / Rejected
- Problem:
- Decision:
- Source of requirement: PDF / Assumption / Engineering necessity
- API/DB/UX impact:
- Risks/trade-offs:
- Tests to update:
```
