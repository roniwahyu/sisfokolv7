# DEV_DOCS-001: Kickoff — Keputusan Scope, Stack, dan Arsitektur Awal

- **Tanggal:** 2026-06-20 07:13
- **Sesi:** Kickoff brainstorming SISFOKOL v7 → Laravel 11
- **Topik:** Ringkasan keputusan dari sesi diskusi Bagian 1
- **Terhubung ke ADR:** 002 (rebuild), 003 (multi-tenant), 004 (scope MVP), 008 (DEV_DOCS)

---

## Konteks proyek

Konversi SISFOKOL v7.00 (PHP Native + MySQL MyISAM, ~75 tabel) menjadi aplikasi **Laravel 11** modern di folder baru `sisfokol-laravel/`. Berdasarkan dokumen di `DOCS/` (terutama `analisis-sisfokol-v7.md` yang sangat komprehensif).

## Sumber yang dipelajari

- `DOCS/analisis-sisfokol/analisis-sisfokol-v7.md` — analisis mendalam: 75 tabel, business flow, 9 role, temuan kritis, blueprint
- `DOCS/dokumen-proyek-sis/` — A02 (visi), A06 (RBAC), B11 (arsitektur), B12 (DB), C13 (data dictionary), D17 (teknologi), D18 (coding standard)
- `DOCS/ARENA_..._workspace.../014 & 016` — laporan dev yang klaim "196 migration selesai" → **diverifikasi OVERSTATED**:
  - Folder `sisfokol-laravel-mvp/` **tidak ada** di workspace (hanya PHP native SISFOKOL + db/)
  - 132 dari 196 migration adalah **placeholder kosong**
  - "100% GREEN" hanya cek brace-balance PHP, bukan migration/test fungsional

**Kesimpulan:** pada dasarnya membangun dari nol berdasarkan desain dokumen, bukan melanjutkan codebase yang ada.

## Keputusan scope (dari Q&A dengan user)

| # | Pertanyaan | Jawaban | Implikasi |
|---|---|---|---|
| 1 | Cakupan build | **Fondasi + Fase 1 MVP** | 6 core modul fully + plugin infra + 1 plugin referensi (Kurikulum) + ETL master |
| 2 | Tenant | **Multi-tenant SaaS** | shared-DB + tenant_id + global scope |
| 3 | DB name | `sisfokol_laravel` | DB baru terpisah dari legacy |
| 4 | Data lama | **Termasuk ETL master** | siswa/guru/kelas/mapel/tapel dari `sisfokol_v7` |
| 5 | Mode tenant | shared-DB + tenant_id | bukan DB-per-tenant |
| 6 | Identifikasi tenant | SuperAdmin tetapkan admin | admin mengelola datanya sendiri; multi-branch |

## Keputusan stack

- **Backend:** Laravel 11 (PHP 8.2+)
- **DB:** MySQL 8 / MariaDB 10.6, engine **InnoDB**, `utf8mb4`
- **RBAC:** Spatie laravel-permission (teams mode, `team_id` = `tenant_id`)
- **Impersonation:** lab404/laravel-impersonate (env-gated)
- **Frontend (Fase 1):** Blade + Bootstrap 5 + Alpine.js + Vite
- **Plugin PWA (Fase 2+):** scaffold saja di Fase 1
- **Export/Report:** Laravel Excel + DomPDF
- **Auth:** bcrypt (cost 12), Sanctum (API Fase 2)

## Keputusan arsitektur: Domain-Modular Monolith + Plugin System

```
Core (selalu aktif, 6 modul):
  Tenancy, Auth, Academic, Evaluation, Finance, Presence

Plugin (plug-and-play per tenant, 9 modul):
  1. Kurikulum (mesin nilai K13/Kurmer/Muatan Lokal/Deep Learning)  ← REFERENSI, dibangun penuh
  2. Discipline, 3. Inventory, 4. Tahfidz, 5. Hafalan Hadist,
  6. Bimbingan Konseling, 7. Pendidikan Karakter, 8. Pelaporan Ortu, 9. PWA
```

- Plugin infrastructure bekerja nyata: registry, aktivasi tenant, middleware, menu renderer, event hooks
- 8 plugin non-Kurikulum: scaffold saja (migrasi + registrasi)
- Coupling Evaluation↔Kurikulum dipecahkan: Evaluation core generic; Kurikulum plugin menyediakan framework TP/LM atau KI/KD bila aktif

## Struktur modular

```
app/Modules/<Domain>/
  Models/ Controllers/ Requests/ Policies/ routes.php
  Database/Migrations/  Resources/views/
```
`ModuleServiceProvider` autowire semua modul.

## Next step saat dokumen ini ditulis

- ✅ Bagian 1 (pendekatan arsitektur) selesai
- ✅ Bagian 2 (Tenancy + Auth + RBAC + Impersonation) selesai → lihat DEV_DOCS-002
- ✅ Bagian 3 (skema database) selesai → lihat DEV_DOCS-003
- ⏭️ Bagian 4 (plugin architecture) — berikutnya
- ⏳ Bagian 5 (core modules + ETL), Bagian 6 (folder structure + tech stack)
- ⏳ Tulis design doc final, minta user review, transition ke writing-plans skill
