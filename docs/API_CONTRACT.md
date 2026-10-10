# API Contract — MSF

> **Versi:** 1.5 (kontrak konsolidasi dari API v1.0 + amendment v1.3–v1.5)  
> **Status:** Spesifikasi — endpoint belum diimplementasikan atau diuji.  
> **Base path:** `/api`; **transport:** HTTPS pada deployment, HTTP lokal saat pengembangan.  
> **Auth:** Laravel session same-origin + middleware auth + CSRF. Delapan endpoint bertanda **PDF** wajib tersedia.

## 1. Konvensi umum

- `Content-Type: application/json`, `Accept: application/json` untuk semua request data.
- Request browser memerlukan sesi login dan CSRF; jangan mengasumsikan `routes/api.php` default Laravel sudah memiliki middleware stateful.
- Owner/MR ID diambil dari session, **bukan** dari body request.
- Format sukses: `{"success":true,"message":"...","data":{...}}`.
- Format gagal: `{"success":false,"message":"...","error_code":"...","errors":{...}}`; `errors` boleh objek kosong; `data` opsional untuk pemulihan aman.
- `200` read/update, `201` creation, `401` unauthenticated, `403` forbidden, `404` not found/no read access, `409` conflict/state, `422` validation.
- `period_month` berformat `YYYY-MM`; tanggal `YYYY-MM-DD`; jam input Direct Actual `HH:mm:ss`; timestamp respons menggunakan ISO-8601 + offset yang jelas.
- Paginate/filter opsional pada GET; jangan mengubah bentuk inti data tanpa dokumentasi.

## 2. Endpoint wajib PDF (P0)

| # | Method | URI | Aktor | Keterangan |
|---|---|---|---|---|
| 1 | POST | `/api/call-lists` | owner yang boleh membuat List | Create draft bulanan |
| 2 | GET | `/api/call-lists` | authenticated | List owner login |
| 3 | GET | `/api/call-lists/{id}` | owner/assigned approver | Detail + histori |
| 4 | PATCH | `/api/call-lists/{id}/approve` | direct manager yang ditunjuk | Approve/Reject |
| 5 | POST | `/api/call-plans` | MR | Create Plan |
| 6 | GET | `/api/call-plans` | MR | List Plan milik MR |
| 7 | POST | `/api/call-actuals` | MR | Final Actual Planned/Without Plan |
| 8 | GET | `/api/call-actuals` | MR | List Actual milik MR |

### 2.1 POST `/api/call-lists`

```json
{"period_month":"2026-11","doctor_ids":[5,8,12]}
```

Validasi: `doctor_ids` tidak kosong, unik dalam request, semua ID dokter terdaftar MCL; owner/periode unik. Initial state `draft`.

Contoh respons 201:
```json
{"success":true,"message":"Call List draft created","data":{"id":41,"period_month":"2026-11","status":"draft","doctor_ids":[5,8,12]}}
```

### 2.2 GET `/api/call-lists` dan GET `/api/call-lists/{id}`

GET list hanya mencakup owner login. Detail mencakup `{id,owner_id,period_month,doctor_ids,status,assigned_approver_id,approval_history}`. Assigned approver boleh mengakses List yang ditugaskan kepadanya melalui detail dan approval queue, tetapi **bukan** semua List organisasi.

### 2.3 PATCH `/api/call-lists/{id}/approve`

Approve:
```json
{"status":"approved","reason":null}
```
Reject:
```json
{"status":"rejected","reason":"Target perlu disesuaikan"}
```

`reason` wajib non-kosong untuk Reject, opsional untuk Approve. Pemberi keputusan harus `assigned_approver_id`, berada pada hierarki direct manager, dan List harus `pending_approval`. Reject menghasilkan **current status `draft`**, tetapi response juga menyertakan **`decision: rejected`** dan log lengkap.

Contoh respons 200 Reject:
```json
{"success":true,"message":"Call List rejected and returned to draft","data":{"id":41,"status":"draft","decision":"rejected","reason":"Target perlu disesuaikan"}}
```

### 2.4 POST `/api/call-plans`

```json
{"call_list_id":41,"doctor_id":5,"plan_date":"2026-11-12","planned_time":"09:00:00"}
```

Harus milik MR, sumber List Approved, dokter anggota List, tanggal dalam bulan List, tidak ada Plan non-Cancelled global dokter/tanggal maupun Actual final di hari tersebut. Respons 201 berisi Plan ID, Doctor ID, tanggal, jam, `status:"scheduled"`.

### 2.5 GET `/api/call-plans`

Menerima opsional `?date=YYYY-MM-DD` (jadwal hari ini) dan `?status=scheduled`; hanya Plan milik MR yang login. Data memuat `doctor`, `plan_date`, `planned_time`, `status`, `schedule_indicator` bila dihitung. Derived indicator bukan lifecycle status.

### 2.6 POST `/api/call-actuals` — **dua jalur payload**

**A. Direct / Manual Actual — jalur wajib PDF, tidak perlu Session**

```json
{
  "plan_id":17,
  "doctor_id":5,
  "visit_date":"2026-11-12",
  "check_in":"08:30:00",
  "check_out":"09:00:00",
  "photo_url":"https://example.test/photo.jpg",
  "signature_url":"https://example.test/signature.png",
  "products":[1,3],
  "visit_reason":null,
  "deviation_reason":null
}
```

**Syarat utama:** `plan_id` boleh `null`. Dokter harus ada di MCL jika Without Plan. `visit_reason` **opsional di API**. `deviation_reason` hanya diwajibkan jika Planned Actual tanggalnya berbeda dari Plan (asumsi desain, tanggal baru tetap dalam bulan Call List). Foto, signature dan minimal satu produk wajib dalam seluruh jalur. Check-out setelah Check-in; date/time tidak di masa depan sebagai asumsi integritas. Jika ada Session aktif untuk dokter/tanggal tersebut, Direct API tidak boleh diam-diam melewati Session; selesaikan atau batalkan Session terkait dahulu. Simpan semua atomik.

**B. Session-backed Actual — P1, tetap memakai endpoint yang sama**

```json
{
  "visit_session_id":91,
  "photo_url":"https://example.test/photo.jpg",
  "signature_url":"https://example.test/signature.png",
  "products":[1,3],
  "deviation_reason":null
}
```

`doctor_id`, `plan_id`, `visit_date`, `check_in`, `check_out` diperoleh server dari Session. `check_out_at` harus sudah tersedia. Client **tidak boleh menimpa** identitas/waktu Session. Jika mengirim field tersebut juga, request ditolak kecuali skema explicit mengizinkan pemeriksaan equality; rekomendasi implementasi adalah melarang payload campuran agar tidak ambigu. Session Plan ID (termasuk `null`) harus sama dengan Plan ID pada Actual final.

**Respons 201** (kedua jalur):
```json
{
  "success":true,
  "message":"Call Actual completed",
  "data":{"id":105,"doctor_id":5,"plan_id":17,"visit_date":"2026-11-12","visit_type":"planned","status":"completed","products":[1,3]}
}
```

**Catatan:** `status:"completed"` pada respons Actual adalah keadaan final output/UI; Actual disimpan hanya sebagai record final, bukan tabel draft yang mempunyai state `in_progress`.

### 2.7 GET `/api/call-actuals`

Owner-scoped; filter opsional `?date=YYYY-MM-DD` atau `?doctor_id=5` untuk recovery. Respons hanya mengembalikan Actual pemilik. Backend tidak boleh mengirim signature/photo orang lain lewat informasi konflik.

## 3. Supporting API (bukan delapan wajib)

| Prioritas | Method | URI | Ringkasan |
|---|---|---|---|
| P0 | PATCH | `/api/call-lists/{id}` | Edit Draft owner; `{period_month?,doctor_ids?}` |
| P0 | POST | `/api/call-lists/{id}/submit` | Draft→Pending Approval dengan event submit; tidak boleh approved sendiri |
| P0 | GET | `/api/approval-queue` | Pengajuan yang assigned kepada direct manager login |
| P0 | GET | `/api/master-customers` | MCL picker dengan search opsional |
| P0 | GET | `/api/products` | Product picker |
| P1 | PATCH | `/api/call-plans/{id}/cancel` | Owner; Scheduled, alasan wajib, tidak ada Actual/Session terhubung |
| P1 | GET | `/api/schedule-availability` | Informasi konflik minimum untuk UI, tanpa detail MR lain |
| P1 | POST | `/api/visit-sessions` | Start/Resume Check-in server; `{plan_id?,doctor_id?}` |
| P1 | GET | `/api/visit-sessions/active` | Active Sessions MR login |
| P1 | PATCH | `/api/visit-sessions/{id}` | Save Draft; optimistic revision |
| P1 | POST | `/api/visit-sessions/{id}/check-out` | Simpan timestamp server sekali |
| P1 | PATCH | `/api/visit-sessions/{id}/cancel` | Cancel dengan alasan |

### 3.1 Submit Call List

`POST /api/call-lists/{id}/submit` tidak membutuhkan body. Hanya owner Draft; validasi atasan langsung/assigned approver. DB update ke `pending_approval`; histori menyimpan event `submitted` atau `resubmitted` (`user_action`) dan `pending_approval` (`system_transition`). Assigned approver ditetapkan pertama kali, dipertahankan saat resubmit. Jika hierarchy berubah, tidak ada silent reassignment.

### 3.2 Visit Sessions (P1)

`POST /api/visit-sessions`: untuk Planned kirim `{ "plan_id":17 }`, dokter derived dari Plan; Without Plan kirim `{ "plan_id":null,"doctor_id":5 }`. Server menentukan Check-in dan Visit Date. Jika request identik dari MR yang sama dan Session In Progress masih ada, return **200 resume**; jika baru return **201**. Jika dokter/tanggal punya Actual atau Session milik MR lain, return 409.

`PATCH /api/visit-sessions/{id}`:
```json
{"expected_revision":2,"photo_url":"https://example.test/photo.jpg","signature_url":null,"products":[1,3],"visit_reason":"Kunjungan mendadak"}
```
Revision lama → 409. Hanya `draft_payload` yang dapat berubah; identitas dan timestamp tidak.

`POST /api/visit-sessions/{id}/check-out`: body kosong; hanya owner dan Session In Progress; waktu check-out dari server, hanya ditetapkan sekali. Tidak otomatis membuat Actual.

`PATCH /api/visit-sessions/{id}/cancel`:
```json
{"reason":"Lupa check-out; perlu laporan waktu manual"}
```

Cancel menghasilkan status `cancelled` dan alasan tersimpan. Form manual berikutnya menggunakan Direct API wajib, tidak ada `POST /api/manual-actuals`.

## 4. Error catalogue

| Kode | HTTP | Arti |
|---|---:|---|
| `VALIDATION_FAILED` | 422 | Request fields gagal validasi |
| `APPROVAL_NOT_ALLOWED` | 403 | Bukan assigned direct manager |
| `CALL_LIST_NOT_APPROVED` | 422 | Plan/Planned Actual merujuk List belum approved |
| `PLAN_DATE_CONFLICT` | 409 | Dokter sudah mempunyai Plan non-Cancelled pada tanggal itu |
| `PLAN_SELECTION_CONFLICT` | 409 | Planned tanggal bergeser tetapi Plan lain milik MR sesuai tanggal aktual tersedia |
| `VISIT_ALREADY_EXISTS` | 409 | Sudah ada Actual global dokter/tanggal |
| `PHOTO_REQUIRED` | 422 | Foto belum diberikan |
| `SIGNATURE_REQUIRED` | 422 | Signature belum diberikan |
| `PRODUCT_REQUIRED` | 422 | Product detailing kosong |
| `DOCTOR_PLAN_MISMATCH` | 422 | Doctor dari payload tidak sesuai Plan |
| `ACTIVE_SESSION_CONFLICT` | 409 | Ada Session aktif yang saling bertentangan |
| `SESSION_PLAN_MISMATCH` | 409 | Session dan Actual berbeda Plan termasuk null mismatch |
| `STALE_DRAFT_REVISION` | 409 | Draft Session telah diperbarui oleh request lain |
| `VISIT_TIME_IN_FUTURE` | 422 | Input waktu melewati waktu sekarang |

**Recovery duplicate Actual, milik sendiri**:
```json
{"success":false,"message":"Kunjungan sudah tercatat","error_code":"VISIT_ALREADY_EXISTS","errors":{},"data":{"owned_by_me":true,"existing_actual_id":105}}
```

**Milik MR lain**: sama-sama 409, **tanpa** `existing_actual_id`, foto, signature, atau identitas pemilik.

## 5. Transaction, idempotency, dan privacy

- Unique `(doctor_id, visit_date)` pada Actual adalah pengaman terakhir global lintas Planned/Unplanned/Non Target.
- Service memakai transaksi dan doctor row lock untuk mencegah race antara Plan, Session, dan Actual.
- `POST` tidak dijanjikan idempotency key sempurna; retries dapat menerima 409, lalu GET own Actual untuk recovery.
- Auth/session dan ownership wajib pada semua read/update; status 404 boleh digunakan untuk mencegah eksposur ID yang tidak dimiliki.
- Input URL untuk foto/signature adalah mock URL sesuai studi kasus; verifikasi konten/unggahan produksi berada di luar scope.

## 6. Definition of Done untuk API

- Seluruh **delapan endpoint wajib** tersedia dan bisa diuji tanpa dependensi Session P1.
- Jalur `plan_id=null` diterima tanpa mewajibkan alasan.
- 401/403/404/409/422 dibedakan secara konsisten.
- JSON Response dan error code stabil dan terdokumentasi.
- Validation parity UI + backend untuk aturan inti.
- Test manual PDF, negative case, concurrency critical telah dijalankan dan dilaporkan apa adanya.
