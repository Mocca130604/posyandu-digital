# Daftar Akun & Kredensial Default (Posyandu Digital)

Dokumen ini berisi informasi seluruh akun default hasil *Database Seeder* (`php artisan db:seed`) beserta hak akses dan rincian perannya.

> **Kata Sandi (Password) untuk Seluruh Akun Default:**  
> `password123`

---

## 1. Administrator Desa (Kepala Desa / Perangkat Desa)
Memiliki kewenangan pemantauan menyeluruh tingkat desa, statistik cakupan penimbangan (D/S), prevalensi stunting, dan agregasi data seluruh Posyandu se-desa.

| Data Kredensial | Rincian |
|---|---|
| **Nama Lengkap** | Bambang Sudarmono, S.STP |
| **Email** | `admin@desa.id` |
| **Nomor Telepon** | `081234567890` |
| **Password** | `password123` |
| **Peran (Role)** | `admin_desa` |
| **Cakupan Akses** | Global Seluruh Wilayah Desa Sukamaju |
| **Halaman Utama** | `/dashboard` (Dashboard Administrator Desa) |

---

## 2. Tenaga Kesehatan: Bidan Desa
Bertanggung jawab atas pengawasan medis, pencatatan imunisasi balita, penanganan kasus balita di Zona Merah, dan rujukan ke Puskesmas.

| Data Kredensial | Rincian |
|---|---|
| **Nama Lengkap** | Bidan Siti Rohmah, S.Tr.Keb |
| **Email** | `bidan@desa.id` |
| **Nomor Telepon** | `081234567891` |
| **Password** | `password123` |
| **Peran (Role)** | `bidan` |
| **Posyandu Binaan** | Posyandu Mawar 03 (RW 005 Sukamaju) |
| **Wewenang Khusus** | Pencatatan & Penghapusan Imunisasi Medis, Validasi Antropometri |

---

## 3. Kader Kesehatan Posyandu
Bertugas dalam pendaftaran sasaran balita baru, pelaksanaan penimbangan bulanan (antropometri), deteksi balita belum ditimbang (*sweeping*), dan penyuluhan gizi.

### A. Kader 1
| Data Kredensial | Rincian |
|---|---|
| **Nama Lengkap** | Kader Sri Wahyuni |
| **Email** | `kader1@desa.id` |
| **Nomor Telepon** | `081234567892` |
| **Password** | `password123` |
| **Peran (Role)** | `kader` |
| **Posyandu Binaan** | Posyandu Mawar 03 |

### B. Kader 2
| Data Kredensial | Rincian |
|---|---|
| **Nama Lengkap** | Kader Dewi Lestari |
| **Email** | `kader2@desa.id` |
| **Nomor Telepon** | `081234567893` |
| **Password** | `password123` |
| **Peran (Role)** | `kader` |
| **Posyandu Binaan** | Posyandu Mawar 03 |

---

## 4. Orang Tua / Wali Balita (Portal Buku KMS Digital)
Orang tua dapat memantau grafik tumbuh kembang anak secara mandiri (KMS Permenkes No. 2 Tahun 2020), riwayat penimbangan, jadwal imunisasi berikutnya, serta panduan nutrisi MPASI.

### A. Keluarga 1
| Data Kredensial | Rincian |
|---|---|
| **Nama Ibu** | Siti Fatimah |
| **Nama Ayah** | Ahmad Syafii |
| **Email Masuk** | `ortu1@desa.id` |
| **Nomor Telepon** | `081234567894` |
| **Password** | `password123` |
| **Peran (Role)** | `orang_tua` |
| **Data Anak** | **Muhammad Rayyan Alfarizqi** (Laki-laki, Usia 9 Bulan) |
| **No KK** | `3276011204180001` |

### B. Keluarga 2
| Data Kredensial | Rincian |
|---|---|
| **Nama Ibu** | Nur Halimah |
| **Nama Ayah** | Bambang Pamungkas |
| **Email Masuk** | `ortu2@desa.id` |
| **Nomor Telepon** | `081234567895` |
| **Password** | `password123` |
| **Peran (Role)** | `orang_tua` |
| **Data Anak** | **Aisyah Ayudia Inara** (Perempuan, Usia 14 Bulan) |
| **No KK** | `3276011204180002` |

### C. Keluarga 3
| Data Kredensial | Rincian |
|---|---|
| **Nama Ibu** | Dewi Sartika |
| **Nama Ayah** | Hendra Wijaya |
| **Email Masuk** | `ortu3@desa.id` |
| **Nomor Telepon** | `081234567896` |
| **Password** | `password123` |
| **Peran (Role)** | `orang_tua` |
| **Data Anak** | **Alvaro Kenzi Danendra** (Laki-laki, Usia 11 Bulan) |
| **No KK** | `3276011204180003` |

---

## 5. Ringkasan Cepat untuk Pengujian (Quick Login Sheet)

| No | Email | Password | Peran |
|:---:|---|---|---|
| 1 | `admin@desa.id` | `password123` | Administrator Desa |
| 2 | `bidan@desa.id` | `password123` | Bidan Desa |
| 3 | `kader1@desa.id` | `password123` | Kader Posyandu 1 |
| 4 | `kader2@desa.id` | `password123` | Kader Posyandu 2 |
| 5 | `ortu1@desa.id` | `password123` | Orang Tua Balita 1 |
| 6 | `ortu2@desa.id` | `password123` | Orang Tua Balita 2 |
| 7 | `ortu3@desa.id` | `password123` | Orang Tua Balita 3 |

*Tips: Pengguna dapat masuk menggunakan alamat **Email** maupun **Nomor Telepon** yang tertera di atas.*
