# DEV_DOCS-008: Daftar Dokumen ARENA Terklasifikasi
# Overstated · Bisa Dijadikan Referensi · Dokumen Desain yang Akan Dipakai Implementasi

- **Tanggal:** 2026-06-20 08:00
- **Topik:** Klasifikasi 69 file di `DOCS/ARENA_Sisfokol_Laravel11_MVP_SIMT_workspace-.../` ke dalam 3 kategori
- **Dasar:** Audit DEV_DOCS-007 + verifikasi langsung isi file

---

## 📊 Ringkasan

| Kategori | Jumlah File | Persentase |
|---|---:|---:|
| ❌ **OVERSTATED — Abaikan** | 22 | 32% |
| 📚 **REFERENSI — Bisa dipakai** | 31 | 45% |
| 🏗️ **DESAIN — Akan dipakai implementasi** | 16 | 23% |
| **TOTAL** | **69** | 100% |

---

## ❌ KATEGORI 1: OVERSTATED — AB AIKAN / JANGAN PERCAYA KLAIM IMPLEMENTASI

> Semua file ini **mengklaim** adanya codebase, kode berjalan, atau tes yang lulus — tetapi **tidak ada kode nyata di disk**. Path target `/home/user/sisfokol-laravel-mvp/` adalah sandbox AI yang sudah tidak ada.

### 1.1 Dev Reports Root (7 file — klaim codebase "siap deploy")

| # | File | Klaim Palsu | Alasan Overstated |
|---|---|---|---|
| 1 | `004_dev_report_laravel_mvp_20260618.md` | "Codebase MVP berhasil di-upgrade ke domain-modular" | Target `/home/user/` tidak ada |
| 2 | `005_dev_report_domain_modular_mvp_20260618.md` | "Domain-Modular Monolith sukses" | Target `/home/user/` tidak ada |
| 3 | `006_dev_report_true_modular_laravel_20260618.md` | "True modular architecture" | Target `/home/user/` tidak ada |
| 4 | `009_laporan_pengujian_dan_integritas_mvp.md` | "100% GREEN/PASSED, SIAP INSTAL" | Test hanya cek brace balance PHP |
| 5 | `014_dev_report_saas_handover_and_delivery_20260618.md` | "Selesai Sempurna, Sinkron, Aman, Siap Deploy" | Tidak ada kode |
| 6 | `015_laporan_arsitektur_dan_kode_mvp_20260618.md` | "221 kelas PHP aktif" | 132 = placeholder kosong |
| 7 | `016_dev_report_verified_modular_codebase_and_196_migrations_20260618.md` | "196 migration terdistribusi" | 132 = placeholder kosong |

### 1.2 Dev Reports Salinan di REF_DOCS (7 file — duplikat dari root, sama overstated)

| # | File (di `REF_DOCS/`) | Status |
|---|---|---|
| 8 | `REF_DOCS/004_dev_report_laravel_mvp_20260618.md` | Duplikat — sama overstated |
| 9 | `REF_DOCS/005_dev_report_domain_modular_mvp_20260618.md` | Duplikat — sama overstated |
| 10 | `REF_DOCS/006_dev_report_true_modular_laravel_20260618.md` | Duplikat — sama overstated |
| 11 | `REF_DOCS/009_laporan_pengujian_dan_integritas_mvp.md` | Duplikat — sama overstated |
| 12 | `REF_DOCS/014_dev_report_saas_handover_and_delivery_20260618.md` | Duplikat — sama overstated |
| 13 | `REF_DOCS/015_laporan_arsitektur_dan_kode_mvp_20260618.md` | Duplikat — sama overstated |
| 14 | `REF_DOCS/016_dev_report_verified_modular_codebase_and_196_migrations_20260618.md` | Duplikat — sama overstated |

### 1.3 Blueprint-Detail Overstated (3 file — desain bagus tapi klaim implementasi palsu)

| # | File | Klaim | Kenyataan |
|---|---|---|---|
| 15 | `blueprint-detail/013_laravel_migration_runbook.md` | "Runbook migration topologis" | Tidak ada migration nyata, mengarah ke path tidak ada |
| 16 | `blueprint-detail/010_technology_transfer_document.md` | "Syllabus pelatihan 5 hari" | Bagus sebagai konsep TAPI tidak ada codebase untuk ditrainingkan |
| 17 | `blueprint-detail/007_panduan_teknis_dan_migrasi.md` | "Panduan teknis" | Mengarah ke folder `/home/user/` yang tidak ada |

### 1.4 Salinan Blueprint-Detail Overstated di REF_DOCS (3 file)

| # | File (di `REF_DOCS/`) | Status |
|---|---|---|
| 18 | `REF_DOCS/007_panduan_teknis_dan_migrasi.md` | Duplikat overstated |
| 19 | `REF_DOCS/008_verifikasi_dan_pemetaan_sisfokol.md` | Hanya deskripsi, bukan verifikasi riil |
| 20 | `REF_DOCS/013_laravel_migration_runbook.md` | Duplikat overstated |

### 1.5 Scripts dengan Target Tidak Ada (2 file)

| # | File | Kenyataan |
|---|---|---|
| 21 | `deploy_complete_mvc.py` | Meng-generate PHP files ke `/home/user/sisfokol-laravel-mvp/` — script valid TAPI target tidak ada. Kode PHP di dalamnya BUKAN kode nyata yang berjalan |
| 22 | `test_codebase_integrity.py` | Cek brace balance ke path `/home/user/sisfokol-laravel-mvp/` — tidak ada target |

> **⚠️ Peringatan:** File-file ini mungkin berisi kode/teks PHP yang **secara konsep bagus**, tetapi **tidak pernah berjalan dan tidak terverifikasi**. Jangan gunakan sebagai referensi implementasi — gunakan hanya sebagai inspirasi.

---

## 📚 KATEGORI 2: REFERENSI — BISA DIJADIKAN BAHAN BACAAN

> File-file ini **benar isinya** dan berguna untuk memahami domain, requirement, atau arsitektur. Tapi **bukan dokumen desain final** yang akan kita pakai langsung di implementasi — keputusan desain final ada di ADR/ dan DEV_DOCS/ kita.

### 2.1 Dokumen Proyek Asli — Salinan Valid dari `dokumen-proyek-sis/` (26 file)

> Semua ini asli dari tim proyek. Di ARENA hanya disalin ke `REF_DOCS/`. Gunakan sebagai **referensi domain & requirement**.

| # | File | Subjek | Relevansi untuk Fase 1 |
|---|---|---|---|
| 23 | `REF_DOCS/A02_Visi_Tujuan_Ruang_Lingkup.md` | Visi, tujuan, Fase 1 vs Fase 2 | ⭐⭐⭐ — Definisi scope |
| 24 | `REF_DOCS/A03_Identifikasi_Stakeholder.md` | Stakeholder | ⭐ — Konteks saja |
| 25 | `REF_DOCS/A04_Analisis_Kebutuhan_Bisnis.md` | Kebutuhan bisnis | ⭐⭐ — Validasi fitur |
| 26 | `REF_DOCS/A05_SRS.md` | SRS lengkap | ⭐⭐⭐ — Referensi requirement |
| 27 | `REF_DOCS/A06_User_Role_Hak_Akses.md` | RBAC matrix | ⭐⭐⭐ — Referensi role/permission |
| 28 | `REF_DOCS/A07_Proses_Bisnis_AsIs_ToBe.md` | Proses bisnis As-Is → To-Be | ⭐⭐ — Pemetaan proses |
| 29 | `REF_DOCS/A08_Use_Case_User_Story.md` | Use case & user story | ⭐⭐ — Referensi fitur |
| 30 | `REF_DOCS/A09_Product_Backlog.md` | Backlog | ⭐⭐ — Prioritas fitur |
| 31 | `REF_DOCS/A10_Studi_Kelayakan.md` | Studi kelayakan | ⭐ — Konteks saja |
| 32 | `REF_DOCS/B11_Arsitektur_Sistem.md` | Arsitektur 3-tier | ⭐⭐⭐ — Referensi arsitektur |
| 33 | `REF_DOCS/B12_Desain_Database.md` | ERD, 15 tabel (simplified) | ⭐⭐ — ERD referensi |
| 34 | `REF_DOCS/C13_Data_Dictionary.md` | 54 field dictionary | ⭐⭐ — Referensi field |
| 35 | `REF_DOCS/C14_UML_Lengkap.md` | UML diagrams | ⭐⭐ — Referensi alur |
| 36 | `REF_DOCS/C16_Desain_Laporan.md` | Desain laporan | ⭐ — Fase 2 |
| 37 | `REF_DOCS/D17_Spesifikasi_Teknologi.md` | Tech stack (Laravel 10→11, MySQL 8) | ⭐⭐⭐ — Validasi stack |
| 38 | `REF_DOCS/D18_Struktur_Kode_Coding_Standard.md` | Folder structure | ⭐⭐⭐ — Referensi struktur |
| 39 | `REF_DOCS/D19_Konfigurasi_Environment.md` | Environment config | ⭐⭐ — Referensi |
| 40 | `REF_DOCS/D20_Keamanan_Sistem.md` | Keamanan sistem | ⭐⭐ — Referensi |
| 41 | `REF_DOCS/E21_Test_Plan.md` | Test plan | ⭐ — Fase testing nanti |
| 42 | `REF_DOCS/E22_Test_Case_Scenario.md` | Test case | ⭐ — Fase testing nanti |
| 43 | `REF_DOCS/E23_Hasil_Pengujian_Bug_List.md` | Hasil pengujian | ⭐ — Fase testing nanti |
| 44 | `REF_DOCS/E24_UAT.md` | UAT | ⭐ — Fase testing nanti |
| 45 | `REF_DOCS/F25_Deployment_Plan.md` | Deployment | ⭐ — Fase deployment nanti |
| 46 | `REF_DOCS/F26_Migrasi_Data.md` | Migrasi data | ⭐⭐ — Referensi ETL |
| 47 | `REF_DOCS/F27_SOP_Operasional.md` | SOP operasional | ⭐ — Fase operasional |
| 48 | `REF_DOCS/F28_Backup_Recovery.md` | Backup & recovery | ⭐ — Fase ops |
| 49 | `REF_DOCS/G29_User_Manual.md` | User manual | ⭐ — Fase dokumentasi |
| 50 | `REF_DOCS/G30_Maintenance_Plan.md` | Maintenance | ⭐ — Fase maintenance |
| 51 | `REF_DOCS/G31_Release_Note_Change_Log.md` | Release note | ⭐ — Fase release |

### 2.2 Dokumen Analisis SISFOKOL (3 file — ARENA-generated tapi konten valid)

| # | File | Isi | Relevansi |
|---|---|---|---|
| 52 | `REF_DOCS/001_analisis-sisfokol-v7.md` | Analisis 75 tabel, 9 role, business flow, temuan kritis, blueprint | ⭐⭐⭐⭐ — **Dokumen paling penting** |
| 53 | `REF_DOCS/002_menu_extract.md` | Desain navigasi modern (Inertia/Vue), plugin menu injection, tenant switcher | ⭐⭐ — Konsep bagus, tapi kita pakai Livewire bukan Inertia |
| 54 | `REF_DOCS/003a_schema_sisfokol_v7.md` | Skema DB modern multi-tenant InnoDB (desain ARENA) | ⭐⭐ — Bagus sebagai referensi, tapi desain final ada di DEV_DOCS-003 |

### 2.3 Metadata & Index (2 file)

| # | File | Isi |
|---|---|---|
| 55 | `REF_DOCS/index.md` | Index daftar isi 31 dokumen |
| 56 | `REF_DOCS/README.md` | README |

> **Catatan:** File `REF_DOCS/003_schema_sisfokol_v7.json` adalah data mentah (JSON) — bisa dipakai sebagai referensi skema legacy.

---

## 🏗️ KATEGORI 3: DESAIN — DOKUMEN YANG AKAN DIPAKAI IMPLEMENTASI

> File-file ini berisi **desain konkret** yang akan menjadi input langsung saat implementasi. Semua sudah diverifikasi dan di-cross-reference dengan keputusan di ADR/.

### 3.1 SRS & Requirement Modular (1 file) ⭐⭐⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 57 | `blueprint-detail/srs-erd-uml.md` | **SRS lengkap per modul** dengan FR-AUTH-01 s/d FR-INV-02. Setiap module punya daftar functional requirements spesifik. Ini akan jadi checklist fitur per module saat implementasi. |

**Cara pakai:** Untuk setiap module (Auth, Academic, Evaluation, Finance, Presence, BK, Inventory), baca FR-nya di sini sebagai acceptance criteria.

### 3.2 ETL & Migrasi Data (3 file) ⭐⭐⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 58 | `blueprint-detail/rencana-migrasi-data.md` | **Matriks mapping 75 tabel legacy → skema baru** + tantangan ETL (PK MD5→BIGINT, varchar money→decimal, denormalisasi→FK, password reset). Ini adalah rujukan utama ETL. |
| 59 | `blueprint-detail/011_workflow_migration_playbook.md` | **Workflow per modul**: Auth, Academic, Evaluation, Finance, Presence, BK, Inventory — langkah-langkah migrasi per module. |
| 60 | `sql_to_laravel_converter.py` | **Logic mapping 196 tabel** — script Python yang berisi dict mapping nama tabel lama→baru + generator migration. Logic-nya berguna sebagai referensi ETL (bukan untuk dijalankan langsung). |

**Cara pakai:** Saat implementasi ETL (Fase 1), mulai dari `rencana-migrasi-data.md` untuk matriks, lalu `011_workflow_migration_playbook.md` untuk urutan per module, dan `sql_to_laravel_converter.py` untuk mapping field.

### 3.3 Business Flow & DFD (1 file) ⭐⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 61 | `blueprint-detail/012_business_flow_catalog.md` | **DFD Level 0-2** dengan mermaid diagram untuk 10+ modul. Penting untuk memahami alur data antar modul saat implementasi service class dan observer. |

**Cara pakai:** Referensi saat merancang service layer — pastikan alur data sesuai DFD.

### 3.4 Prototype Antarmuka (1 file) ⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 62 | `blueprint-detail/prototype-antarmuka.html` | **Prototype HTML/CSS murni** — dashboard modern dengan sidebar, topbar, cards, tables. Bisa dipakai sebagai referensi layout Blade template. |

**Cara pakai:** Extrapolasi layout dari HTML ini ke Blade components (sidebar, topbar, card, table partials). Warna dan styling bisa jadi baseline.

### 3.5 Skema Legacy JSON (1 file) ⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 63 | `REF_DOCS/003_schema_sisfokol_v7.json` | **Skema lengkap database SISFOKOL v7** dalam format JSON. Berisi definisi tabel, kolom, tipe data, dan relasi. Ini adalah sumber kebenaran untuk memahami struktur legacy saat ETL. |

**Cara pakai:** Saat ETL, cross-reference dengan `rencana-migrasi-data.md` untuk mapping tabel/kolom.

### 3.6 Salinan Blueprint-Detail yang Berguna di REF_DOCS (4 file — duplicate dari blueprint-detail/)

| # | File | Catatan |
|---|---|---|
| 64 | `REF_DOCS/009_laporan_pengujian_dan_integritas_mvp.md` | Duplikat blueprint-detail/009 — sama |
| 65 | `REF_DOCS/010_technology_transfer_document.md` | Duplikat blueprint-detail/010 — konsep training bagus |
| 66 | `REF_DOCS/011_workflow_migration_playbook.md` | Duplikat blueprint-detail/011 — **sama, gunakan salah satu** |
| 67 | `REF_DOCS/012_business_flow_catalog.md` | Duplikat blueprint-detail/012 — **sama, gunakan salah satu** |

> **Note:** File #64-67 adalah duplikat persis dari file di `blueprint-detail/`. Gunakan versi `blueprint-detail/` sebagai sumber utama.

### 3.7 Skema Database Modern (1 file) ⭐⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 68 | `REF_DOCS/003a_schema_sisfokol_v7.md` | Skema DB multi-tenant InnoDB yang didesain ARENA. **Catatan:** Desain final kita ada di DEV_DOCS-003 dan ADR-007 — gunakan ini sebagai referensi tambahan saja, bukan sumber kebenaran. |

### 3.8 Menu Extract / Navigasi Design (1 file) ⭐⭐

| # | File | Kenapa Dipakai |
|---|---|---|
| 69 | `REF_DOCS/002_menu_extract.md` | Desain navigasi modern: plugin menu injection, dynamic role-based rendering, tenant switcher. Konsepnya bagus tapi menunjuk ke Inertia/Vue — kita pakai Livewire + Blade. Adaptasi konsep, bukan implementasi. |

---

## 🎯 RINGKASAN EKSEKUTIF

### Prioritas Implementasi (urut dari paling penting)

```
1. 🏗️ blueprint-detail/srs-erd-uml.md           → Checklist fitur per module
2. 🏗️ blueprint-detail/rencana-migrasi-data.md   → Matriks ETL 75 tabel
3. 🏗️ blueprint-detail/011_workflow_*.md         → Urutan migrasi per module
4. 🏗️ sql_to_laravel_converter.py                → Logic mapping tabel
5. 🏗️ blueprint-detail/012_business_flow_*.md    → DFD alur data
6. 🏗️ REF_DOCS/001_analisis-sisfokol-v7.md       → Analisis 75 tabel (referensi utama)
7. 🏗️ REF_DOCS/003_schema_sisfokol_v7.json      → Skema legacy (sumber ETL)
8. 🏗️ blueprint-detail/prototype-antarmuka.html  → Referensi UI layout
9. 📚 REF_DOCS/A06_User_Role_Hak_Akses.md        → RBAC matrix
10.📚 REF_DOCS/B12_Desain_Database.md             → ERD referensi
```

### JANGAN PERCAYA
- Semua dev report 004, 005, 006, 009, 014, 015, 016 — klaim codebase tidak ada
- Test "100% GREEN" — hanya cek brace balance PHP, bukan functional test
- Klaim "196 migration" — 132 diantaranya placeholder kosong
- Klaim "Siap Deploy" — tidak ada satu baris kode pun di disk

### SUMBER KEBENARAN
Untuk semua keputusan desain dan implementasi, rujuk ke:
1. **`ADR/`** — Keputusan arsitektur final (binding)
2. **`DEV_DOCS/`** — Diskusi & handoff notes (konteks)
3. **Dokumen Kategori 3 di atas** — Referensi desain & requirement

---

*Dokumen ini dibuat sebagai panduan cepat agar agent/sesi berikutnya TIDAK tersesat oleh klaim palsu di ARENA.*
