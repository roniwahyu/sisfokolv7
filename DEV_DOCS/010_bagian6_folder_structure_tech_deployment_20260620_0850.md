# DEV_DOCS-010: Bagian 6 — Folder Structure Final + Tech Stack + Deployment

- **Tanggal:** 2026-06-20 08:50
- **Topik:** Struktur folder final Laravel 11 modular + tech stack final + deployment notes
- **Terhubung ke ADR:** 002 (rebuild modular), 004 (scope), 007 (skema DB), 009 (plugin)
- **Sumber referensi:** `D17_Spesifikasi_Teknologi.md`, `D18_Struktur_Kode_Coding_Standard.md` (Opsi A: Laravel recommended)

---

## 🏗️ Struktur Folder Final — `sisfokol-laravel/`

Struktur = **Opsi A D18 (Laravel recommended)** + **domain modular** (`app/Modules/`, `app/Plugins/`).

```
sisfokol-laravel/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── MigrateLegacyDataCommand.php        (ETL entry — per DEV_DOCS-009)
│   │       ├── VerifyEtlCommand.php                (etl:verify)
│   │       ├── GenerateTagihanCommand.php          (tagihan:generate, schedule harian)
│   │       ├── ResetPluginCacheCommand.php         (plugin:cache-reset)
│   │       └── Etl/                                (Step classes — per DEV_DOCS-009)
│   │           ├── StepInterface.php
│   │           ├── MigrateTahunAjaranStep.php
│   │           ├── MigrateGuruStep.php
│   │           ├── MigrateSiswaStep.php
│   │           ├── ... (20 step per topological order)
│   │           ├── Cleansing/{MoneyCleaner,DateCleaner,PhoneCleaner,PasswordResetter}.php
│   │           └── IdMapper.php                    (singleton legacy_kd→new_id)
│   │
│   ├── Exceptions/
│   │   └── Handler.php                             (custom render: 403/419/429 JSON+HTML)
│   │
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── ResolveTenant.php                   (set app('tenant') dari user)
│   │   │   ├── EnsurePluginEnabled.php             (alias route middleware 'plugin:<kode>')
│   │   │   ├── CanImpersonate.php                  (cek IMPERSONATION_ENABLED + hierarki)
│   │   │   ├── BlockWhileImpersonating.php         (blokir POST sensitif saat impersonate)
│   │   │   └── ForcePasswordReset.php              (redirect ke /password/change bila must_reset_password=1)
│   │   └── Kernel.php                              (register middleware + groups)
│   │
│   ├── Models/
│   │   └── Traits/
│   │       ├── BelongsToTenant.php                 (global scope tenant_id per ADR-003)
│   │       └── TracksAuditColumns.php              (auto created_by/updated_by)
│   │
│   ├── Modules/                                    ← CORE (selalu aktif)
│   │   ├── Tenancy/
│   │   │   ├── Controllers/{TenantController,BranchController,TenantSettingsController}.php
│   │   │   ├── Models/{Tenant,Branch,TenantSetting,Subscription}.php
│   │   │   ├── Policies/{TenantPolicy,BranchPolicy}.php
│   │   │   ├── Requests/{StoreTenantRequest,UpdateTenantRequest,...}.php
│   │   │   ├── Services/{TenantContext,KelasSiswaPromotionService}.php
│   │   │   ├── Observers/{TenantObserver,BranchObserver}.php
│   │   │   ├── Database/Migrations/               (4 tabel Tenancy)
│   │   │   ├── Resources/views/{tenants,branches,settings}/
│   │   │   └── routes.php
│   │   │
│   │   ├── Auth/
│   │   │   ├── Controllers/{AuthController,UserController,RoleController,
│   │   │   │                       PermissionController,RbacMenuController,RbacFieldController,
│   │   │   │                       ImpersonationController,AuditLogController}.php
│   │   │   ├── Models/{User,Role,Permission,Session,AuditLog,Menu,MenuRoleOverride,
│   │   │   │                     Field,FieldRoleOverride}.php
│   │   │   ├── Policies/{UserPolicy,RolePolicy,AuditLogPolicy}.php
│   │   │   ├── Requests/{LoginRequest,StoreUserRequest,BayarTagihanRequest,...}.php
│   │   │   ├── Services/{ImpersonationService,RbacBuilderService,FieldAclResolver}.php
│   │   │   ├── Observers/{UserObserver,RoleObserver,ModelHasRolesObserver,
│   │   │   │                     MenuRoleOverrideObserver,FieldRoleOverrideObserver}.php
│   │   │   ├── Database/Migrations/               (9 tabel Auth + 4 ACL menus/fields)
│   │   │   ├── Resources/views/{auth,users,rbac,audit,impersonate}/
│   │   │   └── routes.php
│   │   │
│   │   ├── Academic/
│   │   │   ├── Controllers/{SiswaController,OrangTuaController,GuruController,
│   │   │   │                       TahunAjaranController,SemesterController,KelasController,
│   │   │   │                       KelasSiswaController,MapelController,MapelJenisController,
│   │   │   │                       JadwalController}.php
│   │   │   ├── Models/{Siswa,OrangTua,SiswaOrangTua,Guru,TahunAjaran,Semester,Kelas,
│   │   │   │              KelasSiswa,Mapel,MapelJenis,Jadwal}.php
│   │   │   ├── Policies/{SiswaPolicy,GuruPolicy,KelasPolicy,JadwalPolicy}.php
│   │   │   ├── Requests/{StoreSiswaRequest,UpdateSiswaRequest,...}.php
│   │   │   ├── Services/{SiswaImportService,KelasSiswaPromotionService,JadwalConflictChecker}.php
│   │   │   ├── Observers/{SiswaObserver,GuruObserver,KelasObserver,...}.php
│   │   │   ├── Database/Migrations/               (11 tabel Academic)
│   │   │   ├── Resources/views/{siswa,orang-tua,guru,kelas,mapel,jadwal,tahun-ajaran}/
│   │   │   └── routes.php
│   │   │
│   │   ├── Evaluation/
│   │   │   ├── Controllers/{TpController,LmController,AsesmenFormatifController,
│   │   │   │                       AsesmenSumatifController,RaporController}.php
│   │   │   ├── Models/{Tp,Lm,AsesmenFormatifNilai,AsesmenSumatifNilai,
│   │   │   │              RaportCatatan,RaportSikap,RaportKenaikan}.php
│   │   │   ├── Policies/{AsesmenPolicy,RaporPolicy}.php
│   │   │   ├── Requests/{BulkFormatifRequest,BulkSumatifRequest,...}.php
│   │   │   ├── Services/{RaporService,AsesmenBulkInputService,
│   │   │   │                  EvaluationFrameworkResolver}.php
│   │   │   ├── Observers/{AsesmenObserver,RaporObserver}.php
│   │   │   ├── Database/Migrations/               (7 tabel Evaluation)
│   │   │   ├── Resources/views/{tp,lm,asesmen,raport}/
│   │   │   └── routes.php
│   │   │
│   │   ├── Finance/
│   │   │   ├── Controllers/{ItemPembayaranController,TagihanSiswaController,
│   │   │   │                       PembayaranController,TabunganSiswaController,
│   │   │   │                       LaporanKeuanganController}.php
│   │   │   ├── Models/{ItemPembayaran,TagihanSiswa,Pembayaran,PembayaranRincian,
│   │   │   │              TabunganSiswa}.php
│   │   │   ├── Policies/{ItemPembayaranPolicy,PembayaranPolicy,TabunganPolicy}.php
│   │   │   ├── Requests/{BayarTagihanRequest,StoreItemPembayaranRequest,...}.php
│   │   │   ├── Services/{TagihanGeneratorService,PembayaranService,
│   │   │   │                  TabunganMutasiService,KwitansiGenerator}.php
│   │   │   ├── Observers/{PembayaranObserver,TabunganObserver,...}.php
│   │   │   ├── Database/Migrations/               (5 tabel Finance)
│   │   │   ├── Resources/views/{item-pembayaran,tagihan,pembayaran,tabungan,laporan}/
│   │   │   └── routes.php
│   │   │
│   │   └── Presence/
│   │       ├── Controllers/{PresensiController,AbsensiController,IzinController,
│   │       │                       LaporanPresensiController}.php
│   │       ├── Models/{Presensi,Absensi,Izin}.php
│   │       ├── Policies/{PresensiPolicy,AbsensiPolicy,IzinPolicy}.php
│   │       ├── Requests/{ScanQrRequest,StoreAbsensiRequest,...}.php
│   │       ├── Services/{QrScannerService,PresensiRuleEngine,IzinApprovalService}.php
│   │       ├── Observers/{PresensiObserver,AbsensiObserver,IzinObserver}.php
│   │       ├── Database/Migrations/               (3 tabel Presence)
│   │       ├── Resources/views/{presensi,absensi,izin,laporan}/
│   │       └── routes.php
│   │
│   ├── Plugins/                                   ← PLUG-AND-PLAY
│   │   ├── Kurikulum/                              (REFERENSI — dibangun penuh)
│   │   │   ├── KurikulumPlugin.php                (manifest implement PluginContract)
│   │   │   ├── Providers/KurikulumServiceProvider.php
│   │   │   ├── Controllers/{KurikulumController,StrukturKurikulumController,
│   │   │   │                          KomponenKompetensiController}.php
│   │   │   ├── Models/{Kurikulum,StrukturKurikulum,KomponenKompetensi}.php
│   │   │   ├── Policies/KurikulumPolicy.php
│   │   │   ├── Subscribers/{EvaluationFrameworkSubscriber,RaporSectionSubscriber}.php
│   │   │   ├── Database/Migrations/               (3 tabel Kurikulum)
│   │   │   ├── Resources/views/{kurikulum,struktur,komponen}/
│   │   │   ├── menu.php
│   │   │   ├── permissions.php
│   │   │   └── routes.php
│   │   │
│   │   ├── Discipline/                             (scaffold Fase 1)
│   │   │   ├── DisciplinePlugin.php
│   │   │   ├── Providers/DisciplineServiceProvider.php
│   │   │   ├── Database/Migrations/               (placeholder struktur dasar)
│   │   │   ├── permissions.php
│   │   │   └── (tidak ada controller/route/view di Fase 1)
│   │   │
│   │   ├── Inventory/                              (scaffold)
│   │   ├── Tahfidz/                                (scaffold)
│   │   ├── HafalanHadist/                          (scaffold)
│   │   ├── BimbinganKonseling/                     (scaffold)
│   │   ├── PendidikanKarakter/                     (scaffold)
│   │   ├── PelaporanOrtu/                          (scaffold)
│   │   └── PWA/                                    (scaffold — frontend layer)
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── ModuleServiceProvider.php              (scan Modules + Plugins, register routes)
│   │   ├── PluginRegistryServiceProvider.php       (build PluginRegistry cache)
│   │   ├── AuthServiceProvider.php                 (register Policies + Gate)
│   │   ├── EventServiceProvider.php                (register Observers + plugin subscribers)
│   │   ├── RouteServiceProvider.php
│   │   └── HorizonServiceProvider.php              (Fase 2 — queue dashboard)
│   │
│   ├── Support/                                    (helpers & utilities)
│   │   ├── PluginRegistry.php                      (sumber kebenaran plugin aktif)
│   │   ├── PluginContract.php                      (interface per ADR-009)
│   │   ├── PluginContext.php                       (DI bawaan untuk plugin boot)
│   │   ├── FieldAcl.php                            (resolver @field directive per ADR-010)
│   │   ├── MenuRenderer.php                        (render menu core + plugin aktif)
│   │   ├── BladeDirectives.php                     (@field, @menu, @plugin)
│   │   └── TenantContext.php                       (singleton app('tenant'))
│   │
│   └── Rules/                                      (custom validation rules)
│       ├── NisUniquePerTenant.php
│       ├── NipUniquePerTenant.php
│       └── ValidKurikulumKode.php
│
├── bootstrap/app.php                              (Laravel 11 minimal bootstrap)
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php                               (default mysql + legacy_mysql connection)
│   ├── permission.php                             (Spatie config — teams mode)
│   ├── impersonate.php                            (lab404 config — guard, redirect)
│   ├── modules.php                                (list Modules, autoload paths)
│   ├── plugins.php                                (list Plugins, discovery paths)
│   ├── tenants.php                                (default settings seed)
│   └── (cache, queue, session, etc.)
│
├── database/
│   ├── migrations/                                (Laravel default + framework tables)
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   └── 0001_01_01_000002_create_jobs_table.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── SuperAdminSeeder.php                   (1 SuperAdmin + demo tenant)
│   │   ├── RolePermissionSeeder.php               (11 role + ~30 permission bawaan)
│   │   ├── MenuSeeder.php                         (menu core + ACL menu seed)
│   │   ├── FieldSeeder.php                        (field ACL katalog + default visibility)
│   │   └── DemoTenantSeeder.php                   (SMP IT Demo + 1 admin_sekolah + sample data)
│   └── factories/
│       ├── SiswaFactory.php
│       ├── GuruFactory.php
│       └── ... (untuk testing)
│
├── public/
│   ├── .htaccess
│   ├── index.php
│   └── assets/
│       ├── css/app.css                            (Vite build)
│       ├── js/app.js                              (Vite build — Alpine.js)
│       └── img/
│
├── resources/
│   ├── css/app.css                                (Bootstrap 5 import + custom)
│   ├── js/app.js                                  (Alpine.js bootstrap)
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php                      (main layout: sidebar+topbar+content)
│   │   │   ├── auth.blade.php                     (login layout)
│   │   │   └── print.blade.php                    (kwitansi/raport PDF wrapper)
│   │   ├── partials/
│   │   │   ├── sidebar.blade.php                  (menu renderer MenuRenderer)
│   │   │   ├── topbar.blade.php                   (tenant switcher, impersonation banner)
│   │   │   ├── impersonation_banner.blade.php     (banner merah persistent)
│   │   │   ├── field_acl_directive.blade.php      (@field implementation)
│   │   │   └── datatable_default.blade.php
│   │   ├── dashboard/
│   │   │   └── index.blade.php                    (role-aware dashboard)
│   │   ├── errors/
│   │   │   ├── 403.blade.php
│   │   │   ├── 404.blade.php
│   │   │   ├── 419.blade.php                      (session expired)
│   │   │   ├── 429.blade.php                      (throttle)
│   │   │   └── 500.blade.php
│   │   └── (module views nested per folder module)
│   └── lang/id/                                   (localization Indonesia)
│       ├── auth.php
│       ├── validation.php
│       └── pagination.php
│
├── routes/
│   ├── web.php                                    (load module routes via ModuleServiceProvider)
│   ├── api.php                                    (Fase 2 — Sanctum API)
│   └── channels.php                               (Fase 2 — broadcasting)
│
├── storage/
│   ├── app/
│   │   ├── public/uploads/                        (foto siswa/guru, filebox)
│   │   ├── reports/                               (PDF raport/kwitansi generated)
│   │   └── laravel-excel/                         (import temp)
│   ├── logs/laravel.log
│   └── framework/                                 (cache, sessions, views)
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── PembayaranServiceTest.php          (KRITIS — locking + transaksi)
│   │   │   ├── TagihanGeneratorServiceTest.php
│   │   │   ├── RaporServiceTest.php
│   │   │   └── TabunganMutasiServiceTest.php
│   │   ├── Support/
│   │   │   ├── FieldAclTest.php
│   │   │   ├── MenuRendererTest.php
│   │   │   └── PluginRegistryTest.php
│   │   └── Rules/
│   │       └── NisUniquePerTenantTest.php
│   └── Feature/
│       ├── Auth/
│       │   ├── LoginTest.php
│       │   ├── ImpersonationTest.php
│       │   └── RbacBuilderTest.php
│       ├── Academic/
│       │   ├── SiswaCrudTest.php
│       │   └── KelasSiswaPromotionTest.php
│       ├── Finance/
│       │   ├── PembayaranTest.php                 (transaksi + locking)
│       │   └── TagihanGenerateTest.php
│       ├── Presence/
│       │   └── QrScanTest.php
│       ├── Plugin/
│       │   ├── PluginActivationTest.php
│       │   └── KurikulumPluginTest.php
│       └── Tenant/
│           ├── TenantIsolationTest.php            (KRITIS — no data leak)
│           └── TenantSuspendTest.php
│
├── .env.example
├── .env.testing                                   (config test: in-memory sqlite)
├── .gitignore
├── composer.json
├── package.json                                   (vite, bootstrap, alpine)
├── phpunit.xml
├── vite.config.js
└── README.md                                      (setup, ETL, deployment)
```

### Catatan Struktur

1. **`app/Modules/` dan `app/Plugins/`** = modular monolith (bukan packages terpisah). Tiap modul punya folder MVC-nya sendiri + `routes.php` di-load otomatis oleh `ModuleServiceProvider`.
2. **Migration di tiap module**: `Database/Migrations/` per modul. Tidak di `database/migrations/` global — supaya modul portable (bisa dipindah jadi composer package di Fase 2 bila perlu).
3. **Views di tiap module**: `Resources/views/` per modul, di-namespace saat register (`view:module::siswa.index`).
4. **`app/Support/`** = utility cross-cutting: PluginRegistry, FieldAcl, MenuRenderer, BladeDirectives, TenantContext.
5. **`tests/`** = mirror struktur modul. **Tenant isolation test** & **PembayaranServiceTest** = paling kritis, wajib lulus sebelum release.
6. **Localization `lang/id`** = Indonesia default (bukan en), per D18.

---

## 🛠️ Tech Stack Final

### Paket Composer

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "spatie/laravel-permission": "^6.4",          // RBAC teams mode per ADR-006
    "lab404/laravel-impersonate": "^2.0",          // Login As per ADR-005
    "maatwebsite/excel": "^3.1",                   // Import/export siswa/guru
    "barryvdh/laravel-dompdf": "^3.0",             // Cetak raport + kwitansi
    "simplesoftwareio/simple-qrcode": "^4.0",      // Generate QR code
    "wire-elements/modal": "^2.0",                 // (opsional) modal Livewire
    "predis/predis": "^2.0"                        // Redis cache (Fase 2)
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "laravel/pint": "^1.13",                       // PSR-12 formatter per D18
    "laravel/telescope": "^5.0",                   // Debug dev only
    "mockery/mockery": "^1.6",
    "phpunit/phpunit": "^11.0"
  }
}
```

### Frontend (package.json)

```json
{
  "devDependencies": {
    "vite": "^5.0",
    "laravel-vite-plugin": "^1.0",
    "bootstrap": "^5.3",
    "@popperjs/core": "^2.11",
    "alpinejs": "^3.13",
    "sass": "^1.69"
  }
}
```

### Versi Final Stack

| Layer | Teknologi | Versi | Alasan |
|---|---|---|---|
| Bahasa | PHP | 8.2+ | Laravel 11 requirement |
| Backend Framework | Laravel | 11.x | ADR-002 |
| DB Engine | MySQL/MariaDB | 8.0+/10.6+ | ADR-007, InnoDB wajib |
| RBAC | Spatie laravel-permission | 6.x (teams mode) | ADR-006 |
| Impersonation | lab404/laravel-impersonate | 2.x | ADR-005 |
| Frontend | Blade + Bootstrap 5 + Alpine.js | 5.3/3.13 | D17, ringan, familier |
| Asset Build | Vite | 5.x | Default Laravel 11 (bukan Mix) |
| Export Excel | Laravel Excel (maatwebsite) | 3.x | Import siswa/guru |
| PDF | DomPDF (barryvdh) | 3.x | Cetak raport, kwitansi, surat izin |
| QR Code | simple-qrcode | 4.x | Presensi + kartu siswa |
| Cache | File (Fase 1) → Redis (Fase 2) | - | ADR-010 cache strategy |
| Session | File (Fase 1) → Redis (Fase 2) | - | - |
| Queue | sync (Fase 1) → Redis+Supervisor (Fase 2) | - | Export/import berat |
| Auth | bcrypt (cost 12) | - | ADR-002, wajib |
| API | Sanctum | - | Fase 2 (PWA/mobile) |
| Code Style | Laravel Pint (PSR-12) | - | D18 coding standard |
| Testing | PHPUnit 11 | - | D18 wajib unit+feature test |
| Dev Debug | Laravel Telescope | - | Dev only, disable di prod |

### Deviasi dari D17 & Justifikasi

| D17 Rekomendasi | Pilihan Kita | Alasan |
|---|---|---|
| jQuery | **Hapus** | Alpine.js lebih ringan + deklaratif; jQuery legacy untuk migrasi saja |
| AdminLTE 3 | **Tidak dipakai** | Bootstrap 5 native + custom layout (per `prototype-antarmuka.html`) lebih fleksibel untuk plugin menu injection |
| Redis sejak awal | **Fase 2** | Fase 1 cukup file cache/driver (cukup untuk 1-3 tenant demo); Redis di Fase 2 saat scale |
| Nginx + PHP-FPM | **Laragon (dev) → Nginx (prod)** | Dev pakai Laragon (Windows), prod target Ubuntu+Nginx+PHP-FPM |
| Docker opsional | **Wajib di prod** | Standarisasi env sesuai D17 |

---

## ⚙️ Konfigurasi `.env`

### `.env.example` (template)

```ini
APP_NAME="SISFOKOL Laravel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://sisfokol-laravel.test
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

# Database utama (target modern)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisfokol_laravel
DB_USERNAME=root
DB_PASSWORD=

# Database legacy (READ-ONLY untuk ETL)
LEGACY_DB_CONNECTION=legacy_mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=sisfokol_v7
LEGACY_DB_USERNAME=readonly_user
LEGACY_DB_PASSWORD=

# Session & Cache (Fase 1: file, Fase 2: redis)
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Impersonation (default OFF production)
IMPERSONATION_ENABLED=false

# Bcrypt cost
BCRYPT_COST=12

# Sanctum (Fase 2)
SANCTUM_STATEFUL_DOMAINS=sisfokol-laravel.test

# Mail (notifikasi reset password)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@sekolah.sch.id
MAIL_FROM_NAME="${APP_NAME}"

# WhatsApp Gateway (Fase 2 — plugin PelaporanOrtu)
WA_GATEWAY_URL=
WA_GATEWAY_TOKEN=

# Logging
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

### Override Production (`.env.production`)

```ini
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
IMPERSONATION_ENABLED=false    # Production default off
BCRYPT_COST=12
LOG_LEVEL=warning
```

---

## 🚀 Deployment

### Stack Deployment Target

```
Ubuntu Server LTS 24.04
├── Nginx 1.24+
├── PHP-FPM 8.2+
├── MySQL 8 / MariaDB 10.6 (InnoDB)
├── Redis 7 (Fase 2)
├── Supervisor (queue worker)
└── Docker (opsional, recommended)
```

### CI/CD Pipeline (Fase 1: GitHub Actions minimal)

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env: { MYSQL_DATABASE: sisfokol_test, MYSQL_ROOT_PASSWORD: root }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.2', extensions: mbstring, pdo_mysql, bcmath }
      - run: composer install --no-interaction --prefer-dist
      - run: npm ci && npm run build
      - run: php artisan key:generate
      - run: php artisan migrate --force
      - run: php artisan test --parallel
      - run: ./vendor/bin/pint --test
```

### Deployment Steps (Manual / SSH)

```bash
# 1. Pull latest code
cd /var/www/sisfokol-laravel
git pull origin main

# 2. Install dependencies (no-dev di prod)
composer install --no-dev --optimize-autoloader

# 3. Build frontend assets
npm ci
npm run build

# 4. Migrate database (zero-downtime)
php artisan migrate --force

# 5. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan permission:cache-reset

# 6. Restart services
php artisan queue:restart
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### Supervisor Config (Queue Worker, Fase 2)

```ini
# /etc/supervisor/conf.d/sisfokol-worker.conf
[program:budikunti-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sisfokol-laravel/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sisfokol-laravel/storage/logs/worker.log
```

### Nginx Vhost (Production)

```nginx
server {
    listen 80;
    server_name sisfokol.sekolah.sch.id;
    root /var/www/sisfokol-laravel/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
    location ~ ^/(storage|vendor)/ { deny all; }

    client_max_body_size 20M;   # upload foto, filebox
}
```

### Docker (opsional, recommended Fase 2)

```
docker-compose.yml:
  - app (Laravel + PHP-FPM)
  - web (Nginx reverse proxy)
  - db (MySQL 8)
  - redis (cache/queue)
  - horizon (queue dashboard, Fase 2)
```

### Backup Strategy

| Jenis | Frekuensi | Tools |
|---|---|---|
| Database full | Harian 02:00 | `mysqldump --single-transaction sisfokol_laravel` + retention 30 hari |
| Storage uploads | Mingguan | `rsync storage/app/public/uploads` ke S3/offsite |
| Code | Setiap push | Git remote (GitHub) |
| Audit logs | Tahunan archive | `audit_logs` partition by year (Fase 2) |

### Cut-over Production (Fase 1 Go-Live)

1. **T-7 hari:** Setup server prod, install stack, restore backup dev ke staging
2. **T-3 hari:** Run ETL dari `sisfokol_v7` ke staging, verify
3. **T-1 hari:** Freeze legacy (read-only), backup final
4. **T-0 hari:**
   - Run ETL final ke production DB
   - Run `etl:verify` — wajib PASS
   - Switch DNS/Nginx ke Laravel
   - Announce: "Password baru akan dikirim via email / hubungi admin"
   - Monitor log 24 jam pertama (Telescope + log)
5. **T+7 hari:** Decommission legacy setelah stabil

---

## 📋 Checklist Setup Awal Dev

Untuk agent berikutnya yang mulai implementasi:

```bash
# 1. Create Laravel project
cd D:\laragon\www\sisfokolv7
composer create-project laravel/laravel sisfokol-laravel "11.*"
cd sisfokol-laravel

# 2. Install packages
composer require spatie/laravel-permission lab404/laravel-impersonate \
  maatwebsite/excel barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode
composer require --dev laravel/telescope laravel/pint

# 3. Frontend
npm install bootstrap @popperjs/core alpinejs sass
npm install -D vite laravel-vite-plugin

# 4. Setup DB (via Laragon phpMyAdmin atau CLI)
#    Create database sisfokol_laravel, charset utf8mb4_unicode_ci

# 5. Configure .env (copy dari .env.example)
#    Set DB_DATABASE=sisfokol_laravel
#    Set LEGACY_DB_* ke sisfokol_v7 (READ-ONLY user)

# 6. Publish vendor config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Lab404\Impersonate\ImpersonateServiceProvider"

# 7. Build modular structure
#    Create app/Modules/{Tenancy,Auth,Academic,Evaluation,Finance,Presence}/
#    Create app/Plugins/{Kurikulum,Discipline,Inventory,Tahfidz,HafalanHadist,
#                       BimbinganKonseling,PendidikanKarakter,PelaporanOrtu,PWA}/

# 8. Migrate + seed
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=MenuSeeder
php artisan db:seed --class=FieldSeeder

# 9. Verify
php artisan test
npm run dev
php artisan serve
```

---

## 📊 Estimasi Total Kelas Fase 1

| Kategori | Jumlah |
|---|---:|
| Controllers (6 core + 1 plugin) | ~38 |
| Models (1 per tabel domain + pivot) | ~48 |
| Policies | ~18 |
| FormRequests | ~40 |
| Services | ~19 |
| Observers | ~26 |
| Console Commands | 4 (+20 ETL Step classes) |
| Blade views (estimate) | ~80 |
| Migrations | 49 (48 domain + 1 ETL helper) |
| Seeders | 5 |
| Tests (Unit + Feature) | ~30 |

## Status desain Bagian 6: ✅ FINAL & SIAP DIPRESENTASIKAN

## Next
- ⏭️ Tulis **design doc final** di `docs/superpowers/specs/` (kompilasi semua ADR + DEV_DOCS) → self-review → user review
- ⏳ Setelah user approve → transition `writing-plans` skill → buat rencana implementasi step-by-step
