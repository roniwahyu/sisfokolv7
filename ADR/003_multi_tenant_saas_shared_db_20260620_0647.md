# ADR-003: Arsitektur Multi-Tenant SaaS (Shared Database + tenant_id)

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)

## Konteks

SISFOKOL asli adalah sistem **single-school**. Kebutuhan baru: platform melayani **banyak sekolah** (SaaS), masing-masing bisa punya **multi-branch** (unit/cabang). Pilihan arsitektur multi-tenant:

1. **Database-per-tenant** — isolasi kuat, tapi kompleks (manajemen koneksi, migration per-tenant, backup terpisah)
2. **Shared database + tenant_id + global scope** — satu DB, semua tabel punya `tenant_id`, query otomatis difilter via trait Eloquent

## Keputusan

**Shared database dengan kolom `tenant_id` + global Eloquent scope** (opsi 2).

### Identifikasi tenant
- **Bukan** via subdomain (sesuai keputusan user, 2026-06-20).
- **SuperAdmin** (platform scope, `tenant_id = NULL`) yang **menetapkan Admin Sekolah** ke tenant tertentu.
- **Admin Sekolah** mengelola data tenant-nya sendiri + membuat/assign user dengan role fungsional.
- Tenant di-resolve via middleware `ResolveTenant` dari `tenant_id` user yang login → disimpan ke `app('tenant')`.

### Struktur tenant
```
tenants        (sekolah)  id, nama, npsn, domain, ...
branches       (unit)     id, tenant_id, nama, jenjang, ...
users                     id, tenant_id, branch_id, ...
<setiap tabel domain>     ..., tenant_id, branch_id (nullable)
```

### Isolasi data
- Trait `BelongsToTenant` otomatis menambah `WHERE tenant_id = app('tenant')->id` pada semua query domain.
- Global scope dilepas hanya untuk SuperAdmin.
- `branch_id` bersifat opsional (sekolah tanpa branch tetap jalan).

## Konsekuensi

- ✅ Operasional ringan: 1 DB, 1 migration run untuk semua tenant
- ✅ Sharing infrastruktur (caching, queue, backup) efisien
- ✅ Cukup untuk skala ratusan sekolah
- ⚠️ Risiko "tenant data leak" bila ada query yang lupa pakai trait → dimitigasi: **wajib** trait pada semua model domain + test isolasi
- ⚠️ Backup/restore per-tenant sulit → di Fase 2 bisa dieksport per-tenant via job
