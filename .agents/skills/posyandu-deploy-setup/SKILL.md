---
name: posyandu-deploy-setup
description: >-
  Gunakan skill ini untuk menyiapkan, memulihkan data, dan mendeploy aplikasi
  Posyandu Digital pada server baru atau mesin lain dengan seluruh data yang ada
  (Pengguna, Kader, Balita, Pengukuran Antropometri KMS, dan Imunisasi), serta
  menyiapkan tunneling internet (Cloudflare/Ngrok/SSH) siap pakai.
---

# Skill: Setup & Migrasi Posyandu Digital ke Server Baru

Skill ini memandu proses setup menyeluruh aplikasi **Posyandu Digital** ketika dipindahkan ke server baru, komputer lain, atau VPS. Menjamin **seluruh data yang sudah ada tetap utuh**, aset ter-compile, dan server siap diakses secara online melalui tunneling.

---

## 📁 Struktur Data & Snapshot Cadangan

Seluruh data aktif telah dicadangkan di direktori `database/backup/`:
* `database/backup/posyandu_snapshot.sqlite`: Snapshot biner database SQLite lengkap.
* `database/backup/posyandu_snapshot.sql`: Dump DDL tabel + seluruh baris data.
* `database/backup/posyandu_data.json`: Ekspor data terstruktur format JSON.
* `scripts/restore_database.php`: Skrip pemulihan data otomatis.
* `scripts/export_database.php`: Skrip pencadangan berkala.

---

## 🚀 Prosedur Cepat (1-Perintah)

Jika Anda berada di server baru, jalankan salah satu perintah berikut:

### Pada Windows:
```cmd
.\setup-server.bat
```

### Pada Linux / VPS / macOS:
```bash
bash setup-server.sh
```

---

## 🛠️ Langkah-Langkah Manual / Step-by-Step

Jika Anda ingin menjalankan setiap tahapan secara manual atau menggunakan terminal:

### Langkah 1: Persiapan File Environment (`.env`)
Salin template konfigurasi dan pastikan file `.env` ada:
```bash
cp .env.example .env
```
Pastikan pengaturan koneksi database adalah SQLite:
```env
DB_CONNECTION=sqlite
APP_TIMEZONE="Asia/Jakarta"
APP_LOCALE=id
```

### Langkah 2: Buat Application Key
```bash
php artisan key:generate --force
```

### Langkah 3: Pulihkan Seluruh Data Database
Jalankan skrip restore untuk mengembalikan seluruh akun pengguna, master posyandu, balita, riwayat penimbangan KMS, dan imunisasi:
```bash
php scripts/restore_database.php
```
Pastikan skema tabel sinkron dengan database:
```bash
php artisan migrate --force
```

### Langkah 4: Tautkan Storage & Bersihkan Cache
```bash
php artisan storage:link
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Langkah 5: Pasang Dependensi & Build Aset Frontend
Jika `vendor` atau `node_modules` belum ada di server baru:
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### Langkah 6: Jalankan Server Laravel
Jalankan development server agar mendengarkan koneksi lokal maupun tunnel:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 🌐 Menjalankan Tunneling Internet (Akses Publik Online)

Aplikasi siap dibuka ke publik untuk diakses oleh Kader di Posyandu dan Orang Tua melalui HP/Internet.

### Opsi A: Cloudflare Zero Trust Tunnel (Rekomendasi Utama dengan Custom Domain)
Jika Anda menggunakan **Cloudflare Zero Trust** (melalui Dashboard Cloudflare > Networks > Tunnels):
* **Service / Type:** `HTTP`
* **URL:** `127.0.0.1:8000` (atau `localhost:8000`)
* **Host / IP:** `127.0.0.1`
* **Port:** `8000`
* *Catatan Keamanan:* Aplikasi sudah otomatis dikonfigurasi dengan `trustProxies('*')` di `bootstrap/app.php` sehingga seluruh protokol HTTPS, redirect login, dan CSRF token akan sinkron secara instan tanpa isu redirect loop atau mixed content.

### Opsi B: Cloudflare Quick Tunnel (Tanpa Dashboard)
```bash
cloudflared tunnel --url http://127.0.0.1:8000
```
> Menghasilkan URL sementara seperti `https://random-nama-subdomain.trycloudflare.com`.

### Opsi B: Ngrok
```bash
ngrok http 8000
```

### Opsi C: SSH Localhost.run (Tanpa Perlu Instalasi Apapun)
Jika server memiliki terminal SSH bawaan:
```bash
ssh -R 80:localhost:8000 nokey@localhost.run
```

---

## 🔒 Konfigurasi `APP_URL` Saat Tunneling
Agar formulir login, redirect Inertia.js, dan aset CSS/JS bekerja dengan mulus saat diakses via URL tunnel HTTPS:
1. Buka `.env`
2. Ubah `APP_URL` menjadi URL tunnel publik yang aktif:
   ```env
   APP_URL=https://nama-tunnel-anda.trycloudflare.com
   ```
3. Jalankan `php artisan config:clear`.

---

## 🔑 Akun & Kredensial Default Siap Pakai
Setelah di-restore, semua akun bawaan langsung aktif (lihat [KREDENSIAL_DEFAULT.md](../../KREDENSIAL_DEFAULT.md)):
* **Super Admin**: `admin@posyandu.id` / `password123`
* **Admin Desa**: `admindesa@posyandu.id` / `password123`
* **Bidan Desa**: `bidan@posyandu.id` / `password123`
* **Kader Posyandu**: `kader@posyandu.id` / `password123`
* **Orang Tua**: `orangtua@posyandu.id` / `password123`
