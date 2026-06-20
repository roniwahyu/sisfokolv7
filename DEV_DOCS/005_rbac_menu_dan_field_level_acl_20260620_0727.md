# DEV_DOCS-005: RBAC sampai Menu & Atribut (Field-Level ACL)

- **Tanggal:** 2026-06-20 07:27
- **Topik:** Perluasan ADR-006 — RBAC menjangkau menu, field, dan elemen UI
- **Terhubung ke ADR:** 010 (RBAC menu & field), 006 (granular RBAC), 005 (impersonation)

---

## Latar

ADR-006 hanya atur RBAC level aksi (`siswa.create`). Di SISFOKOL, kebocoran akses sering karena:
1. Menu tak boleh diakses tetap tampil
2. Field sensitif (tunggakan, password ortu, nilai siswa lain, NIK) tetap terbaca di form/tabel
3. Tombol (Edit/Hapus/Cetak) tetap muncul walau tak punya permission

RBAC harus turun 3 lapis lagi, semuanya database-driven.

## 3 lapis tambahan

### Lapis A — Menu-level ACL

```
menus
  id, tenant_id NULLABLE, kode UNIQUE ("academic.siswa", "finance.tagihan"),
  label, icon, route, urutan, parent_id NULLABLE, group,
  permission_required, plugin_kode NULLABLE, is_system, aktif

menu_role_overrides
  id, menu_id FK, role_id FK, tenant_id NULLABLE,
  visible ENUM('show','hide','readonly') DEFAULT 'show'
  UNIQUE(role_id, menu_id, tenant_id)
```

**Resolusi menu per user:**
1. Ambil menus aktif (core + plugin aktif tenant)
2. Filter `permission_required` via RBAC user
3. Apply `menu_role_overrides` (role, menu, tenant) — prioritas tertinggi
4. SuperAdmin → semua

→ Admin bisa sembunyikan "Keuangan" dari BK walau defaultnya boleh. Tanpa kode.

### Lapis B — Field-level ACL

```
fields
  id, kode UNIQUE ("siswa.nis", "tagihan.nominal_kurang"),
  model, kolom, label,
  kategori ENUM('normal','sensitif','sangat_sensitif'),
  default_visibility ENUM('visible','hidden','readonly')

field_role_overrides
  id, field_id FK, role_id FK, tenant_id NULLABLE,
  visibility ENUM('visible','hidden','readonly'),
  UNIQUE(role_id, field_id, tenant_id)
```

**Prioritas (highest wins):** field_role_overrides → fields.default_visibility → visible

**Implementasi Blade:**
```blade
@field('siswa.nis')
    <input type="text" name="nis" value="{{ $siswa->nis }}">
@endfield
```
- `visible` → render normal
- `readonly` → tambah disabled/readonly
- `hidden` → blok kosong (input hidden value kosong, BUKAN display:none — anti inspect)

**Tabel list:** `FieldAcl::columnsForIndex('Siswa')` → controller kirim hanya kolom yang boleh.

### Lapis C — Action/UI element ACL

Pakai permission resource.action yang sudah ada:
```blade
@can('siswa.create') <a class="btn btn-primary">Tambah</a> @endcan
@can('siswa.update') <a class="btn btn-edit">Edit</a> @endcan
@can('siswa.delete') <button class="btn btn-del">Hapus</button> @endcan
@can('raport.cetak') <a class="btn btn-print">Cetak</a> @endcan
```

## UI Admin: RBAC Builder

Satu halaman Auth modul untuk admin visual:
```
RBAC Builder (per tenant, per role)
├─ Tab 1: Role ↔ Permission     (matriks centang resource.action)
├─ Tab 2: Menu Visibility       (role → tree menu → show/hide/readonly)
├─ Tab 3: Field Visibility      (role → list field → visible/hidden/readonly)
└─ Tab 4: User → Role           (assign 1+ role per user)
```

Setiap perubahan: simpan → `permission:cache-reset` + clear menu cache → audit log → diblokir bila impersonation aktif.

## Kategori field default (seed awal, fail-closed)

| Field | Default role boleh lihat |
|---|---|
| `siswa.telepon`, `siswa.alamat`, `siswa.tanggal_lahir` | admin_sekolah, wk (kelas sendiri) |
| `orang_tua.telepon`, `orang_tua.email` | admin_sekolah, bk |
| `tagihan.nominal_kurang`, `pembayaran.total` | admin_sekolah, bendahara |
| `siswa.password` (hashed) | tidak pernah |
| `audit_logs.*` | super_admin, admin_sekolah |
| `nilai` siswa lain | guru (mapelnya), wk (kelasnya), admin |

## Mitigasi risiko

| Risiko | Mitigasi |
|---|---|
| Performa render (cek ACL per field) | `FieldAcl::resolve` batch 1 query untuk semua field model + cache per (user, model) |
| Lupa pakai `@field` | Blade directive + code review checklist; field sensitif default `hidden` (fail-closed) |
| Admin salah setup | Preview mode + audit log + role seed default aman |
| Keamanan hidden | Hidden = input hidden value KOSONG (bukan display:none), anti DOM-inspect |

## Tabel ACL baru yang ditambahkan ke skema Fase 1

- `menus` (per ADR-010)
- `menu_role_overrides`
- `fields`
- `field_role_overrides`

→ Skema Fase 1 bertambah dari 44 → **48 tabel** (update DEV_DOCS-003 + ADR-007 di implementasi).

## Status desain RBAC menu & field: ✅ FINAL & DISETUJUI USER
