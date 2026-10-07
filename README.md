# Posyandu Digital

> **Sistem Informasi & Monitoring Tumbuh Kembang Balita Terpadu (Permenkes No. 2 Tahun 2020)**  
> Aplikasi web monolitik berorientasi *mobile-first* untuk pemantauan kesehatan ibu dan anak (0–60 bulan) di Posyandu seluruh Indonesia. Dirancang khusus untuk kenyamanan Kader di lapangan menggunakan ponsel pintar Android kelas menengah dengan jaringan tidak stabil.

---

## Daftar Isi
1. [Arsitektur & Tumpukan Teknologi](#arsitektur--tumpukan-teknologi)
2. [Matriks Peran Pengguna (RBAC)](#matriks-peran-pengguna-rbac)
3. [Standar Klinis Permenkes No. 2 Tahun 2020](#standar-klinis-permenkes-no-2-tahun-2020)
4. [Kebutuhan Sistem](#kebutuhan-sistem)
5. [Panduan Instalasi & Konfigurasi](#panduan-instalasi--konfigurasi)
6. [Import Data Acuan WHO LMS](#import-data-acuan-who-lms)
7. [Akun Demo & Database Seeding](#akun-demo--database-seeding)
8. [Kompilasi Frontend & Pengujian](#kompilasi-frontend--pengujian)
9. [Catatan Produksi & Keamanan Data (UU PDP)](#catatan-produksi--keamanan-data-uu-pdp)

---

## Arsitektur & Tumpukan Teknologi

Aplikasi dibangun sebagai monolit modern berkecepatan tinggi tanpa pemisahan API gateway yang rumit:

- **Backend**: Laravel 11 dengan PHP 8.3 (`declare(strict_types=1)`, backed enums, readonly DTO, model policies).
- **Frontend**: Inertia.js + Vue 3 (Composition API, `<script setup>`), Tailwind CSS, Lucide Icons (`lucide-vue-next`).
- **Visualisasi Kurva KMS**: Chart.js via `vue-chartjs` dengan kurva standar WHO (-3SD s/d +3SD), pita zona warna, dan titik pertumbuhan balita yang interaktif.
- **Basis Data**: PostgreSQL 14+ dengan tipe data `NUMERIC`, `BIGSERIAL`, `CHECK` constraints, serta `ON DELETE` cascaded/restrict yang ketat.
- **Pengujian Mutu**: Pest Framework (Unit test algoritma Box-Cox WHO & Feature test isolasi RBAC).

---

## Matriks Peran Pengguna (RBAC)

Akses dibatasi secara ketat berdasarkan peran pengguna dan isolasi wilayah posyandu:

| Fitur / Halaman | Admin Desa | Bidan Desa | Kader Posyandu | Orang Tua |
|---|:---:|:---:|:---:|:---:|
| **Dashboard Wilayah Desa (Cakupan D/S & Stunting)** | Ya (Se-Desa) | - | - | - |
| **Dashboard Posyandu & Sweeping Balita** | Ya | Ya (Posyandu Binaan) | Ya (Posyandu Binaan) | - |
| **Portal KMS & Tumbuh Kembang Buah Hati** | - | - | - | Ya (Hanya Anaknya) |
| **Pendaftaran Balita & Orang Tua Baru** | Ya | Ya | Ya | - |
| **Pencatatan Pengukuran Antropometri** | Ya | Ya | Ya | - |
| **Pencatatan & Hapus Imunisasi Medis** | Ya | Ya | - | - |
| **Tinjau Jadwal Imunisasi & Deteksi Tertunda** | Ya | Ya | Ya | Ya (Anak Sendiri) |
| **Manajemen Master Posyandu & Pengguna** | Ya | - | - | - |

---

## Standar Klinis Permenkes No. 2 Tahun 2020

Kalkulasi status antropometri diimplementasikan pada `App\Services\AntropometriService` sesuai protokol resmi:

1. **Perhitungan Usia**: Menggunakan bulan penuh selesai (*completed months*) dihitung dari tanggal lahir hingga tanggal penimbangan (`0–60 bulan`).
2. **Koreksi Posisi Pengukuran**:
   - Anak usia `< 24 bulan` diukur berdiri $\to$ Panjang Badan dikoreksi $+0.7\text{ cm}$.
   - Anak usia $\ge 24 bulan$ diukur telentang $\to$ Tinggi Badan dikoreksi $-0.7\text{ cm}$.
3. **Pilihan Acuan BB/TB**: Usia `< 24 bulan` menggunakan acuan `panjang_cm`; usia $\ge 24 bulan$ menggunakan acuan `tinggi_cm`.
4. **Formula Box-Cox LMS**:
   - Cabang $L \neq 0$: $Z = \frac{(X/M)^L - 1}{L \cdot S}$
   - Cabang $L = 0$: $Z = \frac{\ln(X/M)}{S}$
5. **Restricted Tail Adjustment (Penyesuaian Ekor $|Z| > 3$)**:
   - Diterapkan pada indeks **BB/U** dan **BB/TB** ketika $|Z| > 3$ untuk mencegah distorsi asimetris kurva berat badan. Indeks **TB/U** tidak menggunakan tail adjustment.
6. **Kenaikan Berat Badan (KMS)**:
   - **N (Naik)**: Berat badan bertambah sesuai atau melampaui Kenaikan Berat Badan Minimal (KBM) berdasarkan bulan usia.
   - **T (Tidak Naik / Tetap / Turun)**: Pertambahan berat badan di bawah KBM atau mengalami penurunan.
7. **Deteksi Zona Merah (Kewaspadaan Gizi & Stunting)**:
   - Balita dengan status **Gizi Buruk** atau **Gizi Kurang** (BB/U).
   - Balita dengan status **Severely Stunted** atau **Stunted** (TB/U).
   - Balita dengan status **Gizi Buruk / Wasted** (BB/TB).
   - Balita dengan riwayat **2 kali penimbangan berturut-turut Tidak Naik (T-T)**.

---

## Kebutuhan Sistem

- **PHP**: Versi 8.3 atau lebih baru dengan ekstensi:
  - `pdo_pgsql`, `pgsql`, `bcmath`, `intl`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`.
- **Database**: PostgreSQL 14 atau lebih baru.
- **Node.js**: Versi 20.x LTS atau lebih baru, dan npm.
- **Composer**: Versi 2.6 atau lebih baru.

---

## Panduan Instalasi & Konfigurasi

### 1. Kloning Repositori & Dependensi Backend
```bash
git clone https://github.com/posyandudigital/posyandu-digital.git
cd posyandu-digital

composer install
```

### 2. Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi dasar:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan koneksi PostgreSQL pada file `.env`:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=posyandu_digital
DB_USERNAME=postgres
DB_PASSWORD=secret_password
```

### 3. Migrasi Database
Jalankan migrasi seluruh tabel (dengan PostgreSQL CHECK constraints):
```bash
php artisan migrate
```

---

## Import Data Acuan WHO LMS

Tabel `who_lms_reference` memerlukan data standar antropometri WHO Child Growth Standards 2006.

### Format File CSV (`nilai,l,m,s`)
Pastikan file CSV memiliki kolom header:
```csv
nilai,l,m,s
0,-0.1601,3.3464,0.14602
1,-0.1566,4.4709,0.13495
...
```

### Menjalankan Perintah Artisan
Gunakan perintah `who:import` untuk mengisi data acuan:
```bash
# Contoh import acuan BB/U Laki-laki
php artisan who:import bbu L usia_bulan storage/app/who/wfa_boys_0_to_5_zscores.csv

# Contoh import acuan TB/U Perempuan
php artisan who:import tbu P usia_bulan storage/app/who/lhfa_girls_0_to_5_zscores.csv

# Contoh import acuan BB/TB Laki-laki
php artisan who:import bbtb L panjang_cm storage/app/who/wfl_boys_0_to_2_zscores.csv
```

---

## Akun Demo & Database Seeding

Untuk menyiapkan data awal posyandu binaan, akun tenaga kesehatan, orang tua, dan rekam penimbangan riil selama 6 bulan:

```bash
php artisan db:seed
```

### Kredensial Masuk Bawaan (Default Login)
Semua akun menggunakan kata sandi: `password`

| Peran Pengguna | Email Masuk | Keterangan Akses |
|---|---|---|
| **Admin Desa** | `admin@desa.id` | Memantau seluruh posyandu di desa |
| **Bidan Desa** | `bidan@desa.id` | Posyandu Mawar (Pengukuran & Vaksin) |
| **Kader 1** | `kader1@desa.id` | Posyandu Mawar (Pendaftaran & Penimbangan) |
| **Kader 2** | `kader2@desa.id` | Posyandu Mawar |
| **Orang Tua 1** | `orangtua1@desa.id` | Wali balita (Akses Buku KMS Digital) |
| **Orang Tua 2** | `orangtua2@desa.id` | Wali balita |

*Catatan: Pengguna juga dapat masuk menggunakan nomor telepon yang terdaftar.*

---

## Kompilasi Frontend & Pengujian

### 1. Pemasangan Paket Node & Menjalankan Vite Dev Server
```bash
npm install
npm run dev
```

### 2. Kompilasi Produksi (Production Build)
```bash
npm run build
```

### 3. Menjalankan Seluruh Pengujian (Pest Framework)
```bash
# Menjalankan seluruh pengujian Unit & Feature
php artisan test

# Atau menggunakan binary Pest secara langsung
./vendor/bin/pest
```

Pengujian memvalidasi:
- Keakuratan Box-Cox LMS dan interpolasi linier.
- Penyesuaian ekor $|Z| > 3$ dan batas diskontinuitas 24 bulan.
- Isolasi multi-posyandu dan proteksi RBAC.
- Rollback transaksi database saat kegagalan penyimpanan pengukuran.
- Alur deteksi otomatis Balita Zona Merah (stunting/gizi buruk/T-T).
- Validasi ketat NIK 16 digit dan rentang klinis (berat 0.5–30kg, tinggi 35–130cm).

---

## Catatan Produksi & Keamanan Data (UU PDP)

Untuk panduan mendalam mengenai pengaturan pekerja antrean (Supervisor), Redis persistent caching untuk tabel WHO, pencadangan otomatis WAL PostgreSQL, dan kepatuhan hukum **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)**, silakan rujuk:

## 🔄 Migrasi & Setup Cepat ke Server Baru (Dengan Semua Data Siap Pakai)

Aplikasi telah dilengkapi dengan **Antigravity Skill** (`posyandu-deploy-setup`) dan skrip pemulihan database otomatis. Seluruh data (Kader, Bidan, Balita, Pengukuran KMS, Imunisasi) dapat dipindahkan ke server atau laptop lain dalam 1 langkah:

1. **Jalankan Setup di Server Baru:**
   - **Windows:** Jalankan `setup-server.bat`
   - **Linux / VPS:** Jalankan `bash setup-server.sh`
2. **Jalankan Aplikasi:**
   - `run-app.bat` atau `php artisan serve --host=0.0.0.0 --port=8000`
3. **Tunneling Publik ke Internet (HTTPS):**
   - **Windows:** Jalankan `tunnel.bat` (tersedia pilihan Cloudflare Tunnel, Ngrok, atau SSH)
   - **Linux:** Jalankan `bash tunnel.sh`
4. **Skill Otomatis:**
   - Buka Antigravity di server baru dan panggil skill: `/posyandu-deploy-setup`

---

## Lisensi & Kontribusi

Dikembangkan untuk mendukung program percepatan penurunan stunting nasional di Indonesia. Hak Cipta &copy; 2026 Posyandu Digital.
