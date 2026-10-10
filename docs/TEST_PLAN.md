# Test Plan & Acceptance Matrix — MSF

> **Versi:** 1.5. **Status pengujian:** BELUM DIJALANKAN.  
> **Tujuan:** acceptance tests PDF, negative cases, authorization, persistence, UX mobile, dan concurrency.  
> **Environment minimum:** MySQL 8.0.16+, Laravel, synthetic seed users MR-A, MR-B, DM-A, DM-B, RSM, MM; dokter MCL, products.

## 1. Cara melaporkan hasil

Setiap test harus dicatat: **Test ID, Preconditions, Steps, Expected, Actual, Result (PASS/FAIL/BLOCKED), Evidence screenshot/screen-recording, Notes**. Jangan mengganti *Expected* dengan klaim *Actual* sebelum tes dijalankan.

Contoh catatan saat belum ada implementasi:

| ID | Expected | Actual | Result |
|---|---|---|---|
| PDF-01 | List→Approve→Plan→Actual selesai | Belum dijalankan | NOT RUN |

## 2. Lima skenario manual wajib PDF — P0

| ID | Preconditions & steps | Expected |
|---|---|---|
| **PDF-01** | Login MR-A. Buat Call List November berisi dokter D1, Submit. Login DM-A direct manager: Approve. Login MR-A: buat Plan D1 tanggal 12 Nov. POST Actual Planned valid (photo, signature, products). | List Approved; Plan awal Scheduled lalu Completed; Actual Completed. |
| **PDF-02** | MR-A membuat Call List Draft dan mencoba membuat Plan darinya. | Ditolak; tidak ada Plan tersimpan. |
| **PDF-03** | MR-A menyimpan Actual dokter D1 tanggal X, MR-A/MR-B mencoba Actual kedua untuk D1 X via Planned/Without Plan. | Kedua ditolak; count Actual untuk D1 X tetap 1. |
| **PDF-04** | POST Actual valid kecuali photo_url hilang/kosong. | 422 PHOTO_REQUIRED; tidak ada Actual atau pivot partial. |
| **PDF-05** | RSM atau MM mencoba PATCH approve Call List MR-A yang ditujukan ke DM-A. | 403 APPROVAL_NOT_ALLOWED (atau objek inaccessible); status tidak berubah. |

**Bukti minimum:** 5 hasil expected/actual dan screenshot/screen-recording sesuai PDF. Negative cases harus menunjukkan error code yang benar serta bukti DB tidak berubah.

## 3. Authorization & approval

| ID | Skenario | Expected |
|---|---|---|
| AUTH-01 | User belum login request endpoint protected | 401 |
| AUTH-02 | MR mencoba approve Call List sendiri | 403 |
| AUTH-03 | User ber-role DM tapi bukan `assigned_approver_id` dari List | 403 |
| AUTH-04 | MR-A membaca GET Actual MR-B melalui query/filter/ID | Tidak bocor; 404/403/hasil kosong |
| AUTH-05 | MR-A mencoba menggunakan Plan MR-B | 403/422; tidak ada Actual |
| CL-01 | Submit Draft pertama | Pending dan submit+pending log tercatat, assignee DM |
| CL-02 | DM reject + reason | DB Draft, UI *Perlu Revisi*, log rejected+returned_to_draft |
| CL-03 | MR revisi dan resubmit | Assigned approver sama, histori lama tetap ada |
| CL-04 | Approve oleh assigned DM | Approved final, log benar |
| CL-05 | Reject List yang sudah approved | 409; Approved tetap |
| CL-06 | Duplicate Call List owner/month | 409/422, tidak ada List kedua |
| CL-07 | Submit dan reject/approve concurrency | Hanya transisi state valid, tidak ada double decision |

## 4. Call Plan & Calendar

| ID | Skenario | Expected |
|---|---|---|
| CP-01 | Plan doctor bukan anggota List | 422 |
| CP-02 | Plan di luar bulan List | 422 |
| CP-03 | Plan D1 X dari MR-A dan MR-B bersamaan | Maksimal 1 Plan non-Cancelled |
| CP-04 | Existing Actual D1 X, kemudian create Plan D1 X | 409 |
| CP-05 | Plan Scheduled dibatalkan, buat pengganti D1 X (P1) | Plan baru boleh jika Actual final belum ada |
| CP-06 | Actual unrelated D1 X muncul, Plan MR-A masih Scheduled | Indicator Conflict; Plan tidak auto-Completed |
| CP-07 | Planned Actual tanggal berubah dalam bulan List, dengan deviation_reason | Allowed jika tidak ada konflik |
| CP-08 | Planned Actual di luar bulan List | Ditolak |
| CP-09 | Planned pakai Plan A tanggal 12, MR sama memiliki Plan B dokter sama tanggal 15, Actual tanggal 15 | `PLAN_SELECTION_CONFLICT` / diarahkan Plan B |

## 5. Call Actual & exception modes

| ID | Skenario | Expected |
|---|---|---|
| CA-01 | `plan_id=null`, dokter MCL valid, foto+TTD+produk valid | Sukses; tidak perlu Approved Call List |
| CA-02 | Without Plan dokter masuk Approved List milik MR as-of check-in | `visit_type=unplanned` |
| CA-03 | Without Plan dokter MCL di luar Approved List | `visit_type=non_target` |
| CA-04 | List baru approved **setelah** Check-in historis | Tetap Non Target untuk data itu |
| CA-05 | Without Plan Direct API tanpa `visit_reason` | Tidak ditolak karena alasan kosong |
| CA-06 | UI Without Plan tanpa alasan | UI mengharuskan isi alasan |
| CA-07 | `plan_id` nonnull doctor mismatch | Ditolak |
| CA-08 | Products kosong atau product ID invalid | 422; semua rollback |
| CA-09 | Signature URL kosong | 422; semua rollback |
| CA-10 | `check_out` ≤ `check_in` | 422 |
| CA-11 | Actual future time | 422 (asumsi) |
| CA-12 | Dua POST untuk dokter/tanggal sama lintas mode/MR | Maksimal satu sukses; unique global |
| CA-13 | Simulasi kegagalan pivot saat finalisasi | Rollback Actual dan Plan status |
| CA-14 | Direct Manual Planned valid | Sukses; Plan terkait Completed |
| CA-15 | Without Plan valid saat unrelated Plan Scheduled | Actual sukses jika tidak ada Actual/Session conflict; Plan tidak diubah |
| CA-16 | Direct Actual ketika Session In Progress dokter/tanggal sama | 409; perlu selesaikan/batalkan sesi terlebih dahulu (P1) |

## 6. P1 Session & recovery

| ID | Skenario | Expected |
|---|---|---|
| VS-01 | Mulai Session baru Plan Valid | Server timestamp; 201 |
| VS-02 | MR sama mencoba Start kembali dengan identitas Session sama | 200 resume; check-in lama tetap |
| VS-03 | MR lain Start untuk dokter/tanggal active Session yang sama | 409 |
| VS-04 | Save Draft `expected_revision` benar | New revision dan draft tersimpan |
| VS-05 | Save Draft stale | 409, data terbaru tidak tertimpa |
| VS-06 | Save setelah Completed/Cancelled | 409 |
| VS-07 | Check-out berulang | Timestamp tidak berubah; request kedua konflik |
| VS-08 | Completion tanpa Check-out | 422/409, no final Actual |
| VS-09 | Session Plan null tapi payload memaksa Plan ID | Ditolak SESSION_PLAN_MISMATCH / mixed fields |
| VS-10 | Session Plan 17, Actual memakai Plan 18 | Ditolak |
| VS-11 | Cancel Session dengan reason lalu Manual Actual valid | Session cancelled tetap history, Manual Actual created |
| VS-12 | Cancel sukses, Manual Actual gagal | Session tidak kembali In Progress, form manual tetap dapat diperbaiki |
| VS-13 | Check-in vs Direct Actual concurrent | Tidak ada kombinasi Session aktif + Actual final bertentangan |
| VS-14 | Cancel Plan vs completion concurrent | Hanya satu transition sah |

## 7. UX & frontend

| ID | Skenario | Expected |
|---|---|---|
| UX-01 | Buka Call Actual dari mobile | Jadwal Hari Ini terlihat; tombol Mulai jelas |
| UX-02 | Ada Active Session (P1) | Panel Lanjutkan Kunjungan paling atas |
| UX-03 | MR buka Without Plan | Satu entry point; dokter MCL; alasan UI |
| UX-04 | Planned dari Jadwal Hari Ini | Plan/doctor terisi otomatis tanpa input ID |
| UX-05 | GET salah satu panel gagal | Panel lain tetap tampil dan bisa digunakan |
| UX-06 | Error missing photo / duplicate | Pesan manusiawi + per-field feedback |
| UX-07 | POST sukses di DB tapi response timeout | Status Belum Dapat Dikonfirmasi; GET own Actual ditemukan |
| UX-08 | POST retry duplikat own | 409 own id memungkinkan lihat laporan |
| UX-09 | POST retry duplikat MR lain | Tidak ada actual ID/evidence lain |
| UX-10 | DM reject | List UI Perlu Revisi dengan alasan |

## 8. Concurrency & MySQL proofs

- Buat integration tests terhadap MySQL InnoDB nyata; SQLite tidak memvalidasi generated unique/FK dan locking sama.
- Simulasikan dua concurrent transactions untuk create Plan sama dokter/tanggal dan Actual sama dokter/tanggal. Hanya satu sukses.
- Test complete Actual ketika dua request berbeda berebut doctor row.
- Pastikan GET Availability tidak pernah menjadi validasi final sebelum commit.
- Capture query/exception logs yang relevan (tanpa secrets).

## 9. Exit criteria

**Gate P0:** migration fresh+seed sukses, delapan API wajib diuji, tiga halaman berfungsi, PDF-01..05 PASS dengan evidence, docs README terisi, jalur `plan_id=null` accepted, backend ownership benar.  
**Gate P1:** P0 regression tetap PASS; Session, Recovery, draft revision, concurrency dan advanced UX telah diuji nyata.

Test belum dijalankan tidak boleh dicantumkan sebagai PASS.
