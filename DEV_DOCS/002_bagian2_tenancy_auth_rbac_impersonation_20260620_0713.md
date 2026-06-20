# DEV_DOCS-002: Bagian 2 — Tenancy, Auth, Granular RBAC, Impersonation

- **Tanggal:** 2026-06-20 07:13
- **Topik:** Rincian desain Bagian 2 (sudah dipresentasikan & disetujui user)
- **Terhubung ke ADR:** 003 (multi-tenant), 005 (impersonation), 006 (granular RBAC)

---

## 2.1 Model tenant & aktor

```
SuperAdmin (platform scope, tenant_id NULL)
   │ manages
   ▼
Tenant (sekolah) ──┬── Branch 1 (unit SD)
                   └── Branch 2 (unit SMP)   ← Multi Branch
                          │
                          ▼
                   Users (admin sekolah + 9 role fungsional)
```

| Aktor | Scope | tenant_id | Akses |
|---|---|---|---|
| SuperAdmin | platform | NULL | kelola tenant, branch, plugin katalog, assign admin sekolah |
| Admin Sekolah | 1 tenant | terisi | kelola data tenant, assign role fungsional, aktifkan plugin |
| Role fungsional (9) | tenant/branch | terisi | ks, bendahara, bk, guru, wk, piket, sarpras, siswa, ortu |

## 2.2 Granular Database-Driven RBAC (inti — per ADR-006)

**Prinsip:** seluruh mapping role↔permission & user↔role di DB, atur via UI admin, tanpa ubah kode.

### Skema

```
permissions    id, name UNIQUE, guard_name, display_name, description, module, category
roles          id, name, team_id NULLABLE (NULL=global super_admin), guard_name, display_name, is_system
role_has_permissions        role_id FK, permission_id FK
model_has_roles            role_id FK, model_id, model_type, team_id
model_has_permissions      permission_id FK, model_id, model_type, team_id
users          id, tenant_id NULLABLE, branch_id NULLABLE, username, email, nama, password(bcrypt), tipe, aktif
```

### Yang diatur via UI (database-driven)
| Apa | Tabel | UI? |
|---|---|---|
| Permission tersedia | permissions | ✅ |
| Role tersedia | roles | ✅ (admin buat role kustom) |
| Role↔Permission | role_has_permissions | ✅ RBAC editor |
| User↔Role | model_has_roles | ✅ 1 user banyak role |
| Override per user | model_has_permissions | ✅ |

### Konvensi permission: `<resource>.<aksi>`
- `.view .create .update .delete .manage .export .approve .restore`
- Contoh: `siswa.create`, `tagihan.view`, `raport.cetak`, `plugin.activate`, `audit.view`

### 3 lapis enforcement
1. Route: `middleware('permission:siswa.create')`
2. Controller: `$this->authorize('create', Siswa::class)` via Policy
3. Blade: `@can('siswa.create') ... @endcan`

### Role seed (is_system=1): `super_admin`, `admin_sekolah`, `ks`, `bendahara`, `bk`, `guru`, `wk`, `piket`, `sarpras`, `siswa`, `ortu`
### Tenant scope: Spatie teams mode, team_id = tenant_id. SuperAdmin global tembus semua.

## 2.3 Impersonation "Login As" (per ADR-005)

- Hierarki: SuperAdmin → Admin + semua role; Admin Sekolah → role fungsional di tenant-nya; role fungsional tidak bisa.
- **Env-gated:** `IMPERSONATION_ENABLED=true/false` (default false production)
- Session `impersonated_by`; banner merah persistent; tombol "Return to my account"
- Audit immutable: `impersonate.start` & `impersonate.stop` ke audit_logs
- Aksi sensitif diblokir saat impersonate via `BlockWhileImpersonating` (ubah credential/role/plugin)
- Validasi via `CanImpersonate` middleware

## 2.4 Guard & middleware chain

| Guard | Driver | Untuk |
|---|---|---|
| web | session (default) | SuperAdmin, Admin, semua role fungsional |
| sanctum | token | (Fase 2) API/PWA/mobile |

**Chain (urutan):** `auth → ResolveTenant → EnsurePluginEnabled → BelongsToTenantScope(trait) → role|permission → BlockWhileImpersonating → throttle`

## 2.5 Alur login & resolusi tenant

```
POST /login (username, password [+ tenant_code bila super_admin])
  → validasi + throttle (5/menit)
  → cari user by username case-insensitive + aktif=1
  → Hash::check bcrypt
  → sukses: Auth::login, audit login.success, update last_login_at
  → ResolveTenant: super_admin → no context; lain → app('tenant')->set(tenant_id, branch_id)
  → redirect dashboard per role
```
Login multi-role SISFOKOL (`tp01`..`tp042`) dihilangkan → role dari model_has_roles.

## 2.6 Route groups

```
/ (guest)         login, forgot/reset password
super/            role:super_admin → tenants/*, branches/*, plugins/* (katalog), audit-logs/*
admin/            role:admin_sekolah + tenant → users/*, plugins/* (aktivasi), settings/*
/dashboard        auth (role-aware)
academic/*        admin|ks|guru|wk|siswa(readonly)
finance/*         admin|bendahara
presence/*        admin|piket|ks
evaluation/*      admin|guru|wk|ks|siswa(readonly)
impersonate/*     impersonate.enabled + canImpersonate
```
Tiap modul/plugin punya route file sendiri, di-load ModuleServiceProvider (plugin hanya bila aktif di tenant).

## 2.7 Matriks permission ringkas (Fase 1)

| Permission | super | admin | ks | bendahara | bk | guru | wk | piket | sarpras | siswa | ortu |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| tenant.manage | ✓ | | | | | | | | | | |
| user.manage | ✓ | ✓ | | | | | | | | | |
| plugin.activate | ✓ | ✓ | | | | | | | | | |
| siswa.manage | ✓ | ✓ | | | ✓ | | | | | | |
| guru.manage | ✓ | ✓ | | | | | | | | | |
| kelas.manage | ✓ | ✓ | ✓ | | | ✓ | | | | | |
| jadwal.manage | ✓ | ✓ | ✓ | | | ✓ | | | | | |
| tagihan.manage | ✓ | ✓ | | ✓ | | | | | | | |
| pembayaran.manage | ✓ | ✓ | | ✓ | | | | | | | |
| tabungan.manage | ✓ | ✓ | | ✓ | | | | | | | |
| nilai.manage | ✓ | | | | | ✓ | ✓ | | | | |
| raport.view | ✓ | | ✓ | | | ✓ | ✓ | | | ✓ | |
| raport.cetak | ✓ | | ✓ | | | | ✓ | | | | |
| presensi.manage | ✓ | ✓ | | | | | | ✓ | | | |
| absensi.manage | ✓ | ✓ | | | | | | ✓ | | | |
| izin.manage | ✓ | ✓ | | | | | | ✓ | | | |
| inventaris.manage | ✓ | ✓ | | | | | | | ✓ | | | |
| bk.manage | ✓ | ✓ | | | ✓ | | | | | | |
| dashboard.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| ortu.view | ✓ | | | | ✓ | | | | | | ✓ |

(Catatan: ini seed default; admin bisa ubah via RBAC editor per tenant karena database-driven.)

## Audit & session hardening
- bcrypt cost 12, password policy, reset signed URL
- Throttle 5/menit; audit login ke user_log_login
- Semua create/update/delete domain → audit_logs (who/what/old/new/timestamp) via observer
- Session: secure, httponly, samesite=lax, timeout 30 mnt

## Status desain Bagian 2: ✅ FINAL & DISETUJUI USER
