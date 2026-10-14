# Panduan Deployment ke Shared Hosting Rumahweb (cPanel)

Panduan ini ditujukan untuk mendeploy aplikasi **MSF Mersifarma** ke Shared Hosting Rumahweb dengan domain **`https://mersi-msf.my.id`**.

---

## 1. Persiapan di Komputer Lokal

### A. Build Aset Frontend
Shared hosting biasanya tidak menyediakan Node.js/NPM, sehingga aset Vite dikompilasi terlebih dahulu di lokal:
```bash
npm run build
```
*(Pastikan folder `public/build` sudah terisi file css dan js terkompilasi).*

### B. File Konfigurasi `.env.production`
File acuan [.env.production](file:///c:/Users/Ananta%20Raihan/Kuliah/CV%20KERJA%20YUK/MERSI/.env.production) sudah disediakan di root proyek dengan setting:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://mersi-msf.my.id`
- `DB_HOST=localhost`
- `SESSION_SECURE_COOKIE=true`

File `.env` di lokal Anda **tidak diubah** dan tetap berjalan untuk keperluan local development/testing.

---

## 2. Pengaturan Domain di cPanel Rumahweb

1. Login ke **cPanel Rumahweb**.
2. Masuk ke menu **Domains** (atau **Addon Domains** jika domain bukan domain utama akun hosting Anda).
3. Tambahkan domain: `mersi-msf.my.id`.
4. **PENTING - Pengaturan Document Root**:
   - Jika bisa diatur bebas, arahkan Document Root ke subfolder `public`.
   - Contoh Document Root:
     ```text
     /home/username/mersi-msf/public
     ```
     *(Ganti `username` dengan username cPanel Anda).*
   - **Keuntungan:** Struktur ini membuat seluruh kode aplikasi (`app`, `config`, `.env`, dll.) berada di luar jangkauan publik internet, dan hanya folder `public` yang dapat diakses web browser.

---

## 3. Pengaturan Versi PHP

1. Masuk ke menu **MultiPHP Manager** atau **Select PHP Version** di cPanel.
2. Pilih domain `mersi-msf.my.id`.
3. Set versi PHP minimal **PHP 8.2** (disarankan PHP 8.2 atau PHP 8.3).
4. Pastikan ekstensi berikut aktif:
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `tokenizer`
   - `xml`
   - `ctype`
   - `json`
   - `bcmath`
   - `fileinfo`

---

## 4. Pengaturan Database MySQL di cPanel

1. Masuk ke menu **MySQL® Databases** di cPanel.
2. **Create New Database**: Buat database baru (contoh: `username_mersi_msf`).
3. **Add New User**: Buat user database baru (contoh: `username_msfuser`) beserta password yang kuat.
4. **Add User To Database**: Hubungkan user ke database tersebut dan centang **ALL PRIVILEGES**.
5. Catat:
   - Nama Database lengkap (termasuk prefix username cPanel)
   - Nama User lengkap (termasuk prefix username cPanel)
   - Password database

---

## 5. Upload Berkas ke Hosting

### A. Siapkan ZIP Proyek
Di komputer lokal, kompres berkas proyek menjadi `.zip`.
**Yang WAJIB diikutsertakan:**
- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/` (termasuk subfolder `public/build/`)
- `resources/`
- `routes/`
- `storage/`
- `vendor/` *(sangat disarankan diikutsertakan jika tidak memakai SSH/Composer di hosting)*
- `artisan`
- `composer.json`
- `.env.production`

**Yang TIDAK PERLU diikutsertakan:**
- `node_modules/`
- `.git/`
- `.phpunit.cache/`
- `.playwright-cli/`

### B. Upload & Ekstrak
1. Buka **File Manager** di cPanel.
2. Upload file ZIP ke folder project (misal `/home/username/mersi-msf/`).
3. Klik kanan file ZIP lalu pilih **Extract**.
4. Di dalam folder project di server:
   - Salin isi dari `.env.production` ke file baru bernama `.env`.
   - Buka file `.env` dan sesuaikan:
     ```env
     DB_DATABASE=username_mersi_msf
     DB_USERNAME=username_msfuser
     DB_PASSWORD=password_yang_anda_buat_tadi
     ```

---

## 6. Migrasi dan Seeding Data Demo

Pilih salah satu metode yang paling mudah bagi Anda:

### Metode A: Lewat cPanel Terminal / SSH (Paling Cepat & Otomatis)
Jika akun Rumahweb Anda memiliki fitur **Terminal**:
1. Buka menu **Terminal** di cPanel.
2. Masuk ke folder proyek:
   ```bash
   cd /home/username/mersi-msf
   ```
3. Jalankan migrasi dan seed database sintetis:
   ```bash
   php artisan migrate --seed --force
   ```
4. Jalankan cache optimasi:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Metode B: Lewat phpMyAdmin (Tanpa Terminal)
1. Di komputer lokal, export database `mersi_msf` ke file `.sql` menggunakan phpMyAdmin lokal atau perintah:
   ```bash
   mysqldump -u root mersi_msf > mersi_msf_dump.sql
   ```
2. Buka menu **phpMyAdmin** di cPanel Rumahweb.
3. Pilih database `username_mersi_msf`.
4. Klik tab **Import**, pilih file `.sql` tadi, lalu klik **Import**.

---

## 7. Aktivasi SSL Gratis (HTTPS)

1. Masuk ke menu **SSL/TLS Status** di cPanel Rumahweb.
2. Cari domain `mersi-msf.my.id`.
3. Klik tombol **Run AutoSSL** (atau gunakan menu **Let's Encrypt SSL** jika tersedia).
4. Tunggu beberapa menit hingga status ikon gembok berubah menjadi hijau (valid SSL).

---

## 8. Verifikasi Demo di Browser

Setelah DNS domain mengarah ke server dan SSL aktif:
1. Buka `https://mersi-msf.my.id` di browser.
2. Login menggunakan akun demo sintetis:
   - **MR (Medical Representative):** `mr.budi` / `password`
   - **DM (District Manager):** `dm.hendra` / `password`
   - **RSM (Regional Sales Manager):** `rsm.dewi` / `password`
   - **MM (Marketing Manager):** `mm.anwar` / `password`
3. Coba alur:
   - Target Dokter (Call List)
   - Jadwal Kunjungan (Call Plan)
   - Pelaksanaan Kunjungan (Call Actual)
