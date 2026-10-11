# MSF — Dokumen Konsolidasi Perubahan Alur Bisnis

**Status:** Proposal perubahan v1.8 — untuk review, **belum** menjadi baseline implementasi  
**Tanggal:** 11 Oktober 2026  
**Acuan:** PDF studi kasus *Mobile Sales Force (MSF)* Mersifarma dan paket dokumen desain v1.7 (`DESIGN.md`, `PRD.md`, `TDD.md`, `API_CONTRACT.md`, `DATABASE_SCHEMA.md`, `TEST_PLAN.md`, `DECISIONS.md`, `IMPLEMENTATION_PLAN.md`, `AGENTS.md`)  
**Tujuan:** Menyatukan hasil diskusi mengenai fungsi MM/RSM/DM/MR, prioritas dokter dan produk, hierarki wilayah MCL, perubahan Call Plan, pengalihan kunjungan, serta dampak terhadap desain lama.

> **PENTING:** Dokumen ini adalah *change proposal*, bukan bukti fitur sudah diimplementasikan. Aturan PDF tetap menjadi persyaratan utama. Usulan baru yang lebih ketat atau memperluas sistem harus ditandai sebagai asumsi dan dijelaskan di README. Jangan meminta AI IDE mengimplementasikan seluruh usulan P1/P2 sebelum P0 terbukti berjalan.

## 1. Ringkasan keputusan

Alur bisnis yang diusulkan:

**MM menentukan fokus produk selama tiga bulan → RSM menerjemahkan fokus menjadi arahan dokter prioritas regional/distrik → DM mengumumkan prioritas dokter setiap bulan → MR memilih dokter dan kuantitas target bulanan sendiri → DM approve Call List MR → MR membuat Call Plan → MR melakukan Call Actual → manajemen memantau hasil detailing.**

Aturan tambahan yang disepakati sebagai desain usulan:

1. MCL tetap **satu master data dokter** dengan hierarki **Regional → Distrik → Area**, bukan tabel MCL terpisah per wilayah.
2. Prioritas dokter dari DM adalah **arahan, bukan daftar eksklusif**; MR tetap dapat memilih dokter MCL lain untuk Call List.
3. Pengajuan MR tetap **Call List bulanan**. Arahan produk dan dokter selama tiga bulan tidak menggantikannya.
4. **Approval bersifat satu level per pengaju**, bukan tiga approval berantai untuk satu Call List MR.
5. **Planned Actual tidak boleh diam-diam menggeser dokter/tanggal Plan** menurut asumsi UX terbaru. Dokter/tanggal berbeda memerlukan Plan baru atau jalur Without Plan sesuai aturan PDF.
6. Ada jalur **Kunjungan di Luar Rencana** untuk dokter MCL, dengan filter distrik sebagai default pencarian, bukan larangan mutlak lintas wilayah.
7. Pengalihan Call Plan antar-MR **hanya dalam distrik dan di bawah DM yang sama**: MR asal meminta, MR pengganti menerima, tanpa approval DM tambahan; DM mendapatkan notifikasi/audit trail.
8. Jika tanggal Plan selesai tanpa Actual, Plan menjadi **Expired / Tidak Terlaksana**, bukan Completed atau Cancelled. Dokter dapat dijadwalkan lagi pada tanggal yang memenuhi syarat, dan histori tetap ada.
9. Produk di Call Actual adalah **produk yang benar-benar didetailkan**, minimal satu. Rekomendasi berdasarkan spesialisasi merupakan arahan yang tidak boleh otomatis dicatat sebagai detailing.

## 2. Batasan yang tetap wajib menurut PDF

Bagian ini **tidak boleh dihapus atau diganti** oleh asumsi baru:

- MR membuat **Call List untuk satu bulan** dengan memilih dokter dari Master Customer List (MCL), mengajukan, dan memperoleh approval langsung dari DM.
- Ketiga pasangan otorisasi **harus dapat didemonstrasikan**: MR → DM, DM → RSM, RSM → MM. Pengajuan ditolak hanya dapat direvisi dan diajukan ulang kepada atasan langsung yang sama.
- Call Plan MR hanya boleh bersumber dari Call List **Approved**, dokter harus sesuai daftar, tanggal dalam bulan Call List, dan maksimum satu Plan aktif per dokter/tanggal.
- Call Actual: `plan_id` boleh `null`; jika diisi, `doctor_id` harus sama dengan Plan; jika `null`, dokter harus tercatat di MCL. Maksimum **satu Actual per dokter per hari secara global**, lintas MR/mode.
- Bukti foto, tanda tangan, dan minimal **satu produk** wajib pada Actual; produk merepresentasikan detailing yang terjadi.
- Tetap sediakan **8 endpoint wajib PDF**, **3 halaman** Call List/Plan/Actual yang responsif, dan **5 skenario pengujian manual wajib** berikut bukti pengujian aktual.
- PDF menyatakan data Actual relevan untuk pelaporan/insentif, **tetapi tidak menetapkan formula insentif, kuota per MR, maupun bukti peningkatan distribusi atau penjualan obat**. Jangan mengarang aturan tersebut.

**Catatan kepatuhan:** PDF tidak mewajibkan `visit_date == plan_date`, tidak mendeskripsikan handover Plan lintas MR, dan tidak mendefinisikan isi Call List manajerial DM/RSM. Ketiganya adalah **asumsi tambahan** yang perlu dibatasi, didokumentasikan, dan diuji bila jadi diimplementasikan.

## 3. Perbandingan desain v1.7 vs proposal baru

| Area | Baseline v1.7 | Usulan v1.8 | Dampak / prioritas |
|---|---|---|---|
| Fungsi DM/RSM | DM/RSM mengajukan Call List pribadi sebagai asumsi teknis untuk menguji semua approval | DM/RSM berfungsi sebagai pengelola target **distrik/regional**, bukan pelaksana visit | Perlu definisi semantik Call List manajerial; **P1** |
| MM | Terutama approver pengajuan RSM | Menetapkan arah kampanye produk tiga bulanan dan mengevaluasi regional, **serta tetap approve RSM** | Penambahan use case, **P1/P2** |
| Wilayah dokter | MCL dasar tanpa relasi distrik/area yang formal | MCL pusat diberi hierarki Regional–Distrik–Area | Perubahan skema/filter, **P1** |
| Perencanaan bulanan | MR memilih dokter dari MCL, DM approve | DM memberi **prioritas** bulan ini; MR tetap memilih sendiri dokter dan jumlah target; DM approve | Bisa diawali copy/UI/seed, **tidak perlu memblokir P0** |
| Produk | Master product; minimal 1 di Actual | MM memberi produk fokus dan rekomendasi berdasarkan spesialisasi dokter | Mapping/UX baru, **P1/P2** |
| Planned Actual beda tanggal | Baseline mengizinkan deviasi tanggal di bulan sama dengan `deviation_reason` | **Hapus deviasi** untuk Planned flow: bila tanggal berubah, jadwalkan ulang dulu | Aturan baru lebih ketat dari PDF; review kontrak, **P1** |
| Perubahan pelaksana | Plan hanya digunakan MR pembuatnya | MR dalam distrik/DM sama dapat menerima **handover** tanpa cancel/approval DM | Perubahan ownership/FK/audit, **P1 kompleks** |
| Plan lewat tanggal | Belum ada status expiry otomatis | Setelah akhir hari tanpa Actual → `expired`, tetap dalam histori | Scheduler dan indeks, **P1** |
| Kunjungan di luar rencana | Jalur Actual `plan_id=null`, klasifikasi Unplanned/Non Target | Button khusus, daftar MCL default distrik dan pencarian lebih luas | UX + filter, sebagian **P0 UI**, wilayah **P1** |
| Monitoring | Fokus status Lists, Plans, Actual | Dokter belum dijadwalkan, terjadwal, telah dikunjungi, tidak terlaksana; realisasi produk fokus | Derived reporting **P1/P2** |

## 4. Peran dan proses bisnis *to-be*

### 4.1 MM — Marketing Manager

- Menentukan **produk fokus dan sasaran spesialisasi** untuk kampanye tiga bulanan (contoh: Oktober–Desember).
- Menyampaikan arahan strategi ke RSM, bukan menetapkan kuota masing-masing MR.
- Menyetujui pengajuan Call List manajerial RSM sesuai pasangan approval PDF.
- Meninjau indikator cakupan dokter dan detailing produk fokus. **Jangan sebut peningkatan distribusi terukur** jika tidak ada data penjualan/stok/resep.

### 4.2 RSM — Regional Sales Manager

- Menilai MCL di regional, menentukan **prioritas dokter** yang relevan dengan kampanye MM, lalu berkoordinasi dengan para DM untuk cakupan distrik.
- Mengajukan **Call List/ringkasan regional bulanan** kepada MM sebagai **asumsi penjelas objek approval** (bukan daftar dokter yang secara pribadi akan dikunjungi RSM).
- Menyetujui pengajuan Call List/ringkasan bulanan milik DM yang berada di bawahnya.
- Memantau realisasi per distrik dan dokter fokus yang belum tercapai.

### 4.3 DM — District Manager

- Menerjemahkan arahan regional menjadi **dokter prioritas bulan berjalan** untuk satu distrik.
- **Tidak** menetapkan MR mana mengambil dokter apa atau jumlah target setiap MR; MR memilih dan mengajukan sendiri.
- Mengajukan **Call List/ringkasan prioritas distrik bulanan** kepada RSM sebagai asumsi objek approval manajerial.
- Menyetujui/menolak **Call List bulanan masing-masing MR** langsung di bawahnya, memberi alasan reject bila perlu.
- Memantau jadwal, pelaksanaan, handover dalam satu distrik, dan laporan dokter belum dikunjungi.

### 4.4 MR — Medical Representative

- Memilih dokter dari MCL (diprioritaskan penampilan distrik/area sendiri) dan menetapkan **jumlah dokter target bulanan sendiri**.
- Mengajukan Call List ke DM dan baru membuat Planned Call Plan setelah list Approved.
- Menjalankan visit, mencatat foto, tanda tangan, waktu, dan produk yang **benar-benar** didetailkan.
- Jika berhalangan, dapat meminta MR lain dalam **distrik dan DM yang sama** untuk menggantikan melalui mekanisme handover P1.

**Ilustrasi:** MM menetapkan kampanye Produk A selama tiga bulan. RSM memberi arahan 10 dokter prioritas untuk distrik Jakarta Selatan. DM menjadikan sebagian dokter itu prioritas Oktober dan sisanya prioritas November–Desember. Rudi dapat mengajukan 8 dokter, Ahmad 12 dokter, MR ketiga 10 dokter pada Oktober. DM menilai pengajuan masing-masing. Jumlah dokter prioritas **bukan kuota atau batas maksimum** MR.

## 5. Approval — desain yang tidak boleh menjadi rantai tiga level untuk MR

**Wajib dari PDF (langsung, masing-masing pengaju):**

| Dokumen pengaju | Pemberi keputusan langsung | Boleh dilanjutkan ke Plan MR? |
|---|---|---|
| Call List **MR Rudi** Oktober | DM Joko | **Ya, segera setelah Joko approve** |
| Call List **DM Joko** Oktober (*asumsi*: rangkuman prioritas distrik) | RSM Siti | **Tidak relevan sebagai approval tambahan untuk List Rudi** |
| Call List **RSM Siti** Oktober (*asumsi*: rangkuman prioritas regional) | MM Budi | **Tidak relevan sebagai approval tambahan untuk List Rudi** |

**Catatan desain yang perlu diselesaikan sebelum implementasi manajerial:** PDF meminta persetujuan DM→RSM dan RSM→MM, tetapi tidak mendefinisikan konten Call List DM/RSM. Kita mengusulkan ringkasan prioritas dokter **tetap bulanan**, meskipun strategi MM berjalan selama tiga bulan. Jangan mengubah status Call List MR yang sudah Approved ketika suatu ringkasan manajerial ditolak.

Approval DM tambahan **tidak diperlukan** ketika MR menggeser jadwal dalam bulan yang sah, melakukan handover yang diterima dalam distrik/DM yang sama, atau ketika Plan expired. Jika ada perubahan daftar dokter pada Call List Approved, butuh rancangan revisi/approval ulang yang terpisah; fitur ini **belum tersedia** pada baseline.

## 6. MCL dan hierarki wilayah

**Hierarki referensi:** `Nasional (MM) → Regional (RSM) → Distrik (DM) → Area (MR) → dokter MCL`.

- **Satu dokter = satu identitas MCL**, meskipun terdapat banyak pemakai/target; jangan menduplikasi dokter per MR/distrik.
- Satu master MCL dapat diberi wilayah utama (area, distrik, regional diturunkan dari hierarki).
- MR melihat dokter dalam distrik/area sebagai **filter default**; pencarian ke MCL lebih luas tetap tersedia untuk Non Target, agar tidak menambah pembatasan API yang tidak disebut PDF.
- DM melihat distrik; RSM melihat regional; MM melihat ringkasan cakupan nasional sesuai otorisasi.
- Kewenangan manajer untuk approve/handover **tetap memakai relasi atasan langsung** (`manager_id`) dan pemeriksaan distrik eksplisit; **jangan** menyimpulkan otorisasi dari kesamaan label regional saja.

**Pilihan skema P1:** satu tabel `territories(id,name,level,parent_id)` (`region`,`district`,`area`), tambah `territory_id` pada user/dokter (atau relasi setara). **Jangan hilangkan** field/konsep `region_id` users yang diminta PDF tanpa pemetaan kompatibilitas. Dalam MVP, wilayah dapat direpresentasikan secara sederhana pada seed/UI tanpa migrasi hierarki penuh.

## 7. Kampanye produk dan rekomendasi dokter

**Proses usulan:** MM menetapkan kampanye (produk fokus, periode tiga bulan, target spesialisasi) → RSM memilih cakupan dokter/regional → DM mengarahkan dokter fokus bulan berjalan → MR melaksanakan detailing → manajemen mengevaluasi hasil.

**Desain data opsional P1/P2:** `campaigns`, `campaign_products`, `product_specialties` (atau mapping setara). Jangan membuat relasi dokter–obat manual jika sebenarnya rekomendasi dapat diturunkan dari `master_customers.specialization` dan pemetaan produk–spesialisasi.

**Aturan wajib:**

- `Call Actual.products[]` minimal satu produk yang tercatat di master.
- Produk yang disimpan adalah **produk yang benar-benar dibahas** MR saat visit; **jangan auto-fill produk fokus** sebagai laporan detailing.
- Rekomendasi produk adalah **saran nonblokir**, bukan syarat agar Actual dianggap sah. MR dapat memilih produk master lain.
- Pemetaan produk–spesialisasi harus dikurasi dari materi pemasaran/indikasi yang telah disetujui, tidak ditebak dari gejala umum dan tidak diperlakukan sebagai rekomendasi pengobatan.
- KPI dari data MSF: dokter fokus terkunjungi dan detailing produk fokus; **bukan** bukti resep/penjualan/distribusi obat.

## 8. Call Plan: penjadwalan, pengalihan, dan expiry

### 8.1 Aturan Planned Visit yang diusulkan

- Plan hanya dibuat berdasarkan **Call List MR yang Approved**, sesuai dokter dan bulan yang sama.
- Dalam Planned flow, simpan Actual atas **dokter dan tanggal yang sama dengan Plan**; bila berubah, buat Plan baru atau gunakan jalur Without Plan jika memenuhi aturan PDF.
- **Ini pengetatan tambahan:** PDF hanya mewajibkan `doctor_id` cocok untuk `plan_id` non-null. Catat dengan jelas di README sebelum menjadikannya validasi backend permanen.
- Menjadwalkan ulang dalam bulan sama: batalkan Plan lama **dengan alasan** lalu buat Plan baru; **tidak perlu DM approve lagi** jika dokter tetap memenuhi syarat list Approved.
- Menjadwalkan ulang ke bulan baru: perlu Call List Approved **bulan baru**.
- Jangan menyamakan *pindah pelaksana* dengan *pindah jadwal*: handover yang diterima tidak membuat Plan baru atau membatalkan Plan lama.

### 8.2 Handover antar-MR — P1, bukan syarat P0

| Langkah | Aturan |
|---|---|
| Request | MR pemilik/penanggung jawab saat ini mengajukan pengalihan Plan ke satu MR lain |
| Eligibility | Kedua MR **dalam distrik dan di bawah DM langsung yang sama**; jangan lintas distrik walaupun satu regional |
| Pending | Plan tetap milik penanggung jawab semula dan tetap `scheduled` |
| Accept | Cek Plan masih aktif/belum di-visit/belum lewat; ubah `current_assignee_id` secara atomik, Plan ID tetap; simpan aktor/waktu/alasan |
| Reject | Tidak mengubah Plan; MR asal boleh meminta pengganti lain |
| Notification | DM diinformasikan otomatis lewat tampilan/notifikasi/audit; tidak ada approval DM tambahan |
| Actual | Ditulis oleh MR pelaksana yang menerima. Histori owner asli tetap jelas. Jangan catat hasil sebagai kunjungan yang dilakukan MR awal |
| Competing events | Hanya satu permintaan pending yang efektif per Plan; acceptance/expiry/Actual harus terkunci dan konsisten |

**Peringatan kompatibilitas besar:** v1.7 mempunyai FK komposit `call_plans(call_list_id,mr_id) → call_lists(id,owner_id)` dan `call_actuals(plan_id,mr_id,doctor_id) → call_plans(id,mr_id,doctor_id)`. Relasi ini **mengasumsikan MR Plan, pemilik List, dan pelaksana Actual adalah satu orang**. Handover dengan Plan ID tetap dan MR pengganti **tidak bisa sekadar mengganti `mr_id`**; perlu memisahkan `original_owner_id`/`source_call_list_id` dari `current_assignee_id`/`executed_by_id`, mengganti FK dan authorization secara sadar, serta menyediakan audit log pengalihan. Jika MR pengganti tidak memiliki dokter itu pada Call List sendiri, ia memperoleh **otorisasi delegasi terbatas hanya melalui request yang diterima**. Ini **pengecualian bisnis tambahan**, bukan perilaku yang dijelaskan PDF. Jangan merelaksasi P0 secara global.

Klaim insentif: Actual dihitung sebagai pekerjaan MR yang benar-benar berkunjung; kepemilikan target awal dapat dilaporkan terpisah. **Tidak ada formula insentif** untuk diimplementasikan.

### 8.3 Plan kedaluwarsa / tidak terlaksana — P1

- Setelah **akhir tanggal Plan dalam zona waktu Asia/Jakarta**, bila tidak ada Actual yang merealisasikan Plan, tandai `expired` / **Tidak Terlaksana**; jalankan via scheduler idempoten.
- **Jangan auto-expire** jika ada Visit Session P1 yang masih berlangsung dan perlu dipulihkan; perlukan aturan recovery eksplisit.
- Expired **bukan** Cancelled dan **bukan** Completed.
- Dokter tetap ada pada Call List asal dan histori Plan; aplikasi boleh menampilkan kembali sebagai kandidat Plan **untuk tanggal baru** jika berada pada Call List Approved yang relevan.
- Jangan otomatis membuat Plan/Call List baru, mengubah Actual orang lain, atau menghapus histori.
- Periksa ulang unique active Plan index saat menambah `expired` sebagai state tidak aktif. Aturan global Actual dokter/tanggal tetap berlaku.

### 8.4 Ringkasan status yang terlihat

| Objek | Status/tampilan yang disarankan | Makna |
|---|---|---|
| Dokter pada Approved Call List | Belum Dijadwalkan | Belum ada Plan yang sah untuk periode itu |
| Plan di masa depan | Terjadwal | Belum ada Actual |
| Plan dengan pengalihan aktif | Dialihkan ke [MR] | Jadwal tetap, penanggung jawab berubah |
| Plan lewat tanggal tanpa Actual | Tidak Terlaksana / Expired | Tetap tercatat, boleh dijadwalkan ulang |
| Plan dengan Actual final | Selesai | Realisasi terjadi |
| Plan dibatalkan secara eksplisit | Dibatalkan | Alasan pembatalan tersedia |

## 9. Actual, Unplanned dan Non Target

UI Call Actual tetap **dua pintu**:

1. **Kunjungan Terjadwal:** memilih Plan yang sah. Dokter/Plan diambil dari pilihan; hasil ditulis sesuai MR pelaksana resmi (termasuk handover yang accepted jika P1 aktif).
2. **Kunjungan di Luar Rencana:** mencari dokter dari MCL, default dalam distrik; `plan_id=null`, lalu backend mengklasifikasikan:
   - **Unplanned:** dokter sudah tercantum pada Call List Approved milik MR pada bulan terkait, tetapi dikunjungi tanpa Plan.
   - **Non Target:** dokter sah dalam MCL tetapi tidak memenuhi kondisi di atas.

Untuk kedua mode: foto dan tanda tangan wajib, minimal satu produk, validasi tanggal dan waktu, maksimal **1 Actual per dokter/tanggal global**. **Without Plan tidak otomatis menyelesaikan Plan lain** yang masih ada.

## 10. Dampak yang harus diperbarui jika proposal diterima

| Dokumen | Perubahan yang diperlukan |
|---|---|
| `DESIGN.md` | Ubah responsibility role, peta dua lapis strategi–operasi, MCL wilayah, perbedaan managerial/individual approval, urutan handover/expiry |
| `PRD.md` | User journey, prioritas dokter tidak memblokir pilihan MR, Product–Specialty, dua pintu Actual, status Belum Dijadwalkan/Expired, acceptance criteria P1 |
| `TDD.md` | Service otorisasi distrik+manager untuk handover, locking concurrent accept/expiry, Planned Actual date equality asumsi, scheduler, indikator histori |
| `DATABASE_SCHEMA.md` | Territory FK, mapping specialty, campaign tables jika jadi, status `expired`, handover requests + dua identitas owner/executor, revisi composite FK/unique indexes |
| `API_CONTRACT.md` | **Jangan ubah 8 endpoint PDF**; tambah endpoint P1 handover/expiry exposure/produk rekomendasi, response assignee/audit, 403/409/422 rules |
| `TEST_PLAN.md` | Test lintas distrik ditolak, accepted handover, duplicate Actual, expiry akhir hari, recipient authorization, product matching nonblokir |
| `DECISIONS.md` | ADR baru untuk role area, campaign, default filter MCL, Planned matching, handover, expiry, scope dan risiko PDF |
| `IMPLEMENTATION_PLAN.md` | Tetapkan P0 tetap stabil; pecah P1 menjadi increment teruji, bukan satu batch refactor |
| `AGENTS.md` | Tambahkan sumber aturan baru dan larangan implementasi P1 sebelum memenuhi P0; jangan abaikan composite FK lama |
| `README_REQUIREMENTS.md` | Wajib melabeli semua asumsi yang tidak disebut PDF serta status nyata fitur yang sudah/belum selesai dan hasil test yang benar-benar dijalankan |

**Endpoint tambahan P1 yang bisa dipertimbangkan (belum menjadi kontrak):**

- `POST /api/call-plans/{id}/handover-requests` — ajukan pengalihan.
- `POST /api/handover-requests/{id}/accept` — diterima penerima yang sah.
- `POST /api/handover-requests/{id}/reject` — ditolak penerima.
- `GET /api/handover-requests` — daftar pengajuan milik/ditujukan kepada user.
- `GET /api/products/recommended?doctor_id=...` — rekomendasi produk dari mapping resmi (opsional).
- Expiry dikelola scheduler; **tidak perlu endpoint user untuk memaksa expired**.

## 11. Pengujian yang harus ditambah (selain lima test wajib PDF)

**Approval/role**
1. Rudi→Joko dapat approve; Hendra (DM distrik lain) tidak boleh approve List Rudi.
2. Joko→Siti dan Siti→Budi berhasil sebagai *separate direct approvals*, tanpa menunggu chain untuk List Rudi.

**Territory/MCL**
3. MR melihat filter default dokter distriknya; dokter MCL lain tetap dapat dicari melalui jalur pengecualian (jika fitur wilayah P1 aktif).

**Call Plan/Actual**
4. Planned Actual dengan tanggal berbeda dari Plan ditolak jika aturan pengetatan P1 diaktifkan; replan dalam bulan sah bekerja.
5. Plan lewat akhir hari tanpa Actual menjadi Expired; tetap terlihat; dokter tersedia untuk tanggal baru yang valid.
6. Session yang masih aktif tidak di-expire secara keliru.

**Handover**
7. Rudi mengajukan ke Ahmad (DM/distrik sama); Ahmad Accept; Plan ID tetap; DM melihat notifikasi; Actual tercatat atas Ahmad.
8. Ahmad Reject → Plan masih di Rudi; tidak ada kunjungan fiktif.
9. MR berbeda distrik/DM tidak dapat menerima (403); permintaan yang kadaluwarsa/plan selesai mendapat 409.
10. Concurrent acceptance/Actual/expiry tidak menggandakan ownership/visit.
11. Delegated Actual hanya untuk Plan yang benar-benar diterima, bukan akses ke semua Plan rekan.

**Produk**
12. Produk sesuai spesialisasi tampil sebagai saran; produk valid lain tetap bisa dipilih.
13. Sistem tidak menambahkan produk rekomendasi ke Actual jika MR tidak memilihnya; jika daftar hasil akhir kosong maka 422.

## 12. Urutan implementasi yang aman

| Tahap | Pekerjaan | Status prioritas |
|---|---|---|
| **P0** | Delapan API wajib, MR Call List bulanan dan tiga pasangan approval, Planned/Without Plan Actual, tiga UI, lima test, README runnable | **Wajib, jangan ditunda** |
| **P0a — UX murah** | Copy prioritas dokter DM sebagai informasi, status derived belum dijadwalkan, dua pintu Actual, contoh seed enam akun | Bisa bila tidak menghambat P0 |
| **P1a** | Struktur wilayah MCL, filter distrik/area + fallback pencarian, monitoring distrik/regional | Setelah P0 |
| **P1b** | Planned date matching sebagai aturan tercatat, cancel/replan dan status expired idempoten | Setelah P0 |
| **P1c** | Handover MR satu distrik dengan perubahan relasi ownership/Actual, audit, acceptance, race-condition tests | **Paling kompleks; terakhir** |
| **P2** | Campaign produk 3 bulan, mapping specialty yang dikurasi, UI rekomendasi dan KPI produk prioritas | Bila waktu dan data master memadai |

## 13. Keputusan tersisa sebelum meminta AI IDE melakukan refactor

1. **Apakah fitur kampanye benar-benar harus mempunyai form/MM CRUD dan persistence**, atau cukup uraian bisnis + data demo? Rekomendasi: uraian/seed dahulu.
2. **Bentuk Call List DM/RSM:** putuskan formalnya sebagai *rangkuman prioritas bulanan* untuk menguji ketiga approval, dengan status independen dari List MR. Ini **asumsi**, bukan detail PDF.
3. **Tanggal Actual vs Plan:** pengguna ingin kecocokan wajib. Sepakati apakah hanya kebijakan UX dan rescheduling atau juga validasi ketat di API; validasi API lebih ketat dari teks PDF dan harus dicatat.
4. **Delegasi ke MR tanpa dokter di Call List pribadinya:** desain yang diminta mengizinkannya lewat *accepted handover* terbatas. Putuskan apakah menerima risiko tambahan ini untuk P1; jangan melemahkan validasi P0 bagi Actual tanpa delegasi.
5. **Dokter praktik multi-area:** MVP memberi satu area utama; perlu model lokasi-praktik tersendiri jika scope diperluas.

## 14. Ringkasan satu paragraf untuk presentasi

> MSF mempertahankan proses Call List bulanan, Call Plan, dan Call Actual sesuai studi kasus. Untuk memberikan fungsi manajerial yang lebih konkret, rancangan pengembangan mengasumsikan MM menetapkan fokus produk tiga bulanan, RSM menentukan cakupan dokter prioritas regional, DM menentukan prioritas distrik per bulan, sedangkan MR memilih target dan kapasitas kunjungan sendiri sebelum Call List disetujui DM. MCL diklasifikasikan berdasarkan regional, distrik, dan area, tetapi tetap terpusat. Untuk fleksibilitas di lapangan, rancangan lanjutan mencakup pengalihan Plan antar-MR dalam distrik dan DM yang sama, serta status Tidak Terlaksana jika tanggal Plan berakhir tanpa Actual. Semua peningkatan ini terpisah dari requirement wajib PDF dan akan diimplementasikan bertahap agar validasi, ownership data, serta pengujian tetap terjaga.

---

**Sikap implementasi:** Usulan ini **belum mengubah** paket v1.7 yang ada. Jika disetujui, gunakan sebagai *change specification* untuk memperbarui dokumen sumber satu per satu. Jangan mengklaim migrasi/API/test telah selesai sebelum benar-benar dibuat dan dijalankan.
