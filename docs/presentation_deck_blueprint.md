# Blueprint Rancangan Slide Presentasi PPT (30 Menit)
## Mobile Sales Force (MSF) — PT Mersifarma
**Kasus Studi Junior Software Engineer**  
**Alokasi Waktu Total:** 30 Menit (12 Menit Presentasi Slide + 12 Menit Live Demo + 6 Menit Q&A)

---

### Alokasi Waktu & Timeline Sesi

```
[00:00 - 12:00]  Presentasi Slide Blueprint (Problem, Architecture, Business Rules, Trade-offs)
[12:00 - 24:00]  Live Interactive Demo (PDF-01 s.d. PDF-05 & Modul Manajerial)
[24:00 - 30:00]  Tanya Jawab Teknis & Technical Defense
```

---

## Slide 1: Title & Introduction (1 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Bersih, korporat farmasi modern (*Clean Navy & White* dengan aksen biru medis).
- **Elemen Grafis:** Logo PT Mersifarma Tirta Samudra di sudut atas, badge status `Case Study: Junior Software Engineer`, judul utama besar dan kontras.

### 2. Konten Teks di Slide
- **Judul Utama:** Mobile Sales Force (MSF) Application
- **Sub-judul:** Rekayasa Sistem Pelaporan Lapangan Terintegrasi, Aman, dan Patuh Aturan Bisnis Farmasi
- **Presenter:** Ananta Raihan — Junior Software Engineer Candidate
- **Konteks:** Technical Evaluation & Architecture Walkthrough (Mersifarma Case Study)

### 3. Talking Points (Script Narasi)
> *"Selamat pagi/siang rekan-rekan penilai dan bapak/ibu tim engineering PT Mersifarma. Hari ini saya mempresentasikan implementasi sistem Mobile Sales Force (MSF). Aplikasi ini saya rancang tidak sekadar sebagai formulir web biasa, melainkan sebagai fondasi sistem operasional lapangan yang menjamin integritas data komitmen target dokter, penjadwalan bebas bentrok, serta verifikasi kunjungan nyata yang atomik dan patuh pada seluruh aturan bisnis studi kasus."*

---

## Slide 2: Latar Belakang & Masalah di Industri Farmasi (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** 2 Kolom — Kolom Kiri: Realitas Lapangan (Tantangan Operasional), Kolom Kanan: 4 Kartu Masalah Utama (*Problem Cards*).
- **Elemen Grafis:** Ikon peringatan (*alert*), diagram alur bentrok dokter.

### 2. Konten Teks di Slide
- **Judul:** Tantangan Pengelolaan Sales Lapangan (Problem Statement)
- **4 Titik Kritis Lapangan:**
  1. **Disiplin Komitmen Target Bulanan:** Target sering berubah tanpa otorisasi berjenjang yang sah.
  2. **Bentrok Kunjungan Dokter (*Conflict*):** Risiko dua MR mendatangi dokter yang sama di tanggal yang sama tanpa koordinasi.
  3. **Laporan Kunjungan Fiktif (*Ghost Calls*):** Kunjungan dicatat tanpa bukti autentik verifikasi dokter dan produk detailing.
  4. **Kunjungan Spontan Tak Terakomodasi:** MR bertemu dokter penting di luar rencana, namun sistem kaku menolak pencatatan jika tanpa jadwal awal.

### 3. Talking Points (Script Narasi)
> *"Dalam industri farmasi, Medical Representative beroperasi di lapangan dengan target kunjungan yang sangat tinggi. Masalah yang sering terjadi adalah: pertama, target dokter bulanan diajukan tanpa kepastian persetujuan langsung dari atasan. Kedua, ada risiko bentrok jadwal—dua MR mengunjungi dokter yang sama di hari yang sama, yang merusak etika profesional perusahaan. Ketiga, pelaporan fiktif tanpa bukti verifikasi. Dan keempat, sistem sering kali kaku menolak kunjungan spontan di lapangan. Dari situasi inilah sistem MSF ini kami rancang untuk memecahkan ke-4 tantangan tersebut secara tuntas."*

---

## Slide 3: Arsitektur Solusi & Tech Stack (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** 3 Pilar Utama (Horizontal Flow) di atas, tabel ringkas Tech Stack di bawah.
- **Diagram Alur:** `Target Dokter (Call List)` $\rightarrow$ `Jadwal Kunjungan (Call Plan)` $\rightarrow$ `Pelaksanaan Kunjungan (Call Actual)`.

### 2. Konten Teks di Slide
- **Judul:** Arsitektur Sistem & Pemilihan Teknologi
- **3 Pilar Alur Operasional:**
  - **1. Target Dokter:** Penyusunan komitmen bulanan dari Master Customer List (MCL) $\rightarrow$ Persetujuan 1 tingkat.
  - **2. Jadwal Kunjungan:** Penjadwalan tanggal kunjungan dari target yang telah disetujui $\rightarrow$ Anti-bentrok global.
  - **3. Pelaksanaan Kunjungan:** Realisasi kunjungan Planned maupun Without Plan $\rightarrow$ Bukti wajib foto, tanda tangan, & produk.
- **Tech Stack & Justifikasi:**
  - **Backend:** PHP 8.2 + Laravel 11 (*Strong routing, Form Requests, Transactions, PSR-12*).
  - **Database:** MySQL 8.0+ (InnoDB) (*Strict foreign keys, Unique constraints, Row-level locking*).
  - **Frontend:** Blade + Lightweight JavaScript + CSS Modules (*SSR-native, performa instan tanpa bundle bloat*).

### 3. Talking Points (Script Narasi)
> *"Solusi yang kami bangun bertumpu pada 3 pilar yang saling mengunci: Target Dokter, Jadwal Kunjungan, dan Pelaksanaan Kunjungan. Kami sengaja memilih Laravel 11 dengan MySQL 8 InnoDB. Kami tidak menggunakan arsitektur microservices atau SPA React/Vue yang rumit, melainkan Laravel-native dengan Blade dan lightweight JS. Alasan teknisnya: aplikasi ini membutuhkan validasi atomik transaksi database, keamanan session auth, dan kecepatan render responsif di perangkat bergerak tanpa overhead dependensi yang tidak perlu."*

---

## Slide 4: Solusi Masalah #1 — Tata Kelola Target & Rantai Persetujuan (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Diagram Rantai Persetujuan 1-Tingkat (*Hierarchy Chart*) & Diagram Mesin Status (*State Transition*).

### 2. Konten Teks di Slide
- **Judul:** Solusi #1: Otorisasi & Persetujuan Target Dokter (Call List)
- **Aturan Bisnis Kunci:**
  - **One-Level Direct Approval:** Persetujuan wajib dilakukan oleh atasan langsung yang ditugaskan (`manager_id` aktual), bukan sekadar kesamaan teks role.
    - MR $\rightarrow$ Disetujui oleh DM langsung.
    - DM $\rightarrow$ Disetujui oleh RSM langsung.
    - RSM $\rightarrow$ Disetujui oleh MM langsung.
  - **Status State Machine:** `Draft` $\rightarrow$ `Pending Approval` $\rightarrow$ `Approved` atau `Perlu Revisi`.
  - **Audit Trail & Resubmission:** Penolakan wajib menyertakan alasan (`rejection_reason`). Pengajuan ulang mempertahankan penugasan atasan awal tanpa menghapus riwayat audit.

### 3. Talking Points (Script Narasi)
> *"Untuk memecahkan masalah tata kelola target dokter, kami mengunci aturan bahwa approval Call List hanya boleh 1 tingkat ke atasan langsung. Sistem kami memeriksa assigned_approver_id dan manager_id riil pengguna. Artinya, meskipun seseorang ber-role MM atau RSM, ia tidak bisa meng-approve Call List milik MR jika bukan atasan langsungnya. Jika ada revisi, DM wajib menulis alasan penolakan, target kembali menjadi Draft yang dapat diedit oleh MR, dan seluruh catatan audit historis tersimpan secara aman."*

---

## Slide 5: Solusi Masalah #2 — Integritas Jadwal & Pencegahan Bentrok Global (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Dua Kolom Komparasi — Kolom Kiri: Validasi Integritas Call Plan, Kolom Kanan: Mekanisme Pencegahan Bentrok Kalender.

### 2. Konten Teks di Slide
- **Judul:** Solusi #2: Integritas Kalender Kunjungan Bebas Konflik (Call Plan)
- **Logika Rekayasa Sistem:**
  - **Strict Guard Clause:** Call Plan **hanya dapat dibuat** dari Call List yang sudah disetujui (`approved`). Dokter wajib terdaftar di Call List tersebut dan tanggal harus berada dalam bulan berjalan.
  - **Pencegahan Bentrok Unik Global:** Sistem memberlakukan aturan bahwa satu dokter hanya boleh memiliki maksimal 1 Plan aktif per tanggal di seluruh Indonesia (lintas seluruh MR).
  - **Reschedule & Cancellation:** Jadwal dapat diubah tanggalnya (*reschedule*) atau dibatalkan (*cancelled*) dengan alasan audit jika belum berstatus *completed*.

### 3. Talking Points (Script Narasi)
> *"Pada modul Jadwal Kunjungan, sistem mencegah penjadwalan liar. Call Plan hanya bisa dibuat jika Call List sudah Approved. Jika masih Draft, sistem menolak keras dengan error 422. Lebih jauh lagi, kami menerapkan proteksi bentrok global: jika Dokter A sudah dijadwalkan oleh MR-1 pada tanggal 15, maka MR-2 tidak akan bisa menjadwalkan Dokter A pada tanggal 15 tersebut. Ini menyelesaikan masalah etika bentrok kunjungan di rumah sakit. MR juga diberi fleksibilitas menjadwalkan ulang atau membatalkan jadwal secara transparan."*

---

## Slide 6: Solusi Masalah #3 — Validitas Kunjungan & Fleksibilitas Tanpa Rencana (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Skema Cabang Pelaksanaan Kunjungan (Planned vs Without Plan) & Diagram Transaksi Atomik Database.

### 2. Konten Teks di Slide
- **Judul:** Solusi #3: Validitas Kunjungan Lapangan & Fleksibilitas (Call Actual)
- **Inovasi Rekayasa Sistem:**
  - **Dukungan Kunjungan Tanpa Rencana (`plan_id = null`):** Field `plan_id` bersifat nullable. MR dapat langsung merekam kunjungan spontan tanpa harus membuat jadwal bayangan.
  - **Klasifikasi Otomatis oleh Backend:**
    - `unplanned` $\rightarrow$ Jika dokter terdaftar di target Call List approved MR.
    - `non_target` $\rightarrow$ Jika dokter MCL berada di luar target approved MR.
  - **Transaksi Atomik (DB::transaction):**
    - Wajib: Bukti Foto + Tanda Tangan/Stempel + Minimal 1 Produk Detailing + Waktu Masuk/Keluar.
    - Sifat atomik: Jika salah satu komponen gagal disimpan, database melakukan **rollback total (Zero Partial Write)**.
  - **Batas Kunjungan Global:** Maksimal 1 Actual final per dokter/tanggal di seluruh sistem.

### 3. Talking Points (Script Narasi)
> *"Ini adalah inti dari studi kasus: Pelaksanaan Kunjungan. Di lapangan, MR sering kali bertemu dokter secara mendadak. Sistem kami tidak kaku: field plan_id bersifat nullable, sehingga Direct Call Actual bisa langsung dicatat. Backend kami yang secara cerdas mengklasifikasikan apakah kunjungan itu Unplanned atau Non Target berdasarkan status Call List saat check-in. Seluruh bukti—foto, tanda tangan kanvas, produk detailing, dan jam kunjungan—disimpan dalam satu database transaction. Jika pivot produk gagal, seluruh data di-rollback sehingga tidak ada data sampah atau laporan setengah jadi."*

---

## Slide 7: Desain Operasional Manajerial & Anti-Slop UX (1 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Hierarki Peran Manajerial (DM, RSM, MM) di sebelah kiri, Prinsip Antarmuka *Anti-Slop* di sebelah kanan.

### 2. Konten Teks di Slide
- **Judul:** Efisiensi Peran Manajerial & Kualitas Pengalaman Pengguna (UX)
- **Pembersihan Beban Peran (Role Clarity):**
  - **DM:** Fokus memantau submit target MR wilayahnya & mengusulkan program prioritas distrik.
  - **RSM:** Mengesahkan usulan distrik & menerbitkan arahan kebijakan regional (fokus obat lintas distrik).
  - **MM:** Memantau ringkasan performa eksekutif nasional (*National Executive Summary*) tanpa friksi persetujuan mikro.
- **Standar Desain Antarmuka (*Anti-Slop*):**
  - Desain *clean enterprise*: Menghapus kartu bersarang (*nested box-in-box*), hierarki visual lega, konsistensi badge 4px.
  - Terminologi ramah Indonesia (**Target Dokter, Jadwal Kunjungan, Pelaksanaan Kunjungan**) dengan subtitle PDF resmi di setiap halaman.
  - Responsif di layar smartphone maupun laptop.

### 3. Talking Points (Script Narasi)
> *"Kami juga memperhatikan pengalaman pengguna di level manajerial. Kami membersihkan tumpang-tindih peran: DM tidak lagi dibebani membuat target dokter mandiri, RSM fokus pada arahan regional dan evaluasi distrik, dan MM fokus pada National Executive Summary. Dari sisi antarmuka, kami menerapkan prinsip anti-slop: tidak ada tombol yang menumpuk, layout bersih tanpa kotak bersarang yang berlebihan, dan responsif saat diakses dari ponsel MR di lapangan."*

---

## Slide 8: Kualitas Kode & Disiplin Pengujian (1.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Kartu Metrik Uji (Besar & Hijau) di kiri, Tabel Checklist 5 Skenario Wajib PDF di kanan.

### 2. Konten Teks di Slide
- **Judul:** Disiplin Pengujian & Kepatuhan Standar Industri
- **Metrik Kualitas Uji:**
  - **104 Automated Tests (367 assertions) — 100% Passed / Green.**
  - Standar Kode: Mematuhi PSR-12 terverifikasi via Laravel Pint (*Zero lint error*).
  - 8 dari 8 Endpoint Wajib PDF lulus kontrak request/response.
- **Hasil 5 Skenario Wajib PDF:**
  - `PDF-01`: Happy Path (List $\rightarrow$ Approve $\rightarrow$ Plan $\rightarrow$ Actual) $\rightarrow$ **PASS**
  - `PDF-02`: Penolakan pembuatan Plan dari List Draft $\rightarrow$ **PASS (422)**
  - `PDF-03`: Penolakan Actual kedua pada dokter & tanggal sama $\rightarrow$ **PASS (409)**
  - `PDF-04`: Penolakan Actual tanpa foto & verifikasi rollback total $\rightarrow$ **PASS (422)**
  - `PDF-05`: Penolakan approval oleh non-direct manager $\rightarrow$ **PASS (403)**

### 3. Talking Points (Script Narasi)
> *"Sebagai software engineer, klaim fitur berfungsi hanya valid jika dibuktikan dengan pengujian. Kami memiliki 104 automated feature tests dengan 367 assertions yang mencakup skenario happy path, validasi input, kegagalan otorisasi, dan kondisi bentrok status. Kelima skenario wajib dari PDF studi kasus telah kami petakan dan lulus 100%. Kode kami juga telah diformat menggunakan Laravel Pint memenuhi standar penulisan PSR-12."*

---

## Slide 9: Batasan Desain & Rekayasa Sadar (1 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Dua Kolom Transparansi Teknis — Kolom Kiri: Batasan yang Disengaja (*Scope Boundaries*), Kolom Kanan: Rekayasa Trade-offs.

### 2. Konten Teks di Slide
- **Judul:** Batasan Teknis & Pertimbangan Rekayasa (Engineering Trade-offs)
- **Batasan Sadar Desain (*Deliberate Boundaries*):**
  - **Tanpa GPS & S3 Cloud:** Fokus pada integritas logika bisnis dan kemudahan menjalankan demo secara mandiri (*self-contained local setup*).
  - **Data Uji Sintetis:** Menggunakan data demo tiruan, tanpa membocorkan dokumen studi kasus rahasia atau data riil.
- **Pertimbangan Teknis (*Trade-offs*):**
  - **Waktu Kunjungan:** Direct API mencatat waktu lapor pengguna (*user-reported time*); P1 Visit Session mencatat waktu server secara mutlak.
  - **Refleksi Tooling AI:** AI digunakan sebagai akselerator, namun setiap baris kode, validasi keamanan, dan desain UX disaring secara ketat untuk membuang kode sampah (*anti-slop*).

### 3. Talking Points (Script Narasi)
> *"Kami juga bersikap transparan terhadap batasan sistem. Sistem ini tidak menyertakan modul GPS fisik atau penyimpanan berkas AWS S3 karena fokus studi kasus ini adalah pada integritas model data, logika bisnis, dan API. Kami sengaja merancangnya agar zero-dependency dan dapat langsung dijalankan oleh siapa saja di mesin lokal. Segala trade-off teknis kami dokumentasikan secara sadar di dalam file DECISIONS.md."*

---

## Slide 10: Rencana Live Demo & Penutup (0.5 Menit)

### 1. Komposisi Desain & Visual
- **Layout:** Checklist Alur Demo Interaktif (5 Langkah Cepat) & Transisi ke Browser.

### 2. Konten Teks di Slide
- **Judul:** Alur Pembuktian Interaktif (Live Demo Agenda)
- **Langkah Live Demo (12 Menit):**
  1. **Demo MR & DM:** Penyusunan Target Dokter $\rightarrow$ Otorisasi Persetujuan DM.
  2. **Demo Kalender:** Pembuatan Jadwal Kunjungan $\rightarrow$ Pembuktian anti-bentrok.
  3. **Demo Pelaksanaan:** Input Kunjungan Lengkap (Foto, Kanvas Tanda Tangan, Produk).
  4. **Demo Kasus Negatif:** Bukti penolakan duplikasi dan rollback tanpa foto.
  5. **Demo Manajerial:** Tampilan Arahan Regional RSM & Monitoring Eksekutif MM.
- **Penutup:** *"Sistem siap didemokan secara langsung."*

### 3. Talking Points (Script Narasi)
> *"Untuk membuktikan bahwa seluruh sistem ini bekerja secara riil di peramban dan terhubung ke backend API, mari kita beralih ke sesi Live Demo berdurasi 12 menit. Saya akan mendemokan alur kerja dari sisi MR, DM, RSM, hingga pembuktian skenario penolakan sistem."*

---

## Panduan Eksekusi Live Demo (12 Menit)

| Menit | Aktor | Aksi yang Ditunjukkan | Poin Penekanan untuk Penilai |
|:---:|:---:|---|---|
| **12–14** | **MR (mr@mersi.com)** | Buka **Target Dokter**, buat target dokter baru, submit ke DM. | Tunjukkan scrollable doctor picker yang bersih & status berubah jadi *Menunggu Persetujuan*. |
| **14–16** | **DM (dm@mersi.com)** | Login sebagai DM, lihat antrean masuk, klik **Setujui Target**. | Tunjukkan bahwa DM memverifikasi bawahan langsungnya, bukan MR distrik lain. |
| **16–18** | **MR (mr@mersi.com)** | Buka **Jadwal Kunjungan**, buat jadwal dari target yang baru disetujui. | Tunjukkan bahwa hanya dokter yang diapprove yang muncul di dropdown kalender. |
| **18–21** | **MR (mr@mersi.com)** | Buka **Pelaksanaan Kunjungan**, isi laporan kunjungan (pilih foto, tanda tangan kanvas, pilih produk). | Tunjukkan form interaktif, preview tanda tangan, dan status berubah menjadi *Selesai*. |
| **21–23** | **MR 2 (mr2@mersi.com)** | Coba mencatat kunjungan pada dokter dan tanggal yang sama persis. | Tunjukkan munculnya **Peringatan 409 Conflict** (pencegahan duplikasi global berhasil). |
| **23–24** | **RSM / MM** | Login sebagai RSM/MM, tunjukkan modul arahan regional & Executive Summary. | Tunjukkan pemisahan peran manajerial yang bersih dan bebas beban operasional mikro. |
