# DEV_DOCS-050: Audit Tahap 1 — Verifikasi Gap Implementasi vs DEV_DOCS sejak Epic 3

- **Tanggal audit:** 2026-06-22
- **Auditor:** Arena.ai Agent Mode
- **Repo:** `https://github.com/haisyamalawwab/sisfokolv7.git`
- **Checkout lokal:** `/home/user/sisfokolv7`
- **Branch/commit:** `main` @ `7926ba4` (`docs: add audit report for SISFOKOL v7 Epic 1 identifying critical implementation gaps`)
- **Metode:** verifikasi statis file fisik, migrasi, seeder, MVC, route, grep referensi class/view/event, dan `git log` per area. **Catatan batasan:** sandbox ini tidak memiliki `php`/`composer`, sehingga test suite dan `artisan route:list` belum dapat dijalankan; kesimpulan runtime di bawah berasal dari bukti statis yang deterministik.

---

## 1. Executive Summary

Implementasi sejak Epic 3 **tidak bisa dinyatakan lengkap** walaupun beberapa DEV_DOCS walkthrough menulis “SELESAI/green tests”. Banyak komponen fisik memang ada, tetapi terdapat gap kritis:

1. **RBAC/Menu (Epic 3) ada, tetapi seeder menu/permission tidak sinkron dengan route nyata.** Sidebar dinamis akan banyak menghasilkan item hilang atau `#`; RBAC Builder sendiri memakai permission `rbac.manage` yang tidak disemai oleh `RolePermissionSeeder`.
2. **Plugin infra (Epic 4) ada, tetapi plugin menu tidak pernah dipersist ke tabel `menus`, dan aktivasi plugin tidak otomatis membuat menu muncul di sidebar.**
3. **Academic (Epic 5) hanya benar-benar punya CRUD Siswa.** Rencana menyebut banyak controller/view akademik lain, tetapi file fisik tidak ada. `JadwalConflictChecker` dan `KelasSiswaPromotionService` teruji langsung sebagai service, belum terhubung ke UI/controller operasional.
4. **Evaluation (Epic 6) punya route ke `CurriculumController` yang file-nya tidak ada.** Ini crash pasti saat route dimuat/diakses. Event hook Kurikulum juga tidak dipanggil dari `GradeEntryController` maupun `RaporGeneratorService`.
5. **Divergensi model/schema sangat nyata:** `siswa/kelas/mapel/tahun_ajaran` berjalan paralel dengan `students/classrooms/subjects/academic_years`. Ini bukan isu dokumentasi saja; controller/service lintas modul memang memakai dua dunia data berbeda.
6. **Finance (Epic 7) modul modular ada, tetapi berdampingan dengan controller/model core finance lama; policy module Finance tidak terdaftar dalam provider yang aktif; nomor nota belum benar-benar atomik.**
7. **Presence (Epic 8) memiliki error fisik yang jelas:** `AbsensiController` memanggil relasi `attendable` pada model `Absence`, padahal relasinya `absentable`; view `presence.absensi.*` tidak ada; store absence tidak mengisi kolom wajib `type`.
8. **Kurikulum Plugin (Epic 9) CRUD ada, tetapi integrasinya ke Evaluation belum berjalan.** Selain itu ada mismatch controller vs migration (`deskripsi`, enum `jenis_kegiatan`, enum `pendekatan_pedagogis`) dan policy registration bermasalah.
9. **Git history mendukung kesimpulan ini:** commit kode implementasi mayoritas terjadi pada 2026-06-21; commit terbaru setelah audit kritis mostly dokumentasi, sehingga gap yang sudah dicatat di DEV_DOCS-043/044/045 belum diperbaiki di kode.

Kesimpulan audit tahap 1: status implementasi efektif adalah **PARTIAL / TIDAK LENGKAP**, dengan prioritas fix dimulai dari runtime crash dan integrasi data-model sebelum melanjutkan fitur baru.

---

## 2. Bukti Git History

Ringkas hasil `git log --all --oneline` per area:

| Area | Commit kode terakhir yang relevan | Catatan |
|---|---:|---|
| RBAC/Auth module | `0bdf378`, `5e089e1`, `1716b27`, `622bc33` | Implementasi RBAC/menu/plugin infra ada, tetapi seeder dan route tidak sinkron. |
| Academic module | `36ec319`, `6e3b111`, `b0d3a8e`, `688d5f2` | Model/migration akademik ada; CRUD hanya Siswa. |
| Evaluation module | `36ec319`, `2ebe3f0`, `e4b1a2d`, `b9108a8`, `25d6b09` | Route mengacu controller hilang; event hook tidak dipakai oleh controller/service utama. |
| Finance module | `7e2e7a8` | Implementasi modular satu batch; tidak menghapus/menyatukan finance core lama. |
| Presence module | `41d4302`, `c53ae2a`, `8a154c5`, `ae504e4`, `671a4a6` | Ada implementasi service/controller, tetapi ditemukan runtime defects statis. |
| Kurikulum plugin | `d91072a`, `4f57952`, `30aee20`, `405de67`, `0fd90d5`, `36ec319` | Plugin CRUD/subscriber ada; core evaluation belum memanggilnya. |
| Commit terbaru `7926ba4` s.d. beberapa sebelumnya | docs-only | Audit/recovery plan ditambahkan, tetapi kode belum difix. |

---

## 3. Pemetaan Implementasi per Epic

### Epic 3 — RBAC Builder + Field ACL + Menu Renderer

**Dokumen acuan:** `DEV_DOCS/016_implementation_plan_epic_3_20260621_0805.md`, `DEV_DOCS/018_walkthrough_epic_3_rbac_builder_20260621_0831.md`

#### Ada secara fisik

- Models: `app/Modules/Auth/Models/Menu.php`, `MenuRoleOverride.php`, `Field.php`, `FieldRoleOverride.php`
- Migrations: `app/Modules/Auth/Database/Migrations/2026_06_20_000030_create_menus_table.php`, `2026_06_20_000040_create_fields_table.php`
- Seeder: `database/seeders/MenuSeeder.php`, `FieldSeeder.php`, terdaftar di `DatabaseSeeder.php`
- Support: `app/Support/FieldAcl.php`, `MenuRenderer.php`, `BladeDirectives.php`
- UI/controller: `app/Modules/Auth/Controllers/Rbac*.php`, views `resources/views/rbac/*.blade.php`
- Routes: `app/Modules/Auth/routes.php` prefix `/admin/rbac` dan `/admin/user-roles`
- Tests: `tests/Feature/Rbac/*`

#### Gap kritis

1. **Permission RBAC tidak disemai.**
   - Controller `RbacRoleController`, `RbacMenuController`, `RbacFieldController` memanggil `Gate::authorize('rbac.manage')`.
   - `database/seeders/RolePermissionSeeder.php` tidak mencantumkan `rbac.manage`.
   - Test `RbacBuilderTest` membuat permission ini secara dinamis (`Permission::findOrCreate('rbac.manage')`) sehingga test menutupi gap seeder produksi.

2. **Banyak permission menu tidak ada di seeder role.**
   - `MenuSeeder` memakai: `dashboard.view`, `tenant.view`, `audit.view`, `siswa.view`, `guru.view`, `kelas.view`, `mapel.view`, `jadwal.view`, `tagihan.view`, `pembayaran.view`, `tabungan.view`, `presensi.view`, `absensi.view`, `raport.view`.
   - `RolePermissionSeeder` lebih banyak memakai pola lama: `master.*`, `student.*`, `academic.*`, `presence.*`, `absence.*`, `finance.*`.
   - Akibatnya menu non-superadmin banyak terfilter/hilang karena `MenuRenderer` memanggil `$user->can($m->permission_required)`.

3. **Route name pada `MenuSeeder` tidak sinkron dengan route nyata.**
   Contoh:
   - `academic.siswa` disemai dengan route `siswa.index`, padahal route module adalah `academic.siswa.index`.
   - Finance disemai `tagihan.index`, `pembayaran.index`, `tabungan.index`, padahal route module adalah `finance.tagihan.index`, `finance.pembayaran.index`, `finance.tabungan.index`.
   - Presence disemai `presensi.index`, `absensi.index`; route nyata adalah `presence.rekap`, `presence.absensi.index`, dll.
   - Evaluation disemai `raport.index`, route nyata adalah `evaluation.rapor.index`.
   - Tenancy menu `tenants.index` dan `branches.index` tidak ditemukan route fisiknya.
   - `resources/views/layouts/partials/menu.blade.php` memang fallback ke `#` bila `Route::has()` false, sehingga masalah ini tidak selalu crash, tetapi UX menjadi menu mati.

4. **Field ACL hanya dipakai nyata pada sebagian kecil UI.**
   - `FieldSeeder` menyemai 10 field sensitif.
   - Grep penggunaan `@field`/`@fieldAttr` hanya ditemukan pada `resources/views/academic/siswa/*` untuk `siswa.telepon`.
   - Field `orang_tua.telepon`, `tagihan.nominal_kurang`, `pembayaran.total`, `tabungan.saldo`, dll belum benar-benar diterapkan di view terkait.

#### Status Epic 3

**PARTIAL.** Infrastruktur ada, tetapi konfigurasi produksi (seeder permission/menu route) tidak valid dan adopsi Field ACL belum menyeluruh.

---

### Epic 4 — Plugin System Infrastructure

**Dokumen acuan:** `DEV_DOCS/019_implementation_plan_epic_4_20260621_0828.md`, `DEV_DOCS/023_walkthrough_epic_4_plugin_infra_20260621_0857.md`

#### Ada secara fisik

- Contract/context/registry: `app/Support/PluginContract.php`, `PluginContext.php`, `PluginRegistry.php`
- Provider: `app/Providers/PluginRegistryServiceProvider.php`, terdaftar di `bootstrap/providers.php`
- Middleware: `app/Http/Middleware/EnsurePluginEnabled.php`, alias `plugin` di `bootstrap/app.php`
- Models/migrations: `app/Plugins/Infrastructure/Models/Plugin.php`, `TenantPlugin.php`, migration `create_plugins_table.php`
- Activation: `app/Modules/Auth/Services/PluginActivationService.php`, `PluginController.php`, view `resources/views/plugins/index.blade.php`

#### Gap kritis

1. **Menu plugin tidak dipersist ke `menus`.**
   - `KurikulumPlugin::menu()` dan `app/Plugins/Kurikulum/menu.php` ada.
   - Tidak ditemukan referensi yang membaca `menu.php` atau menyimpan return `menu()` ke tabel `menus`.
   - `MenuRenderer` hanya membaca tabel `menus`; maka aktivasi plugin tidak otomatis membuat menu Kurikulum muncul.

2. **Plugin permissions disemai saat activation, tetapi role admin tidak otomatis diberi permission baru.**
   - `PluginActivationService` membuat permission dari manifest.
   - Tidak ada assignment ke role `admin`/`admin_sekolah` untuk `kurikulum.view`/`kurikulum.manage`.
   - Jika policy controller plugin berjalan, admin bisa terblokir setelah mengaktifkan plugin.

3. **Provider plugin diregister untuk semua plugin yang ditemukan, bukan hanya plugin aktif.**
   - `PluginRegistryServiceProvider::boot()` register provider dari semua plugin hasil discovery.
   - Route plugin tetap dilindungi middleware `plugin:kurikulum`, tetapi subscriber/event provider sudah dimuat global. Subscriber mencoba cek aktif berdasarkan `TenantContext`, namun desain ini belum benar-benar “load only when active”.

#### Status Epic 4

**PARTIAL.** Plugin discovery/activation ada, tetapi integrasi menu/permission/active lifecycle belum lengkap.

---

### Epic 5 — Academic Module

**Dokumen acuan:** `DEV_DOCS/025_implementation_plan_epic_5_20260621_0900.md`, `DEV_DOCS/026_walkthrough_epic_5_academic_20260621_0921.md`

#### Ada secara fisik

- 11 migrations akademik di `app/Modules/Academic/Database/Migrations/`.
- 11 models akademik di `app/Modules/Academic/Models/`.
- Services: `JadwalConflictChecker.php`, `KelasSiswaPromotionService.php`.
- CRUD Siswa: `SiswaController.php`, `StoreSiswaRequest.php`, `UpdateSiswaRequest.php`, `SiswaPolicy.php`, `SiswaObserver.php`, views `resources/views/academic/siswa/*`.
- Route module: `Route::resource('siswa', SiswaController::class)` pada prefix `/academic`.

#### Gap kritis

1. **Rencana menyebut controller akademik lengkap, tetapi file fisik hanya `SiswaController.php`.**
   Tidak ditemukan:
   - `GuruController.php`
   - `OrangTuaController.php`
   - `TahunAjaranController.php`
   - `SemesterController.php`
   - `KelasController.php`
   - `KelasSiswaController.php`
   - `MapelController.php`
   - `MapelJenisController.php`
   - `JadwalController.php`

2. **Views akademik selain siswa tidak ada.**
   - Plan menyebut `resources/views/academic/guru`, `kelas`, `jadwal`, dll.
   - File fisik yang ada hanya `resources/views/academic/siswa/*`.

3. **`JadwalConflictChecker` belum terintegrasi ke controller.**
   - Grep menunjukkan `JadwalConflictChecker` hanya dipakai di test dan file service sendiri, tidak ada `JadwalController` atau flow penyimpanan jadwal modular.

4. **`KelasSiswaPromotionService` belum punya route/controller/UI.**
   - Grep hanya menunjukkan pemakaian di test.

5. **Divergensi schema mulai terlihat sejak Epic 5.**
   - Modul Academic membuat `siswa`, `kelas`, `mapel`, `tahun_ajaran`.
   - Core lama tetap punya `students`, `classrooms`, `subjects`, `academic_years` dan banyak controller/service setelahnya masih memakai tabel core lama.

#### Status Epic 5

**PARTIAL.** Database/model akademik ada dan CRUD Siswa ada, tetapi “Academic Module” sebagai modul operasional lengkap belum selesai.

---

### Epic 6 — Evaluation Module

**Dokumen acuan:** `DEV_DOCS/031_implementation_plan_epic_6_20260621_1030.md`, juga audit `DEV_DOCS/043_review_epic6_7_8_9_divergensi_model_dan_event_20260621_2135.md`

#### Ada secara fisik

- Migrations alter evaluation: `app/Modules/Evaluation/Database/Migrations/*.php`
- Controllers: `GradeEntryController.php`, `RaporController.php`
- Services: `GradeCalculatorService.php`, `RaporGeneratorService.php`, `EvaluationFrameworkResolver.php`
- Events: `EvaluationResolveFramework.php`, `RaportRenderSection.php`
- Views: `resources/views/evaluation/grade-entry/*`, `resources/views/evaluation/rapor/*`
- Tests: `tests/Feature/Evaluation/GradeCalculatorTest.php`, `RaporGeneratorTest.php`

#### Gap kritis

1. **Route mengacu class yang tidak ada.**
   - `app/Modules/Evaluation/routes.php` memakai `App\Modules\Evaluation\Controllers\CurriculumController`.
   - File `app/Modules/Evaluation/Controllers/CurriculumController.php` **tidak ada**.
   - Route `/evaluation/curriculum*` akan gagal.

2. **Views `resources/views/evaluation/curriculum/*` tidak ada.**
   - Plan Epic 6 menyebut `curriculum/index.blade.php` dan `curriculum/form.blade.php`.
   - File fisik tidak ditemukan.

3. **Event hook plugin belum dipanggil oleh core flow.**
   - `EvaluationFrameworkResolver` ada dan bisa dispatch `EvaluationResolveFramework`.
   - Namun `GradeEntryController` tidak memanggil `EvaluationFrameworkResolver::resolve()`.
   - `RaporGeneratorService` tidak membuat/dispatch `RaportRenderSection`.
   - Akibatnya subscriber Kurikulum menjadi kode mati dalam flow aplikasi nyata.

4. **GradeEntry masih memakai dunia core Inggris.**
   - `GradeEntryController` menggunakan `App\Models\Classroom`, `Subject`, `Student`, `AcademicYear`, `Schedule`.
   - Modul Academic memakai `Kelas`, `Mapel`, `Siswa`, `TahunAjaran`.
   - Siswa yang dibuat via `/academic/siswa` tidak otomatis muncul di Grade Entry yang query ke `students`.

5. **Teacher ownership belum cukup diamankan.**
   - `index()` memfilter dropdown untuk guru berdasarkan `Schedule`.
   - Tetapi `form()` hanya validasi `exists:classrooms,id` dan `exists:subjects,id`; tidak ada pengecekan bahwa guru benar-benar mengampu kombinasi kelas-mapel tersebut.

6. **Policies yang direncanakan tidak ada.**
   - Plan menyebut `GradePolicy.php` dan `RaporPolicy.php`; file tidak ditemukan di `app/Modules/Evaluation/Policies/`.

#### Status Epic 6

**INCOMPLETE / HIGH RISK.** Ada service dan sebagian UI, tetapi ada crash class hilang dan integrasi plugin/data model belum berjalan.

---

### Epic 7 — Finance Module

**Dokumen acuan:** `DEV_DOCS/035_implementation_plan_epic_7_20260621_1959.md`, `DEV_DOCS/039_dev_report_finance_module_dan_test_fix_20260621_2026.md`

#### Ada secara fisik

- 5 migrations modular Finance: `item_pembayaran`, `tagihan_siswa`, `pembayaran`, `pembayaran_rincian`, `tabungan_siswa`.
- Models, services, controllers, requests, routes, views modular Finance ada.
- Command: `app/Console/Commands/GenerateTagihanCommand.php`.
- Schedule: `routes/console.php` memanggil `Schedule::command('tagihan:generate')->monthlyOn(1, '02:00')`.
- Tests: `tests/Feature/Finance/*`.

#### Gap kritis

1. **Policy Finance module kemungkinan tidak aktif karena provider tidak dimuat.**
   - `app/Providers/AuthServiceProvider.php` berisi mapping policy Finance.
   - Tetapi `bootstrap/providers.php` tidak mendaftarkan `App\Providers\AuthServiceProvider::class`.
   - `AppServiceProvider` hanya mendaftarkan policy Academic/Presence tertentu, bukan Finance.
   - Controller Finance memakai `Gate::authorize('viewAny', ItemPembayaran::class)` dan sejenisnya. Tanpa policy discovery yang tepat untuk namespace `App\Modules\Finance\Policies`, route controller bisa 403.

2. **Core Finance lama tetap ada dan berjalan paralel.**
   - `app/Http/Controllers/Finance/*` dan models/tables core `payment_items`, `student_bills`, `student_payments`, `student_savings` masih ada.
   - Modular Finance memakai `item_pembayaran`, `tagihan_siswa`, `pembayaran`, `tabungan_siswa` dan model `Siswa`.
   - Ini menguatkan temuan “double bookkeeping” di DEV_DOCS-043.

3. **Nomor nota belum benar-benar atomik.**
   - `KwitansiGenerator::generate()` menghitung `count()` lalu `+1`.
   - Walaupun ada unique index `['tenant_id','no_nota']`, dua transaksi paralel tetap dapat menghasilkan nomor sama lalu salah satu gagal unique constraint. Ini belum memenuhi klaim “sequence generator atomik”.

4. **Command signature tidak sesuai dokumentasi manual.**
   - Dokumen manual memakai `php83 artisan tagihan:generate --tenant_id=1`.
   - Command aktual: `tagihan:generate {tenant_id?} {bulan?}` (positional argument, bukan option `--tenant_id`).

5. **Menu Finance dari RBAC seeder tidak sinkron.**
   - `MenuSeeder` route `tagihan.index`, `pembayaran.index`, `tabungan.index`; route nyata prefix `finance.*`.

#### Status Epic 7

**PARTIAL.** Modul modular ada, tetapi integrasi policy/menu dan konsolidasi dengan core lama belum selesai.

---

### Epic 8 — Presence Module

**Dokumen acuan:** `DEV_DOCS/028_implementation_plan_epic_8_20260621_0937.md`, `DEV_DOCS/029_walkthrough_epic_8_presence_20260621_1016.md`

#### Ada secara fisik

- Migrations alter `attendances`, `absences`, `permits`, tambah `userable` pada `users`.
- Models core `Attendance`, `Absence`, `Permit` sudah ditambah trait tenancy/audit dan relasi polymorphic.
- Controllers: `PresensiController`, `AbsensiController`, `IzinController`, `LaporanPresensiController`.
- Services: `QrScannerService`, `PresensiRuleEngine`, `IzinApprovalService`.
- Views utama presence ada: `scan.blade.php`, `rekap.blade.php`, `laporan.blade.php`, `izin/*`.
- Policies Presence terdaftar manual di `AppServiceProvider`.

#### Gap/runtime defect kritis

1. **`AbsensiController@index` memanggil relasi yang tidak ada.**
   - File: `app/Modules/Presence/Controllers/AbsensiController.php`
   - Kode: `Absence::with('attendable')`
   - Model `app/Models/Absence.php` hanya punya relasi `absentable()`, bukan `attendable()`.
   - Ini akan error Eloquent relation not found.

2. **View absensi tidak ada.**
   - Controller return `view('presence.absensi.index')` dan `view('presence.absensi.create')`.
   - File `resources/views/presence/absensi/index.blade.php` dan `create.blade.php` tidak ditemukan.
   - Static missing-view scan juga menandai keduanya.

3. **Store absensi tidak mengisi kolom wajib `type`.**
   - Migration `absences` memiliki `$table->string('type', 20)` non-null.
   - `AbsensiController@store` mengisi `status => 'absent'`, padahal `status` bukan fillable/kolom pada model Absence.
   - Akibatnya insert bisa gagal karena `type` kosong.

4. **Validasi field naming salah.**
   - `AbsensiController@store` memvalidasi `permitable_id`, padahal context absence semestinya `absentable_id` atau `siswa_id`.
   - Ini indikasi copy-paste dari Permit/Izin.

5. **Divergensi dengan presensi guru/core tetap ada.**
   - `QrScannerService` memakai model modular `Siswa` dan `Attendance` polymorphic `attendable_type=Siswa::class`.
   - `app/Http/Controllers/Teacher/AttendanceController.php` dan komponen lama masih memakai `Student`/core flow.

#### Status Epic 8

**PARTIAL / RUNTIME DEFECT.** Scanner dan izin service ada, tetapi subfitur Absensi fisik rusak dan data attendance belum terkonsolidasi dengan fitur guru/core.

---

### Epic 9 — Plugin Kurikulum

**Dokumen acuan:** `DEV_DOCS/037_implementation_plan_epic_9_20260621_2026.md`, `DEV_DOCS/040_dev_report_epic9_kurikulum_plugin_20260621_2103.md`

#### Ada secara fisik

- Plugin folder `app/Plugins/Kurikulum/` lengkap dengan migrations, models, provider, controllers, policy, subscribers, routes, views.
- Event classes dan `EvaluationFrameworkResolver` ada di Evaluation module.
- Route plugin: prefix `/kurikulum` dengan middleware `plugin:kurikulum`.
- Tests plugin ada.

#### Gap kritis

1. **Klaim event flow ke GradeEntry/Rapor tidak benar pada kode saat ini.**
   - DEV_DOCS-040 menggambarkan `GradeEntryController → EvaluationFrameworkResolver::resolve()`.
   - Kode aktual `GradeEntryController` tidak memanggil resolver.
   - Kode aktual `RaporGeneratorService` tidak memanggil event `RaportRenderSection`.

2. **Kurikulum menu tidak muncul otomatis.**
   - `KurikulumPlugin::menu()` ada, `app/Plugins/Kurikulum/menu.php` ada.
   - Tidak ada kode yang membaca file/menu manifest untuk menyimpan ke tabel `menus`.

3. **Policy registration bermasalah.**
   - `AuthServiceProvider` ditulis, tetapi tidak terdaftar di `bootstrap/providers.php`.
   - Plugin controller memakai `$this->authorize('viewAny', Kurikulum::class)`, sehingga policy harus benar-benar registered/discovered.

4. **Mismatch controller/view vs migration: `deskripsi`.**
   - `KurikulumController` validasi `deskripsi`; views menampilkan/mengedit `deskripsi`.
   - Migration `create_kurikulum_table.php` tidak membuat kolom `deskripsi`.
   - Model `Kurikulum` fillable juga tidak mencantumkan `deskripsi`.

5. **Mismatch enum `jenis_kegiatan`.**
   - Migration `struktur_kurikulum` enum: `intrakurikuler`, `kokurikuler_p5`.
   - Controller dan views memakai: `intrakurikuler`, `kokurikuler`, `ekstrakurikuler`.
   - Submit `kokurikuler`/`ekstrakurikuler` bisa gagal di DB.

6. **Mismatch enum `pendekatan_pedagogis`.**
   - Migration `komponen_kompetensi` enum: `konvensional`, `deep_learning`.
   - Controller validasi hanya `nullable|string|max:50`; view berupa free-text input.
   - Input di luar enum bisa gagal di DB.

7. **Plugin permissions tidak otomatis diberikan ke role existing.**
   - Activation creates permissions but no role assignment/menu persistence.

#### Status Epic 9

**PARTIAL.** CRUD scaffold cukup banyak, tetapi integrasi fungsional utama ke Evaluation belum berjalan dan ada mismatch schema/UI.

---

## 4. Temuan Cross-Cutting Paling Kritis

### 4.1 AuthServiceProvider tidak aktif

`bootstrap/providers.php` berisi:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Providers\PluginRegistryServiceProvider::class,
    Lab404\Impersonate\ImpersonateServiceProvider::class,
];
```

Tidak ada `App\Providers\AuthServiceProvider::class`. Dampak:

- Policy mapping di `app/Providers/AuthServiceProvider.php` untuk core/finance/plugin tidak dijamin aktif.
- Beberapa policy di `App\Policies` mungkin bisa auto-discovered Laravel, tetapi policies di namespace module/plugin (`App\Modules\Finance\Policies`, `App\Plugins\Kurikulum\Policies`) tidak boleh diasumsikan otomatis aktif.

### 4.2 Parallel universe schema

| Domain | Dunia modular Indonesia | Dunia core Inggris |
|---|---|---|
| Siswa | `siswa` / `App\Modules\Academic\Models\Siswa` | `students` / `App\Models\Student` |
| Kelas | `kelas` / `Kelas` | `classrooms` / `Classroom` |
| Mapel | `mapel` / `Mapel` | `subjects` / `Subject` |
| Tahun ajaran | `tahun_ajaran` / `TahunAjaran` | `academic_years` / `AcademicYear` |
| Finance | `tagihan_siswa`, `pembayaran`, `tabungan_siswa` | `student_bills`, `student_payments`, `student_savings` |

Ini menyebabkan alur nyata terputus:

- Siswa dibuat di Academic modular (`siswa`) tidak muncul di Evaluation core (`students`).
- QR presensi memakai `Siswa`, rapor/evaluation memakai `Student`.
- Finance modular membayar `tagihan_siswa`, finance core masih punya `student_bills/student_payments`.

### 4.3 Tests green tidak selalu membuktikan integrasi produk

Contoh fisik:

- `RbacBuilderTest` membuat permission `rbac.manage` secara manual sehingga gap seeder tidak terlihat.
- `JadwalConflictChecker` hanya dipakai di tests, belum ada controller UI yang memakai service.
- `KurikulumPluginTest` memanggil resolver/event langsung, bukan lewat GradeEntry/Rapor real flow.
- Finance service tests tidak membuktikan policy/controller/module route production aktif.
- Presence tests tidak menangkap AbsensiController missing view/relation/type.

---

## 5. Rekomendasi Fix Bertahap

### Tahap 2A — Stabilitas Runtime & Routing (P0)

1. **Daftarkan AuthServiceProvider atau pindahkan policy registration ke AppServiceProvider.**
   - Tambahkan `App\Providers\AuthServiceProvider::class` ke `bootstrap/providers.php`, atau register manual `Gate::policy()` untuk Finance dan Kurikulum di `AppServiceProvider`.

2. **Perbaiki RBAC seeders.**
   - Tambahkan permissions: `rbac.manage`, `dashboard.view`, `tenant.view`, `audit.view`, `siswa.view`, `guru.view`, `kelas.view`, `mapel.view`, `jadwal.view`, `tagihan.view`, `pembayaran.view`, `tabungan.view`, `presensi.view`, `absensi.view`, `rapor.view`/`raport.view` konsisten.
   - Atau ubah `MenuSeeder.permission_required` agar mengikuti permission existing (`academic.*`, `finance.*`, `presence.*`, `absence.*`, dll).

3. **Perbaiki route name di `MenuSeeder`.**
   - `siswa.index` → `academic.siswa.index`
   - `tagihan.index` → `finance.tagihan.index`
   - `pembayaran.index` → `finance.pembayaran.index`
   - `tabungan.index` → `finance.tabungan.index`
   - `absensi.index` → `presence.absensi.index`
   - `presensi.index` → `presence.rekap` atau route baru `presence.presensi.index`
   - `raport.index` → `evaluation.rapor.index` atau konsisten `rapor`.

4. **Fix Evaluation route crash.**
   - Implement minimal `app/Modules/Evaluation/Controllers/CurriculumController.php` + views, atau hapus/comment route `/evaluation/curriculum*` bila digantikan oleh plugin Kurikulum.

5. **Fix Presence Absensi.**
   - `with('attendable')` → `with('absentable')`.
   - Tambahkan views `resources/views/presence/absensi/index.blade.php` dan `create.blade.php`.
   - Validasi field `siswa_id`/`absentable_id`, bukan `permitable_id`.
   - Isi kolom wajib `type` (`alpha`/`sakit`/`ijin`) sesuai migration; jangan tulis `status` kecuali migration ditambah.
   - Tambahkan feature test untuk AbsensiController.

6. **Fix mismatch Kurikulum schema/controller.**
   - Tambahkan kolom `deskripsi` dan fillable, atau hapus field dari controller/view.
   - Samakan enum `jenis_kegiatan`: pilih `kokurikuler_p5` atau ubah migration enum agar cocok.
   - Validasi `pendekatan_pedagogis` sebagai `in:konvensional,deep_learning` dan ubah view ke select.

### Tahap 2B — Konsolidasi Model Data (P0/P1)

1. Tentukan canonical schema:
   - Rekomendasi: gunakan modular Indonesia (`siswa`, `kelas`, `mapel`, `tahun_ajaran`) karena Epic 5/Finance/Presence/Kurikulum sudah mengarah ke sana.
2. Buat compatibility adapter sementara:
   - `Student` bisa menjadi adapter ke `siswa`, atau controller Evaluation dipindahkan ke `Siswa` langsung.
   - `Classroom` → `Kelas`, `Subject` → `Mapel`, `AcademicYear` → `TahunAjaran`.
3. Refactor Evaluation, Teacher Attendance, Rapor, Finance core references agar tidak membuat/membaca tabel paralel.
4. Setelah refactor, rencanakan migrasi drop/deprecate tabel core duplicate: `students`, `classrooms`, `subjects`, `academic_years`, `student_* finance` bila tidak dipakai.

### Tahap 2C — Integrasi Plugin Kurikulum ke Evaluation (P1)

1. Di `GradeEntryController@form`, resolve framework dengan model canonical `Mapel/Kelas`:
   - `app(EvaluationFrameworkResolver::class)->resolve($mapel, $kelas)`
   - Kirim `$framework` ke view grade-entry.
2. Di `RaporGeneratorService::getReportData()`, dispatch `RaportRenderSection` dan masukkan `customSections` ke data PDF.
3. Update `resources/views/evaluation/rapor/pdf.blade.php` agar render `customSections`.
4. Tambahkan feature test E2E: aktifkan plugin → buat kurikulum/struktur/komponen → buka grade entry/rapor → CP/TP muncul.

### Tahap 2D — Plugin Menu/Permission Lifecycle (P1)

1. Pada activation plugin:
   - Persist `manifest->menu()` ke tabel `menus` dengan `tenant_id` atau global + override.
   - Seed permissions.
   - Grant default permission ke role admin/admin_sekolah sesuai kebijakan.
2. Pada deactivation:
   - Jangan hapus data plugin, tapi hide menu/clear cache.
3. Tambahkan tests yang membuktikan menu muncul setelah activation dan hilang setelah deactivation.

### Tahap 2E — Finance Hardening (P1)

1. Ganti `KwitansiGenerator` count+1 dengan tabel sequence atau lock eksplisit per tenant+date.
2. Samakan command docs/signature:
   - Either support `--tenant_id` option, atau update DEV_DOCS/manual ke positional.
3. Tutup/redirect controller Finance core lama atau refactor ke service modular tunggal.
4. Tambahkan controller tests, bukan hanya service tests.

### Tahap 2F — Coverage Verifikasi (P1/P2)

Setelah PHP/composer tersedia, jalankan:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
```

Tambahkan static checks:

- Semua route controller class exists.
- Semua `view('...')` exists.
- Semua menu route `Route::has()` true.
- Semua permissions dari menu/guards tersedia dan diberikan ke role yang tepat.

---

## 6. Checklist Fix Minimal Sebelum Lanjut Epic Baru

- [ ] `AuthServiceProvider` aktif atau semua policy module/plugin didaftarkan manual.
- [ ] `RolePermissionSeeder` dan `MenuSeeder` sinkron dengan route/permission nyata.
- [ ] `CurriculumController` Evaluation dibuat atau route dihapus.
- [ ] Absensi Presence fixed: relation, views, request, `type` column.
- [ ] Kurikulum migration/controller enum + `deskripsi` fixed.
- [ ] Event Kurikulum dipanggil dari GradeEntry dan Rapor.
- [ ] Keputusan canonical model (`Siswa` vs `Student`) dibuat dan difollow-up dengan refactor.
- [ ] Plugin activation memunculkan menu dan memberi permission default.
- [ ] Tests diperluas untuk controller/UI integration, bukan hanya service direct calls.

---

## 7. Status Audit Tahap 1

Audit tahap 1 selesai untuk Epic 3–9 secara statis dan git-history. Rekomendasi saya: **jangan mulai Epic 10/ETL/API besar dulu** sebelum Tahap 2A–2B minimal selesai, karena fondasi route, policy, menu, dan canonical data model masih rapuh.
