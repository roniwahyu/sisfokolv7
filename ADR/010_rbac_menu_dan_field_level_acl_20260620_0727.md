# ADR-010: RBAC Menjangkau Menu dan Atribut (Field-Level ACL)

- **Tanggal:** 2026-06-20 07:27
- **Status:** Diterima (Accepted)
- **Meluaskan:** ADR-006 (Granular Database-Driven RBAC)

## Konteks

ADR-006 menetapkan RBAC database-driven di level aksi (`siswa.create`, dst.). Tetapi di SISFOKOL, kebocoran akses sering terjadi karena:

1. **Menu** yang tidak boleh diakses tetap tampil (admin guru melihat menu "Pembayaran" walau tak bisa klik)
2. **Atribut/kolom sensitif** (nominal tunggakan, password ortu, nilai siswa lain, NIK) tetap terlihat di form/tabel, walau user tak seharusnya lihat
3. **Tombol aksi** (Edit/Hapus/Cetak) tetap muncul walau user tak punya permission

RBAC harus turun **3 lapis tambahan** di bawah resource.action:
- **Menu level** — menu item muncul/disisembunyikan
- **Field level** — kolom tampil/sembunyi/readonly
- **Action/UI element level** — tombol & elemen UI

Dan semuanya harus **database-driven** (admin atur via UI), bukan hardcode di Blade.

## Keputusan

### 1. Menu-level ACL (database-driven)

Tabel baru:
```
menus
  id, tenant_id NULLABLE (NULL=menu global sistem),
  kode UNIQUE,          -- "academic.siswa", "finance.tagihan"
  label, icon, route, urutan, parent_id NULLABLE, group,
  permission_required,  -- nama permission untuk melihat menu ini
  plugin_kode NULLABLE, -- NULL=core; "kurikulum"=plugin
  is_system,            -- bawaan; tak bisa dihapus
  aktif
  + timestamps

menu_role_overrides       (database-driven; admin bisa override per role)
  id, menu_id FK, role_id FK, tenant_id NULLABLE,
  visible ENUM('show','hide','readonly') DEFAULT 'show'
  UNIQUE(role_id, menu_id, tenant_id)
```

**Logic resolusi menu per user:**
1. Ambil semua `menus` aktif (core + plugin aktif tenant)
2. Filter: user harus punya `permission_required` (cek RBAC user)
3. Apply override: bila ada `menu_role_overrides` untuk (role, menu, tenant) → pakai itu (prioritas tertinggi)
4. Bila SuperAdmin → tampilkan semua

→ Admin bisa: sembunyikan menu "Keuangan" dari role BK walau permission defaultnya boleh. **Database-driven, tanpa kode.**

### 2. Field-level ACL (database-driven)

Tabel baru:
```
fields                     (katalog atribut yang dapat di-ACL)
  id, kode UNIQUE,         -- "siswa.nis", "tagihan.nominal_kurang", "pembayaran.total"
  model,                   -- "Siswa", "TagihanSiswa"
  kolom,                   -- nama kolom fisik
  label,                   -- untuk UI
  kategori ENUM('normal','sensitif','sangat_sensitif'),
  default_visibility ENUM('visible','hidden','readonly')  -- bila tak ada override

field_role_overrides       (database-driven)
  id, field_id FK, role_id FK, tenant_id NULLABLE,
  visibility ENUM('visible','hidden','readonly') DEFAULT NULL,
  UNIQUE(role_id, field_id, tenant_id)
```

**Logic field ACL:**
1. Saat render form/tabel, kelas `FieldAcl` resolve visibility tiap field untuk (user, model, kolom)
2. Urutan prioritas (highest wins):
   - `field_role_overrides` untuk (role, field, tenant)
   - `fields.default_visibility`
   - `visible` (fallback)
3. Apply:
   - `visible` → tampilkan normal
   - `readonly` → tampilkan tapi disabled (input readonly)
   - `hidden` → tidak dirender sama sekali (input hidden dengan value kosong, bukan display:none — supaya tidak bocor via inspect)

**Implementasi di Blade via Blade directive + Form helper:**
```blade
@field('siswa.nis')
    <input type="text" name="nis" value="{{ $siswa->nis }}">
@endfield
```
Direktif `@field` resolve via `FieldAcl::visible('siswa.nis')`. Bila `hidden` → blok dirender kosong. Bila `readonly` → tambah `disabled`/`readonly` otomatis.

**Untuk tabel list (DataTables):** `FieldAcl::columnsForIndex('Siswa')` return array kolom yang boleh ditampilkan → controller hanya kirim kolom yang relevan ke view.

### 3. Action/UI element ACL

Tombol & elemen UI pakai permission resource.action yang sudah ada (ADR-006):
```blade
@can('siswa.create') <a class="btn btn-primary">Tambah</a> @endcan
@can('siswa.update') <a class="btn btn-edit">Edit</a> @endcan
@can('siswa.delete') <button class="btn btn-del">Hapus</button> @endcan
@can('raport.cetak') <a class="btn btn-print">Cetak</a> @endcan
```

→ Tidak butuh tabel baru; memakai permission resource.action yang sudah database-driven.

### 4. UI Admin: "RBAC Builder"

Satu halaman di modul Auth untuk admin mengatur semuanya secara visual:

```
RBAC Builder (per tenant, per role)
├─ Tab 1: Role ↔ Permission      (matriks centang resource.action)
├─ Tab 2: Menu Visibility        (pilih role → tree menu → show/hide/readonly)
├─ Tab 3: Field Visibility       (pilih role → list field → visible/hidden/readonly)
└─ Tab 4: User → Role            (assign 1+ role per user)
```

Setiap perubahan:
- Simpan ke tabel terkait
- `permission:cache-reset` + clear menu cache
- Audit log (`rbac.menu_override_changed`, `rbac.field_override_changed`, dst.)
- Diblokir bila impersonation aktif (per ADR-005)

### 5. Kategori field default (seed awal)

Field sensitif default `hidden` untuk role non-admin:
| Field | Default role boleh lihat |
|---|---|
| `siswa.telepon`, `siswa.alamat`, `siswa.tanggal_lahir` | admin_sekolah, wk (kelas sendiri) |
| `orang_tua.telepon`, `orang_tua.email` | admin_sekolah, bk |
| `tagihan.nominal_kurang`, `pembayaran.total` | admin_sekolah, bendahara |
| `siswa.password` (hashed) | tidak pernah ditampilkan |
| `audit_logs.*` | super_admin, admin_sekolah |
| `nilai` siswa lain | guru (mapelnya), wk (kelasnya), admin |

## Konsekuensi

- ✅ RBAC benar-benar granular: menu, atribut, tombol — semuanya database-driven
- ✅ Tidak ada kebocoran visual (menu terlihat, field sensitif terbaca, tombol muncul)
- ✅ Customize per tenant × per role × per field — sangat fleksibel
- ✅ UI builder → admin self-service tanpa developer
- ❌ Kompleksitas query: menu/field ACL butuh eager load + cache (Redis/Fase 1 file cache)
- ❌ Performa render: cek ACL per field → dimitigasi: FieldAcl resolve batch untuk semua field model dalam 1 query + cache per (user, model)
- ⚠️ Risk "lupa pakai @field" → dimitigasi: Blade directive + code review checklist; field sensitif default `hidden` (fail-closed)
- ⚠️ Risk admin salah setup → dimitigasi: preview mode + audit log + role seed default yang aman
