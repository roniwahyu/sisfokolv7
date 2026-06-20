# ADR-007: Prinsip Skema Database (InnoDB, BIGINT PK, FK, Soft Delete, Audit Kolom)

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)

## Konteks

Skema lama SISFOKOL (`analisis-sisfokol-v7.md` §4.4) punya 10 temuan kritis: MyISAM, tanpa FK, PK `varchar(50)` MD5, numerik varchar, denormalisasi tinggi, tanpa audit, tanpa soft-delete, dll. Refactor butuh prinsip skema baru yang seragam untuk semua tabel.

## Keputusan

Semua tabel di `sisfokol_laravel` mengikuti **prinsip seragam** berikut:

### 1. Engine & charset
- `InnoDB` (transaksi, row-locking, FK) — bukan MyISAM
- `utf8mb4` + `utf8mb4_unicode_ci` (support emoji, full UTF-8)
- `$tableOptions` konsisten: `'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'`

### 2. Primary key
- `bigIncrements('id')` → `BIGINT UNSIGNED AUTO_INCREMENT` (bukan hash MD5 varchar)
- Relasi: `unsignedBigInteger('xxx_id')` + foreign key constraint
- Kode bisnis (NIS, NIP, kode mapel) jadi kolom **unique index biasa**, bukan PK

### 3. Timestamps standar
Setiap tabel:
```php
$table->timestamps();                              // created_at, updated_at
$table->softDeletes();                             // deleted_at (nullable)
$table->unsignedBigInteger('created_by')->nullable();
$table->unsignedBigInteger('updated_by')->nullable();
$table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
$table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
```

### 4. Tipe data sesuai domain
| Domain | Tipe | (vs lama) |
|---|---|---|
| Uang (SPP, nominal) | `decimal(15, 2)` | varchar(15) |
| Nilai/skor | `unsignedTinyInteger` (0–100) | varchar(5) |
| Poin pelanggaran | `smallInteger` | varchar |
| Tahun | `smallInteger` (mis. 2026) | varchar(4) |
| Bulan | `tinyInteger` (1–12) | varchar(2) |
| Semester | `tinyInteger` (1–2) | varchar(1) |
| Boolean/status | `boolean` | enum('true','false') |
| Teks panjang | `text` / `longText` bila perlu | longtext berlebihan |

### 5. Foreign key + ON DELETE/UPDATE
- Relasi wajib FK constraint
- Default `ON DELETE RESTRICT` untuk master data (jangan sampai hapus siswa yang punya nilai)
- `nullOnDelete()` untuk relasi opsional (mis. `pegawai_kd`)
- `cascadeOnDelete()` hanya untuk child terikat (mis. rincian pembayaran ikut header)

### 6. Tenant scope (semua tabel domain)
Setiap tabel domain (kecuali `tenants`, `branches`, `users` super_admin) punya:
```php
$table->unsignedBigInteger('tenant_id');
$table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
$table->index(['tenant_id', ...kolom_lain_yang_sering_filter]);
```
Trait `BelongsToTenant` menambah global scope `WHERE tenant_id = app('tenant')->id`.

### 7. Index
- Index pada kolom yang sering difilter/sort/join: `tenant_id`, `siswa_id`, `tapel_id`, `kelas_id`, `tanggal`, `status`
- Composite index untuk query kombinasi umum (mis. `[tenant_id, tapel_id, kelas_id]`)
- Unique constraint untuk kode bisnis: `nis` unique per tenant, `nip` unique per tenant

### 8. Konvensi penamaan
- Tabel: `snake_case` plural (`siswa`, `tagihan_siswa`, `tahun_ajaran`)
- Model: `StudlyCase` singular (`Siswa`, `TagihanSiswa`, `TahunAjaran`)
- FK: `<tabel_singular>_id` (`siswa_id`, `tapel_id`)
- Pivot: urutan alphabetis (`kelas_siswa`, `siswa_orang_tua`)
- Kolom tenant: `tenant_id` (konsisten di semua tabel)

## Konsekuensi

- ✅ Integritas referensial terjamin via FK
- ✅ Soft delete → data tidak hilang permanen; bisa restore
- ✅ Audit → traceability siapa ubah apa kapan
- ✅ Tenant isolation seragam
- ❌ Banyak kolom boilerplate (`tenant_id`, `created_by`, `updated_by`, timestamps, soft-delete) → dimitigasi: **base migration trait/macro** agar setiap migration cukup `$table->tenantAndAuditColumns()` (helper custom)
- ⚠️ FK membuat bulk insert/delete sedikit lebih lambat → trade-off integritas, layak
