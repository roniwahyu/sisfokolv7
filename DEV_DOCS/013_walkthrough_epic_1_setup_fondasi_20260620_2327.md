# DEV_DOCS-013: Walkthrough — Epic 1: Setup & Fondasi

- **Tanggal:** 2026-06-20 23:27
- **Status:** ✅ SELESAI
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith
- **Penulis:** Antigravity (Google DeepMind)

---

## ⚡ Ringkasan Eksekusi

Epic 1 (Setup + Fondasi) telah berhasil diselesaikan seluruhnya. Semua pengujian otomatis (19 feature & unit tests) dan database seeding berjalan dengan status **PASS** (100% Green).

---

## 🛠️ Perubahan Teknis yang Dilakukan

### 1. Penyelarasan Spatie Permission Teams dengan Tenancy
* **Masalah:** Spatie laravel-permission menggunakan mode teams (`teams => true` di `config/permission.php`). Ketika seeder atau pengujian berjalan, Spatie mencoba memasukkan data ke tabel pivot `model_has_roles` tanpa `team_id`, yang memicu error `Integrity constraint violation (Column 'team_id' cannot be null)` karena kolom tersebut bertindak sebagai bagian dari Primary Key tabel.
* **Solusi:** 
  * Menambahkan wrapper dinamis `runInTeamContext` pada [User.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Models/User.php).
  * Meng-override metode penugasan dan pemeriksaan Spatie (`assignRole`, `removeRole`, `syncRoles`, `hasRole`, `hasAnyRole`, `hasAllRoles`, `hasPermissionTo`, `givePermissionTo`, `revokePermissionTo`, `syncPermissions`) agar secara otomatis menyetel `team_id` Spatie berdasarkan `tenant_id` objek user (menggunakan nilai fallback `0` untuk pengguna SuperAdmin global/tanpa tenant).
  * Memastikan keamanan konkurensi context menggunakan konstruksi `try ... finally` agar team context Spatie selalu dikembalikan ke state semula setelah operasi selesai.

### 2. Penambahan Permission pada Seeder
* **Masalah:** Beberapa peran bawaan (seperti `principal` dan `teacher`) didefinisikan dengan permission spesifik (seperti `user.view`, `academic.schedule.view`, dll.) di dalam `RolePermissionSeeder.php`, namun permission tersebut belum didaftarkan di dalam daftar master `$permissions` sehingga memicu exception `PermissionDoesNotExist`.
* **Solusi:** Menambahkan seluruh permission spesifik yang terlewat (total 10 permission baru) ke dalam daftar inisialisasi awal di [RolePermissionSeeder.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/database/seeders/RolePermissionSeeder.php).

### 3. Pendaftaran Middleware Spatie
* **Masalah:** Rute admin dan guru memicu error `BindingResolutionException: Target class [role] does not exist` karena middleware bawaan Spatie (`role`, `permission`, `role_or_permission`) belum diregistrasikan di kernel/bootstrap aplikasi.
* **Solusi:** Mendaftarkan alias middleware tersebut pada berkas [bootstrap/app.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/bootstrap/app.php) dalam `$middleware->alias()`.

### 4. Perbaikan Kasus Pengujian (Test Cases)
* **ExampleTest.php**: Mengubah target assert dari `/` ke `/login` (rute `/` dikonfigurasi mengembalikan redirect 302 ketika berstatus guest).
* **ScheduleTest.php**: Memastikan test memanggil `$this->seed(\Database\Seeders\RolePermissionSeeder::class)` sebelum proses assign role agar relasi Spatie database tersedia dalam memory `RefreshDatabase` yang bersih.

---

## 📈 Verifikasi Akhir

### 1. Seeding Database (`php83 artisan db:seed`)
```powershell
   INFO  Seeding database.  

  Database\Seeders\RolePermissionSeeder ....................................... DONE  
  Database\Seeders\SchoolProfileSeeder ........................................ DONE  
  Database\Seeders\AcademicYearSeeder ......................................... DONE  
  Database\Seeders\DaySeeder .................................................. DONE  
  Database\Seeders\HourSeeder ................................................. DONE  
  Database\Seeders\TimeSlotSeeder ............................................. DONE  
  Database\Seeders\SubjectTypeSeeder .......................................... DONE  
  Database\Seeders\AttendanceTimeSeeder ....................................... DONE  
  Database\Seeders\UserSeeder ................................................. DONE  
  Database\Seeders\ClassroomSeeder ............................................ DONE  
```

### 2. Pengujian Unit & Fitur (`php83 artisan test`)
```powershell
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                                                            0.01s  

   PASS  Tests\Unit\Models\Traits\BelongsToTenantTraitTest
  ✓ global scope filters by tenant id                                                                            0.86s  
  ✓ superadmin context sees all tenants                                                                          0.21s  
  ✓ create auto fills tenant id                                                                                  0.28s  

   PASS  Tests\Unit\Models\Traits\TracksAuditColumnsTest
  ✓ create sets created by from auth                                                                            31.99s  

   PASS  Tests\Unit\Support\TenantContextTest
  ✓ initial state is uninitialized                                                                               0.05s  
  ✓ set and get tenant id                                                                                        0.04s  
  ✓ clear resets state                                                                                           0.02s  
  ✓ is superadmin context when uninitialized                                                                     0.02s  
  ✓ is not superadmin context when initialized                                                                   0.02s  

   PASS  Tests\Feature\AuthTest
  ✓ login page can be rendered                                                                                  31.52s  
  ✓ users can authenticate using username                                                                        0.10s  
  ✓ users can not authenticate with invalid password                                                             0.48s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                0.03s  

   PASS  Tests\Feature\ScheduleTest
  ✓ admin can create schedule                                                                                    0.96s  

   PASS  Tests\Feature\Setup\DatabaseConnectionTest
  ✓ it can connect to default database                                                                           0.02s  
  ✓ it can connect to legacy database                                                                            0.02s  

   PASS  Tests\Feature\Setup\ResolveTenantMiddlewareTest
  ✓ superadmin login leaves context uninitialized                                                                0.07s  
  ✓ normal user initializes context with their tenant                                                            0.04s  

  Tests:    19 passed (32 assertions)
  Duration: 67.32s
```
