# Panduan Operasional & Kepatuhan Produksi (Production Notes)
**Aplikasi Posyandu Digital – Sistem Pemantauan Kesehatan Balita**

Dokumen ini memuat panduan arsitektur produksi, optimalisasi performa, strategi pencadangan data, serta protokol kepatuhan hukum privasi data kesehatan berdasarkan **Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP)** dan regulasi Kementerian Kesehatan RI.

---

## 1. Antrean Latar Belakang (Queue Worker)

Untuk menjaga latensi HTTP tetap di bawah 200ms pada ponsel kader di lapangan, operasi intensif I/O dan jaringan dialihkan ke antrean asinkron (background workers).

### A. Pekerjaan yang Wajib Dijalankan via Antrean
1. **Import Tabel Referensi WHO LMS**: Pembacaan dan upsert ribuan baris CSV referensi Box-Cox WHO.
2. **Notifikasi WhatsApp / SMS Gateway**: Pengingat otomatis jadwal Posyandu dan jadwal vaksinasi tertunda kepada orang tua.
3. **Agregasi Statistik Bulanan**: Rekapitulasi cakupan D/S dan prevalensi stunting tingkat desa untuk dashboard Kepala Desa / Puskesmas.

### B. Konfigurasi Supervisor (Linux Production)
Simpan konfigurasi pada `/etc/supervisor/conf.d/posyandu-worker.conf`:

```ini
[program:posyandu-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/posyandu-digital/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/posyandu-digital/worker.log
stopwaitsecs=3600
```

---

## 2. Strategi Caching Tabel Standar Antropometri WHO LMS

Tabel `who_lms_reference` memuat ~11.000 titik data acuan WHO Child Growth Standards 2006. Data ini bersifat statis (tidak berubah) sehingga sangat ideal untuk di-cache.

### A. Level 1: In-Memory Per-Request Cache (Sudah Terimplementasi)
`AntropometriService` mengimplementasikan properti statis `$memoryCache`:
```php
private static array $memoryCache = [];
```
Pada saat satu request menghitung 3 indikator (BB/U, TB/U, BB/TB), query basis data untuk LMS terdekat hanya dieksekusi satu kali per kombinasi `(indikator, kelamin, acuan)`.

### B. Level 2: Redis Persistent Cache (Rekomendasi Skala Desa/Kecamatan)
Untuk server dengan ratusan kader yang melakukan input serentak saat hari buka Posyandu, aktifkan caching Redis pada level repository:

```php
// Contoh implementasi pada AntropometriService
$cacheKey = "who_lms:{$indikator}:{$jenisKelamin->value}:{$acuan}:{$nilai}";

return Cache::tags(['who_lms'])->rememberForever($cacheKey, function () use ($indikator, $jenisKelamin, $acuan, $nilai) {
    return $this->queryLmsDatabase($indikator, $jenisKelamin, $acuan, $nilai);
});
```

Saat melakukan update data acuan via command `who:import`, bersihkan tag cache:
```bash
php artisan cache:clear --tags=who_lms
```

---

## 3. Strategi Cadangan Database PostgreSQL (Automated Backups)

Data tumbuh kembang dan riwayat imunisasi anak adalah catatan medis vital yang tidak boleh hilang.

### A. Pencadangan Logikal Harian (Logical Backup)
Gunakan utilitas `pg_dump` dengan format custom terkompresi (`-Fc`):

```bash
#!/usr/bin/env bash
# /opt/scripts/backup-posyandu.sh
BACKUP_DIR="/mnt/backups/postgresql"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="posyandu_prod_${TIMESTAMP}.dump"

mkdir -p "$BACKUP_DIR"
pg_dump -U postgres -h 127.0.0.1 -d posyandu_digital -Fc -Z 6 -f "${BACKUP_DIR}/${FILENAME}"

# Hapus backup yang lebih tua dari 30 hari
find "$BACKUP_DIR" -name "posyandu_prod_*.dump" -mtime +30 -delete
```

Jadwalkan via `crontab -e`:
```cron
0 2 * * * /opt/scripts/backup-posyandu.sh > /dev/null 2>&1
```

### B. Point-In-Time Recovery (PITR) via WAL Archiving
Untuk menjamin Recovery Point Objective (RPO) < 5 menit, aktifkan Write-Ahead Logging (WAL) archiving di `postgresql.conf`:

```ini
wal_level = replica
archive_mode = on
archive_command = 'test ! -f /mnt/backups/wal/%f && cp %p /mnt/backups/wal/%f'
max_wal_senders = 3
```

---

## 4. Kepatuhan Privasi NIK & Rekam Medis (UU PDP No. 27/2022)

Berdasarkan **Pasal 4 Ayat (2) UU PDP**, data kesehatan anak dan Nomor Induk Kependudukan (NIK) dikategorikan sebagai **Data Pribadi yang Bersifat Spesifik**. Kelalaian dalam pengamanan data pribadi dapat berimplikasi sanksi administratif dan pidana.

### A. Enkripsi Saat Diam (Encryption-at-Rest)
1. **Laravel Model Attribute Encryption**:
   Gunakan cast `encrypted` pada atribut sensitif:
   ```php
   // app/Models/Balita.php
   protected function casts(): array
   {
       return [
           'nik_anak' => 'encrypted',
           'tanggal_lahir' => 'date',
           // ...
       ];
   }
   ```
2. **Pencarian Cepat dengan Blind Index (HMAC Hash)**:
   Karena data terenkripsi tidak dapat di-search dengan query `LIKE`, tambahkan kolom hash deterministik:
   - Kolom: `nik_anak_hash` (CHAR(64))
   - Generate: `hash_hmac('sha256', $nik, config('app.key'))`
   - Gunakan `nik_anak_hash` untuk pencarian exact match dan constraint `UNIQUE`.

### B. Penyamaran Data (Data Masking) pada Tampilan Antarmuka
Pada layar yang dapat dilihat umum saat penimbangan kader:
- **NIK Balita**: Tampilkan hanya 4 digit pertama dan 4 digit terakhir: `320101******0002`.
- **Nomor KK / Telepon**: Masking sebagian karakter untuk mencegah kebocoran visual (shoulder surfing).

### C. Jejak Audit Akses (Access Logging)
Berdasarkan Pasal 35 UU PDP, Pengendali Data Pribadi wajib merekam seluruh aktivitas pemrosesan data pribadi.
- Setiap aktivitas ekspor data, pencarian identitas balita, atau penghapusan rekam medis wajib dicatat dalam tabel `audit_logs` (User ID, IP Address, Waktu, Resource, Aksi).

### D. Multi-Tenancy Isolasi Wilayah
- Kader dan Bidan dibatasi secara tegas oleh middleware dan Policy Laravel hanya dapat mengakses data balita di **Posyandu binaan masing-masing**.
- Akses lintas posyandu diblokir secara mutlak pada level database query scope (`scopeDiPosyandu`).
