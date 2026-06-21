# Laravel Migration Runbook (Dev Report 013)
## Proyek: Sistem Informasi Sekolah SMP Islam Terpadu (SIS SMP IT) SaaS
**Peran:** Senior DevOps Engineer & Database Administrator  
**Konteks:** Panduan Operasional Migrasi Skema Database Per Modul (Laravel 11)

---

## 1. Pendahuluan

Runbook ini disusun sebagai panduan langkah-demi-langkah bagi tim DevOps dan sysadmin sekolah untuk mengeksekusi, membatalkan (*rollback*), meng-uji, dan melakukan pembibitan (*seeding*) skema database relasional modern **Laravel 11 (InnoDB)** di server development, staging, maupun production.

---

## 2. Persiapan Sebelum Migrasi (Pre-Migration Checklist)

Sebelum menjalankan migrasi di server production, pastikan langkah-langkah berikut telah selesai dieksekusi:

1.  **Backup Database Terakhir:**
    ```bash
    mysqldump -u [username] -p --databases db_smpit_modern > backup_before_migration_20260618.sql
    ```
2.  **Verifikasi Koneksi Database `.env`:**
    Pastikan kredensial koneksi database MySQL di file `.env` sudah benar dan menunjuk ke database InnoDB yang tepat.
3.  **Hapus Cache Konfigurasi:**
    ```bash
    php artisan config:clear
    php artisan cache:clear
    ```

---

## 3. Panduan Migrasi Langkah-Demi-Langkah Per Modul (Step-by-Step Runbook)

Karena sistem menggunakan pola **Domain-Modular Monolith**, pemuatan migrasi dikendalikan secara cerdas oleh `ModuleServiceProvider.php` yang membaca sub-folder database per modul.

### 3.1. Langkah 1: Eksekusi Seluruh Migrasi & Seeder Master SaaS
Jalankan perintah berikut untuk membangun skema database utuh (196 tabel skema baru) dan mengisi data master seeder secara otomatis:
```bash
php artisan migrate --seed
```
*Sistem secara otomatis akan mengeksekusi migrasi dari seluruh modul domain dengan urutan topologi yang aman.*

### 3.2. Langkah 2: Uji Validitas Struktur Tabel Setelah Migrasi
Gunakan CLI MySQL atau phpMyAdmin untuk menguji kesuksesan pembuatan tabel-tabel utama:
```sql
-- Pastikan tabel tenants dan users sudah terbentuk di engine InnoDB
SHOW TABLE STATUS WHERE Name IN ('tenants', 'users', 'siswa', 'guru_karyawan');
```

---

## 4. Prosedur Pembatalan & Pemulihan (Rollback & Reset Procedures)

Jika terjadi kesalahan struktur atau kegagalan transaksi data saat migrasi berlangsung, gunakan prosedur pemulihan berikut:

### 4.1. Membatalkan Langkah Migrasi Terakhir (Rollback Last Batch)
```bash
php artisan migrate:rollback
```

### 4.2. Mengatur Ulang Seluruh Struktur Database (Fresh Reset)
*Catatan: Perintah ini akan menghapus seluruh data yang ada di database. Hanya jalankan di dev/staging server.*
```bash
php artisan migrate:fresh --seed
```

### 4.3. Mengembalikan Backup SQL jika Gagal Total (Production Disaster Recovery)
Jika terjadi kegagalan fatal pada server live production:
1.  Masuk ke CLI mysql:
    ```bash
    mysql -u [username] -p db_smpit_modern < backup_before_migration_20260618.sql
    ```
2.  Periksa kembali integritas data sekolah sebelum sistem dibuka kembali ke publik.

---

## 5. Penyelesaian Masalah Umum (Troubleshooting Guide)

### 5.1. Masalah: "Foreign Key Constraint Violation"
- **Penyebab:** Urutan migrasi tabel detail dieksekusi mendahului tabel master (misal: tabel `kelas` dibuat sebelum tabel `tahun_ajaran` siap).
- **Solusi:** Pastikan penamaan berkas migrasi modular mengikuti timestamp yang benar (000000 -> 000010) agar Laravel mengeksekusi pemuatan tabel master terlebih dahulu.

### 5.2. Masalah: "Syntax error or access violation: 1071 Specified key was too long"
- **Penyebab:** Versi MySQL di bawah 5.7 tidak mendukung panjang key index default utf8mb4.
- **Solusi:** Buka file `app/Providers/AppServiceProvider.php` dan pastikan baris `Schema::defaultStringLength(191);` sudah aktif di fungsi `boot()`.

---

## 6. Penutup

Runbook ini menjamin operasi pemeliharaan database di tingkat sysadmin berjalan mulus dan sistematis. Simpan salinan runbook ini di server production sebagai panduan tanggap darurat (*disaster recovery*).
