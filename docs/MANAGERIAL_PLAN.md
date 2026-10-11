# Perencanaan Arsitektur & Desain Fitur Manajerial (SFA Standard)

> **Dokumen:** Perencanaan Fitur Manajerial (DM, RSM, MM)  
> **Status:** Disetujui untuk implementasi  
> **Kepatuhan:** Anti Slop (Zero Em Dash, Clean & Data-Centric, PSR-12, Real Persistence)

---

## 1. Latar Belakang & Masalah Bisnis
- Data kunjungan lapangan (*Call Actual*) adalah dasar krusial perhitungan insentif representatif lapangan (*MR*) dan pelaporan ke manajemen.
- Saat ini, manajer (DM, RSM, MM) belum dapat melihat riwayat kunjungan yang dilakukan oleh tim bawahannya di halaman Pelaksanaan Kunjungan (`/call-actuals`).
- Akibatnya, kontrol manajemen terhadap validitas bukti kunjungan (*anti-fiktif*) dan pelaporan realisasi lapangan belum terpenuhi.

---

## 2. Batasan Arsitektur
1. **Tetap dalam 3 Halaman Utama:** Tidak membuat halaman atau modul keempat baru. Monitoring manajerial diintegrasikan langsung ke dalam halaman Pelaksanaan Kunjungan (`/call-actuals`) saat akun manajer login.
2. **Hierarki Bawahan (Data Scoping):**
   - **DM (District Manager):** Melihat kunjungan dari seluruh MR yang memiliki `manager_id = dm.id`.
   - **RSM (Regional Sales Manager):** Melihat kunjungan dari seluruh MR di bawah DM yang berada di wilayah regionalnya (`manager_id IN (subordinate_dm_ids)`).
   - **MM (Marketing Manager):** Melihat kunjungan seluruh MR secara nasional.
   - **MR:** Tetap melihat kunjungannya sendiri (`mr_id = mr.id`).

---

## 3. Spesifikasi Teknis Backend API

### Endpoint: `GET /api/call-actuals`
- **Aktor MR:** Mengembalikan data kunjungan milik MR sendiri (perilaku eksisting tetap 100% kompatibel).
- **Aktor Manajer (DM, RSM, MM):**
  - Mengambil daftar ID bawahan (*subordinate user IDs*) berdasarkan hierarki `users.manager_id`.
  - Mengembalikan daftar `CallActual` di mana `mr_id IN (subordinate_ids)`.
  - Memuat relasi: `doctor`, `products`, `plan`, dan `mr` (nama dan role pelaksana).
  - Menerima parameter filter opsional:
    - `mr_id`: Menyaring kunjungan untuk satu anggota tim tertentu.
    - `date`: Menyaring berdasarkan tanggal tertentu (`YYYY-MM-DD`).
    - `month`: Menyaring berdasarkan bulan tertentu (`YYYY-MM`).
    - `visit_type`: Menyaring kategori (`planned`, `unplanned`, `non_target`).

---

## 4. Spesifikasi Desain Antarmuka (UI) Manajerial
Desain mematuhi prinsip **Clean & Data-First** (tanpa teks tutorial, tanpa banner AI yang menggurui):

1. **Header Kompak:**
   - Judul: **Pelaksanaan Kunjungan**
   - Subtitle: **Call Actual : Rekapitulasi dan audit bukti kunjungan tim bawahan.**

2. **Strip Metrik Kinerja (3 Angka Utama):**
   - **Total Kunjungan:** Total kunjungan tim pada periode terpilih.
   - **Terencana (Planned):** Kunjungan dengan bobot insentif penuh (sesuai target yang disetujui).
   - **Di Luar Rencana (Unplanned / Non-Target):** Kunjungan di luar perencanaan.

3. **Baris Filter Cepat:**
   - Dropdown **Pilih Anggota Tim** (Semua Anggota / Nama MR bawahan).
   - Input **Periode Bulan / Tanggal**.
   - Dropdown **Kategori Kunjungan** (Semua / Terencana / Di Luar Rencana / Non-Target).

4. **Tabel Data Kunjungan Tim:**
   - Kolom: Waktu & Tanggal, Petugas MR, Dokter & Faskes, Kategori, Produk Detailing, Bukti Audit.
   - Tombol **Lihat Bukti:** Membuka modal pratinjau foto kunjungan dan tanda tangan digital dokter secara instan untuk verifikasi keabsahan.

---

## 5. Rencana Pengujian & Delivery Gate
- **Unit & Feature Test:** Tambahkan tes backend untuk memastikan manajer dapat mengambil kunjungan tim bawahan dan MR lain tidak bocor ke manajer yang salah.
- **PSR-12:** Format seluruh kode PHP menggunakan Laravel Pint.
- **Anti Slop Gate:** Nol karakter em dash di seluruh antarmuka dan kode.
- **Visual Verification:** Pastikan tabel data terisi dengan baik dan modal bukti berfungsi.
