# DEV_DOCS-009: Bagian 5 — Core Modules Detail + ETL Plan

- **Tanggal:** 2026-06-20 08:30
- **Topik:** Rincian implementasi per core module (controller/policy/service/observer) + rencana ETL dari SISFOKOL v7
- **Terhubung ke ADR:** 004 (scope), 006 (RBAC), 007 (skema DB), 009 (plugin), 010 (RBAC menu/field)
- **Sumber referensi:** `analisis-sisfokol-v7.md` (75 tabel, business flow), `blueprint-detail/rencana-migrasi-data.md` (matriks ETL 75 tabel), `blueprint-detail/011_workflow_migration_playbook.md` (alur per modul)

---

## 🏛️ Konvensi Layering Per Module

Setiap core module mengikuti **arsitektur 5 lapis** konsisten:

```
Controller (HTTP)       → terima Request, validasi via FormRequest, authorize via Policy, delegasi ke Service
  ↓
Policy (Authorization)  → bridge RBAC: return $user->can('siswa.create') (database-driven per ADR-006)
  ↓
Service (Business Logic) → DB::transaction, orchestration model, emit events, rules bisnis
  ↓
Model (Eloquent)         → tenant scope (BelongsToTenant), relations, fillable, casts
  ↓
Observer (Audit)         → catat audit_logs immutable (who/what/old/new/when) — otomatis
```

**FormRequest** terpisah: `StoreXxxRequest`, `UpdateXxxRequest`, validasi domain-specific.
**Route** di `Modules/<X>/routes.php` dengan middleware group per ADR-006 (permission + plugin + tenant).
**Field ACL** otomatis di Blade via `@field('siswa.nis')` per ADR-010.

### Naming Convention
- Controller: `SiswaController` (resource), `RaporController` (action-based khusus)
- Service: `PembayaranService`, `TagihanGeneratorService`, `RaporService`
- Policy: `SiswaPolicy`, `PembayaranPolicy` (registered di `AuthServiceProvider`)
- Observer: `SiswaObserver`, `PembayaranObserver` (registered di `EventServiceProvider`)
- FormRequest: `StoreSiswaRequest`, `UpdateSiswaRequest`, `BayarTagihanRequest`

### Audit Columns Auto-Fill
Trait `BelongsToTenant` + Trait `TracksAuditColumns`:
- `tenant_id` ← `app('tenant')->id` (saat create)
- `created_by` / `updated_by` ← `Auth::id()` (saat create/update)
- Soft delete otomatis via `deleted_at`

---

## 5.1 Module: Tenancy

**Tujuan:** Manajemen tenant (sekolah), branch (unit), pengaturan per-tenant.

### Komponen
| Kelas | Tugas |
|---|---|
| `TenantController` (super-admin scope) | CRUD tenants, assign admin_sekolah, suspend |
| `BranchController` | CRUD branches per tenant |
| `TenantSettingsController` | Key-value settings (`tapel_aktif_id`, `smt_aktif`, `logo_url`, `nama_sekolah`, `alamat`) |
| `TenantPolicy` | Only `super_admin` manage tenants; `admin_sekolah` manage own settings+branch |
| `ResolveTenant` middleware | Set `app('tenant')` dari `Auth::user()->tenant_id` |
| `BelongsToTenant` trait | Global scope WHERE tenant_id |
| `TenantContext` (singleton binding) | DI untuk service yang butuh tenant aktif |

### Business Logic Penting
- **TenantContext singleton** di-resolve di `ResolveTenant` middleware, di-binding ke container. Semua service bisa inject `TenantContext $ctx` → `$ctx->id`, `$ctx->branch_id`, `$ctx->settings()`.
- **Tapel aktif** disimpan di `tenant_settings` key=`tapel_aktif_id`. Semua query akademik/finance/evaluation resolve dari sini (bukan hardcoded session seperti SISFOKOL).
- **Suspend tenant**: set `tenants.aktif=0` + logout semua user tenant via `sessions` table delete.
- **Migration seed**: 1 SuperAdmin user + 1 demo tenant ("SMP IT Demo") + 1 admin_sekolah.

### Routes
```
super/tenants          → CRUD tenants (super_admin only)
super/tenants/{t}/branches → CRUD branches
admin/settings         → tenant_settings (admin_sekolah)
```

---

## 5.2 Module: Auth

**Tujuan:** Login, RBAC, impersonation, audit log, session management, RBAC Builder UI.

### Komponen
| Kelas | Tugas |
|---|---|
| `AuthController` | showLogin, login, logout, forgotPassword, resetPassword |
| `UserController` | CRUD users (admin_sekolah scope) |
| `RoleController`, `PermissionController` | RBAC Builder: role↔permission matrix |
| `RbacMenuController`, `RbacFieldController` | RBAC Builder: menu & field ACL (per ADR-010) |
| `ImpersonationController` | start, stop — env-gated + audit |
| `AuditLogController` | view audit_logs (filterable) |
| `LoginRequest`, `StoreUserRequest` | FormRequest |
| `UserPolicy`, `RolePolicy` | Authorization |
| `ImpersonationService` | Logic start/stop + audit + hierarchical check |
| `RbacBuilderService` | Simpan matrix menu/field/role/permission + cache reset |
| `UserObserver`, `RoleObserver`, `ModelHasRolesObserver` | Audit log semua perubahan RBAC |

### Business Logic Penting
- **Login flow** (per DEV_DOCS-002): throttle 5/menit → bcrypt check → regenerate session → audit `login.success` → resolve tenant → redirect per role.
- **Force password reset** (untuk user hasil ETL): `users.must_reset_password=true` → setelah login redirect ke `/password/change` wajib sebelum akses lain.
- **RBAC Builder UI**: 4 tab (Role↔Permission, Menu Visibility, Field Visibility, User→Role). Setiap save → `permission:cache-reset` + clear menu cache + audit `rbac.*` + **diblokir bila impersonation aktif**.
- **Impersonation**: `ImpersonationService::start($target)` cek `IMPERSONATION_ENABLED=true`, target dalam hierarki, target≠diri, target aktif, di scope tenant. Simpan `session.impersonated_by=Auth::id()`. Audit `impersonate.start`. Middleware `BlockWhileImpersonating` blokir POST ke `/users/*`, `/rbac/*`, `/plugins/*`, `/impersonate/*` (kecuali `/impersonate/stop`).
- **Field ACL resolution** di backend: `FieldAcl::resolveForUser($user, $model)` return map `[kolom => visible/hidden/readonly]` cached per (user, model) selama request. Controller pakai `FieldAcl::columnsForIndex('Siswa')` untuk tentukan kolom list yang dikirim ke view.

### Routes
```
POST /login, /logout, /password/*
admin/users             → CRUD users (admin_sekolah scope)
admin/rbac/permissions  → permission editor
admin/rbac/roles        → role + permission matrix
admin/rbac/menus        → menu ACL editor
admin/rbac/fields       → field ACL editor
admin/audit-logs        → view audit (filter by user/event/model)
impersonate/{user}/start, impersonate/stop  → env-gated
```

---

## 5.3 Module: Academic

**Tujuan:** Master akademik — siswa, orang tua, guru, tahun ajaran, semester, kelas, mapel, jadwal. **Core** dari hampir semua transaksi lain.

### Komponen
| Kelas | Tugas |
|---|---|
| `SiswaController`, `OrangTuaController`, `GuruController` | CRUD master person |
| `TahunAjaranController`, `SemesterController` | CRUD + set aktif |
| `KelasController`, `KelasSiswaController` | CRUD kelas + anggota kelas per tapel |
| `MapelController`, `MapelJenisController` | CRUD mapel |
| `JadwalController` | CRUD jadwal + validasi bentrok |
| `SiswaPolicy`, `GuruPolicy`, `KelasPolicy`, `JadwalPolicy` | RBAC bridge |
| `SiswaImportService` | Import Excel siswa (Laravel Excel) |
| `KelasSiswaPromotionService` | Naik kelas siswa per tapel baru |
| `JadwalConflictChecker` | Cek bentrok guru/kelas/hari/jam |
| Observers semua model | Audit log |

### Business Logic Penting
- **NIS/NIP unique per tenant** (bukan global): `UNIQUE(tenant_id, nis)` supaya 2 sekolah bisa punya NIS sama.
- **Kelas siswa per tapel**: siswa tidak "dimiliki" kelas, tapi ter-relasi via `kelas_siswa` pivot per `tahun_ajaran_id`. Tahun depan siswa masuk kelas baru → insert baru, bukan update. **Perbaikan fatal SISFOKOL** yang timpa `m_siswa.kelas` tiap naik kelas → hilang history.
- **Orang tua normalization**: tabel `orang_tua` terpisah + pivot `siswa_orang_tua` (siswa bisa banyak wali). **Perbaikan** SISFOKOL yang simpan `passwordx_ortu` langsung di `m_siswa`.
- **Jadwal conflict**: `JadwalConflictChecker::validate($jadwal)` cek kombinasi (tenant, tapel, smt, kelas, hari, jam_ke) UNIQUE + guru tidak di 2 kelas di jam sama. Return list conflict.
- **Promotion service**: `KelasSiswaPromotionService::promote($fromTapel, $toTapel, $mapping)` — bulk insert kelas_siswa baru untuk tapel target berdasarkan mapping tingkat (7→8, 8→9, 9→lulus).
- **Tapel/SMT aktif**: resolve dari `tenant_settings.tapel_aktif_id` + `tenant_settings.smt_aktif`. Semua transaksi akademik default ke nilai ini.

### Routes
```
academic/siswa           → resource (admin|ks|guru|wk|bk)
academic/orang-tua       → resource (admin|bk)
academic/guru            → resource (admin)
academic/tahun-ajaran    → resource + set-aktif (admin)
academic/semester        → resource + set-aktif (admin)
academic/kelas           → resource + anggota (admin|ks|wk)
academic/mapel           → resource (admin|ks)
academic/jadwal          → resource (admin|ks|guru) + check-conflict
```

---

## 5.4 Module: Evaluation

**Tujuan:** Tujuan Pembelajaran (TP), Lingkup Materi (LM), asesmen formatif/sumatif, raport. **Generic** — framework KI/KD atau TP/LM dari Kurikulum plugin via event hook.

### Komponen
| Kelas | Tugas |
|---|---|
| `TpController`, `LmController` | CRUD TP/LM per (mapel, tapel, kelas) |
| `AsesmenFormatifController` | Bulk input nilai formatif per TP per siswa |
| `AsesmenSumatifController` | Bulk input nilai sumatif per LM per siswa |
| `RaporController` | Cetak raport (catatan, sikap, kenaikan kelas) |
| `AsesmenFormatifPolicy`, `AsesmenSumatifPolicy` | Hanya guru mapel + wk + admin |
| `RaporService` | Hitung NA, generate deskripsi, render PDF |
| `AsesmenBulkInputService` | Bulk upsert nilai per kelas (transaction) |
| `EvaluationFrameworkResolver` | Listen `Evaluation.ResolveFramework` event → query Kurikulum plugin |
| Observers nilai | Audit + emit `GradeSaved` |

### Business Logic Penting
- **Asesmen formatif**: nilai kualitatif `Tercapai`/`Belum` per TP per siswa. Tidak ada NA. Bulk input → `AsesmenBulkInputService::saveFormatif($kelas, $mapel, $tp, $values)`.
- **Asesmen sumatif**: nilai kuantitatif (0-100). Komponen: `nilai_tes`, `nilai_non_tes`, `nilai_akhir` (weighted). Per LM. NA mapel = agregasi LM (rata-rata berbobot per ADR/spesifikasi sekolah).
- **NA calculation**: `RaporService::hitungNA($siswa, $mapel, $tapel, $smt)` → aggregasi semua LM sumatif, bobot sesuai setting tenant. Output: NA + predikat (A/B/C/D) + deskripsi otomatis ("Ananda menunjukkan penguasaan...").
- **Framework resolution**: Saat controller render form input nilai, fire `Evaluation.ResolveFramework($mapel, $kelas)` → Kurikulum plugin listen (bila aktif) → return metadata framework (KI-1/2/3/4 atau CP + fase + pendekatan pedagogis). Tanpa plugin → generic.
- **Rapor**: `RaporController@cetak($siswa, $tapel, $smt)` → kumpul semua nilai + catatan + sikap + kenaikan → render PDF via DomPDF. Event `Raport.RenderSection` → plugin bisa inject section custom (PendidikanKarakter inject section karakter).
- **Kenaikan kelas**: `raport_kenaikan` — `Naik`/`Tinggal`/`Lulus`. Trigger event `Rapor.KenaikanDecided` → trigger `KelasSiswaPromotionService` bila tapel baru.

### Routes
```
evaluation/tp                  → resource (guru mapel)
evaluation/lm                  → resource (guru mapel)
evaluation/asesmen/formatif    → bulk input (guru mapel)
evaluation/asesmen/sumatif     → bulk input (guru mapel)
evaluation/raport              → preview + cetak (wk|ks)
evaluation/raport/kenaikan     → set kenaikan (wk|ks|admin)
```

---

## 5.5 Module: Finance

**Tujuan:** Item pembayaran, tagihan siswa, transaksi pembayaran, tabungan. **Paling kompleks** karena transaksi keuangan.

### Komponen
| Kelas | Tugas |
|---|---|
| `ItemPembayaranController` | CRUD item (SPP/infaq/kegiatan/lainnya) |
| `TagihanSiswaController` | View tagihan, generate bulk |
| `PembayaranController` | Form bayar + submit (sangat kritis) |
| `TabunganSiswaController` | CRUD tabungan + mutasi debet/kredit |
| `LaporanKeuanganController` | Laporan tunggakan, penerimaan harian, rekonsiliasi |
| `ItemPembayaranPolicy`, `PembayaranPolicy`, `TabunganPolicy` | RBAC + field ACL (nominal_kurang hidden per role) |
| `BayarTagihanRequest` | Validasi pembayaran |
| **`TagihanGeneratorService`** | Generate tagihan SPP bulanan per kelas per tapel |
| **`PembayaranService`** | Pencatatan pembayaran + DB transaction + locking |
| `TabunganMutasiService` | Debit/kredit tabungan + locking |
| `KwitansiGenerator` | Generate no_nota unik + QR code |
| Observers | Audit semua mutasi keuangan |

### Business Logic Penting — `TagihanGeneratorService`

```
TagihanGeneratorService::generateSpp($tapel, $smt, $kelas, $item)
  → DB::transaction:
      1. Ambil semua siswa aktif di kelas (kelas_siswa where tapel, kelas)
      2. Loop tiap bulan (1-12 atau per periode item)
      3. Insert tagihan_siswa jika belum ada (UNIQUE check siswa+item+bulan+tapel)
      4. Skip bila sudah lunas
      5. Audit log bulk
```
Dijalankan via **scheduled command** `php artisan tagihan:generate` per awal bulan, atau manual oleh admin.

### Business Logic Penting — `PembayaranService` (KRITIS)

```php
PembayaranService::bayar($siswa, $rincian, $diterimaOleh)
  → DB::transaction dengan pessimistic locking:
      1. INSERT pembayaran (header): no_nota (unique, dari KwitansiGenerator), tanggal, total
      2. foreach rincian:
           - SELECT tagihan_siswa FOR UPDATE (lock row)
           - INSERT pembayaran_rincian
           - UPDATE tagihan_siswa:
               nominal_bayar += jumlah
               nominal_kurang -= jumlah
               lunas = (nominal_kurang <= 0)
               tanggal_lunas = now() bila lunas
      3. COMMIT
      4. emit PaymentReceived($pembayaran)
      5. Audit log (header + rincian)
  → catch: rollback + throw
```
**Locking wajib** (`FOR UPDATE`) untuk mencegah race condition: kasir A dan B bayar tagihan yang sama bersamaan. Tanpa locking → keuangan rusak (legacy SISFOKOL rentan karena DELETE+INSERT tanpa transaksi).

### Business Logic Penting — `TabunganMutasiService`
- Setor (kredit): `saldo += jumlah` + audit
- Tarik (debit): `SELECT FOR UPDATE`, cek `saldo >= jumlah`, `saldo -= jumlah` + audit. Bila kurang → throw `InsufficientBalanceException`.
- Transaksi pembayaran bisa otomatis debet tabungan (opsional, via setting).

### Field ACL Penting (per ADR-010)
- `tagihan.nominal_kurang` → default `hidden` kecuali admin_sekolah, bendahara
- `pembayaran.total` → default `hidden` kecuali admin_sekolah, bendahara, ks (view)
- `tabungan.saldo` → default `hidden` kecuali admin_sekolah, bendahara, siswa (own)

### Routes
```
finance/item-pembayaran      → resource (admin|bendahara)
finance/tagihan              → view + generate (admin|bendahara)
finance/pembayaran           → create + store (bendahara) + view (admin|wk|siswa own)
finance/pembayaran/{id}/cetak → cetak kwitansi (bendahara|admin)
finance/tabungan             → resource + mutasi (bendahara)
finance/laporan/*            → laporan (admin|bendahara|ks)
```

---

## 5.6 Module: Presence

**Tujuan:** Presensi (QR), absensi, izin. Multi-actor (siswa + pegawai).

### Komponen
| Kelas | Tugas |
|---|---|
| `PresensiController` | Scan QR (endpoint) + manual entry |
| `AbsensiController` | Entri absensi sakit/ijin/alpha per siswa/pegawai |
| `IzinController` | Entri izin masuk/pulang + approval workflow |
| `LaporanPresensiController` | Rekap harian, bulanan, keterlambatan |
| `PresensiPolicy`, `AbsensiPolicy`, `IzinPolicy` | RBAC |
| `QrScannerService` | Decode QR + validasi siswa/pegawai + cek duplikat hari |
| `PresensiRuleEngine` | Hitung telat berdasarkan jam_masuk sekolah (dari tenant_settings) |
| `IzinApprovalService` | Workflow pending→approved/rejected + notifikasi |
| Observers | Audit + emit `Presence.Recorded` |

### Business Logic Penting
- **QR scan flow**: `POST /presence/scan {qr_code}` → `QrScannerService::handle()`:
  1. Decode QR → dapat (entity_type, entity_id, token)
  2. Cek entity aktif di tenant
  3. Cek hari ini sudah scan jenis sama? → throw `AlreadyPresentException`
  4. `PresensiRuleEngine::evaluate($entity, $now)` → tentukan jenis (datang/pulang berdasarkan jam), hitung `telat_menit` bila datang > jam_masuk
  5. INSERT presensi + audit
  6. emit `Presence.Recorded` → plugin PelaporanOrtu (Fase 2) kirim WA ortu
- **Jam masuk/pulang** dari `tenant_settings` (`jam_masuk_sekolah`, `jam_pulang_sekolah`). Default 07:00 / 14:00, dapat diubah admin.
- **Absensi** vs **Presensi**: Absensi = siswa tidak hadir (sakit/ijin/alpha), diinput piket manual. Presensi = scan QR kehadiran fisik. Dua tabel terpisah (per normalisasi).
- **Izin approval**: Piket entry izin → status `pending` → BK/wk approve → `approved`/`rejected`. Setelah approved, generate surat izin PDF dengan QR.
- **Manual mode**: Bila QR gagal scan, piket input manual via `PresensiController@manual` (audit `metode=manual`).

### Routes
```
presence/scan           → POST endpoint QR (piket|siswa self-scan)
presence/manual         → form manual entry (piket)
presence/rekap          → rekap harian (piket|ks|admin)
absensi                 → resource (piket|admin)
izin                    → resource + approve (piket|bk|wk)
izin/{id}/surat         → cetak surat izin PDF
```

---

## 5.7 Plugin Referensi: Kurikulum (built fully in Fase 1)

**Tujuan:** Mesin framework nilai K13/Kurmer/Muatan Lokal/Deep Learning. Listen event `Evaluation.ResolveFramework` + `Raport.RenderSection`.

### Komponen
| Kelas | Tugas |
|---|---|
| `KurikulumPlugin` (manifest) | Implement `PluginContract`: kode=`kurikulum`, permissions, menu |
| `KurikulumServiceProvider` | Register routes, listen events, boot PluginContext |
| `KurikulumController` | CRUD `kurikulum` (K13, KURMER, Muatan Lokal) |
| `StrukturKurikulumController` | CRUD `struktur_kurikulum` (jenjang, kelas, fase, jenis_kegiatan) |
| `KomponenKompetensiController` | CRUD `komponen_kompetensi` (KI/CP + pendekatan pedagogis) |
| `EvaluationFrameworkSubscriber` | Listen `Evaluation.ResolveFramework` → return framework metadata |
| `RaporSectionSubscriber` | Listen `Raport.RenderSection` → inject section kompetensi |

### Business Logic
- **Aktivasi tenant**: Admin → toggle → emit `Plugin.Activated` → seed permission `kurikulum.view/.manage` ke roles admin/ks/guru → cache reset.
- **Framework resolution**: Saat guru buka form input nilai → Evaluation controller fire `Evaluation.ResolveFramework($mapel, $kelas)` → subscriber query `struktur_kurikulum WHERE kurikulum_id=mapel.kurikulum_id AND jenjang=kelas.jenjang AND kelas=kelas.nama` → return `{ki: [...], fase: 'D', pedagogis: 'deep_learning'}` → controller pass ke view.
- **Tanpa Kurikulum aktif**: Evaluation core tampilkan form TP/LM generic tanpa metadata KI/fase (per DEV_DOCS-004 klarifikasi).
- **Rapor injection**: Saat render rapor → fire `Raport.RenderSection($siswa, $tapel, $smt)` → Kurikulum subscriber return section "Capaian Kompetensi" berdasar `komponen_kompetensi`.

### Routes (hanya bila plugin aktif di tenant)
```
kurikulum/                 → CRUD kurikulum (admin|ks)
kurikulum/struktur         → CRUD struktur (admin|ks)
kurikulum/komponen         → CRUD komponen kompetensi (admin|ks|guru)
```
Middleware: `['auth', 'plugin:kurikulum', 'permission:kurikulum.manage']`

---

## 🚚 ETL PLAN: SISFOKOL v7 → sisfokol_laravel

### Arsitektur ETL

```
sisfokol_v7 (legacy_mysql connection, MyISAM, MD5 PK)
        ↓
   ETL Pipeline (Laravel Console Command)
        ↓ (mapping via legacy_id_mappings table)
sisfokol_laravel (mysql default connection, InnoDB, BIGINT PK)
```

**Connection config** di `config/database.php`:
```php
'legacy_mysql' => [
    'driver' => 'mysql',
    'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
    'database' => env('LEGACY_DB_DATABASE', 'sisfokol_v7'),
    'username' => env('LEGACY_DB_USERNAME'),
    'password' => env('LEGACY_DB_PASSWORD'),
    // READ-ONLY access only — recommended: GRANT SELECT only
],
```

### Tabel Pembantu: `legacy_id_mappings`

Ditambahkan ke skema (Fase 1 = 48 + 1 ETL helper = 49 tabel total saat ETL aktif, di-drop setelah verifikasi):

```
legacy_id_mappings
  id BIGINT PK
  tenant_id (target tenant)
  entity_type VARCHAR ('siswa', 'guru', 'tapel', 'kelas', 'mapel', 'user', ...)
  legacy_kd VARCHAR(50) (MD5 lama)
  new_id BIGINT UNSIGNED (FK ke tabel target)
  created_at
  
  UNIQUE(tenant_id, entity_type, legacy_kd)
  INDEX(entity_type, legacy_kd)
```

### Urutan Topologis ETL (dependency-driven)

Eksekusi wajib berurutan untuk hindari FK violation:

| Langkah | Sumber Legacy | Target Modern | Catatan Cleansing |
|---|---|---|---|
| **1. Tahun Ajaran** | `m_tapel` | `tahun_ajaran` | Trim nama, `aktif='true'`→bool |
| **2. Mapel Jenis** | `m_mapel_jns` | `mapel_jenis` | Trim |
| **3. Pegawai/Guru** | `m_pegawai` | `users` + `guru` | **2 step**: create user (force_reset_password) → create guru. `kode`→NIP, hash MD5→bcrypt default |
| **4. Siswa + Ortu** | `m_siswa` | `users` + `siswa` + `orang_tua` + `siswa_orang_tua` | **3 entity per row**: user siswa, user ortu (bila passwordx_ortu ada), profil siswa. `passwordx_ortu`→buat akun ortu terpisah |
| **5. Admin/Lainnya** | `adminx`, `m_user` | `users` | Mapping `tp01..tp042` → role Spatie |
| **6. Mapel** | `m_mapel` | `mapel` | `kode`→unique, `kkm` varchar→decimal, `pegawai_kd`→FK guru via mapping |
| **7. Kelas + Walikelas** | `m_kelas`, `m_walikelas` | `kelas` | `walikelas.peg_kd`→FK wali_kelas_id via mapping |
| **8. Kelas Siswa** | `m_siswa.kelas` (string!) | `kelas_siswa` | **Denorm→FK**: cari kelas by nama, link siswa_id |
| **9. Jadwal** | `jadwal`, `m_waktu_jadwal` | `jadwal` | `mapel_kode`→FK, `waktu` string→TIME jam_mulai/selesai |
| **10. TP/LM** | `kurmer_mapel_tp`, `kurmer_mapel_lm` | `tp`, `lm` | Trim teks |
| **11. Asesmen Formatif** | `kurmer_nilai_asesmen_formatif_detail` | `asesmen_formatif_nilai` | **Denorm→FK**: siswa_kd→siswa_id via mapping, `Tercapai`/`Belum` enum |
| **12. Asesmen Sumatif** | `kurmer_nilai_asesmen_sumatif_detail` | `asesmen_sumatif_nilai` | nilai varchar→decimal, `lm_na`→nilai_akhir |
| **13. Rapor** | `siswa_raport_*` | `raport_catatan/sikap/kenaikan` | Trim |
| **14. Item Pembayaran** | `m_keu_siswa` | `item_pembayaran` | **nominal varchar→decimal(15,2)**: hapus `Rp.`, titik ribuan, spasi → `floatval` |
| **15. Tagihan Siswa** | `siswa_bayar_tagihan` | `tagihan_siswa` | **nominal_bayar/nominal_kurang varchar→decimal**. **Denorm**: siswa_kd→siswa_id, item_kd→item_id. `lunas_status`→`lunas` bool |
| **16. Pembayaran** | `siswa_bayar` + `siswa_bayar_rincian` | `pembayaran` + `pembayaran_rincian` | nominal cleansing, link via mapping |
| **17. Tabungan** | (legacy fitur terpisah) | `tabungan_siswa` | Bila ada di legacy |
| **18. Presensi** | `user_presensi` | `presensi` | **Denorm**: user_kd→user_id atau siswa_id, `telat_ket`→`telat_menit` int |
| **19. Absensi** | `user_absensi` | `absensi` | jenis enum |
| **20. Izin** | `user_ijin` | `izin` | status approval default approved (legacy tidak punya workflow) |

### Strategi Cleansing Kritis

#### 1. Password MD5 → Bcrypt
**Tidak boleh** migrasi MD5 langsung. Strategi:
```
1. Setiap user hasil ETL → password = Hash::make(default)
   default = "<NIS/NIP>@<tanggal_lahir>" bila tanggal_lahir ada, atau random secure string
2. Set users.must_reset_password = true
3. Saat user login pertama kali → redirect /password/change wajib
4. Bila double-hash (bcrypt dari MD5) → maintenis ke depan susah, tidak direkomendasikan
```

#### 2. Nominal varchar → decimal
```php
function cleanMoney(?string $value): float {
    if (!$value) return 0.00;
    // Hapus "Rp", "rp", spasi, titik ribuan
    $clean = preg_replace('/[^0-9,]/', '', $value);
    // Asumsi koma = desimal (lokal ID) → ganti ke titik
    $clean = str_replace(',', '.', $clean);
    // Bila ada multi-titik → ambil terakhir sebagai desimal
    $parts = explode('.', $clean);
    if (count($parts) > 2) {
        $int = implode('', array_slice($parts, 0, -1));
        $dec = end($parts);
        $clean = $int . '.' . $dec;
    }
    return floatval($clean);
}
```

#### 3. Tanggal string → DATE
```php
// Legacy: "2026-01-15", "15-01-2026", "15/01/2026", atau kosong
function cleanDate(?string $value): ?string {
    if (!$value || $value === '0000-00-00') return null;
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) return $dt->format('Y-m-d');
    }
    return null; // log warning, manual reconcile
}
```

#### 4. Phone number → WA format
```php
function cleanPhone(?string $value): ?string {
    $clean = preg_replace('/[^0-9]/', '', $value);
    if (str_starts_with($clean, '0')) return '62' . substr($clean, 1);
    if (str_starts_with($clean, '62')) return $clean;
    if (str_starts_with($clean, '8')) return '62' . $clean;
    return $clean ?: null;
}
```

#### 5. Role mapping legacy → Spatie
| Legacy `tipe` | Role Spatie |
|---|---|
| `tp06` (Admin) | `admin_sekolah` |
| `tp01` (Guru Mapel) | `guru` |
| `tp02` (Siswa) | `siswa` |
| `tp03` (Wali Kelas) | `wk` + `guru` (multi-role) |
| `tp04` (Kepala Sekolah) | `ks` |
| `tp011` (Guru BK) | `bk` |
| `tp033` (Piket) | `piket` |
| `tp041` (Sarpras) | `sarpras` |
| `tp042` (Bendahara) | `bendahara` |
| `tp05` (SuperAdmin — jika ada) | `super_admin` |

### Implementasi ETL Command

```
app/Console/Commands/
  MigrateLegacyDataCommand.php       → entry point: php artisan migrate:legacy-sisfokol {tenant_id}
  Etl/
    StepInterface                    → contract: handle(TenantContext): void
    MigrateTahunAjaranStep.php
    MigrateGuruStep.php
    MigrateSiswaStep.php
    MigrateMapelStep.php
    MigrateKelasStep.php
    MigrateKelasSiswaStep.php
    MigrateJadwalStep.php
    MigrateTpLmStep.php
    MigrateAsesmenStep.php
    MigrateRaporStep.php
    MigrateKeuanganStep.php
    MigratePembayaranStep.php
    MigratePresensiStep.php
    MigrateAbsensiIzinStep.php
    Cleansing/
      MoneyCleaner.php
      DateCleaner.php
      PhoneCleaner.php
      PasswordResetter.php
    IdMapper.php                     → singleton: catat & lookup (entity, legacy_kd) → new_id
```

**Main command flow:**
```php
public function handle() {
    $tenant = Tenant::findOrFail($this->argument('tenant_id'));
    $this->call('migrate:fresh', ['--seed' => false]); // HANYA saat dev/initial
    
    DB::beginTransaction();
    try {
        foreach ($this->steps as $step) {
            $this->info("Running " . get_class($step));
            $step->handle($tenant);
            $this->info("  ✓ Done");
        }
        DB::commit();
        $this->info("ETL SUCCESS. Run verification.");
    } catch (\Throwable $e) {
        DB::rollBack();
        $this->error("ETL FAILED: " . $e->getMessage());
        $this->error("File: " . $e->getFile() . ":" . $e->getLine());
        return 1;
    }
}
```

### Verifikasi Pasca-ETL

Command `php artisan etl:verify {tenant_id}` menjalankan:

```sql
-- 1. Reconciliation count
SELECT COUNT(*) FROM legacy_sisfokol.m_siswa;          -- expect =
SELECT COUNT(*) FROM sisfokol_laravel.siswa WHERE tenant_id=?;

-- 2. Reconciliation money (KRITIS)
SELECT SUM(CAST(nominal_bayar AS DECIMAL(15,2)))
  FROM legacy_sisfokol.siswa_bayar_tagihan;
SELECT SUM(nominal_bayar) FROM sisfokol_laravel.tagihan_siswa WHERE tenant_id=?;
-- Selisih harus 0 (toleransi rounding 0.01)

-- 3. Orphan check (FK integrity)
SELECT COUNT(*) FROM sisfokol_laravel.kelas_siswa ks
  LEFT JOIN sisfokol_laravel.siswa s ON ks.siswa_id=s.id
  WHERE s.id IS NULL;   -- expect 0

-- 4. Unmapped legacy check
SELECT entity_type, COUNT(*) FROM legacy_id_mappings
  GROUP BY entity_type;  -- log untuk debugging

-- 5. Password reset check
SELECT COUNT(*) FROM sisfokol_laravel.users WHERE must_reset_password=0;
-- expect hanya super_admin + admin demo (user ETL wajib =1)
```

Bila semua PASS → drop `legacy_id_mappings` (bila tidak perlu audit) atau simpan untuk referensi. Bila FAIL → inspect → fix script → re-run ETL dari awal.

### Cut-over Strategy
1. **Freeze** legacy (read-only) — `GRANT SELECT ONLY` ke semua user
2. Backup legacy (`mysqldump sisfokol_v7 > backup.sql`)
3. Run `php artisan migrate` di sisfokol_laravel (skema kosong)
4. Run `php artisan migrate:legacy-sisfokol {tenant_id}`
5. Run `php artisan etl:verify {tenant_id}`
6. Bila PASS → switch app → announce ke user → "password baru via reset"
7. Bila FAIL → rollback → inspect → fix

---

## 📊 Summary Fase 1 Implementation Per Module

| Module | Controller | Policy | Service | Observer | Routes |
|---|:-:|:-:|:-:|:-:|:-:|
| Tenancy | 3 | 2 | 1 (TenantContext) | 2 | super/*, admin/settings |
| Auth | 6 | 3 | 3 | 3 | login, admin/*, impersonate |
| Academic | 9 | 4 | 3 | 9 | academic/* |
| Evaluation | 5 | 2 | 3 | 4 | evaluation/* |
| Finance | 5 | 3 | 4 | 5 | finance/* |
| Presence | 4 | 3 | 3 | 3 | presence/*, absensi, izin |
| **Plugin Kurikulum** | 3 | 1 | 2 (subscriber) | 0 | kurikulum/* |

**Total estimasi kelas Fase 1:**
- Controllers: ~35
- Policies: ~18
- Services: ~19
- Observers: ~26
- FormRequests: ~40
- Models: ~40 (satu per tabel domain)
- Migrations: 48 tabel + 1 legacy_id_mappings = **49**
- Console Commands: ~4 (tagihan:generate, migrate:legacy-sisfokol, etl:verify, plugin:cache-reset)

## Status desain Bagian 5: ✅ FINAL & SIAP DIPRESENTASIKAN

## Next
- ⏭️ Bagian 6: Folder structure final + tech stack final + deployment notes
- ⏳ Tulis design doc final → user review → transition `writing-plans`
