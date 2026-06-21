# 📋 ANALISIS MENDALAM & PERENCANAAN REFACTOR
# SISFOKOL v7.00 (Code:SmartOffice) → LARAVEL 11

---

**Tanggal Analisis:** 17 Juni 2026  
**Versi Saat Ini:** SISFOKOL v7.12  
**Target:** Laravel 11 Framework  
**Analyst:** Profesor NLP/AI/Software Engineering  

---

## ═══════════════════════════════════════════════════════════════
## 📑 DAFTAR ISI
## ═══════════════════════════════════════════════════════════════

1. [Executive Summary](#1-executive-summary)
2. [Profil Sistem Existing (AS-IS)](#2-profil-sistem-existing-as-is)
3. [Arsitektur Existing & Technical Debt](#3-arsitektur-existing--technical-debt)
4. [Pemetaan Database (75 Tabel)](#4-pemetaan-database-75-tabel)
5. [Pemetaan Modul & Fitur Bisnis](#5-pemetaan-modul--fitur-bisnis)
6. [Pemetaan Role & Hak Akses (9 Role)](#6-pemetaan-role--hak-akses-9-role)
7. [Analisis Keamanan & Vulnerability](#7-analisis-keamanan--vulnerability)
8. [Gap Analysis: PHP Native vs Laravel 11](#8-gap-analysis-php-native-vs-laravel-11)
9. [Arsitektur Target Laravel 11 (TO-BE)](#9-arsitektur-target-laravel-11-to-be)
10. [Strategi Migrasi Database](#10-strategi-migrasi-database)
11. [Pemetaan Route (URL Mapping)](#11-pemetaan-route-url-mapping)
12. [Design Pattern & Best Practices](#12-design-pattern--best-practices)
13. [Rencana Fase Refactoring](#13-rencana-fase-refactoring)
14. [Estimasi Effort & Timeline](#14-estimasi-effort--timeline)
15. [Risk Assessment & Mitigasi](#15-risk-assessment--mitigasi)
16. [Rekomendasi Tech Stack](#16-rekomendasi-tech-stack)
17. [Kesimpulan & Next Steps](#17-kesimpulan--next-steps)

---

## ═══════════════════════════════════════════════════════════════
## 1. EXECUTIVE SUMMARY
## ═══════════════════════════════════════════════════════════════

### 1.1 Gambaran Umum

SISFOKOL v7.00 (Code:SmartOffice) adalah **Sistem Informasi Sekolah** berbasis PHP Native + MySQL yang dirancang untuk lingkungan sekolah di Indonesia. Sistem ini telah mengalami iterasi aktif (v7.05 - v7.12, Juli 2025 - Feb 2026) dan menangani kebutuhan operasional sekolah secara komprehensif.

### 1.2 Statistik Kunci Codebase

| Metrik | Nilai |
|--------|-------|
| Total File | 13.537 |
| File PHP (termasuk vendor) | 1.675 |
| File PHP Custom (non-vendor) | 442 |
| Total Baris Kode PHP Custom | ~225.227 |
| File JavaScript | 5.218 |
| File CSS | 1.077 |
| Template HTML | 15 |
| Tabel Database | 75 |
| Role Pengguna | 9 |
| Modul Bisnis Utama | 14 |
| Vendor Libraries PHP | ~1.233 files (DomPDF, FPDF, PhpSpreadsheet, QRCode) |

### 1.3 Penilaian Kompleksitas

| Aspek | Skor (1-10) | Keterangan |
|-------|:-----------:|------------|
| Kompleksitas Bisnis | 8/10 | Multi-role, multi-modul, kurikulum merdeka |
| Kompleksitas Teknis Legacy | 7/10 | Spaghetti code, duplikasi masif |
| Keamanan | 3/10 | SQL Injection prone, MD5 password, no CSRF |
| Maintainability | 2/10 | Duplikasi >60%, tight coupling |
| Scalability | 2/10 | No separation of concerns |
| Refactoring Difficulty | 7/10 | Massive code, business logic embedded in views |

### 1.4 Rekomendasi Utama

> **VERDICT: FULL REWRITE (bukan incremental refactor) menggunakan Laravel 11**
> 
> Alasan: Arsitektur existing tidak memiliki separation of concerns sama sekali. 
> Setiap file PHP mencampur business logic, database query, dan HTML rendering.
> Biaya "membungkus" kode legacy ke Laravel justru lebih besar dan berisiko 
> dibanding menulis ulang dengan arsitektur yang benar.

---

## ═══════════════════════════════════════════════════════════════
## 2. PROFIL SISTEM EXISTING (AS-IS)
## ═══════════════════════════════════════════════════════════════

### 2.1 Identitas Sistem

```
Nama        : SISFOKOL v7.00 (Code:SmartOffice)
Versi       : v7.12 (terakhir: 3 Feb 2026)
Bahasa      : PHP 8.2.4 (Native/Procedural)
Database    : MySQL/MariaDB 10.4.28
Web Server  : XAMPP
UI Framework: AdminLTE 3 + Bootstrap 4 + jQuery
PDF Engine  : DomPDF + FPDF
Excel       : PhpSpreadsheet
QR Code     : QRCode Library
Pembuat     : Agus Muhajir, S.Kom
```

### 2.2 Fitur Utama (Dari README)

1. ✅ Sistem Penilaian Mapel dan Raport  
2. ✅ Sistem Bimbingan Konseling/BK  
3. ✅ Sistem Presensi Kehadiran dengan QRCode  
4. ✅ Sistem Keuangan Siswa dan Tunggakan  
5. ✅ Sistem Inventaris/Sarana Prasarana  
6. ✅ Sistem Jadwal Pelajaran  
7. ✅ Sistem Jurnal Guru Mengajar  
8. ✅ Sistem Absensi/Ijin  
9. ✅ Sistem Guru Piket  
10. ✅ Sistem Filebox RPP Silabus  
11. ✅ Sistem Tabungan Siswa (v7.07+)  
12. ✅ Kurikulum Merdeka (Asesmen Formatif/Sumatif/Proyek)  
13. ✅ Sistem Pelanggaran & Pembinaan Siswa  
14. ✅ Sistem Prestasi Siswa  

### 2.3 Struktur Direktori Existing

```
sisfokol/
├── index.php                  # Entry point → redirect ke login.php
├── login.php                  # Login handler (990 baris, ALL ROLES)
├── logout.php                 # Logout handler
├── expire.php                 # Session expiry
│
├── adm/                       # 🔴 ADMIN Panel (135 PHP files)
│   ├── index.php              # Dashboard Admin (1502 lines)
│   ├── ab/                    # Absensi (7 files)
│   ├── akad/                  # Akademik - Mapel (3 files)
│   ├── ek/                    # Ekstra Kurikuler (4 files)
│   ├── h/                     # History Login/Entri (2 files)
│   ├── im/                    # Ijin/Meninggalkan (9 files)
│   ├── inv/                   # Inventaris (5 files)
│   ├── jw/                    # Jadwal (3 files)
│   ├── keu/                   # Keuangan (12 files)
│   ├── m/                     # Master Data (15+ files)
│   ├── nabung/                # Tabungan Siswa (6 files)
│   ├── nil/                   # Nilai & Raport (6 files)
│   ├── pb/                    # Pembinaan (8 files)
│   ├── pen/                   # Penilaian (6 files)
│   ├── ph/                    # Piket Handler (3 files)
│   ├── pl/                    # Pelanggaran (8 files)
│   ├── ps/                    # Presensi (14 files)
│   ├── pt/                    # Prestasi (8 files)
│   └── s/                     # Setting Password (1 file)
│
├── admgr/                     # 🟢 GURU MAPEL Panel (22 PHP files)
│   ├── index.php              # Dashboard Guru
│   ├── h/                     # History
│   ├── jwl/                   # Jadwal Mengajar
│   ├── kurmer/                # Kurikulum Merdeka
│   ├── pm/                    # Pembelajaran
│   ├── rs/                    # RPP/Silabus Upload
│   └── s/                     # Setting
│
├── admsw/                     # 🔵 SISWA Panel (23 PHP files)
│   ├── index.php              # Dashboard Siswa
│   ├── d/                     # Data (raport, pelanggaran, prestasi)
│   ├── h/                     # History
│   ├── k/                     # Cek jadwal
│   ├── keu/                   # Keuangan Siswa
│   ├── pen/                   # Penilaian
│   └── s/                     # Setting
│
├── admwk/                     # 🟡 WALI KELAS Panel (38 PHP files)
│   ├── index.php              # Dashboard Wali Kelas
│   ├── d/                     # Data siswa
│   ├── gm/                    # Guru Mapel view
│   ├── h/                     # History
│   ├── jw/                    # Jadwal
│   ├── keu/                   # Keuangan
│   ├── kurmer/                # Kurikulum Merdeka
│   ├── nil/                   # Nilai
│   ├── ps/                    # Presensi
│   └── s/                     # Setting
│
├── admks/                     # 🟠 KEPALA SEKOLAH Panel (62 PHP files)
│   └── (mirror dari adm/ untuk read-only)
│
├── admpiket/                  # 🟣 PIKET Panel (44 PHP files)
│   ├── ab/                    # Absensi
│   ├── im/                    # Ijin
│   ├── ph/                    # Catatan Piket
│   ├── pl/                    # Pelanggaran
│   └── ps/                    # Presensi
│
├── admbk/                     # 🟤 GURU BK Panel (57 PHP files)
│   ├── ab/, im/, m/, pb/, pl/, ps/, pt/
│
├── admbdh/                    # 💰 BENDAHARA Panel (24 PHP files)
│   ├── keu/                   # Keuangan
│   └── nabung/                # Tabungan
│
├── adminv/                    # 🏗️ SARPRAS Panel (10 PHP files)
│   └── inv/                   # Inventaris
│
├── inc/                       # ⚙️ INCLUDES (Core)
│   ├── config.php             # Konfigurasi DB & Site
│   ├── fungsi.php             # Helper Functions (2005 lines, 36 functions)
│   ├── koneksi.php            # Database Connection
│   ├── niltpl.php             # Template Value Parser
│   ├── niltpl2.php            # Template Value Parser (simplified)
│   ├── cek/                   # Session Checker per Role (10 files)
│   └── class/                 # Vendor Libraries
│       ├── dompdf/            # PDF Generator (529 files)
│       ├── fpdf/              # PDF Generator Alt (27 files)
│       ├── phpspreadsheet/    # Excel Handler (604 files)
│       ├── qrcode/            # QR Generator (73 files)
│       ├── paging.php         # Pagination Class
│       └── mysql_backup.php   # DB Backup
│
├── template/                  # 🎨 TEMPLATE / UI
│   ├── adminlte3/             # AdminLTE 3 Framework
│   ├── adm.html               # Template Admin (1294 lines)
│   ├── admks.html             # Template Kepsek (910 lines)
│   ├── admbk.html             # Template BK (694 lines)
│   ├── admpiket.html          # Template Piket (663 lines)
│   └── ... (15 HTML templates total)
│
├── filebox/                   # 📁 FILE UPLOADS
│   ├── album/, arsip/, artikel/, buletin/
│   ├── excel/, jurnal/, logo/, materi/
│   ├── pegawai/, siswa/, soal/, tugas/, video/
│
├── db/                        # 💾 DATABASE
│   ├── sisfokol_v7.sql        # Main DB (1.4MB, 75 tables)
│   ├── update_v7.06.sql       # Patch
│   └── update_v7.07.sql       # Patch (tabungan)
│
└── img/                       # 🖼️ Static Images
```

---

## ═══════════════════════════════════════════════════════════════
## 3. ARSITEKTUR EXISTING & TECHNICAL DEBT
## ═══════════════════════════════════════════════════════════════

### 3.1 Pola Arsitektur (Anti-Pattern)

```
┌────────────────────────────────────────────────────┐
│            CURRENT ARCHITECTURE (NO MVC)           │
│                                                    │
│  ┌──────────────────────────────────────────────┐  │
│  │              SINGLE PHP FILE                  │  │
│  │                                               │  │
│  │  session_start();                             │  │
│  │  require("config.php");                       │  │
│  │  require("fungsi.php");                       │  │
│  │  require("koneksi.php");   ← DB Connection    │  │
│  │  require("cek/role.php");  ← Auth Check       │  │
│  │                                               │  │
│  │  // BUSINESS LOGIC                            │  │
│  │  if ($_POST['btnOK']) {                       │  │
│  │      mysqli_query($koneksi, "INSERT...");     │  │
│  │  }                                            │  │
│  │                                               │  │
│  │  // VIEW (HTML mixed with PHP)                │  │
│  │  ob_start();                                  │  │
│  │  echo '<table>...';                           │  │
│  │  while($row = mysqli_fetch_assoc($q)) {       │  │
│  │      echo '<tr><td>'.$row['nama'].'</td></tr>';│  │
│  │  }                                            │  │
│  │  $isi = ob_get_contents();                    │  │
│  │  ob_end_clean();                              │  │
│  │                                               │  │
│  │  // TEMPLATE ENGINE (Custom)                  │  │
│  │  require("niltpl.php");                       │  │
│  └──────────────────────────────────────────────┘  │
│                                                    │
│  ZERO SEPARATION OF CONCERNS                       │
│  ZERO REUSABLE COMPONENTS                          │
│  ZERO AUTOMATED TESTING                            │
└────────────────────────────────────────────────────┘
```

### 3.2 Technical Debt Inventory

#### 🔴 CRITICAL DEBT

| # | Debt | Severity | Impact |
|---|------|:--------:|--------|
| 1 | **MASSIVE CODE DUPLICATION** | 🔴 Critical | Pelanggaran.php exists di 4 role (adm, admbk, admpiket, admsw) dengan perbedaan hanya 2 baris (require path). **~60-70% kode terduplikasi lintas role.** |
| 2 | **SQL Injection Vulnerable** | 🔴 Critical | Semua 413 file menggunakan string concatenation dalam `mysqli_query()`. Fungsi `cegah()` dan `nosql()` hanya regex-based, mudah di-bypass. |
| 3 | **MD5 Password Hashing** | 🔴 Critical | Semua password di-hash dengan MD5 (sudah broken/deprecated sejak 2004). |
| 4 | **No CSRF Protection** | 🔴 Critical | Tidak ada token CSRF di seluruh form. |
| 5 | **Hardcoded Credentials** | 🔴 Critical | DB credentials di `inc/config.php` tanpa `.env` |

#### 🟠 HIGH DEBT

| # | Debt | Severity | Impact |
|---|------|:--------:|--------|
| 6 | **No Input Validation** | 🟠 High | Validasi hanya `empty()` check |
| 7 | **Session Management Manual** | 🟠 High | Session handling tanpa regeneration, fixation protection |
| 8 | **No API Layer** | 🟠 High | Tidak bisa integrasi mobile/third-party |
| 9 | **Inline HTML in PHP** | 🟠 High | View logic bercampur dengan `echo` dan `ob_start()` |
| 10 | **Custom Template Engine** | 🟠 High | `LoadTpl()` dan `ParseVal()` sangat primitif (string replace `{var}`) |

#### 🟡 MEDIUM DEBT

| # | Debt | Severity | Impact |
|---|------|:--------:|--------|
| 11 | **No Composer/Package Manager** | 🟡 Medium | Vendor libraries manual |
| 12 | **File Upload tanpa Validasi** | 🟡 Medium | 43 file menghandle upload tanpa proper validation |
| 13 | **No Error Handling** | 🟡 Medium | Error di-suppress (`ini_set('display_errors', 0)`) |
| 14 | **No Logging** | 🟡 Medium | Hanya log ke DB, no proper logging |
| 15 | **Global Variables Everywhere** | 🟡 Medium | `$koneksi`, `$sumber`, `$today` dll semua global |

### 3.3 Analisis Duplikasi Kode

Berikut bukti duplikasi masif:

```
File yang IDENTIK (hanya beda 2 baris require):
───────────────────────────────────────────────
adm/pl/pelanggaran.php      ≈ admbk/pl/pelanggaran.php    (beda: cek/adm → cek/admbk)
adm/pl/pelanggaran_pdf.php  ≈ admbk/pl/pelanggaran_pdf.php
adm/nil/raport_pdf.php      ≈ admks/nil/raport_pdf.php    ≈ admsw/d/raport_pdf.php
adm/pb/pembinaan.php        ≈ admbk/pb/pembinaan.php
adm/im/ijin.php             ≈ admbk/im/ijin.php           ≈ admpiket/im/ijin.php

Presensi files: 12 variasi duplikasi
Laporan bulanan: 15+ variasi duplikasi
Dashboard index: 9 variasi (setiap role punya sendiri)
```

**Estimasi: ~60-70% kode adalah DUPLIKASI murni.**

---

## ═══════════════════════════════════════════════════════════════
## 4. PEMETAAN DATABASE (75 TABEL)
## ═══════════════════════════════════════════════════════════════

### 4.1 Klasifikasi Tabel Berdasarkan Domain

#### 🏢 MASTER DATA (17 tabel)

| # | Tabel | Deskripsi | Relasi Utama |
|---|-------|-----------|--------------|
| 1 | `adminx` | Admin users | Standalone |
| 2 | `a_profil` | Profil sekolah (lat/long) | Standalone |
| 3 | `m_pegawai` | Master pegawai/guru | FK ke banyak tabel |
| 4 | `m_siswa` | Master siswa | FK ke banyak tabel |
| 5 | `m_kelas` | Master kelas | FK ke jadwal, nilai |
| 6 | `m_mapel` | Master mata pelajaran | FK ke guru, nilai |
| 7 | `m_mapel_jns` | Jenis mata pelajaran | FK ke m_mapel |
| 8 | `m_mapel_deskripsi` | Deskripsi mapel raport | FK ke m_mapel |
| 9 | `m_tapel` | Tahun pelajaran | FK ke semua tabel akademik |
| 10 | `m_ruang` | Master ruangan | FK ke jadwal |
| 11 | `m_hari` | Master hari | FK ke jadwal |
| 12 | `m_jam` | Master jam pelajaran | FK ke jadwal |
| 13 | `m_waktu` | Waktu presensi | FK ke presensi |
| 14 | `m_waktu_jadwal` | Waktu jadwal | FK ke jadwal |
| 15 | `m_walikelas` | Wali kelas assignment | FK ke m_pegawai, m_kelas |
| 16 | `m_user` | Master user umum | Standalone |
| 17 | `m_ekstra` | Master ekstrakurikuler | FK ke siswa_ekstra |

#### 👤 ROLE-SPECIFIC TABLES (7 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 18 | `m_ks` | Kepala Sekolah (link ke m_pegawai) |
| 19 | `m_gurubk` | Guru BK (link ke m_pegawai) |
| 20 | `m_bendahara` | Bendahara (link ke m_pegawai) |
| 21 | `m_sarpras` | Sarpras (link ke m_pegawai) |
| 22 | `m_piket` | Petugas Piket (link ke m_pegawai) |
| 23 | `m_pembinaan` | Kategori pembinaan |
| 24 | `m_bk_point` | Point BK |

#### 📊 AKADEMIK & NILAI (19 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 25 | `jadwal` | Jadwal pelajaran |
| 26 | `siswa_mapel_absensi` | Absensi per mapel |
| 27 | `siswa_nilai_bln` | Nilai bulanan |
| 28 | `siswa_nilai_smt` | Nilai semester |
| 29 | `siswa_nilai_thn` | Nilai tahunan |
| 30 | `siswa_raport_catatan` | Catatan raport |
| 31 | `siswa_raport_kenaikan` | Status kenaikan |
| 32 | `siswa_raport_rangking` | Ranking |
| 33 | `siswa_raport_sikap` | Nilai sikap |
| 34 | `siswa_soal` | Bank soal |
| 35 | `siswa_soal_nilai` | Nilai soal |
| 36 | `siswa_tugas` | Tugas siswa |
| 37 | `siswa_saran` | Saran untuk siswa |

#### 📗 KURIKULUM MERDEKA (10 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 38 | `kurmer_asesmen_formatif` | Master asesmen formatif |
| 39 | `kurmer_mapel_lm` | Lingkup Materi (LM) |
| 40 | `kurmer_mapel_tp` | Tujuan Pembelajaran (TP) |
| 41 | `kurmer_nilai_asesmen_formatif` | Nilai asesmen formatif |
| 42 | `kurmer_nilai_asesmen_formatif_detail` | Detail formatif |
| 43 | `kurmer_nilai_asesmen_sumatif` | Nilai asesmen sumatif |
| 44 | `kurmer_nilai_asesmen_sumatif_detail` | Detail sumatif |
| 45 | `kurmer_nilai_proyek` | Nilai proyek |
| 46 | `kurmer_nilai_proyek_proses` | Proses penilaian proyek |
| 47 | `kurmer_proyek` | Master proyek |
| 48 | `kurmer_proyek_detail` | Detail proyek |

#### 💰 KEUANGAN (6 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 49 | `m_keu_siswa` | Konfigurasi keuangan siswa |
| 50 | `siswa_bayar` | Pembayaran siswa |
| 51 | `siswa_bayar_rincian` | Rincian pembayaran |
| 52 | `siswa_bayar_tagihan` | Tagihan siswa |
| 53 | `m_tabungan` | Setting tabungan |
| 54 | `wa_tagihan_siswa` | WA tagihan |

#### 🏗️ INVENTARIS (8 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 55 | `inv_kib_a` | KIB A - Tanah |
| 56 | `inv_kib_b` | KIB B - Peralatan/Mesin |
| 57 | `inv_kib_c` | KIB C - Gedung/Bangunan |
| 58 | `inv_kib_d` | KIB D - Jalan/Irigasi |
| 59 | `inv_kib_e` | KIB E - Aset Tetap Lainnya |
| 60 | `inv_kib_f` | KIB F - Konstruksi |
| 61 | `m_kib_jenis` | Jenis KIB |
| 62 | `m_kib_kode` | Kode KIB |

#### 👮 BK & KEDISIPLINAN (5 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 63 | `m_bk_point_jenis` | Jenis point BK |
| 64 | `m_bk_prestasi` | Prestasi siswa (BK) |
| 65 | `siswa_pelanggaran` | Pelanggaran siswa |
| 66 | `siswa_prestasi` | Prestasi siswa |
| 67 | `siswa_ekstra` | Ekstra kurikuler siswa |

#### ⏰ PRESENSI & ABSENSI (5 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 68 | `user_presensi` | Presensi hadir |
| 69 | `user_absensi` | Absensi/ketidakhadiran |
| 70 | `user_ijin` | Ijin meninggalkan |
| 71 | `user_piket` | Piket petugas |
| 72 | `rev_guru_absensi` | Revisi absensi guru |

#### 📋 LOG & AUDIT (4 tabel)

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 73 | `user_log_login` | Log login |
| 74 | `user_log_entri` | Log entri/aktivitas |
| 75 | `rev_guru_agenda` | Agenda guru |
| — | `user_filebox` | File box uploads |

### 4.2 Masalah Database Design

```
CRITICAL ISSUES:
━━━━━━━━━━━━━━━
1. ❌ SEMUA PK menggunakan VARCHAR(50) berisi MD5 hash → Tidak efisien
2. ❌ TIDAK ADA Foreign Key constraints → Integritas data tidak terjamin  
3. ❌ Engine MyISAM (bukan InnoDB) → Tidak support transaction
4. ❌ Data denormalisasi berlebihan → nama siswa diulang di banyak tabel
5. ❌ Tidak ada index selain PK → Query performance buruk
6. ❌ Kolom harga/nominal menggunakan VARCHAR → Kalkulasi bermasalah
7. ❌ Tidak ada soft-delete → Data langsung dihapus permanen
8. ❌ Tidak ada timestamps (created_at, updated_at) standar
9. ❌ Charset campuran (latin1 + utf8mb4)
```

---

## ═══════════════════════════════════════════════════════════════
## 5. PEMETAAN MODUL & FITUR BISNIS
## ═══════════════════════════════════════════════════════════════

### 5.1 Module Map

```
┌──────────────────────────────────────────────────────────────────┐
│                     SISFOKOL BUSINESS MODULES                     │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │   📋 MASTER     │  │   📊 AKADEMIK   │  │   📗 KURMER     │  │
│  │   DATA          │  │   & NILAI       │  │   (Kur.Merdeka) │  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ • Tahun Ajar    │  │ • Mapel/Jadwal  │  │ • Asesmen       │  │
│  │ • Kelas         │  │ • Nilai Bln     │  │   Formatif      │  │
│  │ • Ruang         │  │ • Nilai Smt     │  │ • Asesmen       │  │
│  │ • Pegawai       │  │ • Nilai Thn     │  │   Sumatif       │  │
│  │ • Siswa         │  │ • Raport        │  │ • Proyek P5     │  │
│  │ • Guru Mapel    │  │ • Kenaikan      │  │ • Lingkup       │  │
│  │ • Wali Kelas    │  │ • Ranking       │  │   Materi (LM)   │  │
│  │ • Petugas       │  │ • Sikap         │  │ • Tujuan        │  │
│  │ • Import/Export │  │ • Catatan       │  │   Pembelajaran  │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │   ⏰ PRESENSI   │  │   📝 ABSENSI    │  │   🎒 BK &      │  │
│  │   & KEHADIRAN   │  │   & IJIN        │  │   KEDISIPLINAN  │  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ • Scan QRCode   │  │ • Entri Absensi │  │ • Pelanggaran   │  │
│  │ • Manual Entry  │  │ • Surat Ijin    │  │ • Pembinaan     │  │
│  │ • Pulang        │  │ • Cetak PDF     │  │ • Prestasi      │  │
│  │ • Telat         │  │ • QRCode Ijin   │  │ • Point BK      │  │
│  │ • Lap Harian    │  │ • Lap per Guru  │  │ • Cetak PDF     │  │
│  │ • Lap Bulanan   │  │ • Lap per Siswa │  │ • Laporan       │  │
│  │ • Lap Tahunan   │  │ • Lap per Bulan │  │                 │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │   💰 KEUANGAN   │  │   🏗️ INVENTARIS │  │   📁 FILEBOX    │  │
│  │   SISWA         │  │   (SARPRAS)     │  │   & PIKET       │  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ • Item Bayar    │  │ • KIB A-F       │  │ • RPP/Silabus   │  │
│  │ • Tagihan       │  │ • Barang        │  │ • Catatan Piket │  │
│  │ • Pembayaran    │  │ • Rekapitulasi  │  │ • Jurnal Guru   │  │
│  │ • Nota Cetak    │  │ • Import/Export │  │ • Materi Ajar   │  │
│  │ • Tunggakan     │  │                 │  │ • Soal/Tugas    │  │
│  │ • WA Notifikasi │  │                 │  │                 │  │
│  │ • Tabungan      │  │                 │  │                 │  │
│  │ • Lunas         │  │                 │  │                 │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                   │
│  ┌─────────────────┐  ┌─────────────────┐                       │
│  │   📊 DASHBOARD  │  │   🔐 AUTH &     │                       │
│  │   & LAPORAN     │  │   USER MGMT     │                       │
│  ├─────────────────┤  ├─────────────────┤                       │
│  │ • Chart 14 Hari │  │ • Multi-Role    │                       │
│  │ • Statistik     │  │   Login         │                       │
│  │ • Log Login     │  │ • Session Mgmt  │                       │
│  │ • Log Entri     │  │ • Password      │                       │
│  │ • Notifikasi    │  │ • Log Audit     │                       │
│  └─────────────────┘  └─────────────────┘                       │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘
```

---

## ═══════════════════════════════════════════════════════════════
## 6. PEMETAAN ROLE & HAK AKSES (9 ROLE)
## ═══════════════════════════════════════════════════════════════

### 6.1 Role Matrix

| # | Role | Kode | Folder | Login Tipe | Auth Table | Jumlah Fitur |
|---|------|------|--------|:----------:|------------|:------------:|
| 1 | **Administrator** | tp06 | `adm/` | adminx | `adminx` | FULL (135 files) |
| 2 | **Kepala Sekolah** | tp04 | `admks/` | m_ks + m_pegawai | `m_ks` | VIEW ALL (62 files) |
| 3 | **Guru Mapel** | tp01 | `admgr/` | m_mapel + m_pegawai | `m_mapel` | 22 files |
| 4 | **Wali Kelas** | tp03 | `admwk/` | m_walikelas + m_pegawai | `m_walikelas` | 38 files |
| 5 | **Guru BK** | tp011 | `admbk/` | m_gurubk + m_pegawai | `m_gurubk` | 57 files |
| 6 | **Siswa** | tp02 | `admsw/` | m_siswa | `m_siswa` | 23 files |
| 7 | **Petugas Piket** | tp033 | `admpiket/` | m_piket + m_pegawai | `m_piket` | 44 files |
| 8 | **Bendahara** | tp042 | `admbdh/` | m_bendahara + m_pegawai | `m_bendahara` | 24 files |
| 9 | **Sarpras** | tp041 | `adminv/` | m_sarpras + m_pegawai | `m_sarpras` | 10 files |

### 6.2 Feature Access Matrix (CRUD)

```
Fitur              │ ADM  │ KS   │ GURU │ WK   │ BK   │ SW   │ PIKET│ BDH  │ SARPRAS
───────────────────┼──────┼──────┼──────┼──────┼──────┼──────┼──────┼──────┼────────
Master Pegawai     │ CRUD │ R    │  -   │  -   │  -   │  -   │  -   │  -   │  -
Master Siswa       │ CRUD │ R    │  -   │ R    │ R    │  -   │  -   │  -   │  -
Master Kelas       │ CRUD │ R    │  -   │  -   │  -   │  -   │  -   │  -   │  -
Jadwal             │ CRUD │ R    │ R    │ R    │  -   │ R    │  -   │  -   │  -
Nilai Mapel        │ CRUD │ R    │ CRU  │ R    │  -   │ R    │  -   │  -   │  -
Raport             │ CRUD │ R    │  -   │ CRUD │  -   │ R    │  -   │  -   │  -
Presensi           │ CRUD │ R    │ R    │ R    │ R    │ R    │ CRUD │  -   │  -
Absensi            │ CRUD │ R    │  -   │ R    │ CRUD │  -   │ CRUD │  -   │  -
Ijin               │ CRUD │ R    │  -   │  -   │ CRUD │  -   │ CRUD │  -   │  -
Pelanggaran        │ CRUD │ R    │  -   │  -   │ CRUD │ R    │ CRUD │  -   │  -
Pembinaan          │ CRUD │ R    │  -   │  -   │ CRUD │ R    │  -   │  -   │  -
Prestasi           │ CRUD │ R    │  -   │  -   │ CRUD │ R    │  -   │  -   │  -
Keuangan Siswa     │ CRUD │ R    │  -   │ R    │  -   │ R    │  -   │ CRUD │  -
Tabungan           │ CRUD │  -   │  -   │ R    │  -   │  -   │  -   │ CRUD │  -
Inventaris/KIB     │ CRUD │ R    │  -   │  -   │  -   │  -   │  -   │  -   │ CRUD
Piket              │ CRUD │ R    │  -   │  -   │  -   │  -   │ CRUD │  -   │  -
Ekstra             │ CRUD │ R    │  -   │  -   │  -   │ R    │  -   │  -   │  -
Kurmer Formatif    │ CRUD │ R    │ CRUD │ R    │  -   │  -   │  -   │  -   │  -
Kurmer Sumatif     │ CRUD │ R    │ CRUD │ R    │  -   │  -   │  -   │  -   │  -
Kurmer Proyek      │ CRUD │ R    │  -   │ CRUD │  -   │  -   │  -   │  -   │  -
RPP/Silabus Upload │ R    │ R    │ CRUD │  -   │  -   │  -   │  -   │  -   │  -
Ganti Password     │ CU   │ CU   │ CU   │ CU   │ CU   │ CU   │ CU   │ CU   │ CU
```

---

## ═══════════════════════════════════════════════════════════════
## 7. ANALISIS KEAMANAN & VULNERABILITY
## ═══════════════════════════════════════════════════════════════

### 7.1 Vulnerability Assessment

| # | Vulnerability | Severity | File/Location | Detail |
|---|--------------|:--------:|---------------|--------|
| 1 | **SQL Injection** | 🔴 CRITICAL | 413 files | Semua query menggunakan string concatenation. Fungsi `cegah()` mudah di-bypass. |
| 2 | **Weak Password Hashing** | 🔴 CRITICAL | login.php, semua pass.php | `md5()` digunakan. Rainbow table attack trivial. |
| 3 | **No CSRF Token** | 🔴 CRITICAL | Semua form | Tidak ada proteksi CSRF sama sekali. |
| 4 | **Session Fixation** | 🟠 HIGH | login.php | `session_start()` tanpa `session_regenerate_id()` |
| 5 | **XSS (Cross-Site Scripting)** | 🟠 HIGH | Semua output | Output tanpa proper escaping, `htmlspecialchars()` inconsistent |
| 6 | **Insecure File Upload** | 🟠 HIGH | 43 files | Tidak ada MIME type validation, tidak ada size limit proper |
| 7 | **Information Disclosure** | 🟡 MEDIUM | config.php | Error suppressed tapi credentials hardcoded |
| 8 | **Path Traversal** | 🟡 MEDIUM | File include | Relative path includes (`../../inc/`) |
| 9 | **No Rate Limiting** | 🟡 MEDIUM | login.php | Brute force attack possible |
| 10 | **Cleartext HTTP** | 🟡 MEDIUM | config.php | `$sumber = "http://localhost/..."` |

### 7.2 Analisis Fungsi Keamanan Custom

```php
// MASALAH: Fungsi cegah() TIDAK AMAN
function cegah($str) {
    $str = trim(htmlentities(htmlspecialchars($str)));
    // Hanya regex replace karakter tertentu → MUDAH DI-BYPASS
    // Tidak menggunakan prepared statements
    // Contoh bypass: Unicode encoding, double encoding
}

// MASALAH: nosql() TIDAK AMAN  
function nosql($str) {
    // Sama - regex based, mudah di-bypass
    // Seharusnya menggunakan PDO Prepared Statements
}

// MASALAH: Password MD5
$password = md5(cegah($_POST["passwordx"]));
// MD5 sudah BROKEN sejak 2004
// Seharusnya: password_hash() + password_verify() (bcrypt/argon2)
```

---

## ═══════════════════════════════════════════════════════════════
## 8. GAP ANALYSIS: PHP NATIVE vs LARAVEL 11
## ═══════════════════════════════════════════════════════════════

| Aspek | PHP Native (SISFOKOL) | Laravel 11 Target |
|-------|----------------------|-------------------|
| **Architecture** | No pattern (spaghetti) | MVC + Service Layer + Repository |
| **Routing** | File-based (direct URL) | Route files (web.php, api.php) |
| **Database** | Raw `mysqli_query()` | Eloquent ORM + Query Builder |
| **Migration** | Manual SQL | Laravel Migrations + Seeders |
| **Auth** | Custom session check per file | Laravel Breeze/Fortify + Spatie Permission |
| **Validation** | Manual `empty()` check | Form Request Validation |
| **Template** | Custom `LoadTpl()` + `ParseVal()` | Blade Template Engine |
| **CSRF** | ❌ None | ✅ `@csrf` directive (automatic) |
| **Password** | MD5 | Bcrypt/Argon2 (`Hash::make()`) |
| **SQL Injection** | ❌ String concat | ✅ Prepared Statements (automatic) |
| **File Upload** | Manual `move_uploaded_file()` | Laravel Storage + File Validation |
| **PDF** | DomPDF/FPDF manual | Laravel DomPDF / Snappy |
| **Excel** | PhpSpreadsheet manual | Laravel Excel (Maatwebsite) |
| **QR Code** | Custom library | Simple QR Code package |
| **Testing** | ❌ None | PHPUnit + Pest |
| **API** | ❌ None | RESTful API + Sanctum |
| **Caching** | ❌ None | Redis/File Cache |
| **Queue** | ❌ None | Laravel Queue (WA notifications) |
| **Logging** | DB insert only | Monolog + Laravel Log |
| **Error Handling** | Suppressed | Exception Handler + Custom Pages |
| **Package Manager** | ❌ Manual vendor | ✅ Composer |
| **Frontend Build** | ❌ None | Vite |
| **Environment** | Hardcoded config.php | `.env` file |

---

## ═══════════════════════════════════════════════════════════════
## 9. ARSITEKTUR TARGET LARAVEL 11 (TO-BE)
## ═══════════════════════════════════════════════════════════════

### 9.1 High-Level Architecture

```
┌───────────────────────────────────────────────────────────────────┐
│                    LARAVEL 11 ARCHITECTURE                        │
│                                                                   │
│  ┌─────────┐    ┌──────────┐    ┌──────────┐    ┌─────────────┐ │
│  │ Browser │───▶│  Routes  │───▶│Middleware │───▶│ Controllers │ │
│  │ /Mobile │    │ web.php  │    │ Auth      │    │             │ │
│  └─────────┘    │ api.php  │    │ Role      │    └──────┬──────┘ │
│                 └──────────┘    │ CSRF      │           │        │
│                                 └──────────┘           ▼        │
│                                              ┌──────────────┐   │
│                                              │   Services   │   │
│                                              │   (Business  │   │
│  ┌─────────────┐    ┌──────────────┐        │    Logic)    │   │
│  │   Blade     │◀───│  View        │        └──────┬───────┘   │
│  │  Templates  │    │  Composers   │               │           │
│  └─────────────┘    └──────────────┘               ▼           │
│                                              ┌──────────────┐   │
│  ┌─────────────┐                             │ Repositories │   │
│  │   Vite      │                             │ (Data Access)│   │
│  │ AdminLTE 4  │                             └──────┬───────┘   │
│  │ + Tailwind  │                                    │           │
│  └─────────────┘                                    ▼           │
│                                              ┌──────────────┐   │
│                                              │   Eloquent   │   │
│                                              │   Models     │   │
│                                              └──────┬───────┘   │
│                                                     │           │
│                                              ┌──────▼───────┐   │
│                                              │   MySQL /    │   │
│                                              │   MariaDB    │   │
│                                              └──────────────┘   │
└───────────────────────────────────────────────────────────────────┘
```

### 9.2 Struktur Direktori Laravel 11

```
sisfokol-laravel/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── SisfokolMigrateData.php          # Data migration command
│   │       └── SendTunggakanNotification.php     # WA notification scheduler
│   │
│   ├── Enums/
│   │   ├── UserRole.php                          # Admin, Guru, Siswa, etc.
│   │   ├── PresensiStatus.php                    # Hadir, Sakit, Ijin, Alpha
│   │   ├── PembayaranStatus.php                  # Lunas, Belum, Sebagian
│   │   └── Semester.php                          # Ganjil, Genap
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   └── PasswordController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── MasterData/
│   │   │   │   │   ├── PegawaiController.php
│   │   │   │   │   ├── SiswaController.php
│   │   │   │   │   ├── KelasController.php
│   │   │   │   │   ├── MapelController.php
│   │   │   │   │   ├── TapelController.php
│   │   │   │   │   ├── RuangController.php
│   │   │   │   │   └── EkstraController.php
│   │   │   │   ├── Akademik/
│   │   │   │   │   ├── JadwalController.php
│   │   │   │   │   ├── NilaiController.php
│   │   │   │   │   └── RaportController.php
│   │   │   │   ├── Presensi/
│   │   │   │   │   ├── PresensiController.php
│   │   │   │   │   ├── AbsensiController.php
│   │   │   │   │   └── IjinController.php
│   │   │   │   ├── Keuangan/
│   │   │   │   │   ├── PembayaranController.php
│   │   │   │   │   ├── TagihanController.php
│   │   │   │   │   └── TabunganController.php
│   │   │   │   ├── BK/
│   │   │   │   │   ├── PelanggaranController.php
│   │   │   │   │   ├── PembinaanController.php
│   │   │   │   │   └── PrestasiController.php
│   │   │   │   ├── Inventaris/
│   │   │   │   │   └── KibController.php
│   │   │   │   ├── KurikulumMerdeka/
│   │   │   │   │   ├── AsesmenFormatifController.php
│   │   │   │   │   ├── AsesmenSumatifController.php
│   │   │   │   │   └── ProyekController.php
│   │   │   │   └── Laporan/
│   │   │   │       ├── LaporanPresensiController.php
│   │   │   │       ├── LaporanKeuanganController.php
│   │   │   │       └── LaporanAkademikController.php
│   │   │   │
│   │   │   ├── Guru/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── JurnalMengajarController.php
│   │   │   │   ├── NilaiController.php
│   │   │   │   ├── KurmerController.php
│   │   │   │   └── RppSilabusController.php
│   │   │   │
│   │   │   ├── WaliKelas/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── RaportController.php
│   │   │   │   ├── KurmerProyekController.php
│   │   │   │   └── SiswaController.php
│   │   │   │
│   │   │   ├── Siswa/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── RaportController.php
│   │   │   │   └── KeuanganController.php
│   │   │   │
│   │   │   ├── KepalaSekolah/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── LaporanController.php
│   │   │   │
│   │   │   ├── Piket/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PresensiController.php
│   │   │   │   └── PelanggaranController.php
│   │   │   │
│   │   │   ├── GuruBK/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── KonselingController.php
│   │   │   │
│   │   │   ├── Bendahara/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PembayaranController.php
│   │   │   │   └── TabunganController.php
│   │   │   │
│   │   │   └── Sarpras/
│   │   │       ├── DashboardController.php
│   │   │       └── InventarisController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── CheckActiveSession.php
│   │   │
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   └── LoginRequest.php
│   │       ├── MasterData/
│   │       │   ├── StorePegawaiRequest.php
│   │       │   ├── StoreSiswaRequest.php
│   │       │   └── StoreKelasRequest.php
│   │       ├── Akademik/
│   │       │   ├── StoreNilaiRequest.php
│   │       │   └── StoreJadwalRequest.php
│   │       ├── Keuangan/
│   │       │   ├── StorePembayaranRequest.php
│   │       │   └── StoreTabunganRequest.php
│   │       └── BK/
│   │           ├── StorePelanggaranRequest.php
│   │           └── StorePembinaanRequest.php
│   │
│   ├── Models/
│   │   ├── User.php                              # Unified user model
│   │   ├── Pegawai.php
│   │   ├── Siswa.php
│   │   ├── Kelas.php
│   │   ├── Mapel.php
│   │   ├── MapelDeskripsi.php
│   │   ├── MapelJenis.php
│   │   ├── TahunPelajaran.php
│   │   ├── Ruang.php
│   │   ├── WaliKelas.php
│   │   ├── Jadwal.php
│   │   ├── Presensi.php
│   │   ├── Absensi.php
│   │   ├── Ijin.php
│   │   ├── NilaiBulanan.php
│   │   ├── NilaiSemester.php
│   │   ├── NilaiTahunan.php
│   │   ├── RaportCatatan.php
│   │   ├── RaportKenaikan.php
│   │   ├── RaportSikap.php
│   │   ├── Pelanggaran.php
│   │   ├── Pembinaan.php
│   │   ├── Prestasi.php
│   │   ├── Pembayaran.php
│   │   ├── PembayaranRincian.php
│   │   ├── Tagihan.php
│   │   ├── Tabungan.php
│   │   ├── Ekstra.php
│   │   ├── SiswaEkstra.php
│   │   ├── Piket.php
│   │   ├── KibA.php ~ KibF.php                   # Inventaris
│   │   ├── KurmerAsesmenFormatif.php
│   │   ├── KurmerAsesmenSumatif.php
│   │   ├── KurmerProyek.php
│   │   ├── KurmerNilaiFormatif.php
│   │   ├── KurmerNilaiSumatif.php
│   │   ├── KurmerNilaiProyek.php
│   │   ├── LogLogin.php
│   │   ├── LogEntri.php
│   │   └── FileBox.php
│   │
│   ├── Services/
│   │   ├── PresensiService.php
│   │   ├── NilaiService.php
│   │   ├── RaportService.php
│   │   ├── KeuanganService.php
│   │   ├── TabunganService.php
│   │   ├── BKService.php
│   │   ├── InventarisService.php
│   │   ├── JadwalService.php
│   │   ├── KurmerService.php
│   │   ├── QrCodeService.php
│   │   ├── WhatsAppService.php
│   │   ├── ImportExportService.php
│   │   └── ReportService.php
│   │
│   ├── Repositories/
│   │   ├── PegawaiRepository.php
│   │   ├── SiswaRepository.php
│   │   ├── NilaiRepository.php
│   │   ├── PresensiRepository.php
│   │   └── KeuanganRepository.php
│   │
│   ├── Exports/
│   │   ├── PegawaiExport.php
│   │   ├── SiswaExport.php
│   │   ├── PresensiExport.php
│   │   ├── NilaiExport.php
│   │   └── InventarisExport.php
│   │
│   ├── Imports/
│   │   ├── PegawaiImport.php
│   │   ├── SiswaImport.php
│   │   └── InventarisImport.php
│   │
│   ├── Notifications/
│   │   ├── TagihanNotification.php
│   │   └── PresensiNotification.php
│   │
│   └── Policies/
│       ├── SiswaPolicy.php
│       ├── NilaiPolicy.php
│       ├── KeuanganPolicy.php
│       └── InventarisPolicy.php
│
├── database/
│   ├── migrations/
│   │   ├── 0001_create_users_table.php
│   │   ├── 0002_create_pegawai_table.php
│   │   ├── 0003_create_siswa_table.php
│   │   ├── 0004_create_kelas_table.php
│   │   ├── ... (estimated 40-45 migration files)
│   │   └── 0045_create_log_tables.php
│   │
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── RolePermissionSeeder.php
│   │   ├── AdminSeeder.php
│   │   └── DemoDataSeeder.php
│   │
│   └── factories/
│       ├── PegawaiFactory.php
│       ├── SiswaFactory.php
│       └── ...
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php                     # Main layout (AdminLTE)
│   │   │   ├── guest.blade.php                   # Login layout
│   │   │   └── pdf.blade.php                     # PDF layout
│   │   ├── components/
│   │   │   ├── sidebar/
│   │   │   │   ├── admin.blade.php
│   │   │   │   ├── guru.blade.php
│   │   │   │   ├── siswa.blade.php
│   │   │   │   └── ... (per role)
│   │   │   ├── data-table.blade.php
│   │   │   ├── form-group.blade.php
│   │   │   ├── notification-badge.blade.php
│   │   │   └── chart-card.blade.php
│   │   ├── auth/
│   │   │   └── login.blade.php
│   │   ├── admin/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── master/
│   │   │   ├── akademik/
│   │   │   ├── presensi/
│   │   │   ├── keuangan/
│   │   │   ├── bk/
│   │   │   ├── inventaris/
│   │   │   └── laporan/
│   │   ├── guru/
│   │   ├── wali-kelas/
│   │   ├── siswa/
│   │   ├── kepala-sekolah/
│   │   ├── piket/
│   │   ├── guru-bk/
│   │   ├── bendahara/
│   │   ├── sarpras/
│   │   └── pdf/
│   │       ├── raport.blade.php
│   │       ├── nota-pembayaran.blade.php
│   │       ├── surat-ijin.blade.php
│   │       └── laporan-presensi.blade.php
│   │
│   ├── css/
│   └── js/
│
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── api.php
│
├── config/
│   └── sisfokol.php                              # Custom config
│
├── storage/
│   └── app/
│       └── public/
│           ├── pegawai/                           # Foto pegawai
│           ├── siswa/                             # Foto siswa
│           ├── qrcode/                            # Generated QR
│           ├── dokumen/                           # RPP, Silabus, dll
│           └── materi/                            # Materi ajar
│
├── tests/
│   ├── Feature/
│   │   ├── Auth/LoginTest.php
│   │   ├── Admin/PegawaiTest.php
│   │   ├── PresensiTest.php
│   │   └── KeuanganTest.php
│   └── Unit/
│       ├── NilaiServiceTest.php
│       └── KeuanganServiceTest.php
│
├── .env
├── composer.json
├── package.json
└── vite.config.js
```

### 9.3 Key Design Decisions

#### A. Unified User Model (KRUSIAL)

```
EXISTING (9 tabel auth terpisah):          TARGET (1 tabel users + roles):
adminx                                     users
m_pegawai (guru, BK, wali, dll)   ───▶     ├── id
m_siswa                                    ├── username
m_ks                                       ├── password (bcrypt)
m_gurubk                                   ├── role (enum/spatie)
m_bendahara                                ├── userable_type (polymorphic)
m_sarpras                                  ├── userable_id
m_piket                                    ├── email
m_walikelas                                ├── is_active
                                           └── timestamps
                                           
                                           + Polymorphic relation ke:
                                             - pegawai (guru, BK, wali, dsb)
                                             - siswa
```

#### B. Spatie Laravel Permission

```php
// Roles
'admin', 'kepala-sekolah', 'guru-mapel', 'wali-kelas',
'guru-bk', 'siswa', 'piket', 'bendahara', 'sarpras'

// Permissions (contoh)
'master-data.manage', 'siswa.view', 'siswa.create', 'siswa.edit', 'siswa.delete',
'nilai.manage', 'raport.manage', 'raport.view',
'presensi.manage', 'presensi.view', 
'keuangan.manage', 'keuangan.view',
'inventaris.manage', 'inventaris.view',
'bk.manage', 'bk.view',
'kurmer.manage', 'kurmer.view',
'laporan.view', 'laporan.export'
```

---

## ═══════════════════════════════════════════════════════════════
## 10. STRATEGI MIGRASI DATABASE
## ═══════════════════════════════════════════════════════════════

### 10.1 Prinsip Migrasi

1. **InnoDB** untuk semua tabel (support FK & transactions)
2. **Auto-increment ID** (bukan MD5 hash varchar) + UUID optional
3. **Foreign Key constraints** proper
4. **Timestamps** (`created_at`, `updated_at`, `deleted_at`)
5. **Soft Deletes** untuk data penting
6. **Proper data types** (decimal untuk uang, integer untuk angka)
7. **Charset UTF-8mb4** konsisten

### 10.2 Mapping Tabel Lama → Baru

```
LEGACY TABLE              │ LARAVEL TABLE             │ PERUBAHAN KUNCI
──────────────────────────┼───────────────────────────┼──────────────────────────
adminx                    │ users (role=admin)        │ Merge ke unified users
m_pegawai                 │ pegawai + users           │ Split auth ke users
m_siswa                   │ siswa + users             │ Split auth ke users
m_ks                      │ role_assignments          │ Role via Spatie
m_gurubk                  │ role_assignments          │ Role via Spatie
m_bendahara               │ role_assignments          │ Role via Spatie
m_sarpras                 │ role_assignments          │ Role via Spatie
m_piket                   │ piket_assignments         │ Tetap tabel sendiri (ada jadwal)
m_walikelas               │ wali_kelas_assignments    │ Tetap tabel sendiri (ada kelas)
m_mapel                   │ guru_mapel (pivot)        │ Relasi many-to-many
m_kelas                   │ kelas                     │ + FK ke tapel
m_tapel                   │ tahun_pelajaran           │ Normalize
m_ruang                   │ ruang                     │ Minimal change
m_hari                    │ (constant/enum)           │ Tidak perlu tabel
m_jam                     │ jam_pelajaran             │ Minimal change
m_waktu                   │ waktu_presensi            │ Minimal change
m_mapel_jns               │ jenis_mapel               │ Minimal change
m_mapel_deskripsi         │ mapel_deskripsi           │ FK ke mapel
m_ekstra                  │ ekstrakurikuler           │ Minimal change
jadwal                    │ jadwal                    │ + proper FK
siswa_mapel_absensi       │ absensi_mapel             │ + proper FK
siswa_nilai_bln           │ nilai_bulanan             │ decimal type
siswa_nilai_smt           │ nilai_semester            │ decimal type
siswa_nilai_thn           │ nilai_tahunan             │ decimal type
siswa_raport_*            │ raport_*                  │ + proper FK
siswa_bayar*              │ pembayaran*               │ decimal type + FK
m_tabungan                │ tabungan_settings         │ decimal type
siswa_pelanggaran         │ pelanggaran               │ + proper FK + soft delete
siswa_prestasi            │ prestasi                  │ + proper FK
siswa_ekstra              │ siswa_ekstra (pivot)      │ Many-to-many pivot
inv_kib_a ~ inv_kib_f     │ inventaris_kib            │ STI/polymorphic (1 tabel)
m_kib_*                   │ kib_jenis, kib_kode       │ Minimal change
user_presensi             │ presensi                  │ + proper FK
user_absensi              │ absensi                   │ + proper FK
user_ijin                 │ surat_ijin                │ + proper FK
user_piket                │ piket_jadwal              │ + proper FK
user_log_login            │ log_login (or activity_log)│ Spatie Activity Log
user_log_entri            │ log_entri (or activity_log)│ Spatie Activity Log
user_filebox              │ file_box                  │ + Laravel Storage
kurmer_*                  │ kurmer_*                  │ Proper FK + normalization
wa_tagihan_siswa          │ wa_notifications          │ Queue-based
```

### 10.3 Data Migration Command

```php
// php artisan sisfokol:migrate-data
// Step 1: Migrate master data (pegawai, siswa, kelas, mapel)
// Step 2: Create unified users from existing auth tables
// Step 3: Migrate transactional data (nilai, presensi, keuangan)
// Step 4: Migrate files (filebox → Laravel storage)
// Step 5: Verify data integrity
```

---

## ═══════════════════════════════════════════════════════════════
## 11. PEMETAAN ROUTE (URL MAPPING)
## ═══════════════════════════════════════════════════════════════

### 11.1 Route Structure

```php
// routes/web.php

// === AUTH ===
Route::get('/login', [LoginController::class, 'showLogin']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

// === ADMIN ===
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index']);
    
    // Master Data
    Route::resource('pegawai', Admin\MasterData\PegawaiController::class);
    Route::resource('siswa', Admin\MasterData\SiswaController::class);
    Route::resource('kelas', Admin\MasterData\KelasController::class);
    Route::resource('mapel', Admin\MasterData\MapelController::class);
    Route::resource('tapel', Admin\MasterData\TapelController::class);
    Route::resource('ruang', Admin\MasterData\RuangController::class);
    Route::resource('ekstra', Admin\MasterData\EkstraController::class);
    
    // Import/Export
    Route::post('pegawai/import', [PegawaiController::class, 'import']);
    Route::get('pegawai/export', [PegawaiController::class, 'export']);
    Route::post('siswa/import', [SiswaController::class, 'import']);
    Route::get('siswa/export', [SiswaController::class, 'export']);
    
    // Akademik
    Route::resource('jadwal', Admin\Akademik\JadwalController::class);
    Route::resource('nilai', Admin\Akademik\NilaiController::class);
    Route::get('raport/{siswa}', [Admin\Akademik\RaportController::class, 'show']);
    Route::get('raport/{siswa}/pdf', [Admin\Akademik\RaportController::class, 'pdf']);
    
    // Presensi
    Route::resource('presensi', Admin\Presensi\PresensiController::class);
    Route::resource('absensi', Admin\Presensi\AbsensiController::class);
    Route::resource('ijin', Admin\Presensi\IjinController::class);
    Route::get('ijin/{ijin}/pdf', [IjinController::class, 'pdf']);
    Route::get('ijin/{ijin}/qrcode', [IjinController::class, 'qrcode']);
    
    // Keuangan
    Route::resource('pembayaran', Admin\Keuangan\PembayaranController::class);
    Route::resource('tagihan', Admin\Keuangan\TagihanController::class);
    Route::resource('tabungan', Admin\Keuangan\TabunganController::class);
    Route::get('nota/{pembayaran}/pdf', [PembayaranController::class, 'nota']);
    
    // BK
    Route::resource('pelanggaran', Admin\BK\PelanggaranController::class);
    Route::resource('pembinaan', Admin\BK\PembinaanController::class);
    Route::resource('prestasi', Admin\BK\PrestasiController::class);
    
    // Inventaris
    Route::resource('inventaris', Admin\Inventaris\KibController::class);
    
    // Kurikulum Merdeka
    Route::prefix('kurmer')->group(function () {
        Route::resource('formatif', KurmerFormatifController::class);
        Route::resource('sumatif', KurmerSumatifController::class);
        Route::resource('proyek', KurmerProyekController::class);
    });
    
    // Laporan
    Route::prefix('laporan')->group(function () {
        Route::get('presensi', [LaporanController::class, 'presensi']);
        Route::get('keuangan', [LaporanController::class, 'keuangan']);
        Route::get('akademik', [LaporanController::class, 'akademik']);
        // ... per tgl, bln, thn, pegawai, siswa, kelas
    });
    
    // Log & Audit
    Route::get('log/login', [LogController::class, 'login']);
    Route::get('log/entri', [LogController::class, 'entri']);
});

// === GURU MAPEL ===
Route::prefix('guru')->middleware(['auth', 'role:guru-mapel'])->group(function () {
    Route::get('/', [Guru\DashboardController::class, 'index']);
    Route::resource('jurnal', Guru\JurnalMengajarController::class);
    Route::resource('nilai', Guru\NilaiController::class);
    Route::resource('kurmer', Guru\KurmerController::class);
    Route::resource('rpp', Guru\RppSilabusController::class);
    Route::get('jadwal', [Guru\DashboardController::class, 'jadwal']);
});

// === WALI KELAS ===
Route::prefix('wali-kelas')->middleware(['auth', 'role:wali-kelas'])->group(function () {
    Route::get('/', [WaliKelas\DashboardController::class, 'index']);
    Route::get('siswa', [WaliKelas\SiswaController::class, 'index']);
    Route::resource('raport', WaliKelas\RaportController::class);
    Route::resource('kurmer-proyek', WaliKelas\KurmerProyekController::class);
    Route::get('keuangan', [WaliKelas\DashboardController::class, 'keuangan']);
});

// === SISWA ===
Route::prefix('siswa')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/', [Siswa\DashboardController::class, 'index']);
    Route::get('raport', [Siswa\RaportController::class, 'index']);
    Route::get('raport/pdf', [Siswa\RaportController::class, 'pdf']);
    Route::get('jadwal', [Siswa\DashboardController::class, 'jadwal']);
    Route::get('keuangan', [Siswa\KeuanganController::class, 'index']);
    Route::get('pelanggaran', [Siswa\DashboardController::class, 'pelanggaran']);
    Route::get('prestasi', [Siswa\DashboardController::class, 'prestasi']);
});

// === KEPALA SEKOLAH ===
Route::prefix('kepala-sekolah')->middleware(['auth', 'role:kepala-sekolah'])->group(function () {
    Route::get('/', [KepalaSekolah\DashboardController::class, 'index']);
    Route::get('laporan/{type}', [KepalaSekolah\LaporanController::class, 'show']);
    // Read-only access ke semua data
});

// === PIKET ===
Route::prefix('piket')->middleware(['auth', 'role:piket'])->group(function () {
    Route::get('/', [Piket\DashboardController::class, 'index']);
    Route::resource('presensi', Piket\PresensiController::class);
    Route::resource('pelanggaran', Piket\PelanggaranController::class);
    Route::resource('ijin', Piket\IjinController::class);
    Route::get('catatan', [Piket\DashboardController::class, 'catatan']);
});

// === GURU BK ===
Route::prefix('guru-bk')->middleware(['auth', 'role:guru-bk'])->group(function () {
    Route::get('/', [GuruBK\DashboardController::class, 'index']);
    Route::resource('pelanggaran', GuruBK\PelanggaranController::class);
    Route::resource('pembinaan', GuruBK\PembinaanController::class);
    Route::resource('prestasi', GuruBK\PrestasiController::class);
    Route::resource('ijin', GuruBK\IjinController::class);
});

// === BENDAHARA ===
Route::prefix('bendahara')->middleware(['auth', 'role:bendahara'])->group(function () {
    Route::get('/', [Bendahara\DashboardController::class, 'index']);
    Route::resource('pembayaran', Bendahara\PembayaranController::class);
    Route::resource('tabungan', Bendahara\TabunganController::class);
});

// === SARPRAS ===
Route::prefix('sarpras')->middleware(['auth', 'role:sarpras'])->group(function () {
    Route::get('/', [Sarpras\DashboardController::class, 'index']);
    Route::resource('inventaris', Sarpras\InventarisController::class);
});

// === SHARED ===
Route::middleware('auth')->group(function () {
    Route::get('password', [PasswordController::class, 'edit']);
    Route::put('password', [PasswordController::class, 'update']);
    Route::get('presensi/scan', [PresensiScanController::class, 'index']);
});
```

### 11.2 Mapping URL Lama → Baru

```
LEGACY URL                          │ LARAVEL URL
────────────────────────────────────┼───────────────────────────────
/login.php                          │ /login
/adm/index.php                      │ /admin
/adm/m/pegawai.php                  │ /admin/pegawai
/adm/m/siswa.php                    │ /admin/siswa
/adm/ps/presensi.php                │ /admin/presensi
/adm/keu/item.php                   │ /admin/tagihan
/adm/nil/raport.php                 │ /admin/raport/{siswa}
/admgr/index.php                    │ /guru
/admgr/pm/absensi.php               │ /guru/jurnal
/admwk/index.php                    │ /wali-kelas
/admwk/nil/raport.php               │ /wali-kelas/raport
/admsw/index.php                    │ /siswa
/admsw/d/raport.php                 │ /siswa/raport
/admks/index.php                    │ /kepala-sekolah
/admpiket/index.php                 │ /piket
/admbk/index.php                    │ /guru-bk
/admbdh/index.php                   │ /bendahara
/adminv/index.php                   │ /sarpras
```

---

## ═══════════════════════════════════════════════════════════════
## 12. DESIGN PATTERN & BEST PRACTICES
## ═══════════════════════════════════════════════════════════════

### 12.1 Pattern yang Direkomendasikan

```
1. Repository Pattern     → Data access abstraction
2. Service Pattern        → Business logic encapsulation  
3. Form Request           → Input validation
4. Policy                 → Authorization
5. Observer               → Side effects (logging, notifications)
6. Event/Listener         → Decoupled actions
7. Blade Components       → Reusable UI elements
8. Resource/Collection    → API response formatting
9. Enum                   → Type-safe constants
10. Action Classes        → Complex single-purpose operations
```

### 12.2 Contoh Implementasi Service Pattern

```php
// app/Services/NilaiService.php
class NilaiService
{
    public function __construct(
        private NilaiRepository $nilaiRepo,
        private SiswaRepository $siswaRepo,
    ) {}

    public function simpanNilaiBulanan(array $data): NilaiBulanan
    {
        // Validasi siswa aktif
        $siswa = $this->siswaRepo->findActiveOrFail($data['siswa_id']);
        
        // Calculate average, predicate
        $data['rata_rata'] = $this->hitungRataRata($data['nilai']);
        $data['predikat'] = $this->tentukanPredikat($data['rata_rata']);
        
        return $this->nilaiRepo->createNilaiBulanan($data);
    }

    public function generateRaport(Siswa $siswa, string $semester): array
    {
        return [
            'siswa' => $siswa,
            'nilai' => $this->nilaiRepo->getNilaiSemester($siswa, $semester),
            'sikap' => $this->nilaiRepo->getSikap($siswa, $semester),
            'catatan' => $this->nilaiRepo->getCatatan($siswa, $semester),
            'ranking' => $this->nilaiRepo->getRanking($siswa, $semester),
            'kenaikan' => $this->nilaiRepo->getKenaikan($siswa, $semester),
        ];
    }
}
```

### 12.3 Contoh Implementasi Blade Component (Reusable)

```blade
{{-- resources/views/components/laporan-filter.blade.php --}}
{{-- Reusable filter untuk semua laporan (bln, tgl, thn, kelas) --}}
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Filter Laporan</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $action }}">
            @if($showTanggal ?? true)
                <x-form-group label="Tanggal" name="tanggal" type="date" :value="request('tanggal')" />
            @endif
            @if($showBulan ?? true)
                <x-form-group label="Bulan" name="bulan" type="month" :value="request('bulan')" />
            @endif
            @if($showKelas ?? false)
                <x-form-group label="Kelas" name="kelas_id" type="select" :options="$kelasList" />
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Tampilkan
            </button>
            <a href="{{ $action }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

{{-- USAGE: Menggantikan 15+ file laporan yang duplikat --}}
<x-laporan-filter :action="route('admin.laporan.presensi')" :showKelas="true" :kelasList="$kelas" />
```

---

## ═══════════════════════════════════════════════════════════════
## 13. RENCANA FASE REFACTORING
## ═══════════════════════════════════════════════════════════════

### 📊 Roadmap Overview

```
FASE 1          FASE 2          FASE 3          FASE 4          FASE 5
Foundation      Core Modules    Academic        Advanced        Polish
(4 minggu)      (6 minggu)      (6 minggu)      (4 minggu)      (3 minggu)
────────────    ────────────    ────────────    ────────────    ────────────
█████████████   █████████████   █████████████   █████████████   █████████████
│             │               │               │               │
│ • Laravel   │ • Presensi    │ • Nilai       │ • Kurmer      │ • Testing
│   Setup     │ • Absensi     │ • Raport      │ • Inventaris  │ • Optimasi
│ • Database  │ • Ijin        │ • Jadwal      │ • Tabungan    │ • Deployment
│   Migration │ • Master      │ • Penilaian   │ • WA Notif    │ • Dokumentasi
│ • Auth      │   Data (CRUD) │ • PDF Export  │ • Import/     │ • UAT
│ • UI Layout │ • Keuangan    │ • Excel       │   Export      │ • Go-Live
│ • Roles     │ • BK          │ • Piket       │ • Dashboard   │
│             │ • Laporan     │ • Filebox     │   Chart       │
│             │   Dasar       │               │ • Log/Audit   │
└─────────────┴───────────────┴───────────────┴───────────────┘

                    TOTAL: ~23 MINGGU (~6 BULAN)
```

---

### 🔷 FASE 1: FOUNDATION (Minggu 1-4)

#### Minggu 1: Project Setup
```
□ Laravel 11 fresh install
□ Git repository setup
□ Composer dependencies:
  - laravel/breeze (auth)
  - spatie/laravel-permission (roles)
  - barryvdh/laravel-dompdf (PDF)
  - maatwebsite/excel (Excel)
  - simplesoftwareio/simple-qrcode (QR)
  - spatie/laravel-activitylog (logging)
□ .env configuration
□ Vite + AdminLTE 3/4 setup
□ Base layout (app.blade.php)
```

#### Minggu 2: Database Architecture
```
□ Design ERD baru (normalized)
□ Write all migrations (40-45 files)
□ Define Eloquent models with relationships
□ Write seeders:
  - RolePermissionSeeder (9 roles, ~30 permissions)
  - AdminSeeder (default admin)
  - DemoDataSeeder (sample data)
□ Run & verify migrations
```

#### Minggu 3: Authentication & Authorization
```
□ Implement multi-role login (unified)
□ Custom LoginController (handle role-based redirect)
□ Middleware: RoleMiddleware, ActiveSessionMiddleware
□ Spatie role/permission setup
□ Password change feature (all roles)
□ Session management
□ Login logging
```

#### Minggu 4: UI Framework & Shared Components
```
□ AdminLTE layout integration
□ Sidebar components per role (9 sidebars)
□ Reusable Blade components:
  - DataTable component
  - Form components
  - Filter components (tgl, bln, thn, kelas)
  - Notification badge
  - Chart card
  - Breadcrumb
  - Pagination
□ Base CSS/JS setup
□ Dashboard skeleton for all roles
```

**Deliverable Fase 1:** Aplikasi bisa login dengan 9 role, setiap role melihat dashboard kosong dengan sidebar menu yang benar.

---

### 🔷 FASE 2: CORE MODULES (Minggu 5-10)

#### Minggu 5-6: Master Data CRUD
```
□ PegawaiController (CRUD + import/export + foto + QR)
□ SiswaController (CRUD + import/export + foto + QR)
□ KelasController (CRUD)
□ TapelController (CRUD)
□ RuangController (CRUD)
□ MapelController (CRUD + jenis + deskripsi)
□ EkstraController (CRUD)
□ WaliKelasController (assignment)
□ GuruMapelController (assignment)
□ Form Request Validation untuk semua
```

#### Minggu 7-8: Presensi, Absensi, Ijin
```
□ PresensiService (business logic)
□ PresensiController (scan QR, manual, pulang)
□ AbsensiController (CRUD)
□ IjinController (CRUD + PDF + QR Code)
□ Laporan: per tgl, bln, thn, pegawai, siswa, kelas
□ Reusable laporan filter component
□ Piket panel access
```

#### Minggu 9: Keuangan Siswa
```
□ KeuanganService (business logic)
□ TagihanController (setup item pembayaran)
□ PembayaranController (entri bayar, nota)
□ TunggakanController (view)
□ NotaCetakController (PDF)
□ Laporan keuangan: per tgl, bln, thn
□ Lunas report
```

#### Minggu 10: BK & Kedisiplinan
```
□ BKService
□ PelanggaranController (CRUD + PDF + point)
□ PembinaanController (CRUD + PDF)
□ PrestasiController (CRUD)
□ Laporan BK: per bln, tgl, thn, kelas, siswa
□ Access dari role: Admin, BK, Piket, Siswa (view)
```

**Deliverable Fase 2:** Master data lengkap, presensi berjalan, keuangan & BK berfungsi.

---

### 🔷 FASE 3: ACADEMIC MODULES (Minggu 11-16)

#### Minggu 11-12: Nilai & Penilaian
```
□ NilaiService (hitung rata-rata, predikat, ranking)
□ NilaiBulananController
□ NilaiSemesterController
□ NilaiTahunanController
□ MapelNilaiController (entri nilai per mapel)
□ Guru Mapel: entri nilai
□ Admin: manage semua nilai
□ Wali Kelas & Siswa: view
```

#### Minggu 13-14: Raport & Jadwal
```
□ RaportService (generate raport data)
□ RaportController (view + PDF)
□ Raport components: catatan, kenaikan, sikap, ranking
□ JadwalController (CRUD)
□ JadwalService (conflict check)
□ Laporan jadwal: per guru, per mapel
□ Siswa view jadwal
```

#### Minggu 15: Jurnal Guru & Filebox
```
□ JurnalMengajarController (absensi mapel + agenda)
□ RppSilabusController (upload/download)
□ FileBoxController (materi, soal, tugas)
□ PDF export jurnal
□ Admin & Kepsek: view jurnal
```

#### Minggu 16: Piket System
```
□ PiketController (CRUD)
□ Catatan piket
□ Piket role panel completion
□ Integration presensi + piket
□ PDF export catatan piket
```

**Deliverable Fase 3:** Sistem akademik lengkap, raport bisa dicetak, jadwal berjalan.

---

### 🔷 FASE 4: ADVANCED FEATURES (Minggu 17-20)

#### Minggu 17-18: Kurikulum Merdeka
```
□ KurmerService
□ AsesmenFormatifController (CRUD + nilai + detail)
□ AsesmenSumatifController (CRUD + nilai + detail)
□ ProyekController (CRUD + proses + nilai)
□ LingkupMateriController (LM)
□ TujuanPembelajaranController (TP)
□ Raport Asesmen PDF
□ Raport Proyek P5 PDF
```

#### Minggu 19: Inventaris & Tabungan
```
□ InventarisService
□ KibController (A-F, polymorphic)
□ Import/Export inventaris
□ Rekap inventaris
□ TabunganService
□ TabunganController (debet/kredit, history)
□ Laporan tabungan
```

#### Minggu 20: Dashboard, Notifikasi, Audit
```
□ Dashboard charts (Chart.js - 14 hari terakhir)
□ Real-time notification badges
□ WhatsApp notification integration (tagihan)
□ Activity log (Spatie)
□ Log login & entri viewer
□ Data migration command dari DB lama
```

**Deliverable Fase 4:** Semua fitur advanced berjalan, migrasi data dari sistem lama.

---

### 🔷 FASE 5: POLISH & DEPLOYMENT (Minggu 21-23)

#### Minggu 21: Testing
```
□ Feature tests: Auth, CRUD operations
□ Unit tests: Services (NilaiService, KeuanganService, dll)
□ Browser tests: Critical user flows
□ Data migration verification
□ Security audit
□ Performance profiling
```

#### Minggu 22: Optimisasi & Deployment Prep
```
□ Query optimization (N+1 fix, eager loading)
□ Caching strategy (config, route, view)
□ Asset optimization (Vite build)
□ Server setup documentation
□ Backup & restore procedure
□ SSL/HTTPS configuration
```

#### Minggu 23: UAT & Go-Live
```
□ User Acceptance Testing (semua 9 role)
□ Training documentation per role
□ Data migration final run
□ Parallel run (old + new system)
□ Cut-over plan
□ Go-live
□ Post-deployment monitoring
```

**Deliverable Fase 5:** Sistem production-ready, tested, deployed.

---

## ═══════════════════════════════════════════════════════════════
## 14. ESTIMASI EFFORT & TIMELINE
## ═══════════════════════════════════════════════════════════════

### 14.1 Work Breakdown Structure (WBS)

| Fase | Durasi | Man-Days | Prioritas |
|------|:------:|:--------:|:---------:|
| **Fase 1:** Foundation | 4 minggu | 20 MD | 🔴 P0 |
| **Fase 2:** Core Modules | 6 minggu | 30 MD | 🔴 P0 |
| **Fase 3:** Academic Modules | 6 minggu | 30 MD | 🟠 P1 |
| **Fase 4:** Advanced Features | 4 minggu | 20 MD | 🟡 P2 |
| **Fase 5:** Polish & Deploy | 3 minggu | 15 MD | 🔴 P0 |
| **TOTAL** | **23 minggu** | **115 MD** | — |

### 14.2 Resource Estimation

| Skenario | Tim | Durasi | Biaya Est. (IDR) |
|----------|:---:|:------:|:----------------:|
| **Solo Developer** (1 Senior) | 1 | ~6 bulan | 60-90 juta |
| **Small Team** (1 Senior + 1 Junior) | 2 | ~4 bulan | 80-120 juta |
| **Ideal Team** (1 Lead + 2 Dev + 1 QA) | 4 | ~3 bulan | 150-200 juta |

### 14.3 Effort per Module (Detail)

| Module | Files to Create | Est. Hours | Complexity |
|--------|:--------------:|:----------:|:----------:|
| Auth & Roles | 8-10 | 24h | Medium |
| UI Layout & Components | 15-20 | 32h | Medium |
| Master Pegawai | 6-8 | 16h | Medium |
| Master Siswa | 6-8 | 16h | Medium |
| Master Data Lain | 10-12 | 24h | Low |
| Presensi System | 12-15 | 40h | High |
| Absensi & Ijin | 10-12 | 32h | Medium |
| Keuangan Siswa | 12-15 | 40h | High |
| BK (Pelanggaran/Pembinaan/Prestasi) | 10-12 | 32h | Medium |
| Nilai & Penilaian | 10-12 | 40h | High |
| Raport (+ PDF) | 8-10 | 40h | Very High |
| Jadwal | 6-8 | 24h | Medium |
| Kurikulum Merdeka | 12-15 | 48h | Very High |
| Inventaris (KIB A-F) | 8-10 | 24h | Medium |
| Tabungan | 6-8 | 16h | Medium |
| Piket System | 6-8 | 16h | Medium |
| Dashboard & Charts | 10-12 | 32h | Medium |
| Import/Export Excel | 8-10 | 32h | High |
| PDF Generation | 8-10 | 32h | High |
| QR Code | 3-4 | 8h | Low |
| WA Notification | 3-4 | 16h | Medium |
| Logging & Audit | 4-5 | 8h | Low |
| Migration Scripts | 5-8 | 24h | High |
| Testing | 20-25 | 48h | High |
| **TOTAL** | **~200-250 files** | **~650h** | — |

---

## ═══════════════════════════════════════════════════════════════
## 15. RISK ASSESSMENT & MITIGASI
## ═══════════════════════════════════════════════════════════════

### 15.1 Risk Matrix

| # | Risk | Probability | Impact | Severity | Mitigation |
|---|------|:-----------:|:------:|:--------:|------------|
| 1 | Data loss saat migrasi | Medium | 🔴 Critical | HIGH | Backup ganda, parallel run, rollback plan |
| 2 | Fitur tidak terpetakan | Medium | 🟠 High | HIGH | Checklist fitur detail, UAT per role |
| 3 | Business logic tersembunyi | High | 🟠 High | HIGH | Review setiap PHP file, interview end users |
| 4 | User resistance (familiar dg UI lama) | Medium | 🟡 Medium | MEDIUM | Pertahankan layout AdminLTE, training |
| 5 | Timeline overrun | High | 🟡 Medium | HIGH | Buffer 20%, prioritas P0 first |
| 6 | Kurikulum Merdeka complexity | Medium | 🟠 High | HIGH | Dedicated sprint, refer to Kemendikbud spec |
| 7 | PDF raport layout mismatch | High | 🟡 Medium | MEDIUM | Template PDF approval dulu |
| 8 | Performance degradation | Low | 🟡 Medium | LOW | Eager loading, caching, N+1 check |
| 9 | Hosting compatibility | Low | 🟠 High | MEDIUM | Test di shared hosting (PHP 8.2+) |
| 10 | Multi-tahun pelajaran data | Medium | 🟡 Medium | MEDIUM | Filter tapel di semua query |

### 15.2 Dependencies & Blockers

```
BLOCKER 1: Skema database final harus di-approve sebelum coding mulai
BLOCKER 2: Template PDF raport harus di-verify dengan pihak sekolah
BLOCKER 3: Format Kurikulum Merdeka harus sesuai Permendikbud terbaru
BLOCKER 4: Ketersediaan data sample untuk testing
```

---

## ═══════════════════════════════════════════════════════════════
## 16. REKOMENDASI TECH STACK
## ═══════════════════════════════════════════════════════════════

### 16.1 Backend

| Component | Technology | Version | Justification |
|-----------|-----------|:-------:|---------------|
| Framework | **Laravel** | **11.x** | Target framework, LTS, ecosystem mature |
| PHP | PHP | 8.2+ | Laravel 11 requirement |
| Database | MySQL/MariaDB | 8.0+/10.6+ | Compatibility with existing data |
| Auth | Laravel Breeze | Latest | Simple, customizable |
| Authorization | Spatie Permission | 6.x | Robust role/permission |
| PDF | barryvdh/laravel-dompdf | Latest | Easy integration |
| Excel | Maatwebsite/Excel | 3.x | Industry standard |
| QR Code | simplesoftwareio/qrcode | Latest | Easy QR generation |
| Activity Log | Spatie Activity Log | 4.x | Replace custom logging |
| Backup | Spatie Backup | 8.x | Automated backup |
| IDE Helper | barryvdh/ide-helper | Latest | DX improvement |

### 16.2 Frontend

| Component | Technology | Justification |
|-----------|-----------|---------------|
| Template Engine | **Blade** | Laravel native |
| CSS Framework | **AdminLTE 3** (awal) → 4 (later) | Familiar untuk user existing |
| CSS Utility | Tailwind CSS (optional) | Modern, jika perlu custom |
| JS Bundle | Vite | Laravel 11 default |
| Charts | Chart.js | Sudah dipakai di existing |
| DataTables | jQuery DataTables | Familiar, powerful |
| Icons | Font Awesome 5/6 | Sudah dipakai |

### 16.3 DevOps & Tools

| Component | Technology | Justification |
|-----------|-----------|---------------|
| Version Control | Git + GitLab | Existing repo di GitLab |
| Testing | PHPUnit + Pest | Laravel native |
| CI/CD | GitLab CI | Integrated |
| Deployment | Forge / Manual | Sesuai budget sekolah |
| Hosting | VPS / Shared Hosting | Sesuai kemampuan sekolah |

---

## ═══════════════════════════════════════════════════════════════
## 17. KESIMPULAN & NEXT STEPS
## ═══════════════════════════════════════════════════════════════

### 17.1 Kesimpulan

SISFOKOL v7.12 adalah sistem yang **fungsional dan komprehensif** untuk konteks sekolah di Indonesia. Sistem ini menangani hampir semua kebutuhan operasional sekolah mulai dari presensi, akademik, keuangan, BK, sampai inventaris. Namun, dari sisi teknis, sistem ini memiliki **technical debt yang sangat besar**:

1. **ARSITEKTUR**: Zero separation of concerns. Setiap PHP file adalah monolith kecil yang menggabungkan routing, auth, business logic, database, dan view.

2. **KEAMANAN**: Vulnerability kritis di semua level — SQL injection, weak hashing, no CSRF. Ini adalah risiko terbesar jika sistem digunakan online.

3. **MAINTAINABILITY**: Duplikasi kode ~60-70%. Perubahan kecil harus dilakukan di 4-9 file sekaligus. Bug fix menjadi nightmare.

4. **SCALABILITY**: Tidak ada caching, no API, no queue. Tidak siap untuk integrasi mobile atau third-party.

**Rekomendasi: FULL REWRITE ke Laravel 11** (bukan incremental refactor) karena:
- Tidak ada fondasi arsitektur yang bisa di-"wrap"
- Biaya membungkus kode spaghetti > biaya tulis ulang
- Opportunity untuk fix semua security issues sekaligus
- Business logic sudah terpetakan jelas dari existing code

### 17.2 Next Steps (Immediate Actions)

```
MINGGU 1:
━━━━━━━━
□ 1. Review & approve dokumen analisis ini
□ 2. Finalisasi ERD database baru
□ 3. Setup Laravel 11 project
□ 4. Setup Git repository

MINGGU 2:
━━━━━━━━
□ 5. Write database migrations
□ 6. Implement auth system
□ 7. Setup AdminLTE layout
□ 8. Begin Fase 1 development

ONGOING:
━━━━━━━
□ 9. Weekly progress review
□ 10. Parallel testing dengan user (per sprint)
□ 11. Data migration script development
□ 12. Documentation
```

### 17.3 Success Criteria

| # | Criteria | Measurement |
|---|----------|-------------|
| 1 | Semua 14 modul berfungsi | Feature checklist 100% |
| 2 | Semua 9 role bisa login & operasi | UAT pass per role |
| 3 | Zero SQL injection vulnerability | Security scan pass |
| 4 | Password menggunakan bcrypt | Code review |
| 5 | CSRF protection aktif | Automated test |
| 6 | PDF raport sesuai format | Stakeholder approval |
| 7 | Data lama berhasil migrasi | Data integrity check |
| 8 | Performance < 2 detik per page | Load test |
| 9 | Mobile responsive | Cross-device test |
| 10 | Dokumentasi lengkap | Doc review |

---

## ═══════════════════════════════════════════════════════════════
## LAMPIRAN
## ═══════════════════════════════════════════════════════════════

### A. Daftar 36 Helper Functions di fungsi.php

| # | Function | Purpose | Laravel Equivalent |
|---|----------|---------|-------------------|
| 1 | `cegah($str)` | Input sanitization | `$request->validated()` |
| 2 | `cegah2($str)` | Input sanitization v2 | `$request->validated()` |
| 3 | `nosql($str)` | Anti SQL injection | Eloquent (automatic) |
| 4 | `balikin($str)` | Reverse cegah encoding | Not needed |
| 5 | `balikin2($str)` | Reverse cegah2 encoding | Not needed |
| 6 | `strip($str)` | Strip characters | `Str::of()` |
| 7 | `strip2($str)` | Strip characters v2 | `Str::of()` |
| 8 | `titikdua($str)` | Replace colon | `Str::replace()` |
| 9 | `ParseVal($tpl, $arr)` | Template parser | Blade `@yield/@section` |
| 10 | `LoadTpl($path)` | Load HTML template | Blade views |
| 11 | `xfree($q)` | Free query result | Not needed (auto GC) |
| 12 | `xclose($conn)` | Close DB connection | Not needed (auto) |
| 13 | `xkapital($str)` | Capitalize | `Str::title()` |
| 14 | `xheadline($str)` | Headline format | `Str::headline()` |
| 15 | `xgedi($str)` | Large text | CSS class |
| 16 | `xloc($url)` | Redirect | `redirect()` |
| 17 | `xloc2($url, $msg)` | Redirect with message | `redirect()->with()` |
| 18 | `xpesan($str)` | Alert message | `session()->flash()` |
| 19 | `pekem($msg, $url)` | Alert + redirect | `redirect()->with('error')` |
| 20 | `pekem2($msg)` | Alert only | `session()->flash()` |
| 21 | `nocache()` | No cache headers | Middleware |
| 22 | `delete($file)` | Delete file | `Storage::delete()` |
| 23 | `xduit($str)` | Format currency | `number_format()` |
| 24 | `xduit2($str)` | Format currency v2 | `number_format()` |
| 25 | `xduit3($str)` | Format currency v3 | `number_format()` |
| 26 | `xpredikat($str)` | Grade predicate | Custom helper/enum |
| 27 | `xhuruf($str)` | Grade letter | Custom helper/enum |
| 28 | `xhuruff($str)` | Grade letter v2 | Custom helper/enum |
| 29 | `xongkof($str)` | Cost format | `number_format()` |
| 30 | `my_filesize($str)` | File size format | Laravel File helper |
| 31 | `xduitf($str)` | Currency format | `number_format()` |
| 32 | `split_sql($sql)` | Split SQL statements | Migration (not needed) |
| 33 | `pathasli2($str)` | Path helper | `public_path()` |
| 34 | `pathasli1($str)` | Path helper | `storage_path()` |
| 35 | `seo_friendly_url($str)` | URL slug | `Str::slug()` |
| 36 | `add_days($date, $n)` | Add days to date | `Carbon::addDays()` |

### B. Daftar 75 Tabel Database

```
adminx, a_profil, inv_kib_a, inv_kib_b, inv_kib_c, inv_kib_d,
inv_kib_e, inv_kib_f, jadwal, kurmer_asesmen_formatif, kurmer_mapel_lm,
kurmer_mapel_tp, kurmer_nilai_asesmen_formatif,
kurmer_nilai_asesmen_formatif_detail, kurmer_nilai_asesmen_sumatif,
kurmer_nilai_asesmen_sumatif_detail, kurmer_nilai_proyek,
kurmer_nilai_proyek_proses, kurmer_proyek, kurmer_proyek_detail,
m_bendahara, m_bk_point, m_bk_point_jenis, m_bk_prestasi, m_ekstra,
m_gurubk, m_hari, m_jam, m_kelas, m_keu_siswa, m_kib_jenis, m_kib_kode,
m_ks, m_mapel, m_mapel_deskripsi, m_mapel_jns, m_pegawai, m_pembinaan,
m_piket, m_ruang, m_sarpras, m_siswa, m_tapel, m_user, m_waktu,
m_waktu_jadwal, m_walikelas, rev_guru_absensi, rev_guru_agenda,
siswa_bayar, siswa_bayar_rincian, siswa_bayar_tagihan, siswa_ekstra,
siswa_mapel_absensi, siswa_nilai_bln, siswa_nilai_smt, siswa_nilai_thn,
siswa_pelanggaran, siswa_prestasi, siswa_raport_catatan,
siswa_raport_kenaikan, siswa_raport_rangking, siswa_raport_sikap,
siswa_saran, siswa_soal, siswa_soal_nilai, siswa_tugas, user_absensi,
user_filebox, user_ijin, user_log_entri, user_log_login, user_piket,
user_presensi, wa_tagihan_siswa
```

### C. Default Credentials (dari README)

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin |
| Kepala Sekolah | 234 | 234 |
| Sarpras | 234 | 234 |
| Bendahara | 234 | 234 |
| Wali Kelas | 234 | 234 |
| Guru BK | 234 | 234 |
| Guru Mapel | 234 | 234 |
| Siswa | 810001 | 810001 |
| Piket | 1122 | 1122 |

---

**Document Version:** 1.0  
**Generated:** 17 Juni 2026  
**Repository:** https://gitlab.com/hajirodeon/sisfokol-v7.00-code-smartoffice  
**Classification:** Internal - Project Planning  

---

*"The best time to refactor was yesterday. The second best time is today."*
