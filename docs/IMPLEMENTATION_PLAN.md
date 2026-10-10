# Implementation Plan — MSF Laravel

> **Versi:** 1.5 implementation sequence + UI label v1.6 + operational clarity v1.7; **status:** rencana kerja, belum ada kode implementasi.  
> **Tujuan:** menghasilkan deliverable sesuai PDF secepat mungkin tanpa menjadikan enhancement P1 sebagai dependency P0.

## 1. Workflow IDE yang diharapkan

1. Baca `AGENTS.md` → `DOCS_INDEX.md` → `DESIGN.md` → `PRD.md` → `API_CONTRACT.md` → `DATABASE_SCHEMA.md` → `TDD.md` → `TEST_PLAN.md`.
2. Verifikasi environment Laravel/PHP/Composer/MySQL lokal.
3. Implementasi per fase P0; **setelah tiap fase** tulis ringkasan file yang diubah, tes yang dijalankan, dan kegagalan yang ditemukan.
4. Jangan mengklaim PASS tanpa mengeksekusi tes dan melihat hasilnya.
5. Tulis asumsi yang diperlukan jika spesifikasi belum menentukan suatu detail. Hindari menambah fitur.
6. Pada **heading tiga halaman** tampilkan nama Indonesia lengkap **Target Dokter**, **Jadwal Kunjungan**, **Pelaksanaan Kunjungan**, disertai subtitle **Call List Bulanan**, **Call Plan**, **Call Actual**. Desktop navigation gunakan nama lengkap, mobile sempit boleh ringkas menjadi Target/Jadwal/Kunjungan dengan accessible name lengkap.
7. **P0 Call Actual:** tombol **Isi Laporan Kunjungan**, waktu manual, **Simpan Laporan**. Tombol **Mulai/Akhiri Kunjungan** dengan timestamp server **hanya** saat P1 Session benar-benar dibangun.
8. Jika status Call List Draft pernah direject, UI **Perlu Revisi**; tetapi sesudah submit ulang wajib **Menunggu Persetujuan** dan setelah approved **Disetujui**. Histori reject tetap ada sebagai audit, bukan state saat ini.

## 2. Roadmap P0

| Fase | Deliverable | Exit gate |
|---|---|---|
| 0 — Setup | Laravel monolith, `.env.example`, session auth, role, frontend Blade layout, same-origin JSON API/CSRF | Login dan request API auth bekerja |
| 1 — Schema | Roles/users/MCL/products/List/approval logs/Plan/Actual/product pivot; seeds sintetis, MySQL constraints | `migrate:fresh --seed` sukses, FK/unique terbukti |
| 2 — Call List | Create draft, edit, submit, owner list/detail, manager queue, approve/reject, history | MR→DM happy path, reject/resubmit dan unauthorized tests |
| 3 — Call Plan | Create/list, approved membership/date validation, no duplicate plan/actual | Plan valid diterima; duplicate/unapproved ditolak |
| 4 — Call Actual | Direct POST Planned dan Without Plan, GET own, products, photo/signature, cross-table locking, classification | Example PDF diterima; double Actual ditolak |
| 5 — Blade UI | 3 halaman dengan mobile UX Planned-first, Without Plan, form time/photo/signature/products, loading/errors | Alur manual end-to-end dan responsive terverifikasi |
| 6 — Submission | README run instructions, seed login examples, test evidence, tools reflection, screenshots, demo | 5 PDF required tests PASS + evidence, runnable from fresh clone |

**Prioritas keras:** tahap 4 dan 5 harus selesai sebelum P1. Jangan mengerjakan Visit Session persisten jika API Planned/Without Plan P0 belum stabil.

## 3. Roadmap P1 (jika P0 selesai)

| Order | Deliverable | Gate |
|---|---|---|
| 1 | `visit_sessions` migration + generated uniqueness | MySQL migration valid |
| 2 | Start/Resume server Check-in | Duplicate concurrent check-in dicegah |
| 3 | Save Draft revision + Checkout + Complete Session-backed Actual | Plan/Session consistency, rollback |
| 4 | Cancel Session + guided Manual Recovery | Cancel tidak hilang; manual disimpan |
| 5 | Cancel Plan + schedule indicator Conflict | Status Plan tetap sah |
| 6 | Additional concurrency + mobile UX tests | Semua P0 regression tetap PASS |

## 4. Rencana folder implementasi (ilustratif)

```text
app/
  Http/
    Controllers/Api/
    Requests/
  Policies/
  Models/
  Services/
resources/
  views/
    layouts/
    call-lists/
    call-plans/
    call-actuals/
    components/
  js/
routes/
  web.php
  api.php                 # jika digunakan, tetap pasang session+CSRF sesuai desain
bootstrap/
database/
  migrations/
  seeders/
tests/
  Feature/
  Unit/
DESIGN.md
PRD.md
TDD.md
API_CONTRACT.md
DATABASE_SCHEMA.md
TEST_PLAN.md
IMPLEMENTATION_PLAN.md
AGENTS.md
README.md                 # tulis sesudah aplikasi nyata tersedia
```

Nama class/folder boleh disesuaikan konvensi Laravel proyek, selama kontrak perilaku dan HTTP dipertahankan.

## 5. P0 Test-driven checklists

### Setelah migration

- [ ] Roles dan demo users hierarchy benar.
- [ ] FK, composite ownership, unique owner/bulan bekerja.
- [ ] Generated active-plan unique bekerja di MySQL.
- [ ] Global Actual doctor/day unique bekerja.

### Setelah Call List

- [ ] Draft, submit, pending, approve, reject-resubmit.
- [ ] Assigned direct manager, bukan role-only manager.
- [ ] Approval log actor + origin akurat.

### Setelah Call Plan

- [ ] Hanya Approved List owner.
- [ ] Dokter anggota, tanggal dalam bulan.
- [ ] Konflik lintas MR benar.

### Setelah Call Actual

- [ ] Planned valid.
- [ ] Without Plan valid dengan `plan_id:null`.
- [ ] Foto, signature, products minimal satu.
- [ ] Duplicate global ditolak.
- [ ] Rollback atomic ketika error.
- [ ] GET owner scope dan info conflict safe.

### Setelah frontend

- [ ] Jadwal Hari Ini sebagai fokus Call Actual.
- [ ] Dua pilihan saja: Terjadwal dan Di Luar Rencana.
- [ ] Form P0 menampilkan input **Waktu Mulai/Waktu Selesai** dan tombol **Isi Laporan Kunjungan → Simpan Laporan**; tidak mengklaim Check-in/Check-out server terjadi.
- [ ] Tidak ada asumsi Session P1 diperlukan untuk submit P0.
- [ ] Validasi pesan error/loading dan layar mobile.

## 6. README & presentation readiness

Aplikasi harus memiliki README terpisah yang berisi:

- Persyaratan environment, instalasi, command migration+seed, server start, kredensial demo **sintetis**.
- Daftar endpoint, fitur P0 vs P1 yang benar-benar dikerjakan.
- Asumsi desain dan keterbatasan (khususnya `plan_id=null`, status log, time provenance).
- **Lima skenario manual** PDF dengan expected, actual, PASS/FAIL, screenshot/recording.
- Tools dipakai: tujuan, contoh output accepted/rejected dan alasan, cara memverifikasi hasil.
- Panduan demo singkat Call List→Approval→Plan→Actual dan negative cases.

Jangan memasukkan PDF sumber kasus atau data sensitif ke repository publik. Jika publikasi sumber menjadi ambigu, minta klarifikasi penyelenggara.

## 7. Aturan evaluasi berkala

Pada akhir setiap fase lakukan review singkat:

- **Requirements** — endpoint/fitur PDF mana sudah nyata bekerja?
- **Correctness** — validasi, transaction, dan authorization apa sudah terbukti?
- **Tests** — PASS/FAIL actual, bukan asumsi?
- **Regressions** — perubahan memecahkan fitur sebelumnya?
- **Scope** — apakah P1 mengganggu P0?

Jika menemukan kontradiksi, dokumentasikan keputusan di `DECISIONS.md` sebelum mengubah business rule. Jangan secara diam-diam menghapus nullable Plan, mengubah satu Actual global, atau membuat approval multi-level.
