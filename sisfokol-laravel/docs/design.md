# Design Document — SISFOKOL Laravel 11

**Versi:** 1.0 FINAL (APPROVED 2026-06-20)
**Tanggal:** 2026-06-20 09:00
**Status:** ✅ APPROVED — transition ke writing-plans
**Lokasi implementasi:** `D:\laragon\www\sisfokolv7\sisfokol-laravel\` (akan dibuat)

---

## 0. Executive Summary

Konversi **SISFOKOL v7.00** (PHP Native + MySQL MyISAM, ~75 tabel) menjadi aplikasi **Laravel 11** modern di folder baru `sisfokol-laravel/`. **Bukan modifikasi** — rebuild total, SISFOKOL hanya jadi referensi domain.

**Target Fase 1 MVP:**
- 6 core modul fully functional: Tenancy, Auth, Academic, Evaluation, Finance, Presence
- Plugin infra fully working + 1 plugin referensi (Kurikulum) dibangun penuh + 8 plugin scaffold
- ETL data master dari `sisfokol_v7` → `sisfokol_laravel`
- Multi-tenant SaaS (shared-DB + tenant_id)
- Granular RBAC 5 lapis (database-driven, sampai menu & field level)
- Impersonation "Login As" (hierarkis, env-gated)

**Sumber kebenaran:** dokumen ini + `ADR/` (10 file keputusan binding) + `DEV_DOCS/` (10 file detail desain).

---

## 1. Konteks & Motivasi

### 1.1. Sumber Referensi Domain
- `DOCS/analisis-sisfokol/analisis-sisfokol-v7.md` — analisis 75 tabel, 9 role, business flow, temuan kritis, blueprint (paling penting)
- `DOCS/dokumen-proyek-sis/` — 31 dokumen proyek asli (A02-G31): SRS, ERD, RBAC, tech stack, coding standard
- `DOCS/ARENA_..._workspace.../` — 69 file (22 overstated diabaikan, 31 referensi, 16 desain dipakai — lihat DEV_DOCS-008)

### 1.2. Mengapa Rebuild Total (bukan modifikasi)
SISFOKOL v7 punya hutang teknis kritis (per `analisis-sisfokol-v7.md`):
- **Keamanan:** MD5 tanpa salt, SQL injection (concat query), tanpa CSRF, tanpa prepared statement
- **Database:** MyISAM (tanpa transaksi/FK), PK varchar(50) MD5, numerik sebagai varchar, denormalisasi tinggi
- **Arsitektur:** PHP native prosedural, HTML/PHP/JS/SQL bercampur
- **Tidak ada** testing, audit log, soft delete, API layer, RBAC granular

→ ADR-002: rebuild total sebagai Laravel 11 modular monolith.

---

## 2. Arsitektur

### 2.1. Domain-Modular Monolith + Plugin System

```
app/Modules/<Domain>/     ← 6 CORE modul (selalu aktif, fully functional Fase 1)
  Controllers/ Models/ Policies/ Requests/ Services/ Observers/
  Database/Migrations/ Resources/views/ routes.php

app/Plugins/<Nama>/       ← 9 PLUG-AND-PLAY plugin
  <Nama>Plugin.php        ← manifest (implement PluginContract)
  Providers/ Models/ Controllers/
  Database/Migrations/ Resources/views/
  menu.php  permissions.php  routes.php
```

ModuleServiceProvider scan + autoload semua module dan plugin saat boot. Plugin hanya load route/menu bila aktif di tenant.

### 2.2. Core Modules (6)
| Modul | Tanggung Jawab |
|---|---|
| **Tenancy** | Tenant, Branch, TenantSettings (tapel/smt aktif, profil sekolah), Subscriptions |
| **Auth** | User, Role, Permission, RBAC Builder, Impersonation, AuditLog, Session |
| **Academic** | Siswa, OrangTua, Guru, TahunAjaran, Semester, Kelas, KelasSiswa, Mapel, Jadwal |
| **Evaluation** | TP, LM, AsesmenFormatif, AsesmenSumatif, Rapor (Catatan/Sikap/Kenaikan) |
| **Finance** | ItemPembayaran, TagihanSiswa, Pembayaran, PembayaranRincian, TabunganSiswa |
| **Presence** | Presensi (QR), Absensi, Izin |

### 2.3. Plugins (9)
| Plugin | Status Fase 1 |
|---|---|
| **Kurikulum** | ✅ Penuh — mesin framework nilai K13/Kurmer/Muatan Lokal/Deep Learning |
| Discipline, Inventory, Tahfidz, HafalanHadist, BimbinganKonseling, PendidikanKarakter, PelaporanOrtu, PWA | 🔧 Scaffold — manifest + ServiceProvider + permissions + migration placeholder |

### 2.4. Multi-Tenant SaaS (Shared Database)
- Semua tabel domain punya `tenant_id` + composite index
- Trait `BelongsToTenant` menambah global scope `WHERE tenant_id = app('tenant')->id`
- Tenant di-resolve via middleware `ResolveTenant` dari `Auth::user()->tenant_id` (bukan subdomain)
- SuperAdmin (tenant_id=NULL) tembus semua tenant

### 2.5. Aktor & Hierarki
```
SuperAdmin (platform, tenant_id=NULL)
   ↓ manages
Tenant (sekolah) → Branch (unit SD/SMP)
   ↓ assigns
Admin Sekolah → Users (9 role fungsional: ks, bendahara, bk, guru, wk, piket, sarpras, siswa, ortu)
```

---

## 3. Skema Database

### 3.1. Prinsip (per ADR-007)
- **Engine:** InnoDB, `utf8mb4_unicode_ci` (bukan MyISAM)
- **PK:** `BIGINT UNSIGNED AUTO_INCREMENT` (bukan varchar MD5)
- **FK:** wajib constraint + `ON DELETE/UPDATE`
- **Standar kolom:** `timestamps()` + `softDeletes()` + `created_by/updated_by` (audit)
- **Tenant:** `tenant_id` di semua tabel domain + composite index
- **Tipe sesuai domain:** uang `decimal(15,2)`, nilai `tinyint`, tahun `smallint`
- **Helper macro:** `$table->tenantAndAuditColumns()` untuk boilerplate

### 3.2. Kuantitas 48 Tabel Fase 1

| Modul | Jumlah | Tabel |
|---|---:|---|
| Tenancy | 4 | tenants, branches, tenant_settings, subscriptions |
| Auth & RBAC | 9 | users, roles, permissions, role_has_permissions, model_has_roles, model_has_permissions, sessions, audit_logs |
| Academic | 11 | siswa, orang_tua, siswa_orang_tua, guru, tahun_ajaran, semester, kelas, kelas_siswa, mapel, mapel_jenis, jadwal |
| Evaluation | 7 | tp, lm, asesmen_formatif_nilai, asesmen_sumatif_nilai, raport_catatan, raport_sikap, raport_kenaikan |
| Finance | 5 | item_pembayaran, tagihan_siswa, pembayaran, pembayaran_rincian, tabungan_siswa |
| Presence | 3 | presensi, absensi, izin |
| Plugin infra | 2 | plugins, tenant_plugins |
| Plugin Kurikulum | 3 | kurikulum, struktur_kurikulum, komponen_kompetensi |
| RBAC Menu ACL | 2 | menus, menu_role_overrides |
| RBAC Field ACL | 2 | fields, field_role_overrides |
| **TOTAL** | **48** | (+1 `legacy_id_mappings` saat ETL aktif, di-drop setelah verify) |

### 3.3. Normalisasi Kunci vs SISFOKOL

| Aspek | SISFOKOL Lama | sisfokol_laravel Baru |
|---|---|---|
| Engine | MyISAM | InnoDB |
| PK | varchar(50) MD5 | BIGINT AUTO_INCREMENT |
| FK | tidak ada | FK + ON DELETE/UPDATE |
| Uang | varchar(15) | decimal(15,2) |
| Snapshot siswa/item di transaksi | ya | FK ke master |
| Soft delete | tidak | deleted_at semua tabel |
| Audit | log_login/log_entri | audit_logs JSON + created_by/updated_by |
| Orang tua | passwordx_ortu di m_siswa | tabel orang_tua + pivot siswa_orang_tua |
| Tahun ajaran aktif | hardcoded session | tenant_settings key-value |
| Kelas siswa | m_siswa.kelas overwrite tiap naik | kelas_siswa pivot per tapel (history) |

---

## 4. RBAC 5 Lapis (Database-Driven)

Engine: **Spatie laravel-permission** (teams mode, `team_id = tenant_id`).

| Lapis | Tabel | Contoh |
|---|---|---|
| 1. Resource.Action | role_has_permissions, model_has_roles | `siswa.create` ✅/❌ |
| 2. Menu Visibility | menus, menu_role_overrides | menu "Keuangan" show/hide per role |
| 3. Field/Atribut | fields, field_role_overrides | field `nominal_kurang` visible/hidden/readonly per role |
| 4. UI Element | (pakai resource.action) | tombol Edit/Hapus/Cetak via `@can()` |
| 5. Route | middleware `permission:` | `Route::middleware('permission:siswa.create')` |

### Konvensi Permission: `<resource>.<aksi>`
- Aksi standar: `.view .create .update .delete .manage .export .approve .restore`
- Contoh: `siswa.create`, `tagihan.view`, `raport.cetak`, `plugin.activate`, `audit.view`

### Role Seed (`is_system=1`)
`super_admin`, `admin_sekolah`, `ks`, `bendahara`, `bk`, `guru`, `wk`, `piket`, `sarpras`, `siswa`, `ortu` (11 role)

### Enforcement di Kode (3 lapis)
1. Route: `Route::middleware('permission:siswa.create')`
2. Controller: `$this->authorize('create', Siswa::class)` via Policy
3. Blade: `@can('siswa.create') ... @endcan` + `@field('siswa.nis') ... @endfield`

### RBAC Builder UI (admin, 4 tab)
- Tab 1: Role ↔ Permission (matriks centang)
- Tab 2: Menu Visibility (role → tree menu → show/hide/readonly)
- Tab 3: Field Visibility (role → list field → visible/hidden/readonly)
- Tab 4: User → Role (assign 1+ role per user)

Setiap perubahan: `permission:cache-reset` + clear menu cache + audit log + **diblokir saat impersonation aktif**.

---

## 5. Auth & Impersonation

### 5.1. Auth Flow
- bcrypt (cost 12), Sanctum (API Fase 2)
- Login: throttle 5/menit → bcrypt check → regenerate session → audit `login.success` → resolve tenant → redirect per role
- Session: secure, httponly, samesite=lax, timeout 30 mnt
- Force Password Reset: user hasil ETL `must_reset_password=1` → wajib `/password/change` setelah login

### 5.2. Impersonation "Login As" (per ADR-005)
- **Hierarki:** SuperAdmin → Admin + semua role; Admin Sekolah → role fungsional tenant; role fungsional tidak bisa
- **Env-gated:** `IMPERSONATION_ENABLED=true/false` (default false di production)
- Session `impersonated_by`; banner merah persistent; tombol "Return to my account"
- Audit immutable: `impersonate.start` & `impersonate.stop` ke audit_logs
- `BlockWhileImpersonating` blokir aksi sensitif (credential/role/plugin)
- `CanImpersonate` middleware: feature aktif + target dalam hierarki + target≠diri + target aktif + scope tenant

### 5.3. Guard & Middleware Chain
| Guard | Driver | Untuk |
|---|---|---|
| web | session | SuperAdmin, Admin, semua role fungsional |
| sanctum | token | (Fase 2) API/PWA |

**Chain:** `auth → ResolveTenant → EnsurePluginEnabled → BelongsToTenantScope → role|permission → BlockWhileImpersonating → throttle`

---

## 6. Plugin System

### 6.1. PluginContract (per ADR-009)
```php
interface PluginContract {
    public function kode(): string;          // "kurikulum"
    public function nama(): string;
    public function versi(): string;
    public function isCore(): bool;
    public function dependencies(): array;
    public function providerClass(): string;
    public function permissions(): array;
    public function menu(): array;
    public function boot(PluginContext $ctx): void;
}
```

### 6.2. Registry & Aktivasi
- `ModuleServiceProvider` scan `app/Plugins/*/` → instantiate manifest → simpan ke `PluginRegistry` → sync tabel `plugins`
- Aktivasi per-tenant via `tenant_plugins`: ON → emit `Plugin.Activated` → seed permission → cache reset → boot; OFF → emit `Plugin.Deactivated` → menu hilang, route 403, **data tetap**
- Middleware `plugin:<Kode>` cek `(tenant_id, plugin_id, aktif=1)`; SuperAdmin bypass

### 6.3. Event Hooks (loose coupling)
| Event Core | Subscriber |
|---|---|
| `SiswaRegistered` | PelaporanOrtu (Fase 2) |
| `GradeSaved` | PelaporanOrtu |
| `PaymentReceived` | PelaporanOrtu |
| `Evaluation.ResolveFramework` | **Kurikulum** (framework TP/LM atau KI/KD) |
| `Raport.RenderSection` | Kurikulum + PendidikanKarakter |
| `Plugin.Activated/Deactivated` | semua plugin re-evaluate boot |

### 6.4. Decoupling Evaluation ↔ Kurikulum
- **Kurikulum aktif:** `mapel.kurikulum_id` FK menentukan framework; event `Evaluation.ResolveFramework` di-resolve subscriber → inject metadata KI/fase/pedagogis ke view
- **Kurikulum non-aktif:** tabel `tp`/`lm` tetap generic (kode+teks+urutan manual), tanpa metadata framework

---

## 7. Core Modules Detail

### Layering per Module (konsisten)
```
Controller → Policy → Service → Model → Observer
  + FormRequest terpisah + @field ACL di Blade + route middleware
```

### 7.1. Module Highlights

| Module | Service/Logic Penting |
|---|---|
| **Tenancy** | `TenantContext` singleton DI; tapel aktif dari `tenant_settings`; suspend tenant = logout massal |
| **Auth** | RBAC Builder 4-tab; Force Password Reset; Impersonation env-gated; FieldAcl batch-resolve |
| **Academic** | `kelas_siswa` pivot per tapel (history); NIS unique per-tenant; `JadwalConflictChecker`; `KelasSiswaPromotionService` |
| **Evaluation** | `RaporService` hitung NA + deskripsi otomatis; `AsesmenBulkInputService`; `EvaluationFrameworkResolver` (event hook) |
| **Finance** | ⚠️ **`PembayaranService` paling kritis** — `DB::transaction` + `SELECT FOR UPDATE` row-lock; `TagihanGeneratorService` via scheduled command |
| **Presence** | `QrScannerService` multi-step; `PresensiRuleEngine` hitung telat; `IzinApprovalService` workflow pending→approved |

### 7.2. PembayaranService (KRITIS — pseudo-code)
```
bayar($siswa, $rincian, $diterimaOleh):
  DB::transaction:
    1. INSERT pembayaran (no_nota unique, tanggal, total)
    2. foreach rincian:
         SELECT tagihan_siswa FOR UPDATE  ← lock row
         INSERT pembayaran_rincian
         UPDATE tagihan_siswa:
           nominal_bayar += jumlah
           nominal_kurang -= jumlah
           lunas = (nominal_kurang <= 0)
           tanggal_lunas = now() bila lunas
    3. COMMIT
    4. emit PaymentReceived
    5. Audit log
  catch → rollback + throw
```

Tanpa locking → race condition rusak keuangan (legacy SISFOKOL rentan: DELETE+INSERT tanpa transaksi).

---

## 8. ETL Plan: SISFOKOL v7 → sisfokol_laravel

### 8.1. Arsitektur
```
sisfokol_v7 (legacy_mysql, MyISAM, MD5 PK, READ-ONLY)
       ↓
  ETL Pipeline (Laravel Console Command, DB::transaction)
       ↓ (mapping via legacy_id_mappings)
sisfokol_laravel (mysql default, InnoDB, BIGINT PK)
```

### 8.2. Tabel Helper
`legacy_id_mappings (id, tenant_id, entity_type, legacy_kd, new_id)` — di-drop setelah verifikasi.

### 8.3. Topological Order (20 step)
1. Tahun Ajaran → 2. Mapel Jenis → 3. Guru (+ users) → 4. Siswa + Ortu (+ users) → 5. Admin/Lainnya → 6. Mapel → 7. Kelas + Walikelas → 8. Kelas Siswa → 9. Jadwal → 10. TP/LM → 11. Asesmen Formatif → 12. Asesmen Sumatif → 13. Rapor → 14. Item Pembayaran → 15. Tagihan → 16. Pembayaran → 17. Tabungan → 18. Presensi → 19. Absensi → 20. Izin

### 8.4. Strategi Cleansing Kritis

| Masalah | Strategi |
|---|---|
| Password MD5 | Default `<NIS/NIP>@<tgl_lahir>` → bcrypt; `must_reset_password=1` → force reset saat login |
| Nominal varchar | `cleanMoney()`: hapus Rp/titik ribuan; koma→titik desimal |
| Tanggal string | Multi-format parser (Y-m-d, d-m-Y, d/m/Y); null bila invalid |
| Phone | `cleanPhone()`: 08xxx→628xxx (format WA) |
| Role mapping | `tp06→admin_sekolah`, `tp01→guru`, `tp03→wk+guru` (multi-role), `tp02→siswa`, dst. |
| Denormalisasi | Buang snapshot siswa/item; ganti dengan FK via `legacy_id_mappings` |

### 8.5. Verifikasi Pasca-ETL (`etl:verify`)
- Count reconciliation (siswa, guru, tagihan)
- Money SUM compare (selisih harus 0, toleransi 0.01)
- Orphan FK check (0 orphans)
- Password reset check (semua user ETL `must_reset_password=1`)

### 8.6. Cut-over
1. T-7: Setup server prod + staging
2. T-3: ETL staging + verify
3. T-1: Freeze legacy (read-only), backup final
4. T-0: ETL final → verify → switch DNS → announce password reset
5. T+7: Decommission legacy

---

## 9. Tech Stack Final

### 9.1. Backend (composer.json)
- Laravel 11, PHP 8.2+
- spatie/laravel-permission 6.x (teams mode)
- lab404/laravel-impersonate 2.x
- maatwebsite/excel 3.x (import/export)
- barryvdh/laravel-dompdf 3.x (raport, kwitansi)
- simplesoftwareio/simple-qrcode 4.x (presensi)
- predis/predis 2.x (Redis Fase 2)

### 9.2. Frontend (package.json)
- Vite 5.x (build tool, bukan Mix)
- Bootstrap 5.3 + @popperjs/core
- Alpine.js 3.x (interaktivitas deklaratif)
- sass

### 9.3. Deviasi dari D17
- **Hapus jQuery** → Alpine.js (lebih ringan)
- **Tidak pakai AdminLTE** → Bootstrap 5 native + custom layout (mendukung plugin menu injection)
- **Redis Fase 2** → Fase 1 cukup file cache/session
- **Docker wajib prod** → standarisasi environment

### 9.4. Code Style & Testing
- Laravel Pint (PSR-12) — wajib lulus sebelum merge
- PHPUnit 11 — Unit (Service) + Feature (endpoint) test
- Test paling kritis: `TenantIsolationTest`, `PembayaranServiceTest`, `RbacBuilderTest`, `ImpersonationTest`

---

## 10. Folder Structure Final

```
sisfokol-laravel/
├── app/
│   ├── Console/Commands/{MigrateLegacyDataCommand, VerifyEtlCommand,
│   │                       GenerateTagihanCommand, ResetPluginCacheCommand,
│   │                       Etl/ (20 Step + Cleansing + IdMapper)}
│   ├── Http/Middleware/{ResolveTenant, EnsurePluginEnabled, CanImpersonate,
│   │                     BlockWhileImpersonating, ForcePasswordReset}
│   ├── Models/Traits/{BelongsToTenant, TracksAuditColumns}
│   ├── Modules/{Tenancy, Auth, Academic, Evaluation, Finance, Presence}/
│   │   (Controllers, Models, Policies, Requests, Services, Observers,
│   │    Database/Migrations, Resources/views, routes.php)
│   ├── Plugins/{Kurikulum (penuh), Discipline, Inventory, Tahfidz,
│   │             HafalanHadist, BimbinganKonseling, PendidikanKarakter,
│   │             PelaporanOrtu, PWA (scaffold)}/
│   ├── Providers/{ModuleServiceProvider, PluginRegistryServiceProvider,
│   │               AuthServiceProvider, EventServiceProvider, ...}
│   ├── Support/{PluginRegistry, PluginContract, PluginContext, FieldAcl,
│   │             MenuRenderer, BladeDirectives, TenantContext}
│   └── Rules/{NisUniquePerTenant, NipUniquePerTenant, ValidKurikulumKode}
├── config/{app, auth, database (default+legacy_mysql), permission, impersonate,
│           modules, plugins, tenants}
├── database/{migrations, seeders (SuperAdmin, RolePermission, Menu, Field,
│              DemoTenant), factories}
├── public/assets/{css,js,img}
├── resources/{css/app.css, js/app.js, views (layouts, partials, dashboard,
│              errors), lang/id}
├── routes/{web.php, api.php (Fase 2)}
├── storage/{app/public/uploads, app/reports, logs, framework}
├── tests/{Unit/{Services, Support, Rules}, Feature/{Auth, Academic, Finance,
│           Presence, Plugin, Tenant}}
├── .env.example, .env.testing, composer.json, package.json, phpunit.xml,
│   vite.config.js, README.md
```

**Estimasi kelas Fase 1:** ~38 Controllers, ~48 Models, ~18 Policies, ~40 FormRequests, ~19 Services, ~26 Observers, 49 Migrations, 5 Seeders, ~80 Blade views, ~30 Tests.

---

## 11. Deployment

### 11.1. Stack Target
```
Ubuntu Server LTS 24.04
├── Nginx 1.24+ (reverse proxy)
├── PHP-FPM 8.2+
├── MySQL 8 / MariaDB 10.6 (InnoDB)
├── Redis 7 (Fase 2)
├── Supervisor (queue worker Fase 2)
└── Docker (recommended untuk env consistency)
```

### 11.2. CI/CD (GitHub Actions)
- Trigger: push, pull_request
- Service: MySQL 8 (test DB)
- Steps: composer install → npm ci → npm run build → key:generate → migrate → `php artisan test` → `pint --test`

### 11.3. Deployment Manual (SSH)
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan event:cache && php artisan permission:cache-reset
php artisan queue:restart
sudo systemctl reload php8.2-fpm && sudo systemctl reload nginx
```

### 11.4. Backup
| Jenis | Frekuensi | Tools |
|---|---|---|
| Database full | Harian 02:00 | `mysqldump --single-transaction`, retention 30 hari |
| Storage uploads | Mingguan | rsync ke S3/offsite |
| Code | Setiap push | Git remote |
| Audit logs | Tahunan archive | partition by year (Fase 2) |

---

## 12. Estimasi Scope & Acceptance Criteria

### 12.1. Fase 1 Definition of Done
- [ ] `php artisan migrate` jalan tanpa error (49 migrations)
- [ ] `php artisan db:seed` menghasilkan SuperAdmin + demo tenant + role/permission/menu/field seed
- [ ] `php artisan test` lulus (termasuk TenantIsolationTest, PembayaranServiceTest, RbacBuilderTest, ImpersonationTest)
- [ ] Login super_admin → buat tenant → assign admin_sekolah → login admin → aktivasi plugin Kurikulum → buat siswa → input nilai → cetak raport PDF → bayar tagihan → scan QR presensi — semua alur end-to-end jalan
- [ ] `php artisan migrate:legacy-sisfokol {tenant_id}` jalan + `etl:verify` PASS
- [ ] Field ACL terbukti: role BK tidak bisa lihat field `nominal_kurang`; role siswa tidak bisa lihat field siswa lain
- [ ] Impersonation: SuperAdmin bisa Login As admin_sekolah; banner merah muncul; aksi sensitif diblokir; audit log tercatat
- [ ] 8 plugin scaffold terdaftar di registry + bisa diaktifkan tanpa crash (route 404 bila belum ada implementasi)

### 12.2. Out of Scope Fase 1 (Fase 2+)
- API Sanctum untuk PWA/mobile
- WhatsApp Gateway real (PelaporanOrtu, notifikasi)
- Redis cache/session/queue
- Plugin non-Kurikulum: controller/route/view implementation (scaffold saja)
- Subscription/billing SaaS otomatis
- Backup per-tenant export
- Audit log partition by year
- Broadcast/WebSocket real-time

---

## 13. Sumber Kebenaran & Konvensi

### 13.1. Dokumentasi
| Folder | Isi | Sifat |
|---|---|---|
| `ADR/` (10 file) | Keputusan arsitektur final (binding) | Immutable setelah Accepted; Superseded bila berubah |
| `DEV_DOCS/` (10 file) | Diskusi & detail desain per bagian | Append-friendly |
| `sisfokol-laravel/docs/design.md` | Dokumen ini (kompilasi) | Sumber kebenaran implementasi |

### 13.2. Urutan Baca untuk Agent Implementasi
1. Dokumen ini (`design.md`)
2. `ADR/001–010` (keputusan binding)
3. `DEV_DOCS/009` (Section 5: detail per module + ETL)
4. `DEV_DOCS/010` (Section 6: folder + tech + deployment)
5. `DOCS/analisis-sisfokol/analisis-sisfokol-v7.md` (referensi domain)
6. `DEV_DOCS/008` (panduan dokumen ARENA: mana yang dipakai)

### 13.3. Aturan Komunikasi Antar Agent
- Agent wajib baca ADR + DEV_DOCS terbaru sebelum bertindak
- Kontradiksi → cek ADR-nya; bila Superseded, ikuti pengganti
- Setiap keputusan baru → ADR baru
- Setiap diskusi besar → DEV_DOCS baru

---

## 14. Open Items / Catatan

Item-item berikut perlu konfirmasi user sebelum/dalam implementasi, tapi **tidak mem-block mulai Fase 1**:

1. **KKM default per mapel** — apakah per-tenant configurable atau hardcoded default 75?
2. **Bobot NA sumatif** (tes vs non-tes) — apakah per-tenant configurable?
3. **Format kwitansi/raport PDF** — apakah ada template dari sekolah atau ikut prototype?
4. **WhatsApp Gateway** di Fase 1 — apakah wajib (bila ya, plugin PelaporanOrtu perlu di-promote ke penuh)?
5. **Branding** (logo, warna tema) — apakah per-tenant configurable?

### Resolusi (2026-06-20, user "next" = approve dengan default)
User approve design.md dengan **default values** berikut (dapat diubah via `tenant_settings` saat runtime tanpa code change):

| # | Item | Default Fase 1 | Catatan |
|---|---|---|---|
| 1 | KKM default | **75.00** per mapel, **tenant configurable** via `tenant_settings` key `kkm_default` | Admin sekolah bisa override per-mapel di kolom `mapel.kkm` |
| 2 | Bobot NA sumatif | **60% tes + 40% non-tes**, tenant configurable via `tenant_settings` keys `bobot_nilai_tes`, `bobot_nilai_non_tes` | `RaporService::hitungNA()` baca dari settings |
| 3 | Template PDF | **Adaptasi dari `prototype-antarmuka.html`** + layout DomPDF standar | Kwitansi: A5 portrait; Raport: A4 portrait |
| 4 | WhatsApp Gateway | **Fase 2** — PelaporanOrtu scaffold di Fase 1, event hook siap (`PaymentReceived`, `GradeSaved`), gateway real di Fase 2 | Plugin subscribe event tapi no-op di Fase 1 |
| 5 | Branding | **Per-tenant configurable** via `tenant_settings` (`logo_url`, `warna_primer`, `nama_sekolah`, `alamat`, `telepon`) | Default tema biru (`#1e4d8c`) dari prototype |

---

## 15. Self-Review

### 15.1. Kelengkapan (ceklis)
- [x] Arsitektur (modular + plugin) — ADR-002, 004, 009; DEV_DOCS-001, 004
- [x] Multi-tenant — ADR-003; DEV_DOCS-002 §2.1
- [x] RBAC 5 lapis — ADR-006, 010; DEV_DOCS-002 §2.2, DEV_DOCS-005
- [x] Impersonation — ADR-005; DEV_DOCS-002 §2.3
- [x] Skema 48 tabel + ETL helper — ADR-007; DEV_DOCS-003, 009
- [x] Plugin contract & lifecycle — ADR-009; DEV_DOCS-004
- [x] Detail per module (controller/service/policy/observer) — DEV_DOCS-009 §5.1–5.7
- [x] ETL mapping + cleansing + verify — DEV_DOCS-009 §ETL
- [x] Folder structure — DEV_DOCS-010 §2
- [x] Tech stack + deployment — DEV_DOCS-010 §3, §5
- [x] Acceptance criteria — dokumen ini §12.1

### 15.2. Konsistensi (cross-check)
| Cek | Hasil |
|---|---|
| Jumlah tabel 48 konsisten di ADR-007, DEV_DOCS-003, DEV_DOCS-006, dokumen ini §3.2 | ✅ |
| Stack di DEV_DOCS-001 ↔ DEV_DOCS-010 ↔ dokumen ini §9 | ✅ |
| Role seed 11 role di ADR-006 ↔ DEV_DOCS-002 ↔ dokumen ini §4 | ✅ |
| Plugin 9 (1 penuh + 8 scaffold) di ADR-004 ↔ ADR-009 ↔ DEV_DOCS-001 ↔ dokumen ini §2.3 | ✅ |
| Impersonation hierarki di ADR-005 ↔ DEV_DOCS-002 ↔ dokumen ini §5.2 | ✅ |
| ETL topological order ↔ dependency tabel (no FK violation) | ✅ |
| Field ACL default di ADR-010 ↔ DEV_DOCS-005 ↔ dokumen ini §4 | ✅ |

### 15.3. Risiko & Mitigasi
| Risiko | Mitigasi |
|---|---|
| Tenant data leak (lupa trait) | Wajib `BelongsToTenant` di semua model domain + `TenantIsolationTest` wajib lulus |
| Race condition keuangan | `PembayaranService` wajib `DB::transaction` + `FOR UPDATE` + `PembayaranServiceTest` lulus |
| Lupa pakai `@field` di Blade | Blade directive + code review checklist + field sensitif default `hidden` (fail-closed) |
| Plugin crash saat aktivasi | Manifest + ServiceProvider wajib, registry test, fallback graceful (route 404 bukan 500) |
| Password MD5 bocor saat ETL | Default password kuat + `must_reset_password=1` + log semua user affected |
| ARENA docs misleading | DEV_DOCS-007 + DEV_DOCS-008 panduan; sumber kebenaran = ADR + DEV_DOCS, BUKAN ARENA |
| Scope creep Fase 1 | Definition of Done §12.1; Fase 2+ items §12.2 eksplisit |
| Impersonation lupa disable di prod | Default `.env IMPERSONATION_ENABLED=false`; audit log + banner persistent |

### 15.4. Yang Belum Diperinci (akan di-detail di `writing-plans`)
- Exact code signature tiap Service/Controller (akan muncul saat planning per task)
- Exact migration SQL per tabel (akan ditulis saat planning per module)
- Exact test case per fitur (akan ditulis saat planning per task)
- UI mockup detail per halaman (akan adaptasi dari `prototype-antarmuka.html` saat planning)

Hal-hal di atas sengaja tidak diperinci di design doc — itu ranah implementation plan, bukan design.

---

## 16. Status & Approval

- **Status dokumen:** ✅ **APPROVED** (2026-06-20, user "next" = approve dengan default values per §14 Resolusi)
- **Next:** transition ke `writing-plans` skill → buat implementation plan step-by-step → baru mulai kode
- **NO CODE has been written yet** — masih 100% fase desain

### Tanda tangan user approval
- [x] **Approved** — lanjut ke `writing-plans` (dengan default values per §14 Resolusi)
- [ ] Approved with revisions
- [ ] Rejected

---

## Lampiran: Index ADR & DEV_DOCS

### ADR (Architecture Decision Records — binding)
| # | Judul | Status |
|---|---|---|
| 001 | Record Architecture Decisions | Accepted |
| 002 | Rebuild Total ke Laravel 11 Modular Monolith | Accepted |
| 003 | Multi-Tenant SaaS (Shared Database + tenant_id) | Accepted |
| 004 | Scope MVP Fase 1 | Accepted |
| 005 | Impersonation "Login As" Hierarkis & Env-Gated | Accepted |
| 006 | Granular Database-Driven RBAC | Accepted |
| 007 | Prinsip Skema Database Normalisasi | Accepted |
| 008 | DEV_DOCS sebagai Memory & Handoff Antar Agent | Accepted |
| 009 | Plugin Contract Plug-and-Play | Accepted |
| 010 | RBAC Menu & Field-Level ACL | Accepted |

### DEV_DOCS (Memory & Detail Desain)
| # | Topik |
|---|---|
| 001 | Kickoff — Keputusan Scope, Stack, Arsitektur Awal |
| 002 | Bagian 2 — Tenancy, Auth, RBAC, Impersonation |
| 003 | Bagian 3 — Skema Database 48 Tabel |
| 004 | Bagian 4 — Arsitektur Plugin |
| 005 | RBAC Menu & Field-Level ACL |
| 006 | Handover Session 1 |
| 007 | Audit Dokumen ARENA (valid vs overstated) |
| 008 | Daftar Dokumen ARENA Terklasifikasi |
| 009 | Bagian 5 — Core Modules Detail + ETL Plan |
| 010 | Bagian 6 — Folder Structure + Tech Stack + Deployment |
