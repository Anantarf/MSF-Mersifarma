# MSF Mersifarma — Business Scenario & Seeder Blueprint (Pediatri)

> **Status:** Referensi data demo/seeder, **bukan** perintah perubahan schema atau implementasi. 12 Oktober 2026.
> **Acuan:** `DESIGN.md` v1.8.2 (target TO-BE). `DESIGN.md` v1.5/v1.6 yang pernah dilampirkan lebih lama. P0 PDF tetap prioritas; skema Regional→Distrik, fokus RSM, prioritas DM, Top List adalah P1 bila belum tersedia pada database.
> **Semua nama orang, dokter, fasilitas dan merek produk di sini fiktif** dan tidak mewakili informasi produk/indikasi yang disetujui untuk promosi nyata. Jangan memasukkan pasien atau data pribadi sebenarnya.

## A. Makna bisnis

MSF mencatat aktivitas profesional medical representative (MR), bukan resep pasien, omset, pembelian obat, ataupun data stok. Tiga proses operasional wajib: **Call List (Target Dokter) → Call Plan (Jadwal Kunjungan) → Call Actual (Pelaksanaan Kunjungan)**. Daftar dokter berasal dari satu MCL; identitas dokter tidak digandakan antar MR/distrik.

**Hierarki:** Budi Santoso (MM nasional) → Siti Rahma (RSM DKI Jakarta) → Joko Widodo (DM Jakarta Selatan) dan Hendra Gunawan (DM Jakarta Timur) → Rudi Hartono dan Ahmad Dahlan (MR Jaksel, bawahan Joko), serta Nadia Putri (MR Jaktim, bawahan Hendra).

**Alur strategis P1 yang disepakati:** RSM mengajukan fokus produk + spesialisasi + masa berlaku berikut snapshot kandidat dokter MCL regional, MM menyetujui List/fokus RSM, DM memilih nama dokter prioritas dari distrik sendiri dan mengajukan kepada RSM, RSM menyetujui List prioritas DM, barulah Top List muncul pada picker MR distrik terkait. Approval setiap pengajuan **satu tingkat direct manager**; ini **bukan** satu Call List MR yang harus disetujui tiga manajer secara berantai. MR tetap boleh memilih dokter lain dari MCL, termasuk di luar Top List, dan tetap dapat beroperasi melalui jalur P0 tanpa campaign P1.

## B. Narasi akhir-ke-akhir

Pada Oktober 2026, Siti mengusulkan program informasi produk pediatri untuk spesialisasi `Spesialis Anak` di DKI Jakarta, aktif 1 Oktober sampai 31 Desember. Produk fokus fiktif: `PediaTemp Sirup` dan `PediaHydra`. Budi mengevaluasi dan menyetujui Call List manajerial RSM, yang berisi snapshot seluruh kandidat dokter Sp.A di regionalnya.

Joko kemudian memilih tiga dokter Sp.A dari Jakarta Selatan: dr. Andi Pratama, Sp.A.; dr. Rina Maharani, Sp.A.; dr. Maya Putri, Sp.A. Siti menyetujui List prioritas Joko. Hendra memilih dr. Putri Lestari, Sp.A., dan dr. Bima Saputra, Sp.A., di Jakarta Timur, lalu Siti menyetujuinya. Prioritas yang sudah approved dan sedang berlaku muncul sebagai Top List menurut distrik MR, tetapi tidak otomatis mengubah Call List MR yang sudah approved.

Rudi (MR Jaksel) memilih Andi, Rina, dan Fajar untuk Call List Oktober. Ahmad (MR Jaksel) memilih Maya, Andi, dan Tania. Nadia (MR Jaktim) memilih Putri dan Bima. Masing-masing mengirim Call List bulanan kepada DM langsung, yang dapat menyetujui/menolak secara mandiri. Setelah Approved, hanya MR yang boleh membuat Call Plan dari daftar doktornya. Manager List bukan sumber Call Plan.

Pada 8 Oktober, Rudi mengunjungi Andi sesuai Plan dan mencatat PediaTemp Sirup. Pada 9 Oktober, Ahmad mengunjungi Maya sesuai Plan dan mencatat PediaZinc, yang **bukan** produk fokus. Rudi menemui Fajar pada 10 Oktober tanpa Plan tetapi Fajar ada dalam Call List Approved Rudi sehingga diklasifikasikan **Unplanned**. Pada 11 Oktober, Rudi menemui Tania tanpa Plan; Tania memang ada di MCL tetapi tidak ada dalam Call List Rudi sehingga diklasifikasikan **Non Target**, walaupun Tania berada di Call List Ahmad. Semua Actual membutuhkan foto, tanda tangan, waktu valid, dan minimal satu produk benar-benar dibahas.

Pada monitoring Joko untuk contoh ini, ada **3 dokter prioritas**, **2 dokter prioritas unik dikunjungi** (Andi dan Maya), serta **1 dokter prioritas dengan detailing produk fokus** (Andi). Rina yang baru Scheduled 16 Oktober belum dianggap visited. Fokus dan prioritas bukan otomatis sukses hanya karena Approved, dipilih dalam List, atau dijadwalkan.

## C. Master seed — identitas stabil

### Pengguna

| Key stabil    | Nama           | Role | Manager       | District (P1)          |
| ------------- | -------------- | ---- | ------------- | ---------------------- |
| `MM_BUDI`   | Budi Santoso   | mm   | —            | Nasional               |
| `RSM_SITI`  | Siti Rahma     | rsm  | `MM_BUDI`   | DKI Jakarta (regional) |
| `DM_JOKO`   | Joko Widodo    | dm   | `RSM_SITI`  | Jakarta Selatan        |
| `DM_HENDRA` | Hendra Gunawan | dm   | `RSM_SITI`  | Jakarta Timur          |
| `MR_RUDI`   | Rudi Hartono   | mr   | `DM_JOKO`   | Jakarta Selatan        |
| `MR_AHMAD`  | Ahmad Dahlan   | mr   | `DM_JOKO`   | Jakarta Selatan        |
| `MR_NADIA`  | Nadia Putri    | mr   | `DM_HENDRA` | Jakarta Timur          |

### Master dokter / MCL

| Key stabil    | Nama                      | Spesialisasi   | Distrik (P1)    |
| ------------- | ------------------------- | -------------- | --------------- |
| `DOC_ANDI`  | dr. Andi Pratama, Sp.A.   | Spesialis Anak | Jakarta Selatan |
| `DOC_RINA`  | dr. Rina Maharani, Sp.A.  | Spesialis Anak | Jakarta Selatan |
| `DOC_MAYA`  | dr. Maya Putri, Sp.A.     | Spesialis Anak | Jakarta Selatan |
| `DOC_FAJAR` | dr. Fajar Nugroho, Sp.PD. | Penyakit Dalam | Jakarta Selatan |
| `DOC_TANIA` | dr. Tania Kusuma          | Dokter Umum    | Jakarta Selatan |
| `DOC_PUTRI` | dr. Putri Lestari, Sp.A.  | Spesialis Anak | Jakarta Timur   |
| `DOC_BIMA`  | dr. Bima Saputra, Sp.A.   | Spesialis Anak | Jakarta Timur   |

Gunakan satu nilai spesialisasi yang konsisten dengan master dan filter UI (`Spesialis Anak`, jangan sebagian disimpan sebagai `Pediatri` bila filter exact-match). Fasilitas/alamat/telepon dapat berupa data sintetis; jangan membuat record dokter terpisah untuk tiap MR.

### Katalog produk (semuanya fiktif)

| Key stabil     | Nama             | Kategori simulasi         | Produk fokus? |
| -------------- | ---------------- | ------------------------- | ------------- |
| `PROD_TEMP`  | PediaTemp Sirup  | Pediatri / antipiretik    | Ya            |
| `PROD_HYDRA` | PediaHydra       | Pediatri / rehidrasi oral | Ya            |
| `PROD_ZINC`  | PediaZinc        | Pediatri / suplementasi   | Tidak         |
| `PROD_VITA`  | VitaCare General | Umum / suplemen           | Tidak         |

Jangan seed dosis, klaim efektivitas, atau indikasi promosi yang belum diverifikasi; nama kategori hanya label pengujian alur, bukan edukasi medis.

## D. Pengajuan manajerial (P1 apabila schema mendukung)

| Owner  | Periode`period_month` | Dokter snapshot Call List manajerial | Fokus/Status                                                       | Approver langsung |
| ------ | ----------------------- | ------------------------------------ | ------------------------------------------------------------------ | ----------------- |
| Siti   | `2026-10-01`          | Andi, Rina, Maya, Putri, Bima        | Fokus 2 produk, Sp.A, efektif 2026-10-01 s.d. 2026-12-31; Approved | Budi              |
| Joko   | `2026-10-01`          | Andi, Rina, Maya                     | Prioritas Jaksel; Approved                                         | Siti              |
| Hendra | `2026-10-01`          | Putri, Bima                          | Prioritas Jaktim; Approved                                         | Siti              |

Ini merupakan **tiga Call List berbeda**, bukan satu list yang melewati tiga approval. Objek strategi P1 tidak boleh dimasukkan ke tabel/kolom fiktif jika migration belum ada. Pada P0, seeder boleh membuat List manajerial sederhana untuk membuktikan pasangan approval DM→RSM dan RSM→MM tanpa mengklaim program fokus otomatis berfungsi.

## E. Call List MR bulanan (P0)

| Owner | Bulan         | Dokter dalam List | Status               | Approver                                         |
| ----- | ------------- | ----------------- | -------------------- | ------------------------------------------------ |
| Rudi  | Oktober 2026  | Andi, Rina, Fajar | Approved             | Joko                                             |
| Ahmad | Oktober 2026  | Maya, Andi, Tania | Approved             | Joko                                             |
| Nadia | Oktober 2026  | Putri, Bima       | Approved             | Hendra                                           |
| Rudi  | November 2026 | Rina, Maya        | Draft                | Joko (saat submit)                               |
| Ahmad | November 2026 | Andi, Maya        | Pending Approval     | Joko                                             |
| Nadia | November 2026 | Putri, Bima       | Draft setelah Reject | Hendra; wajib ada log`rejected` beserta alasan |

Database baseline hanya mengizinkan satu Call List untuk setiap owner per bulan. Status persistent draft/pending_approval/approved; **Rejected ditunjukkan sebagai Draft dengan event rejection pada approval log**, bukan status DB `rejected` permanen. Jangan mengisi `approved_at` untuk Draft/Pending.

## F. Plan dan Actual terhubung (P0)

| MR    | Tanggal & waktu WIB     | Dokter | Call Plan    | Call Actual                        | Produk pada Actual |
| ----- | ----------------------- | ------ | ------------ | ---------------------------------- | ------------------ |
| Rudi  | 2026-10-08 09:00–09:30 | Andi   | Completed    | Planned, final                     | PediaTemp Sirup    |
| Ahmad | 2026-10-09 10:00–10:25 | Maya   | Completed    | Planned, final                     | PediaZinc          |
| Rudi  | 2026-10-10 11:00–11:30 | Fajar  | Tidak dibuat | Unplanned, final,`plan_id=null`  | VitaCare General   |
| Rudi  | 2026-10-11 11:30–12:00 | Tania  | Tidak dibuat | Non Target, final,`plan_id=null` | VitaCare General   |
| Rudi  | 2026-10-16 10:00        | Rina   | Scheduled    | Tidak ada                          | —                 |
| Ahmad | 2026-10-16 14:00        | Andi   | Scheduled    | Tidak ada                          | —                 |
| Nadia | 2026-10-17 09:00        | Putri  | Scheduled    | Tidak ada                          | —                 |

Setiap Actual memiliki timestamp check-in < check-out, evidence foto + tanda tangan sintetis yang dapat dibuka aplikasi, serta >=1 produk. Planned selalu mereferensikan Plan dan dokter yang sama. Tidak ada duplikasi Actual `doctor_id + visit_date` secara global; `plan_id=null` untuk Unplanned/Non Target. Plan yang selesai hanya Plan yang benar-benar direferensikan oleh Actual final. Gunakan tanggal/waktu lokal Asia/Jakarta secara konsisten.

## G. Quality gate dan implementasi seeder

**P0 dapat di-seed sekarang** selama mengikuti migrations repository: roles, users/hierarchy, MCL, products, Call Lists MR/manajerial sederhana beserta approval logs, Plans, Actuals, pivots. `migrate:fresh --seed` harus konsisten; relasikan menggunakan ID hasil query alias natural/stable (mis. email/kode), bukan ID numerik acak. Idempotensi dengan key unik (`updateOrCreate`/`firstOrCreate`) hanya bila cocok dengan constraint sebenarnya. Hindari data demo yang melanggar FK, duplicate dokter/tanggal, status, maupun tanggal di masa depan untuk Actual final. Seeder harus eksplisit `DemoScenarioSeeder` dan benar-benar dipanggil `DatabaseSeeder`, bukan class kosong atau komentar.

**P1 hanya jika migrations dan flow nyata telah tersedia:** region/district FK, metadata fokus produk, spesialisasi, periode berlaku, Call List prioritas DM, Top List, KPI lintas scope. Jangan membuat product campaign palsu dengan mengarang kolom pada schema lama. Jangan mengubah backend cuma demi seeder tanpa persetujuan.

**Visit Session saat demo:** jangan seeding session in_progress historis yang belum selesai. Jika perlu satu sesi aktif untuk demo UI, buat fixture khusus berdasarkan tanggal demo atau gunakan flow aplikasi nyata di hari demo; jangan campur ke seed deterministik historis. Jangan membuat state sesi Completed tanpa menjaga konsistensi Actual-nya. UI memang boleh memakai Session server dan jalur Manual/Direct secara paralel bila kode mendukung.

**Kasus negatif:** `Plan from unapproved list`, duplicate Actual dokter-tanggal, Actual tanpa foto/tanda tangan/produk, serta approval oleh atasan yang tidak berwenang **tidak diinsert sebagai seed valid**; jalankan sebagai feature test/request yang harus ditolak.

**Monitoring yang benar:** Jakarta Selatan punya prioritas Andi/Rina/Maya (3 dokter), yang punya Actual valid adalah Andi/Maya (2), dan yang memiliki detailing produk fokus dalam Actual adalah Andi (1). `2/3` dan `1/3` adalah angka ilustrasi untuk P1, bukan KPI aktif bila backend belum punya data dan query prioritas.

**Data privacy:** Semua record demo sintetis; jangan masukkan nama dokter nyata, data pasien, foto tanda tangan asli, atau materi promosi/klaim produk farmasi yang belum disetujui. Batasi bukti foto/TTD melalui access control seperti data Actual lainnya.
