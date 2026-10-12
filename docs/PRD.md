# PRD - Mobile Sales Force (MSF)

> **Versi:** 1.8 (Konsolidasi Peran Manajerial, Hierarki Wilayah, dan Tata Kelola Kunjungan)
> **Status:** Spesifikasi produk yang sudah disesuaikan dengan implementasi; automated test terakhir 65 passed, 239 assertions.
> **Sumber utama:** Dokumen studi kasus MSF PT Mersifarma dan dokumen konsolidasi `MSF_Change_Impact_v1.8_Proposal.md`.
> **Revisi yang digabung:** PRD v1.2/v1.3, Logic Flow v1.4, Operational UX v1.5, UI Label v1.6, Operational Correction v1.7, Consolidation Proposal v1.8.

## 1. Ringkasan produk

MSF adalah aplikasi web mobile-first untuk Medical Representative (MR) mencatat target dokter bulanan, merencanakan kunjungan, dan menyampaikan realisasi kunjungan dengan bukti foto, tanda tangan, serta produk yang didetailkan. Manager menyetujui atau menolak **Call List** bawahan langsungnya. Approval tidak boleh melompat role atau melewati atasan yang ditugaskan.

**Pembagian Peran:**
1. **MM:** Menentukan fokus produk dan target spesialisasi kampanye 3 bulanan (bukan kuota MR individu), serta menyetujui Call List manajerial RSM.
2. **RSM:** Menerjemahkan fokus produk menjadi arahan dokter prioritas regional, serta menyetujui Call List manajerial DM.
3. **DM:** Menetapkan arahan dokter prioritas bulanan tingkat distrik sebagai arahan non-eksklusif (bukan kuota kaku), serta menyetujui Call List MR bawahannya.
4. **MR:** Memilih dokter target dari MCL secara mandiri, menentukan jumlah targetnya sendiri, menyusun jadwal kunjungan (Call Plan), dan mengeksekusi kunjungan (Call Actual).

**Tiga fitur/domain inti:** Call List, Call Plan, Call Actual. **Tiga halaman utama di UI:** Target Dokter (Call List Bulanan), Jadwal Kunjungan (Call Plan), Pelaksanaan Kunjungan (Call Actual). Tidak perlu membuat dashboard terpisah atau modul reporting berlebih untuk memenuhi studi kasus.

## 2. Aktor dan wewenang

| Role | Aktivitas | Larangan |
|---|---|---|
| MR | Buat/edit/submit Call List mandiri, buat Plan, catat Actual milik sendiri | Approve List sendiri, ubah Actual milik MR lain |
| DM | Tetapkan arahan prioritas distrik; approve/reject Call List MR **bawahan langsung**; pantau riwayat actual tim | Approve List MR di luar bawahan; membatasi kuota MR secara kaku |
| RSM | Tetapkan arahan prioritas regional; approve/reject Call List DM **bawahan langsung**; pantau actual regional | Approve langsung List MR; melewati jenjang DM |
| MM | Tetapkan kampanye produk 3 bulanan; approve/reject Call List RSM **bawahan langsung**; pantau cakupan nasional | Approve langsung List MR/DM; intervensi kuota harian |

**Prinsip Invariant Approval:** Approval Call List tetap mutlak **Satu Tingkat Langsung (Single-Level)**: MR -> DM, DM -> RSM, RSM -> MM. Tidak pernah ada rantai persetujuan 3 tingkat untuk satu Call List milik MR. Begitu DM menyetujui Call List MR, MR dapat langsung membuat Call Plan tanpa menunggu keputusan RSM atau MM.

**Asumsi Call List Manajerial:** Call List yang diajukan DM ke RSM atau RSM ke MM berfungsi sebagai ringkasan target wilayah bulanan untuk mendemonstrasikan otorisasi berjenjang studi kasus, bukan daftar dokter yang akan dikunjungi secara pribadi oleh manajer. Manager terhubung melalui `users.manager_id`, bukan hanya pengecekan string `role`.

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

**Representasi status desain:** database menyimpan state aktif `draft`, `pending_approval`, `approved`, sedangkan status/transisi `submitted` dan `rejected` disimpan sebagai event log. Ini **asumsi representasi**, bukan persis lima nilai status tersimpan yang ditulis di PDF. UI menampilkan *Perlu Revisi â€” Ditolak* berdasarkan riwayat setelah reject.

### 3.2 Call Plan (P0)

- `CP-01`: Hanya MR membuat Plan dari Call List **Approved** miliknya.
- `CP-02`: Dokter harus merupakan anggota Call List tersebut.
- `CP-03`: Tanggal Plan wajib berada dalam bulan Call List.
- `CP-04`: Satu dokter paling banyak memiliki satu Plan per tanggal, dicek **global lintas MR** (interpretasi konservatif aturan PDF); hanya Plan Cancelled yang tidak memblokir pembuatan Plan baru.
- `CP-05`: Tolak Plan jika sudah ada Actual final untuk dokter/tanggal sama.
- `CP-06`: Status Planned: Scheduled; menjadi Completed ketika Actual terkait selesai; Cancelled dengan alasan melalui fitur pendukung (P1).
- `CP-07`: Plan yang terhalang Actual lain **tidak otomatis Completed**; UI boleh menampilkan *Tidak Dapat Direalisasikan* sebagai indikator turunan.
- `CP-08`: Plan berstatus `scheduled` yang melewati akhir hari (Asia/Jakarta) tanpa record Actual otomatis bertransisi menjadi `expired` (Tidak Terlaksana) melalui scheduled job. Dokter yang plan-nya expired tetap tercatat di histori dan dapat dijadwalkan kembali pada tanggal lain dalam bulan yang sama jika masih ada di Call List Approved.
- `CP-09`: Planned Actual mencocokkan tanggal yang sama persis dengan tanggal Plan (`visit_date == plan_date`). Jika tanggal kunjungan bergeser, MR menjadwalkan ulang Plan atau menggunakan jalur Kunjungan di Luar Rencana (`plan_id = null`).

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
- `CA-13`: Produk yang dicatat pada Actual adalah produk yang benar-benar dipresentasikan MR saat visit (minimal 1 produk). Rekomendasi produk berbasis spesialisasi dokter merupakan arahan non-blokir di UI dan tidak boleh otomatis dicatat sebagai detailing tanpa pilihan MR.

**Asumsi tambahan yang harus dicantumkan README:** Planned Actual mencocokkan tanggal yang sama dengan Plan. Jika jadwal bergeser, MR membatalkan Plan lama dengan alasan atau membuat Plan baru; jika mendesak, MR menggunakan jalur `plan_id = null`. Timestamp tidak boleh berada di masa depan. Jalur `plan_id = null` selalu legal dan tidak mengharuskan adanya Approved List.

## 4. UX yang disetujui (v1.5; label v1.6, operational correction v1.7)

### 4.0 Terminologi antarmuka dan keterlacakan ke PDF (v1.7)

**Keputusan:** label menu dan judul halaman menggunakan bahasa Indonesia yang mudah dimengerti MR. **Istilah asli PDF tampil sebagai subtitle pada heading halaman**, bukan wajib di setiap item navigasi. Nama domain/API/database tidak diubah.

| Modul pada PDF / backend | Label menu / heading utama UI | Subtitle pada heading halaman | Tujuan pengguna |
|---|---|---|---|
| Call List | **Target Dokter** | **Call List Bulanan** | Menentukan dan mengajukan target bulanan |
| Call Plan | **Jadwal Kunjungan** | **Call Plan** | Menyusun jadwal kunjungan dokter |
| Call Actual | **Pelaksanaan Kunjungan** | **Call Actual** | Memulai, mengakhiri, dan mencatat hasil kunjungan |

**Konsistensi label aksi:**

| Tindakan domain | Teks tombol/menu UI |
|---|---|
| Save Draft Call List | Simpan Draft |
| Submit / Resubmit Call List | Ajukan Target / Ajukan Ulang |
| Approve / Reject | Setujui / Tolak |
| Create Call Plan | Buat Jadwal |
| Start Visit / Check-in (**P1 Session saja**) | Mulai Kunjungan |
| Check-out (**P1 Session saja**) | Akhiri Kunjungan |
| Open Direct/Manual Actual form (**P0**) | **Isi Laporan Kunjungan** |
| Manual time inputs (**P0**) | **Waktu Mulai** dan **Waktu Selesai** |
| Finalize Call Actual (**P0/P1**) | **Simpan Laporan** |
| Without Plan entry | Kunjungan di Luar Rencana |
| Resume Session (P1) | Lanjutkan Kunjungan |
| Manual Actual | Catat Kunjungan Manual |

**Konsistensi status:**

| State/event internal | Label UI |
|---|---|
| `draft` | Draft |
| `pending_approval` | Menunggu Persetujuan |
| `approved` | Disetujui |
| `draft` dengan histori rejection dan belum submit ulang | Perlu Revisi (tampilkan alasan penolakan terbaru) |
| `scheduled` | Terjadwal |
| `in_progress` | Sedang Berlangsung |
| `completed` | Selesai |
| `cancelled` | Dibatalkan |
| `expired` | Tidak Terlaksana |
| *derived* (dokter di Approved List tanpa Plan aktif) | Belum Dijadwalkan |

**Aturan tampilan:**

- **Navigasi desktop:** pakai nama Indonesia lengkap (Target Dokter, Jadwal Kunjungan, Pelaksanaan Kunjungan); subtitle di menu tidak wajib.
- **Navigasi mobile sempit:** boleh ringkas menjadi Target, Jadwal, Kunjungan bila ruang terbatas; sediakan accessible label / tooltip dengan nama lengkap. Jangan mengganti nama halaman.
- **Heading masing-masing halaman:** tampilkan nama Indonesia lengkap dengan subtitle kecil terminologi PDF (Call List Bulanan, Call Plan, Call Actual). Di mobile, subtitle tetap tersedia pada heading halaman, **tidak perlu pada setiap item navigasi**.
- Gunakan istilah aksi, form, alert, empty state, dan notifikasi secara konsisten. Status teknis tetap disimpan dengan nilai aslinya.
- Jangan tampilkan nama kolom (`plan_id`, `visit_session_id`, `mr_id`) atau kode error mentah kepada MR; backend/API dan dokumentasi teknis tetap menggunakan nama asli.
- **P0 (tanpa Session persisten):** CTA dari Jadwal Hari Ini adalah **Isi Laporan Kunjungan**. MR memasukkan **Waktu Mulai** dan **Waktu Selesai**, foto, TTD, produk, lalu menekan **Simpan Laporan**. Tidak ada tombol Mulai/Akhiri Kunjungan yang berpura-pura merekam timestamp ke server; formulir In Progress adalah **state lokal UI**, bukan status Session database.
- **P1 (dengan Session persisten):** CTA **Mulai Kunjungan** benar-benar melakukan Check-in server; **Akhiri Kunjungan** benar-benar menyimpan Check-out server; **Simpan Laporan** membuat Actual final secara terpisah. Check-out saja **belum** membuat Call Actual final atau menandai Plan Completed.
- **Call List derived status:** jika status aktif `approved`, tampilkan **Disetujui**; jika `pending_approval`, tampilkan **Menunggu Persetujuan** meskipun ada rejection lama; jika `draft`, tampilkan **Perlu Revisi** hanya bila pernah direject dan belum disubmit ulang, selain itu **Draft**. Alasan diambil dari keputusan penolakan terbaru yang relevan. Jangan menampilkan Perlu Revisi berdasarkan histori lama saat sudah pending/approved.
- Klasifikasi Planned/Unplanned/Non Target tetap ditentukan sistem, tidak perlu menjadi pilihan kategori untuk MR.
- Penamaan modul ini adalah **keputusan UX**, bukan perubahan requirement PDF atau API contract.

### Call List
Pilih bulan â†’ pilih dokter â†’ Draft â†’ Submit â†’ direct manager Approve/Reject â†’ jika rejected, revisi dan resubmit.

### Call Plan
Pilih Approved List â†’ dokter yang termasuk List â†’ tanggal/jam â†’ simpan. Pesan duplikat harus mudah dipahami.

### Call Actual: satu halaman, dua opsi

1. Tampilkan **Kunjungan Sedang Berlangsung** jika ada (P1).
2. Tampilkan **Jadwal Hari Ini** dengan tombol **Isi Laporan Kunjungan** untuk P0, atau **Mulai Kunjungan** untuk P1 saat Check-in server benar-benar diimplementasikan.
3. Tampilkan opsi sekunder **Kunjungan di Luar Rencana**; pilih dokter MCL, sistem merekomendasikan Plan jika relevan, dan minta alasan melalui UI.
4. **P0:** Isi Laporan Kunjungan â†’ Waktu Mulai/Selesai manual â†’ Foto + TTD + Produk â†’ Simpan Laporan. **P1:** Mulai (server Check-in) â†’ Foto + TTD + Produk â†’ Akhiri (server Check-out) â†’ Simpan Laporan.
5. Tampilkan **Riwayat Selesai**.

Sistem menentukan Planned/Unplanned/Non Target; MR tidak memilih nama klasifikasi. Field ID dan istilah `visit_session_id` tidak tampak kepada pengguna. **P0:** In Progress â†’ Completed dapat berupa state form lokal dengan input waktu; **P1:** Session persisten memakai timestamp server dan dapat dilanjutkan.

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

1. MR membuat List, Submit, DM direct manager Approve, MR Plan, MR Actual â†’ sukses.
2. Plan dari List belum Approved â†’ error.
3. Dua Actual dokter/tanggal sama walaupun mode atau MR berbeda â†’ hanya satu boleh tersimpan.
4. Actual tanpa foto â†’ error.
5. RSM/MM mencoba approve List milik MR â†’ ditolak.
6. Direct API dengan `plan_id=null`, dokter MCL, bukti lengkap â†’ diterima.
7. Frontend 3 halaman terintegrasi dengan API, loading/error dan responsif; label navigasi Indonesia dan subtitle terminologi PDF pada judul masing-masing halaman. P0 memakai waktu manual dan tidak menampilkan aksi Check-in/Check-out server palsu.
8. Bukti tes manual berupa screenshot/screen recording dan hasil expected-vs-actual.

9. Label `Perlu Revisi` hanya muncul untuk Draft yang telah direject dan belum disubmit ulang; setelah resubmit status Menunggu Persetujuan, setelah approval Disetujui.

**Catatan:** Hasil uji di atas adalah skenario rencana, bukan klaim PASS. `DESIGN.md` menjadi spesifikasi teknis induk; `TEST_PLAN.md` menjadi sumber matriks uji; README aplikasi baru dapat dinyatakan final setelah implementasi.
