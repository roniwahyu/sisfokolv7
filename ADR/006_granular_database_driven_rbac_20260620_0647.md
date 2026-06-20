# ADR-006: Granular Database-Driven RBAC (Resource.Action Permissions)

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)

## Konteks

SISFOKOL v7 memakai "RBAC" primitif berbasis **kode tipe** (`tp01`..`tp042`) yang di-hardcode per folder role. Kelemahan:

- Role tidak bisa diubah tanpa ubah kode
- Tidak ada granularity: role = akses penuh ke folder, tanpa kontrol per aksi (create/edit/delete)
- 1 user = 1 role; tidak bisa kombinasi (mis. guru yang juga wali kelas)
- Tidak bisa customize per tenant (tenant A ingin BK bisa lihat keuangan, tenant B tidak)

Kebutuhan baru: **RBAC granular yang sepenuhnya database-driven** — admin sekolah bisa atur mapping role↔permission dan user↔role **melalui UI**, tanpa sentuh kode, per tenant.

## Keputusan

Adopsi **Spatie laravel-permission** (teams mode, `team_id` = `tenant_id`) sebagai engine, dengan konvensi permission **`resource.aksi`**. **Seluruh mapping tersimpan di database**, bukan di kode.

### Skema database

```
permissions
  id, name (UNIQUE), guard_name, display_name, description, module, category
  -- name mengikuti konvensi: resource.aksi, mis. "siswa.create", "tagihan.view"
  -- display_name/description/module/category untuk UI permission manager

roles
  id, name, team_id (NULL=global super_admin), guard_name, display_name, is_system
  -- is_system=1 → role bawaan (tak bisa dihapus admin), tapi permission-nya tetap bisa diubah

role_has_permissions   (mapping role ↔ permission,  database-driven)
  permission_id, role_id

model_has_roles        (mapping user ↔ role, per team/tenant, database-driven)
  role_id, model_id, model_type, team_id

model_has_permissions  (override langsung user ↔ permission, opsional)
  permission_id, model_id, model_type, team_id
```

### Prinsip "Database-Driven" (inti ADR ini)

| Apa | Disimpan di mana | Dapat diatur via UI? |
|---|---|---|
| Daftar permission yang tersedia | tabel `permissions` | ✅ (admin bisa tambah permission modul/plugin) |
| Role apa saja yang ada | tabel `roles` | ✅ (admin buat role kustom per tenant) |
| Role punya permission apa | `role_has_permissions` | ✅ **inti RBAC editor** — centang/uncentang per role |
| User punya role apa | `model_has_roles` | ✅ assign user ke 1+ role |
| Override per user | `model_has_permissions` | ✅ tambah/cabut permission spesifik ke user |

→ **Tidak ada** `Gate::define()` atau `Role::const` yang hardcode rule bisnis. Kode hanya tanyakan `user->can('siswa.create')`; jawaban 100% dari database.

### Konvensi penamaan permission

Format: **`<resource>.<aksi>`**

| Aksi standar | Arti |
|---|---|
| `.view` | lihat list & detail |
| `.create` | tambah |
| `.update` | edit |
| `.delete` | hapus (soft) |
| `.manage` | semua aksi di atas (shortcut) |
| `.export` | export Excel/PDF |
| `.approve` | approval workflow |
| `.restore` | batal-soft-delete |

Contoh permission core Fase 1: `tenant.manage`, `user.manage`, `plugin.activate`, `siswa.manage`, `guru.manage`, `kelas.manage`, `mapel.manage`, `jadwal.manage`, `tahun_ajaran.manage`, `nilai.manage`, `raport.view`, `raport.cetak`, `tagihan.manage`, `pembayaran.manage`, `tabungan.manage`, `presensi.manage`, `absensi.manage`, `izin.manage`, `audit.view`.

### Role seed (bawaan, `is_system=1`)

`super_admin` (global), `admin_sekolah`, `ks`, `bendahara`, `bk`, `guru`, `wk` (wali kelas), `piket`, `sarpras`, `siswa`, `ortu`.

Role bawaan bisa diubah permission-nya per tenant, tetapi tidak bisa dihapus. Admin bisa membuat **role kustom** (`is_system=0`) untuk kombinasi unik per sekolah.

### Granularitas enforcement di kode

Aplikasi memeriksa permission di **3 lapis**:

1. **Route** — `Route::middleware('permission:siswa.create')`
2. **Controller** — `$this->authorize('create', Siswa::class)` (via Policy)
3. **Blade** — `@can('siswa.create') ... @endcan`

Policy Laravel menjadi jembatan: `SiswaPolicy::create()` return `$user->can('siswa.create')` — sehingga logic tunggal, database yang menentukan.

### Tenant scope

Spatie **teams mode** aktif. Saat admin assign role ke user, `team_id` = tenant_id diset otomatis. Permission lookup efektif:
```
user → roles (di team=tenant) → permissions
        + model_has_permissions (di team=tenant)   ← override
```
SuperAdmin pakai role global (`team_id = NULL`) → tembus semua tenant.

### Audit RBAC

Setiap perubahan mapping (`role_has_permissions`, `model_has_roles`) dicatat di `audit_logs` (event `rbac.role_permission_changed`, `rbac.user_role_changed`) — siapa admin yang mengubah, role mana, permission mana, tambah/hapus. Demikian juga saat impersonation aktif, perubahan RBAC **diblokir** (lihat ADR-005).

## Konsekuensi

- ✅ Admin sekolah fully self-service: atur role, permission, dan assignment tanpa developer
- ✅ Granular per aksi (bukan folder-level seperti SISFOKOL)
- ✅ Multi-role per user (guru + wali kelas dalam satu akun)
- ✅ Customize per tenant (tenant A ≠ tenant B untuk role yang sama)
- ✅ Plugin contribute permission sendiri saat aktivasi (lihat ADR-008 plugin system)
- ⚠️ UI permission manager perlu jelas (grup per `module`, deskripsi) supaya admin tidak bingung
- ⚠️ Performance: Spatie cache role/permission; perlu `php artisan permission:cache-reset` setelah perubahan via UI (di-handle otomatis di controller)
- ⚠️ Migrasi dari model tipe SISFOKOL: mapping `tp01→guru`, `tp02→siswa`, dst. saat ETL (lihat ADR-009)
