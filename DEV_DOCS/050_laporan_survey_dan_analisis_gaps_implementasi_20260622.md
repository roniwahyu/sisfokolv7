# DEV_DOCS-050: Laporan Survey, Pemetaan, & Analisis Gaps Implementasi (Critical Audit)

- **Tanggal Audit:** 2026-06-22
- **Penulis:** Antigravity (Google DeepMind pair-agent)
- **Repo:** https://github.com/haisyamalawwab/sisfokolv7
- **Tujuan:** Pemetaan gap antara rencana pengembangan di `DEV_DOCS` (mulai dari `012`) dengan kondisi fisik riil pada codebase `sisfokol-laravel/`.

---

## ⚡ 1. RINGKASAN EKSEKUTIF: MASALAH UTAMA
Berdasarkan investigasi fisik pada database, migrations, seeders, routes, dan MVC pada codebase, kami mengonfirmasi adanya **"Ilusi Penyelesaian" (Illusion of Completeness)**. 

Meskipun modul-modul (Academic, Auth, Presence, Finance, Evaluation, dan Plugin Kurikulum) telah diimplementasikan dan pengujian otomatisnya menunjukkan status **PASS (112 tests green)**, sistem tidak terintegrasi secara fungsional di dunia nyata karena **terbelahnya basis data (Parallel Universes)** dan **keterputusan event hook (Dead Code)**.

---

## 🔍 2. DETAIL PEMETAAN DAN GAP ANALISIS PER EPIC

### EPIC 1: Setup & Fondasi
* **Status Klaim (`DEV_DOCS-012`, `013`):** ✅ Selesai
* **Verifikasi Fisik:**
  * Kelas fondasi ([TenantContext.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Support/TenantContext.php), [BelongsToTenant.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/Traits/BelongsToTenant.php), [TracksAuditColumns.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/Traits/TracksAuditColumns.php), dan helpers) terimplementasi dengan benar.
  * Seeder dasar (`SuperAdminSeeder`, `RolePermissionSeeder`, `MenuSeeder`, `FieldSeeder`) tersedia dan berfungsi.
* **Gaps Temuan:**
  * ❌ **Model Core Mengabaikan Tenancy**: Model-model core di `app/Models/` ([Student.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/Student.php), [Classroom.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/Classroom.php), [Subject.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/Subject.php)) **tidak menggunakan** trait `BelongsToTenant`. Ini melanggar keputusan keamanan multi-tenancy ADR-003.

### EPIC 2 & 3: Auth & RBAC
* **Status Klaim (`DEV_DOCS-014` s.d. `018`):** ✅ Selesai
* **Verifikasi Fisik:**
  * Login/logout throttling, force password reset middleware ([ForcePasswordReset.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Http/Middleware/ForcePasswordReset.php)), dan impersonation guard berjalan aman.
  * Ajax Permission Matrix, Override Menu dinamis ([MenuRenderer.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Support/MenuRenderer.php)), dan Field ACL Blade directives terimplementasi.
* **Gaps Temuan:**
  * ❌ **API Infrastructure Gaps**: Berkas `config/cors.php` belum dipublish. Folder `app/Http/Resources/` belum dibuat. Konfigurasi Sanctum di `.env.example` belum lengkap.

### EPIC 4: Plugin Infrastructure
* **Status Klaim (`DEV_DOCS-019`, `023`):** ✅ Selesai
* **Verifikasi Fisik:**
  * `PluginRegistryServiceProvider`, `PluginContract`, `EnsurePluginEnabled` middleware, dan `PluginActivationService` terimplementasi.
* **Gaps Temuan:**
  * ❌ **Event Hook Core Terputus**: Infrastruktur siap melakukan aktivasi/deaktivasi plugin per tenant, namun core utama (Evaluation & Rapor) tidak memicu event-event tersebut secara nyata.

### EPIC 5: Academic Module (Modular)
* **Status Klaim (`DEV_DOCS-025`, `026`):** ✅ Selesai
* **Verifikasi Fisik:**
  * 11 tabel modular migrasi bahasa Indonesia (`siswa`, `kelas`, `mapel`, `tahun_ajaran`, `semester`) beserta model, controller, service conflict checker, dan views Blade tersedia lengkap di bawah folder [app/Modules/Academic/](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Modules/Academic/).
* **Gaps Temuan (Kritis):**
  * ❌ **Akar Parallel Universes**: Data siswa baru dimasukkan ke tabel `siswa`. Namun, modul penilaian (Evaluation) membaca dari tabel core `students`. Keduanya terputus secara fisik di basis data.

### EPIC 6: Evaluation Module (Penilaian & Rapor)
* **Status Klaim (`DEV_DOCS-031`):** ✅ Selesai
* **Verifikasi Fisik:**
  * `GradeEntryController.php`, `RaporController.php`, dan `GradeCalculatorService.php` terimplementasi.
* **Gaps Temuan (Fatal):**
  * ❌ **Controller Hilang (Crash Rute)**: Berkas `CurriculumController.php` **tidak ada di disk** di dalam folder `app/Modules/Evaluation/Controllers/`, padahal diimpor dan dideklarasikan di rute `app/Modules/Evaluation/routes.php`. Mengakses rute `/evaluation/curriculum` dipastikan memicu crash runtime `Class not found`.
  - ❌ **Event Hook Terputus (Kode Mati)**: Pemicu event `event(new EvaluationResolveFramework(...))` di `GradeEntryController` dan `event(new RaportRenderSection(...))` di `RaporGeneratorService` **tidak pernah ditulis**. Sehingga Plugin Kurikulum (Epic 9) tidak pernah terpanggil.
  - ❌ **Type Mismatch Parameter**: Event `EvaluationResolveFramework` mengharuskan parameter model modular (`Mapel` & `Kelas`), sementara `GradeEntryController` menyuplai model core (`Subject` & `Classroom`). Ini akan memicu fatal `TypeError` saat dipanggil.

### EPIC 7: Finance Module (Modular & Core)
* **Status Klaim (`DEV_DOCS-035`, `039`):** ✅ Selesai
* **Verifikasi Fisik:**
  * Modular Finance (`PembayaranController` SPP + tabungan siswa) terimplementasi di bawah `app/Modules/Finance/`.
* **Gaps Temuan:**
  * ❌ **Tumpang Tindih Menu Kasir (Redundansi)**: Ada dua system kasir terpisah: Kasir Modular (mengelola model `Siswa`/tabel `siswa`) dan Kasir Core (mengelola model `Student`/tabel `students`). Pembayaran di kasir modular tidak memotong tagihan di kasir core, memicu kekacauan laporan keuangan sekolah.
  * ❌ **Duplikasi View**: Folder view di `resources/views/finance/` terduplikasi:
    - `payment-items` vs `item-pembayaran`
    - `student-bills` vs `tagihan`
    - `student-payments` vs `pembayaran`
    - `student-savings` vs `tabungan`

### EPIC 8: Presence Module
* **Status Klaim (`DEV_DOCS-027` s.d. `029`):** ✅ Selesai
* **Verifikasi Fisik:**
  * Kamera scanner QR, rekap absensi, approval izin, dan laporan bulanan terimplementasi.
* **Gaps Temuan:**
  * ❌ **Roster Presensi Terbelah**: Mesin scan QR merekam kehadiran pada `attendable_type = Siswa::class` (tabel `siswa`), sedangkan menu absensi kelas guru memuat daftar murid menggunakan model `Student` (tabel `students`). Guru tidak akan bisa melihat status kehadiran gerbang murid.

### EPIC 9: Plugin Kurikulum
* **Status Klaim (`DEV_DOCS-037`, `040`):** ✅ Selesai
* **Verifikasi Fisik:**
  * CRUD Kurikulum Merdeka/K13, Struktur Kurikulum, Komponen CP/TP, serta subscribers (`EvaluationFrameworkSubscriber`, `RaporSectionSubscriber`) terimplementasi.
* **Gaps Temuan:**
  * ❌ **Kode Mati Terisolasi**: Seluruh subscriber plugin ini tidak pernah berjalan karena core Evaluation tidak pernah men-dispatch event-nya.

### EPIC 10 & 11: 8 Plugin Scaffold & ETL
* **Status Klaim (`DEV_DOCS-042`):** ⏳ Pending
* **Gaps Temuan:**
  * ❌ **Scaffolding Only**: 8 plugin tambahan masih berupa boilerplate folder kosong tanpa migrasi dan model. `MigrateLegacyDataCommand` untuk ETL migrasi data legacy `sisfokolv7` masih berupa draft skeleton.

---

## 📜 3. SEJARAH GIT & AKAR MASALAH (RCA)

Pemeriksaan log git mengungkapkan penyebab terjadinya dualisme sistem ini:
1. **Initial Upload**: Repositori diunggah pertama kali dengan menyertakan tabel dan model core bahasa Inggris (`students`, `classrooms`, `subjects`, `academic_years`) bawaan generator lama.
2. **Pola Kerja Modular Terisolasi**:
   - Pengembang/agen berikutnya diinstruksikan membangun modul akademik, presensi, dan keuangan modular dengan penamaan bahasa Indonesia (`siswa`, `kelas`, `mapel`).
   - Karena modul baru dibuat secara independen di bawah folder `app/Modules/`, pengembang membuat tabel dan model baru dari nol tanpa menghapus, menyinkronkan, atau melakukan migrasi pada tabel core Inggris yang sudah ada.
3. **Hacks pada Unit Test**:
   - Demi meloloskan pengujian rapor (`RaporGeneratorTest.php`) agar tetap berwarna hijau (*green*), pengembang melakukan penugasan ID secara paksa pada test setup:
     ```php
     $this->student = new Student([...]);
     $this->student->id = $this->siswa->id; // Bypass mass-assignment protection
     $this->student->save();
     ```
     Hal ini menyembunyikan kenyataan bahwa di dunia nyata, ID auto-increment basis data antara tabel `siswa` dan `students` akan menyimpang (*diverge*) dan menyebabkan sistem tidak sinkron.

---

## 🛠️ 4. REKOMENDASI PERBAIKAN BERTAHAP

### TAHAP 1: Unifikasi Model & Database (Kritis/Utama)
1. **Hapus Tabel Redundan**: Drop tabel-tabel Inggris (`students`, `classrooms`, `subjects`, `academic_years`) dari basis data.
2. **Alihkan Model Core**: Modifikasi properti `$table` pada model-model core di `app/Models/` agar merujuk langsung ke tabel modular bahasa Indonesia:
   - `Student` $\rightarrow$ `protected $table = 'siswa';`
   - `Classroom` $\rightarrow$ `protected $table = 'kelas';`
   - `Subject` $\rightarrow$ `protected $table = 'mapel';`
   - `AcademicYear` $\rightarrow$ `protected $table = 'tahun_ajaran';`
3. **Tambahkan Trait Tenancy**: Tambahkan `use BelongsToTenant, TracksAuditColumns;` ke dalam model-model core tersebut untuk menjamin isolasi data per sekolah.
4. **Sesuaikan Foreign Key**:
   - Ubah constraint `foreignId('student_id')->constrained('students')` menjadi `constrained('siswa')` di semua tabel penilaian, keuangan, dan presensi.
   - Gunakan Model Accessor/Mutator pada model core untuk menangani perbedaan nama kolom (e.g. `student->name` memetakan ke kolom `nama` di tabel `siswa`).

### TAHAP 2: Aktivasi Event Hook & Perbaiki Rute Crash
1. **Buat Controller Kurikulum**: Implementasikan berkas [CurriculumController.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Modules/Evaluation/Controllers/CurriculumController.php) di modul `Evaluation` agar rute `/evaluation/curriculum` tidak crash.
2. **Suntikkan Dispatcher Event**:
   - Di [GradeEntryController.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Modules/Evaluation/Controllers/GradeEntryController.php), pemicu event framework sebelum form nilai dirender:
     ```php
     $event = new EvaluationResolveFramework($subject, $classroom);
     event($event);
     $framework = $event->framework;
     ```
   - Di [RaporGeneratorService.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Modules/Evaluation/Services/RaporGeneratorService.php), pemicu event render seksi rapor:
     ```php
     $event = new RaportRenderSection($siswa, $academicYear, $semester);
     event($event);
     $customSections = $event->sections;
     ```
3. **Perbaiki Parameter Type Hint**: Ubah type hint pada parameter konstruktor event `EvaluationResolveFramework` dan `RaportRenderSection` agar merujuk ke class model hasil unifikasi di Tahap 1.

### TAHAP 3: Konsolidasi Logika Duplikat (Finance & Presence)
1. **Hapus Duplikasi Kasir**: Hapus `StudentPaymentController` (Core Finance) beserta views-nya. Tetapkan kasir modular (`PembayaranController` di `app/Modules/Finance`) sebagai kasir tunggal sistem.
2. **Unifikasi Presensi**: Sambungkan `QrScannerService` langsung ke query rekap dan presensi guru dengan memanfaatkan model `Student` yang telah di-unifikasi (menghilangkan dualisme `attendable_type`).
