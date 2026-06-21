# Dokumen Transfer Teknologi & Rencana Pelatihan (Dev Report 010)
## Proyek: Sistem Informasi Sekolah SMP Islam Terpadu (SIS SMP IT) SaaS
**Peran:** Enterprise Systems Architect, Lead Developer, & Technical Trainer

---

## 1. Silabus Pelatihan Intensif 5 Hari (5-Day Technical Training Syllabus)

Program pelatihan ini dirancang untuk membekali tim pengembang sekunder, staff IT sekolah, dan administrator sistem agar mampu mengelola, mengembangkan, dan memelihara codebase **Domain-Modular Monolith** & SaaS ini secara mandiri.

### Hari 1: Pengenalan Arsitektur & Lingkungan Kerja (Core & Architecture)
- **Materi Pagi (09:00 - 12:00):**
  - Dekonstruksi kelemahan sistem legacy (prosedural, MyISAM, kerentanan MD5).
  - Visi arsitektur baru: Domain-Modular Monolith & SaaS Enterprise.
  - Memahami standardisasi file struktur Laravel 11.
- **Materi Siang (13:30 - 16:30):**
  - Setup lingkungan lokal dev: PHP 8.2+, Docker, Composer, dan Node.js.
  - Menjelaskan konfigurasi `.env` dan setting koneksi database InnoDB.
- **Target Capaian:** Peserta memahami perbedaan fundamental antara platform legacy dan modern, serta sukses menjalankan server development lokal (`php artisan serve`).

### Hari 2: Siklus Hidup SaaS & Isolasi Multi-Tenant
- **Materi Pagi (09:00 - 12:00):**
  - Konsep Multi-Tenancy: Shared Database vs Multi-Database.
  - Alur deteksi tenant berdasarkan subdomain sekolah (`IdentifyTenant` middleware).
  - Analisis tabel master `tenants`, `plugins`, dan pivot `tenant_plugins`.
- **Materi Siang (13:30 - 16:30):**
  - Penerapan isolasi data di tingkat database menggunakan Eloquent Trait `BelongsToTenant`.
  - Simulasi pencegahan kebocoran data (*cross-tenant data leakage prevention*).
- **Target Capaian:** Peserta mampu membuat tenant baru di database dan memverifikasi isolasi data antar sekolah secara aman.

### Hari 3: Pengembangan Domain-Modular Monolith (Modular MVC)
- **Materi Pagi (09:00 - 12:00):**
  - Pembagian 7 domain utama di `app/Modules/`.
  - Mekanisme *Dynamic Autowiring* oleh `ModuleServiceProvider.php`.
  - Cara membuat modul domain baru secara mandiri.
- **Materi Siang (13:30 - 16:30):**
  - Integrasi rute, view namespace (`module::view-name`), dan migrasi internal modul.
  - Desain Reusable Frontend Partials: Sidebar, Topbar, Tenant & Role Switcher.
- **Target Capaian:** Peserta mampu meng-generate satu modul baru lengkap dengan rute, model, controller, dan view-nya.

### Hari 4: Migrasi Database & Operasi ETL (Extract, Transform, Load)
- **Materi Pagi (09:00 - 12:00):**
  - Bedah 196 skema tabel migrasi hasil normalisasi 3NF.
  - Analisis penataan foreign key constraints dan engine InnoDB.
- **Materi Siang (13:30 - 16:30):**
  - Mengeksekusi script ETL `MigrateLegacyDataCommand.php` untuk memindahkan data riil dari 75 tabel legacy.
  - Teknik penanganan data cleansing: konversi varchar harga ke decimal, dan MD5 ke Bcrypt password.
  - Prosedur uji validasi dan rekonsiliasi total saldo keuangan & jumlah siswa.
- **Target Capaian:** Peserta menguasai prosedur migrasi database dari sistem lama ke sistem baru tanpa ada data loss.

### Hari 5: QA Testing, Deployment, & Handover
- **Materi Pagi (09:00 - 12:00):**
  - Menjalankan Static Code Linter & Integrity Testing menggunakan skrip `test_codebase_integrity.py`.
  - Penulisan Unit Test & Feature Test menggunakan PHPUnit/Pest di Laravel.
- **Materi Siang (13:30 - 16:30):**
  - Prosedur deployment ke server VPS Cloud (Nginx, SSL, PHP-FPM, MySQL 8).
  - Pengisian Dokumen Berita Acara Serah Terima (Checklist Handover).
- **Target Capaian:** Aplikasi sukses dideploy ke staging server dan tim IT sekolah menandatangani dokumen handover.

---

## 2. Checklist Handover Kesiapan Sistem (Handover Checklist)

| No | Parameter Kesiapan | Status | Metode Verifikasi |
| --- | --- | :---: | --- |
| 1 | Seluruh 7 Domain Modul terbentuk sempurna | **READY** | Jalankan verifikasi struktur di `app/Modules/` |
| 2 | Sistem Autowiring ModuleServiceProvider aktif | **READY** | Cek registrasi di `bootstrap/providers.php` |
| 3 | Middleware deteksi Subdomain aktif | **READY** | Verifikasi file `IdentifyTenant.php` |
| 4 | Trait `BelongsToTenant` terikat ke seluruh Model | **READY** | Verifikasi file `app/Traits/BelongsToTenant.php` |
| 5 | Pustaka skema database 196 tabel siap | **READY** | Jalankan `python sql_to_laravel_converter.py` |
| 6 | Database Seeder data simulasi terpasang | **READY** | Verifikasi file `DatabaseSeeder.php` |
| 7 | Script Integrity Testing lolos 100% Green | **READY** | Jalankan `python test_codebase_integrity.py` |
| 8 | Dokumentasi teknis & DFD Level 0-2 lengkap | **READY** | Periksa folder `blueprint-detail/` |

---

## 3. Materi Pendukung & Penutup

Materi ini disusun untuk menjamin proses **Knowledge Transfer** berjalan mulus. Pengembang sekunder diwajibkan membaca seluruh seri laporan pengembangan (001 s/d 009) sebelum memulai sesi hari pertama pelatihan demi efektivitas pemahaman arsitektur sistem.
