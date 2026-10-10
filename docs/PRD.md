# PRD — Mobile Sales Force (MSF)

> **Versi:** 1.5 (consolidated business baseline)  
> **Status:** Spesifikasi final untuk mulai implementasi; aplikasi belum dibuat atau diuji.  
> **Sumber utama:** dokumen studi kasus MSF PT Mersifarma.  
> **Revisi yang digabung:** PRD v1.2/v1.3, Logic Flow v1.4, Operational UX v1.5.

## 1. Ringkasan produk

MSF adalah aplikasi web mobile-first untuk Medical Representative (MR) mencatat target dokter bulanan, merencanakan kunjungan, dan menyampaikan realisasi kunjungan dengan bukti foto, tanda tangan, serta produk yang didetailkan. Manager melakukan approval terhadap **Call List** bawahannya dalam satu tingkat hierarki.

**Tiga fitur inti:** Call List, Call Plan, Call Actual. **Tiga halaman utama:** Call List, Call Plan, Call Actual. Tidak perlu membuat dashboard, modul reporting/insentif, atau halaman Unplanned terpisah untuk memenuhi studi kasus.

## 2. Aktor dan wewenang

| Role | Aktivitas | Larangan |
|---|---|---|
| MR | Buat/edit/submit Call List, buat Plan, catat Actual milik sendiri | Approve List sendiri, ubah Actual milik MR lain |
| DM | Approve/reject Call List MR **bawahan langsung** | Approve List MR di luar bawahan; melewati jenjang |
| RSM | Approve/reject Call List DM **bawahan langsung** | Approve langsung List MR |
| MM | Approve/reject Call List RSM **bawahan langsung** | Approve langsung List MR/DM |

**Asumsi demonstrasi:** DM dan RSM boleh membuat Call List pribadi agar aturan DM→RSM dan RSM→MM dapat ditunjukkan; pembuatan Call Plan dan Actual melalui UI difokuskan pada MR. Manager terhubung melalui `users.manager_id`, bukan hanya pengecekan string `role`.

## 3. Kebutuhan fungsional

### 3.1 Call List (P0)

- `CL-01`: Pemilik memilih periode bulan dan beberapa dokter dari Master Customer List (MCL).
- `CL-02`: Simpan sebagai Draft; maksimal satu Call List per pemilik/bulan (**asumsi desain**).
- `CL-03`: Submit kepada atasan langsung. Manager menerima daftar pengajuan yang memang ditujukan kepadanya.
- `CL-04`: Approve atau Reject dengan alasan untuk Reject. Approval **single-level**, bukan berjenjang untuk satu pengajuan.
- `CL-05`: Setelah Reject, List kembali dapat diedit sebagai Draft, lalu dikirim ulang ke approver sama.
- `CL-06`: Riwayat submitted, pending_approval, rejected, returned_to_draft, resubmitted, approved tidak hilang.
- `CL-07`: List Approved immutable pada MVP; tidak dapat direject kembali setelah Plan dibuat.
- `CL-08`: Manager melihat tab *Pengajuan untuk Disetujui* pada halaman Call List, bukan halaman utama keempat.

**Representasi status desain:** database menyimpan state aktif `draft`, `pending_approval`, `approved`, sedangkan status/transisi `submitted` dan `rejected` disimpan sebagai event log. Ini **asumsi representasi**, bukan persis lima nilai status tersimpan yang ditulis di PDF. UI menampilkan *Perlu Revisi — Ditolak* berdasarkan riwayat setelah reject.

### 3.2 Call Plan (P0)

- `CP-01`: Hanya MR membuat Plan dari Call List **Approved** miliknya.
- `CP-02`: Dokter harus merupakan anggota Call List tersebut.
- `CP-03`: Tanggal Plan wajib berada dalam bulan Call List.
- `CP-04`: Satu dokter paling banyak memiliki satu Plan per tanggal, dicek **global lintas MR** (interpretasi konservatif aturan PDF); hanya Plan Cancelled yang tidak memblokir pembuatan Plan baru.
- `CP-05`: Tolak Plan jika sudah ada Actual final untuk dokter/tanggal sama.
- `CP-06`: Status Planned: Scheduled; menjadi Completed ketika Actual terkait selesai; Cancelled dengan alasan melalui fitur pendukung (P1).
- `CP-07`: Plan yang terhalang Actual lain **tidak otomatis Completed**; UI boleh menampilkan *Tidak Dapat Direalisasikan* sebagai indikator turunan.

### 3.3 Call Actual (P0)

- `CA-01`: MR dapat mencatat Planned Visit dengan `plan_id` valid.
- `CA-02`: MR dapat mencatat Call Actual **tanpa Plan** (`plan_id = null`) untuk dokter valid MCL. **Jangan mewajibkan Plan di backend**: ini syarat kontrak PDF.
- `CA-03`: Foto URL dan tanda tangan URL wajib; `products` minimal satu Product ID valid.
- `CA-04`: Check-out harus setelah Check-in.
- `CA-05`: Maksimal **satu Actual per dokter per tanggal, global seluruh MR dan seluruh mode**.
- `CA-06`: Planned Actual memverifikasi Plan, pemilik, dokter sama, Call List Approved dan periode tanggal yang sah.
- `CA-07`: Actual tanpa Plan diklasifikasikan backend berdasarkan kondisi ketika Check-in:
  - **Unplanned**: dokter ada pada Call List Approved pemilik di bulan tersebut **yang sudah approved ketika Check-in**.
  - **Non Target**: dokter valid MCL tetapi tidak memenuhi aturan Unplanned tersebut.
- `CA-08`: Snapshot `visit_type` disimpan saat finalisasi; tidak dihitung ulang belakangan.
- `CA-09`: `visit_reason` diminta pada formulir frontend untuk mode di luar rencana, tetapi **opsional pada Direct API** untuk kompatibilitas PDF.
- `CA-10`: Actual tanpa Plan tidak membuat Plan baru atau menyelesaikan Plan lain secara otomatis.
- `CA-11`: Completion bersifat atomic: Actual, relasi produk, dan update status Plan/Session terkait berhasil seluruhnya atau rollback.
- `CA-12`: Actual final immutable pada MVP.

**Asumsi tambahan yang harus dicantumkan README:** Planned Actual dapat bergeser tanggal hanya di dalam bulan Call List dan memerlukan `deviation_reason`; timestamp tidak boleh berada di masa depan. Jika MR mempunyai Plan Scheduled lain untuk dokter yang sama tepat di tanggal aktual, frontend/Planned flow mengarahkan ke Plan yang benar. Ini **tidak** menghapus jalur `plan_id = null`.

## 4. UX yang disetujui (v1.5)

### Call List
Pilih bulan → pilih dokter → Draft → Submit → direct manager Approve/Reject → jika rejected, revisi dan resubmit.

### Call Plan
Pilih Approved List → dokter yang termasuk List → tanggal/jam → simpan. Pesan duplikat harus mudah dipahami.

### Call Actual: satu halaman, dua opsi

1. Tampilkan **Kunjungan Sedang Berlangsung** jika ada (P1).
2. Tampilkan **Jadwal Hari Ini** dengan tombol utama **Mulai Kunjungan**.
3. Tampilkan opsi sekunder **Kunjungan di Luar Rencana**; pilih dokter MCL, sistem merekomendasikan Plan jika relevan, dan minta alasan melalui UI.
4. Jalankan alur **Mulai → Check-in → Foto + TTD + Produk → Check-out → Selesaikan**.
5. Tampilkan **Riwayat Selesai**.

Sistem menentukan Planned/Unplanned/Non Target; MR tidak memilih nama klasifikasi. Field ID dan istilah `visit_session_id` tidak tampak kepada pengguna. **P0:** In Progress → Completed dapat berupa state form lokal dengan input waktu; **P1:** Session persisten memakai timestamp server dan dapat dilanjutkan.

## 5. Recovery / pengecualian (P1)

- **Lupa Check-out:** jika sesi masih akurat, lanjutkan. Jika tidak, lakukan *Perbaiki melalui Pencatatan Manual*: batalkan Session dengan alasan, lalu gunakan Direct Actual dengan waktu yang diisi MR. Dua HTTP request, **bukan transaksi atomik tunggal**.
- **Submit timeout:** jangan langsung menyatakan gagal; GET daftar Actual milik MR dan cari laporan yang telah tersimpan; 409 duplicate bisa berarti permintaan sebelumnya berhasil.
- **Plan terhalang Actual lain:** tampilkan Conflict, jangan otomatis Completed. Cancel Plan boleh jika tidak ada Actual/Session terkait.
- **Satu GET panel gagal:** bagian halaman lain tetap tampil dengan error lokal.

## 6. Security, non-functional, dan usability

- Backend Laravel + Blade + JavaScript + MySQL, responsive mobile-first.
- Autentikasi session, CSRF same-origin, role + ownership checks; `mr_id` dan approver tidak dipercaya dari payload client.
- Data Actual, draft, foto, tanda tangan dan identitas MR lain tidak bocor melalui API konflik.
- Loading state, field-level validation, pesan error jelas, retry terkontrol.
- Server adalah sumber kebenaran untuk konflik/approval; front-end advisory validation bukan satu-satunya proteksi.
- Dapat dijalankan lokal reviewer dengan migrations + synthetic seed, langkah instalasi jelas.

## 7. Prioritas delivery

| Prioritas | Implementasi |
|---|---|
| **P0 wajib** | 8 endpoint PDF, endpoint pendukung edit/submit/master/queue, master seed, Call List/Approval, Plan, Actual Planned + Without Plan, 3 halaman, 5 test manual, README |
| **P1** | Session persisten, resume, save draft, recovery, cancel Plan, schedule conflict UX, tests tambahan |
| **P2** | Reminder otomatis, GPS, offline sync, dashboard tambahan, deployment |

**P1 tidak boleh menghalangi jalur Direct Actual P0.**

## 8. Acceptance criteria dan bukti

1. MR membuat List, Submit, DM direct manager Approve, MR Plan, MR Actual → sukses.
2. Plan dari List belum Approved → error.
3. Dua Actual dokter/tanggal sama walaupun mode atau MR berbeda → hanya satu boleh tersimpan.
4. Actual tanpa foto → error.
5. RSM/MM mencoba approve List milik MR → ditolak.
6. Direct API dengan `plan_id=null`, dokter MCL, bukti lengkap → diterima.
7. Frontend 3 halaman terintegrasi dengan API, loading/error dan responsif.
8. Bukti tes manual berupa screenshot/screen recording dan hasil expected-vs-actual.

**Catatan:** Hasil uji di atas adalah skenario rencana, bukan klaim PASS. `DESIGN.md` menjadi spesifikasi teknis induk; `TEST_PLAN.md` menjadi sumber matriks uji; README aplikasi baru dapat dinyatakan final setelah implementasi.
