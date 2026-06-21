# Analisis Mendalam & Blueprint SISFOKOL v7.00 (Code:SmartOffice)

**Tanggal Analisis:** 17 Juni 2026  
**Analis:** Tim Business Analyst & Software Engineer  
**Sumber Kode:** `sisfokol-v7.00-code-smartoffice` (PHP Native 8.2.4 + MySQL/MariaDB)  
**Tujuan:** Ekstraksi business flow, data logic, domain knowledge, dan perancangan ulang (blueprint) sistem informasi sekolah yang modern, aman, dan skalabel.

---

## 1. Executive Summary

SISFOKOL v7.00 *Code:SmartOffice* adalah sistem informasi sekolah berbasis web yang dikembangkan oleh **Agus Muhajir, S.Kom**. Sistem ini ditujukan untuk lingkungan sekolah Indonesia yang mengusung konsep *Smart Office*, di mana berbagai bagian kantor sekolah saling terhubung dalam satu platform.

### 1.1. Fitur Utama yang Tercakup

| No | Domain | Fitur Kunci |
| --- | --- | --- |
| 1 | **Akademik** | Mata pelajaran, jadwal, RPP/silabus, jurnal mengajar, penilaian Kurikulum Merdeka (formatif, sumatif, proyek), raport asesmen & proyek |
| 2 | **Keuangan** | Item pembayaran siswa (SPP/infaq), tunggakan, pembayaran, kwitansi, laporan keuangan, tabungan siswa |
| 3 | **Kedisiplinan** | Jenis pelanggaran, daftar pelanggaran, pembinaan, prestasi siswa |
| 4 | **Presensi & Izin** | Presensi kehadiran guru/siswa dengan QR Code, absensi (sakit/ijin/alpha), izin masuk/pulang |
| 5 | **Inventaris** | Buku inventaris KIB A–F, KIR, daftar kode barang |
| 6 | **Filebox** | Penyimpanan RPP, silabus, dan dokumen guru |
| 7 | **Dashboard & Laporan** | Laporan per tanggal/bulan/tahun/kelas/siswa/pegawai |

### 1.2. Temuan Kritis Tingkat Tinggi

| Aspek | Temuan | Dampak |
| --- | --- | --- |
| **Keamanan** | Password di-hash dengan **MD5 tanpa salt**, query SQL di-*concatenate* langsung dari input, tidak ada CSRF token, tidak ada prepared statement | Sangat rentan terhadap SQL Injection, rainbow table, session hijacking |
| **Arsitektur** | PHP Native prosedural tanpa framework, campuran HTML/PHP/JS/SQL dalam satu file | Sulit di-maintain, duplicated code, tidak scalable |
| **Database** | **MyISAM**, tanpa **Foreign Key**, kolom numerik banyak disimpan sebagai `varchar`, primary key berupa hash MD5 | Tidak ada integritas referensial, performa buruk, inkonsistensi data |
| **Data Model** | Denormalisasi tinggi: data siswa/guru/mapel/kelas di-*embed* di banyak tabel transaksi | Duplikasi data, sulit update, anomali data |
| **Testing** | Tidak ada unit test, integration test, maupun UAT form | Risiko regresi tinggi saat perubahan |
| **Deployment** | Konfigurasi hardcoded di `inc/config.php`, tidak ada environment management | Tidak siap untuk CI/CD dan multi-environment |

> **Rekomendasi Strategis:** SISFOKOL v7.00 lebih baik dijadikan **referensi domain & fitur** daripada dijadikan fondasi teknis langsung. Disarankan membangun ulang sistem dengan arsitektur modern (framework Laravel/PHP, database relasional normal, REST API, RBAC, audit log, dan automated testing).

---

## 2. Konteks, Stakeholder, dan Pemetaan Role

### 2.1. Role Pengguna

Sistem memiliki **9 role** dengan folder masing-masing, plus template untuk orang tua (`admortu.html`) walau tidak ada folder modul tersendiri.

| Role | Folder | Fokus Tugas |
| --- | --- | --- |
| **Administrator** | `adm/` | Master data, user akses, konfigurasi, laporan keseluruhan |
| **Kepala Sekolah** | `admks/` | Monitoring akademik, keuangan, disiplin, presensi, cetak raport |
| **Bendahara** | `admbdh/` | Keuangan siswa, pembayaran, tunggakan, tabungan |
| **Guru BK** | `admbk/` | Pelanggaran, pembinaan, prestasi, laporan disiplin |
| **Guru Mapel** | `admgr/` | Penilaian Kurmer, jurnal mengajar, RPP/silabus, jadwal mengajar |
| **Wali Kelas** | `admwk/` | Penilaian proyek, raport asesmen, keuangan kelas, presensi |
| **Petugas Piket** | `admpiket/` | Presensi harian, absensi, izin masuk/pulang, catatan kejadian |
| **Sarpras** | `adminv/` | Inventaris KIB A–F, KIR, rekapitulasi aset |
| **Siswa** | `admsw/` | Lihat kelas hari ini, pelanggaran, pembinaan, prestasi, raport, keuangan, jadwal |
| **Orang Tua** | `admortu/` (template saja) | Monitoring pelanggaran, prestasi, presensi |

### 2.2. Pemetaan Role ke Modul (Ringkasan)

| Modul | Admin | KS | Bendahara | BK | Guru | Wali Kelas | Piket | Sarpras | Siswa |
| --- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| Master Data | ✅ | ❌ | ❌ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Akademik/Mapel | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | 👁️ |
| Jadwal | ✅ | ✅ | ❌ | ❌ | 👁️ | 👁️ | ❌ | ❌ | 👁️ |
| Penilaian Kurmer | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Cetak Raport | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | 👁️ |
| Presensi | ✅ | ✅ | ❌ | ❌ | 👁️ | ❌ | ✅ | ❌ | ❌ |
| Absensi/Izin | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | 👁️ |
| Pelanggaran | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ | 👁️ |
| Pembinaan | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | 👁️ |
| Prestasi | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | 👁️ |
| Keuangan Siswa | ✅ | 👁️ | ✅ | ❌ | ❌ | 👁️ | ❌ | ❌ | 👁️ |
| Tabungan Siswa | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Inventaris | ✅ | 👁️ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Filebox | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |

*Legenda: ✅ = CRUD/entri, 👁️ = view/laporan, ⚠️ = sebagian, ❌ = tidak ada akses.*

---

## 3. Arsitektur & Teknologi

### 3.1. Stack Teknologi

| Layer | Teknologi | Keterangan |
| --- | --- | --- |
| **Bahasa** | PHP Native 8.2.4 | Tidak menggunakan framework MVC |
| **Database** | MySQL/MariaDB dengan engine **MyISAM** | Tidak mendukung transaksi dan FK |
| **Frontend** | AdminLTE 3, Bootstrap 4, jQuery, FontAwesome | Template berbasis HTML statis |
| **Excel** | PHPExcel | Import/export data |
| **QR Code** | Library QR internal | Untuk kartu dan presensi |
| **Template Engine** | Custom `LoadTpl()` + `ParseVal()` | Replace placeholder `{variabel}` |
| **Session** | PHP native session | Disimpan di file, timeout 3600 detik |
| **Server** | XAMPP / Apache | Direkomendasikan oleh pengembang |

### 3.2. Struktur Repositori

```
sisfokol-v7.00-code-smartoffice/
├── adm/              # Modul Admin
├── admks/            # Modul Kepala Sekolah
├── admbdh/           # Modul Bendahara
├── admbk/            # Modul Guru BK
├── admgr/            # Modul Guru Mapel
├── admwk/            # Modul Wali Kelas
├── admpiket/         # Modul Petugas Piket
├── adminv/           # Modul Sarpras
├── admsw/            # Modul Siswa
├── inc/              # Config, koneksi, fungsi, class helper
│   ├── config.php
│   ├── koneksi.php
│   ├── fungsi.php
│   ├── class/
│   ├── cek/          # Validasi session per role
│   └── js/
├── db/               # Dump SQL
│   └── sisfokol_v7.sql
├── filebox/          # Upload file
├── img/              # Aset gambar
├── template/         # HTML template AdminLTE per role
│   ├── adm.html
│   ├── admks.html
│   ├── ...
│   └── adminlte3/    # Asset AdminLTE
├── index.php -> login.php
├── login.php         # Login multi-role
├── logout.php
└── expire.php
```

### 3.3. Pola Autentikasi & Akses

```mermaid
flowchart LR
    A[Login Form] --> B{Pilih Tipe User}
    B -->|tp01| C[Guru Mapel]
    B -->|tp02| D[Siswa]
    B -->|tp03| E[Wali Kelas]
    B -->|tp04| F[Kepala Sekolah]
    B -->|tp06| G[Administrator]
    B -->|tp033| H[Petugas Piket]
    B -->|tp011| I[Guru BK]
    B -->|tp042| J[Bendahara]
    B -->|tp041| K[Sarpras]
    C --> L[Session: tipe_session, kd1_session, janiskd]
    L --> M[Redirect ke Folder Role]
    M --> N[File cek/role.php validasi session]
```

- Password disimpan sebagai `md5(passwordx)` tanpa salt.
- Tidak ada mekanisme **password reset** otomatis, **rate limiting**, atau **2FA**.
- Setiap role memiliki file `inc/cek/role.php` yang memeriksa session tertentu.

---

## 4. Analisis Skema Database

### 4.1. Overview Skema

Database `sisfokol_v7` memiliki **75 tabel** dengan struktur berikut:

| Kategori | Jumlah | Contoh Tabel |
| --- | --- | --- |
| **Master Data** | ~15 | `m_siswa`, `m_pegawai`, `m_kelas`, `m_tapel`, `m_mapel`, `m_mapel_jns`, `m_mapel_deskripsi`, `m_walikelas`, `m_gurubk`, `m_bendahara`, `m_sarpras`, `m_ks`, `m_piket`, `m_hari`, `m_jam`, `m_ruang` |
| **Akademik & Penilaian** | ~18 | `jadwal`, `m_waktu_jadwal`, `kurmer_mapel_tp`, `kurmer_mapel_lm`, `kurmer_asesmen_formatif`, `kurmer_nilai_asesmen_formatif`, `kurmer_nilai_asesmen_formatif_detail`, `kurmer_nilai_asesmen_sumatif`, `kurmer_nilai_asesmen_sumatif_detail`, `kurmer_proyek`, `kurmer_proyek_detail`, `kurmer_nilai_proyek`, `kurmer_nilai_proyek_proses`, `siswa_nilai_bln`, `siswa_nilai_smt`, `siswa_nilai_thn`, `siswa_mapel_absensi`, `siswa_soal`, `siswa_tugas`, `siswa_soal_nilai` |
| **Keuangan** | 4 | `m_keu_siswa`, `siswa_bayar`, `siswa_bayar_tagihan`, `siswa_bayar_rincian` |
| **Disiplin & Prestasi** | 5 | `m_bk_point_jenis`, `m_bk_point`, `m_bk_prestasi`, `siswa_pelanggaran`, `siswa_prestasi` |
| **Presensi & Izin** | 5 | `m_waktu`, `user_presensi`, `user_absensi`, `user_ijin`, `user_piket` |
| **Inventaris** | 9 | `inv_kib_a`–`inv_kib_f`, `m_kib_jenis`, `m_kib_kode` |
| **Rapor & Catatan** | 5 | `siswa_raport_catatan`, `siswa_raport_sikap`, `siswa_raport_kenaikan`, `siswa_raport_rangking`, `siswa_saran` |
| **Ekstrakurikuler** | 2 | `m_ekstra`, `siswa_ekstra` |
| **Filebox & Log** | 4 | `user_filebox`, `user_log_login`, `user_log_entri`, `a_profil` |
| **Autentikasi** | 3 | `adminx`, `m_user`, `wa_tagihan_siswa` |
| **Jurnal Mengajar** | 2 | `rev_guru_agenda`, `rev_guru_absensi` |

> **Lampiran lengkap 75 tabel beserta kolom dan constraint tersedia di file `schema_sisfokol_v7.md`.**

### 4.2. ERD Konseptual (Entitas Kunci)

```mermaid
erDiagram
    M_TAPEL ||--o{ M_KELAS : "berlaku pada"
    M_KELAS ||--o{ M_SISWA : "berisi"
    M_SISWA ||--o{ SISWA_BAYAR_TAGIHAN : "memiliki tagihan"
    M_SISWA ||--o{ SISWA_BAYAR_RINCIAN : "memiliki rincian bayar"
    M_SISWA ||--o{ SISWA_PELANGGARAN : "melanggar"
    M_SISWA ||--o{ SISWA_PRESTASI : "berprestasi"
    M_SISWA ||--o{ SISWA_EKSTRA : "mengikuti"
    M_SISWA ||--o{ SISWA_NILAI_SMT : "dinilai"
    M_SISWA ||--o{ KURMER_NILAI_ASESMEN_FORMATIF : "asesmen"
    M_SISWA ||--o{ USER_PRESENSI : "presensi"
    M_SISWA ||--o{ USER_ABSENSI : "absensi"
    M_PEGAWAI ||--o{ M_GURUBK : "menjadi BK"
    M_PEGAWAI ||--o{ M_WALIKELAS : "menjadi wali"
    M_PEGAWAI ||--o{ M_BENDAHARA : "menjadi bendahara"
    M_PEGAWAI ||--o{ M_SARPRAS : "menjadi sarpras"
    M_PEGAWAI ||--o{ M_KS : "menjadi KS"
    M_PEGAWAI ||--o{ M_MAPEL : "mengajar"
    M_MAPEL ||--o{ KURMER_MAPEL_TP : "memiliki TP"
    M_MAPEL ||--o{ KURMER_MAPEL_LM : "memiliki LM"
    M_KELAS ||--o{ JADWAL : "dijadwalkan"
    JADWAL ||--o{ REV_GURU_AGENDA : "dibuat agenda"
```

### 4.3. Data Dictionary — Tabel Kunci

#### `m_siswa` — Data Master Siswa

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Primary key hash MD5 | Bukan auto-increment |
| `kode` | varchar(50) | NIS siswa | Seharusnya unique index |
| `nama` | varchar(100) | Nama siswa | |
| `tapel` | varchar(100) | Tahun pelajaran aktif | Denormalisasi: seharusnya relasi ke `m_tapel` |
| `kelas` | varchar(100) | Kelas | Denormalisasi: seharusnya relasi ke `m_kelas` |
| `usernamex` | varchar(100) | Username login | |
| `passwordx` | varchar(100) | Password hash MD5 | **Lemah, perlu bcrypt** |
| `passwordx_ortu` | varchar(100) | Password orang tua | **Lemah** |
| `nowa` | varchar(100) | Nomor WhatsApp | |
| `jml_absen_sakit` | varchar(5) | Counter absensi | Counter manual, tidak terkait `user_absensi` secara otomatis |
| `subtotal_belum` | varchar(15) | Total tunggakan | Denormalisasi, bisa dihitung dari tabel keuangan |
| `jml_pelanggaran` | varchar(5) | Counter pelanggaran | Denormalisasi |
| `qrcode` | varchar(100) | Kode QR | |

#### `m_pegawai` — Data Master Guru/Karyawan

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 | |
| `kode` | varchar(100) | NIP/NIY | |
| `nama` | varchar(100) | Nama | |
| `jabatan` | varchar(100) | Jabatan | |
| `usernamex` | longtext | Username | Tipe `longtext` tidak efisien |
| `passwordx` | longtext | Password MD5 | **Sangat tidak aman** |
| `jml_absen_sakit` | varchar(5) | Counter | |
| `jml_mengajar` | varchar(5) | Counter jurnal | |

#### `m_mapel` — Data Mapel per Kelas & TAPIL

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 | |
| `tapel` | varchar(100) | Tahun pelajaran | |
| `kelas` | varchar(100) | Kelas | |
| `kode` | varchar(100) | Kode mapel | |
| `nama` | longtext | Nama mapel | Tipe `longtext` berlebihan |
| `kkm` | varchar(5) | Kriteria Ketuntasan Minimal | Seharusnya `decimal`/`int` |
| `pegawai_kd` | varchar(50) | FK ke `m_pegawai` | Tanpa constraint FK |
| `rpp_acc`, `silabus_acc` | enum | Status approve | |

#### `jadwal` — Jadwal Pelajaran

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 | |
| `tapel`, `smt`, `kelas`, `hari`, `hari_no`, `jam_ke` | varchar | Komponen jadwal | Numerik disimpan string |
| `mapel_kode`, `mapel_nama` | varchar | Mapel | Denormalisasi nama mapel |
| `waktu` | varchar(100) | Rentang waktu | Seharusnya terkait `m_waktu_jadwal` |

#### `m_keu_siswa` — Master Item Pembayaran

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 | |
| `tapel`, `smt`, `kelas`, `thn`, `bln`, `nama` | varchar | Identifikasi item | `thn` dan `bln` redundan dengan `tapel` |
| `nominal` | varchar(15) | Nominal tagihan | **Seharusnya DECIMAL(12,2)** |

#### `siswa_bayar_tagihan` — Tagihan per Siswa

| Kolom | Tipe | Fungsi | Catatan Kritis |
| --- | --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 | |
| `siswa_kd`, `siswa_kode`, `siswa_nama`, `siswa_tapel`, `siswa_kelas` | varchar | Data siswa (denormalisasi) | Banyak duplikasi dari `m_siswa` |
| `item_kd`, `item_nama`, `item_tapel`, `item_smt`, `item_kelas`, `item_thn`, `item_bln` | varchar | Data item (denormalisasi) | Duplikasi dari `m_keu_siswa` |
| `item_nominal`, `nominal_bayar`, `nominal_kurang` | varchar(15) | Nominal | Seharusnya DECIMAL |
| `lunas_status` | enum | Status lunas | |

#### `siswa_bayar` & `siswa_bayar_rincian` — Transaksi Pembayaran

- `siswa_bayar`: header nota pembayaran (nomor nota, tanggal, total).
- `siswa_bayar_rincian`: detail pembayaran per item tagihan, berisi banyak kolom duplikat dari `m_keu_siswa` dan `m_siswa`.

#### `kurmer_nilai_asesmen_formatif` & `_detail`

- Menyimpan nilai formatif per siswa per mapel.
- `_detail` menyimpan capaian per **Tujuan Pembelajaran (TP)** dengan nilai `Tercapai`/`Belum`.
- Terdapat duplikasi data `siswa_kd`, `siswa_nis`, `siswa_nama`, `kode`, `nama`, `kktp` di setiap baris detail.

#### `kurmer_nilai_asesmen_sumatif` & `_detail`

- Menyimpan nilai sumatif dengan `lm_na` (NA lingkup materi), `as_non_tes`, `as_tes`, `as_na`, `nil_raport`.
- Detail menyimpan nilai per **Lingkup Materi (LM)**.

#### `siswa_pelanggaran` — Pelanggaran Siswa

| Kolom | Tipe | Fungsi |
| --- | --- | --- |
| `kd` | varchar(50) PK | Hash MD5 |
| `tapel_nama`, `kelas_nama` | varchar | Data tapel/kelas siswa |
| `siswa_kd`, `siswa_nis`, `siswa_nama` | varchar | Identifikasi siswa |
| `tgl` | date | Tanggal kejadian |
| `jenis_kd`, `jenis_kode`, `jenis_nama` | varchar | Jenis pelanggaran |
| `point_kd`, `point_kode`, `point_nama`, `point_nilai`, `point_sanksi` | varchar | Detail pelanggaran & poin |
| `bina_kd`, `bina_nama`, `bina_ket`, `bina_tgl` | varchar | Pembinaan yang diberikan |
| `sahya` | enum | Status validasi |

#### `user_presensi`, `user_absensi`, `user_ijin`

- `user_presensi`: catatan kehadiran (datang/pulang) dengan field `telat_ket`.
- `user_absensi`: absensi sakit/ijin/alpha per hari.
- `user_ijin`: perizinan masuk/pulang dengan status `IJIN MASUK` / `IJIN PULANG`.
- Ketiga tabel menyimpan `user_kd`, `user_kode`, `user_nama`, `user_jabatan`, `user_kelas` (denormalisasi).

### 4.4. Temuan Kritis Database

| No | Temuan | Dampak | Rekomendasi Blueprint |
| --- | --- | --- | --- |
| 1 | **MyISAM** engine | Tidak ada transaction, row-level locking, FK | Gunakan **InnoDB** |
| 2 | **Tidak ada Foreign Key** | Integritas data tidak terjamin | Definisikan FK dengan `ON DELETE/UPDATE` |
| 3 | Primary key `kd` berupa **varchar(50)** hash MD5 | Sulit di-query, tidak auto-increment | Gunakan **BIGINT UNSIGNED AUTO_INCREMENT** |
| 4 | **Numerik disimpan sebagai varchar** (`nominal`, `nilai`, `point`, `luas`, `harga`) | Tidak bisa kalkulasi akurat, sorting error | Gunakan **DECIMAL/INT** sesuai domain |
| 5 | **Denormalisasi tinggi** (nama/kelas/tapel di-embed di tabel transaksi) | Duplikasi data, update berantai | Normalisasi hingga 3NF, gunakan FK |
| 6 | **Tidak ada tabel audit/perubahan** selain log login & entri | Sulit trace siapa mengubah nilai/keuangan | Tambah `audit_logs` dengan `old_value`/`new_value` |
| 7 | **Tidak ada soft delete** | Data terhapus permanen | Tambah `deleted_at` pada setiap tabel |
| 8 | **FULLTEXT index** pada kolom nama/nominal | Tidak optimal untuk pencarian sederhana | Gunakan composite index & search engine jika perlu |
| 9 | **Tidak ada tabel konfigurasi/setting** | Tahun ajaran aktif hardcoded di session | Buat tabel `settings` dengan key-value |
| 10 | **Tidak ada tabel orang tua** | Data wali siswa tersebar di `m_siswa.passwordx_ortu` | Buat tabel `orang_tua` dan relasi `siswa_orang_tua` |

---

## 5. Business Flow & Logic

### 5.1. Alur Umum Sistem (High-Level)

```mermaid
flowchart TD
    A[Login Multi-Role] --> B[Dashboard Role]
    B --> C[Master Data]
    B --> D[Akademik]
    B --> E[Keuangan]
    B --> F[Disiplin & Prestasi]
    B --> G[Presensi & Izin]
    B --> H[Inventaris]
    B --> I[Laporan & Raport]
    C --> C1[Tapel, Kelas, Ruang, Mapel, Pegawai, Siswa]
    D --> D1[Jadwal -> Jurnal -> Asesmen -> Raport]
    E --> E1[Item -> Tagihan -> Bayar -> Kwitansi -> Laporan]
    F --> F1[Jenis -> Pelanggaran -> Pembinaan / Prestasi]
    G --> G1[Set Waktu -> QR Scan -> Presensi -> Absensi -> Izin]
    H --> H1[KIB A-F -> KIR -> Rekap]
```

### 5.2. Akademik: Dari Jadwal sampai Raport

```mermaid
flowchart LR
    A[Master Mapel per Kelas/TAPIL] --> B[Set Jadwal: hari, jam, kelas, mapel, guru]
    B --> C[Guru Mengajar: Agenda & Absensi Mapel]
    C --> D[Input TP/LM Mapel]
    D --> E[Asesmen Formatif per TP]
    E --> F[Asesmen Sumatif per LM]
    F --> G[Nilai Proyek & Proses]
    G --> H[Wali Kelas: Cetak Raport Asesmen]
    H --> I[Kepala Sekolah: Validasi & Cetak Massal]
```

**Logic yang terdeteksi:**

- Penilaian Kurikulum Merdeka menggunakan **TP (Tujuan Pembelajaran)** dan **LM (Lingkup Materi)**.
- Nilai formatif bersifat **kualitatif Tercapai/Belum** per TP.
- Nilai sumatif bersifat **kuantitatif** (0–100) dengan komponen: non-tes, tes, NA.
- Proyek dinilai per **dimensi/elemen/sub-elemen/capaian fase**.
- Raport dicetak dalam dua format: **Raport Asesmen** dan **Raport Proyek**.
- Data nilai lama (siswa_nilai_bln, siswa_nilai_smt, siswa_nilai_thn) masih ada, mengindikasasi adanya model penilaian lama yang mungkin masih digunakan sebagai cadangan.

### 5.3. Keuangan: SPP, Infaq, dan Tabungan

```mermaid
flowchart LR
    A[Admin: Buat Item Pembayaran<br>tapel+smt+kelas+thn+bln+nama+nominal] --> B[Generate Tagihan per Siswa]
    B --> C[Siswa_memiliki_tagihan dengan nominal_kurang]
    C --> D[Bendahara: Input Nota Pembayaran]
    D --> E[Hitung: terbayar = lama + bayar_baru]
    E --> F[kurang = nominal - terbayar]
    F --> G{kurang == 0?}
    G -->|Ya| H[Status LUNAS]
    G -->|Tidak| I[Status BELUM LUNAS]
    H --> J[Simpan rincian ke siswa_bayar_rincian]
    I --> J
    J --> K[Cetak Kwitansi / Laporan]
```

**Logic yang terdeteksi:**

- Tagihan di-generate ulang setiap kali pembukaan nota baru (`nota.php?s=baru`) dengan `DELETE` lalu `INSERT` untuk siswa bersangkutan.
- Setiap item pembayaran diidentifikasi dengan hash `md5(tapel+smt+kelas+thn+bln+nama)`.
- Nominal disimpan sebagai `varchar`, perhitungan dilakukan dengan `round()` di PHP.
- Ada fitur **tabungan siswa** dengan set debet/kredit/saldo.

### 5.4. Disiplin: Pelanggaran → Pembinaan

```mermaid
flowchart LR
    A[Master Jenis Pelanggaran] --> B[Master Point Pelanggaran<br>+ sanksi + nilai poin]
    B --> C[BK/Piket: Entri Pelanggaran per Siswa]
    C --> D[Hitung Total Point per Siswa]
    D --> E[BK: Pembinaan Siswa<br>bina_kd diisi ke pelanggaran]
    E --> F[Update counter di m_siswa]
    F --> G[Cetak Surat Panggilan / Notifikasi WA]
```

**Logic yang terdeteksi:**

- `m_bk_point` memiliki `point` (varchar) dan `sanksi`.
- `siswa_pelanggaran` menyimpan snapshot data jenis & point (denormalisasi).
- Jika sudah dibina, `bina_kd` diisi; jika `NULL`, berarti belum dibina.
- Terdapat fitur kirim notifikasi WhatsApp ke orang tua saat pelanggaran tercatat.

### 5.5. Presensi & Izin

```mermaid
flowchart LR
    A[Admin: Set Waktu Masuk & Pulang] --> B[Petugas Piket: Scan QR Code]
    B --> C[Entri Presensi Kehadiran/Pulang]
    C --> D[Hitung Keterlambatan]
    D --> E[Laporan Terlambat & Presensi]
    B --> F[Entri Absensi: Sakit/Ijin/Alpha]
    B --> G[Entri Ijin Masuk/Pulang]
    G --> H[Cetak Surat Ijin dengan QR]
```

**Logic yang terdeteksi:**

- Presensi menggunakan QR code yang tercetak di kartu siswa/pegawai.
- Ada mode **manual** jika QR tidak bisa discan.
- `user_presensi` menyimpan waktu postdate dan keterlambatan (`telat_ket`).
- `user_absensi` menyimpan status `Sakit`, `Ijin`, `Alpha`.
- `user_ijin` memiliki status `IJIN MASUK` atau `IJIN PULANG` dan bisa dicetak sebagai surat izin.

### 5.6. Inventaris (Sarpras)

- Mengikuti klasifikasi pemerintah: **KIB A (Tanah), B (Peralatan & Mesin), C (Gedung & Bangunan), D (Jalan/Irigasi/Jaringan), E (Aset Tetap Lainnya), F (Konstruksi Dalam Pengerjaan)**.
- Tabel `m_kib_kode` menyimpan kode barang sesuai klasifikasi.
- Tabel `inv_kib_a` s/d `inv_kib_f` menyimpan aset per kategori.
- Ada tabel `m_kib_jenis` untuk jenis aset.

### 5.7. Jurnal Mengajar

- Guru melihat jadwal mengajar (`admgr/pm/entri.php`).
- Setiap jadwal memiliki link **DETAIL** ke agenda per kelas/mapel.
- `rev_guru_agenda` menyimpan: tanggal, jam, pertemuan ke, indikator, catatan, tindak lanjut, daftar siswa absen, catatan wali kelas.
- `rev_guru_absensi` menyimpan absensi per siswa per pertemuan mapel.

---

## 6. Domain Knowledge (Konteks Pendidikan Indonesia)

| Istilah | Makna dalam Sistem |
| --- | --- |
| **TAPIL / TAP** | Tahun Pelajaran, misalnya 2026/2027 |
| **SMT** | Semester (1 atau 2) |
| **KKM** | Kriteria Ketuntasan Minimal |
| **TP** | Tujuan Pembelajaran (Kurikulum Merdeka) |
| **LM** | Lingkup Materi (Kurikulum Merdeka) |
| **NA** | Nilai Akhir |
| **PAT** | Penilaian Akhir Tahun / PAS (Penilaian Akhir Semester) |
| **KIB** | Kartu Inventaris Barang |
| **KIR** | Kartu Inventaris Ruangan |
| **RPP** | Rencana Pelaksanaan Pembelajaran |
| **Silabus** | Silabus mata pelajaran |
| **Piket** | Petugas piket harian yang mengawasi kehadiran dan kedisiplinan |
| **SPP** | Sumbangan Suka Rela/Pembayaran bulanan siswa |
| **Infaq** | Pembayaran infaq/wakaf sekolah |
| **Tabungan Siswa** | Tabungan internal siswa di sekolah |

---

## 7. Analisis Kritis (SWOT + Risk)

### 7.1. Strengths (Kekuatan)

- Fitur **lengkap** untuk sekolah Indonesia: akademik, keuangan, disiplin, presensi, inventaris.
- Sudah **teruji** di banyak sekolah (versi lama SISFOKOL).
- UI menggunakan **AdminLTE** yang familiar.
- Mendukung **Kurikulum Merdeka** dengan TP/LM/Proyek.
- Ada **import/export Excel** untuk migrasi data cepat.

### 7.2. Weaknesses (Kelemahan)

| No | Kelemahan | Detail |
| --- | --- | --- |
| 1 | **Arsitektur monolitik prosedural** | PHP native, HTML/PHP/SQL bercampur, tidak ada layer bisnis |
| 2 | **Keamanan sangat lemah** | MD5, SQL injection, XSS, CSRF, no HTTPS |
| 3 | **Database tidak normal** | MyISAM, no FK, numerik varchar, denormalisasi tinggi |
| 4 | **Tidak ada API** | Tidak bisa integrasi dengan aplikasi mobile atau layanan eksternal |
| 5 | **Tidak ada testing** | Tidak ada unit test, integration test, UAT form |
| 6 | **Kode duplikat** | Paging, warna tabel, koneksi, validasi diulang di setiap file |
| 7 | **Mobile experience buruk** | Sidebar desktop, form tidak responsif optimal |
| 8 | **Internationalization tidak ada** | Hanya Bahasa Indonesia, sulit dilokalkan |
| 9 | **Tidak ada cache/queue** | Proses berat (laporan, export) langsung di request |
| 10 | **Maintenance sulit** | Update satu modul bisa merusak modul lain |

### 7.3. Opportunities (Peluang)

- Adopsi **framework modern** (Laravel) untuk meningkatkan kecepatan pengembangan.
- Integrasi dengan **Dapodik Kemendikbudristek** untuk sinkron data siswa.
- Penggunaan **WhatsApp Gateway** resmi untuk notifikasi ortu.
- Pengembangan **mobile app** berbasis API untuk siswa/orang tua.
- Implementasi **e-learning** dan **bank soal** (sudah ada dasar `siswa_soal`).

### 7.4. Threats (Ancaman)

- **Kebocoran data** akibat kelemahan keamanan.
- **Kehilangan data** karena tidak ada transaction/backup strategy di kode.
- **Kesulitan legal/compliance** terkait perlindungan data pribadi siswa.
- **Skalability** buruk jika jumlah siswa > 1000.

### 7.5. Risk Matrix

| Risiko | Likelihood | Impact | Level | Mitigasi |
| --- | --- | --- | --- | --- |
| SQL Injection | Tinggi | Sangat Tinggi | Kritis | Gunakan prepared statement / ORM |
| Password breach | Tinggi | Tinggi | Tinggi | Hash bcrypt + salt + password policy |
| Data corruption | Sedang | Tinggi | Tinggi | InnoDB + FK + transaction + backup |
| Server overload | Sedang | Sedang | Sedang | Queue, cache, pagination server-side |
| Vendor lock-in | Sedang | Sedang | Sedang | Dokumentasi, open standard, API |

---

## 8. Blueprint: Pembangunan Ulang dari Awal

### 8.1. Visi Sistem Baru

> Membangun **Sistem Informasi Sekolah SMP Islam Terpadu** yang **modular, aman, skalabel, dan berbasis API**, mengintegrasikan akademik, keuangan, kedisiplinan, presensi, dan inventaris dalam satu platform dengan pengalaman pengguna yang modern.

### 8.2. Arsitektur Target

```mermaid
flowchart TB
    subgraph "Presentation Layer"
        A1[Web Portal AdminLTE/Bootstrap]
        A2[Mobile App / PWA]
        A3[Public Website Sekolah]
    end
    subgraph "API Gateway"
        B[Nginx + Laravel API]
    end
    subgraph "Application Layer"
        C1[Auth Service]
        C2[Academic Service]
        C3[Finance Service]
        C4[Discipline Service]
        C5[Presence Service]
        C6[Inventory Service]
        C7[Report Service]
        C8[Notification Service]
    end
    subgraph "Data Layer"
        D1[(MySQL 8 InnoDB)]
        D2[Redis Cache/Queue]
        D3[MinIO/S3 File Storage]
        D4[Elasticsearch (opsional)]
    end
    A1 --> B
    A2 --> B
    A3 --> B
    B --> C1 --> D1
    C2 --> D1
    C3 --> D1
    C4 --> D1
    C5 --> D1
    C6 --> D1
    C7 --> D1
    C8 --> D2
    C7 --> D3
```

### 8.3. Teknologi Rekomendasi

| Layer | Teknologi | Alasan |
| --- | --- | --- |
| Backend | **Laravel 10/11** | MVC, Eloquent ORM, migration, routing, queue, test |
| Database | **MySQL 8 / MariaDB 10.6** | InnoDB, JSON column, window functions |
| Cache/Queue | **Redis** | Session, cache, queue job |
| Search | **Meilisearch / Elasticsearch** | Pencarian cepat |
| Frontend | **Laravel Blade + Bootstrap 5 + Vue.js 3** | Progressive enhancement |
| Mobile | **Flutter / PWA** | Akses siswa/orang tua |
| File Storage | **MinIO / AWS S3** | RPP, silabus, kwitansi PDF |
| Report | **Laravel Excel + DomPDF / Snappy** | Export & cetak |
| Notification | **Mailgun/SMTP + WhatsApp Official API** | Notifikasi ortu |
| Container | **Docker + Docker Compose** | Dev/prod parity |
| CI/CD | **GitHub Actions / GitLab CI** | Automated testing & deployment |

### 8.4. Database Design Baru (Normalisasi)

#### Prinsip Utama

1. Gunakan **InnoDB** dengan **Foreign Key**.
2. Primary key **BIGINT UNSIGNED AUTO_INCREMENT**.
3. UUID/ULID opsional untuk public identifier.
4. Numerik: `DECIMAL(12,2)` untuk uang, `TINYINT/INT` untuk nilai.
5. Soft delete `deleted_at` di setiap tabel.
6. Audit log `created_by`, `updated_by`, `created_at`, `updated_at`.
7. Tabel terpisah untuk **orang tua/wali**.
8. Relasi many-to-many dengan tabel pivot.

#### ERD Target (Core)

```mermaid
erDiagram
    TAHUN_AJARAN ||--o{ SEMESTER : "memiliki"
    TAHUN_AJARAN ||--o{ SISWA : "aktif"
    TINGKAT ||--o{ KELAS : "memiliki"
    KELAS ||--o{ SISWA : "berisi"
    KELAS ||--o{ WALIKELAS : "diwali"
    GURU ||--o{ WALIKELAS : "menjadi"
    GURU ||--o{ MATA_PELAJARAN : "mengajar"
    GURU ||--o{ JADWAL : "mengajar"
    MATA_PELAJARAN ||--o{ JADWAL : "dijadwalkan"
    MATA_PELAJARAN ||--o{ TUJUAN_PEMBELAJARAN : "memiliki"
    MATA_PELAJARAN ||--o{ LINGKUP_MATERI : "memiliki"
    SISWA ||--o{ NILAI_FORMATIF : "dinilai"
    SISWA ||--o{ NILAI_SUMATIF : "dinilai"
    SISWA ||--o{ NILAI_PROYEK : "dinilai"
    SISWA ||--o{ TAGIHAN : "memiliki"
    SISWA ||--o{ PEMBAYARAN : "membayar"
    SISWA ||--o{ PRESENSI : "hadir"
    SISWA ||--o{ ABSENSI : "absen"
    SISWA ||--o{ PELANGGARAN : "melanggar"
    SISWA ||--o{ PRESTASI : "berprestasi"
    SISWA ||--o{ EKSTRAKURIKULER : "mengikuti"
    ORANG_TUA ||--o{ SISWA_ORANG_TUA : "memiliki"
    SISWA ||--o{ SISWA_ORANG_TUA : "memiliki"
    KATEGORI_PELANGGARAN ||--o{ PELANGGARAN : "mengelompokkan"
    ASET ||--o{ KIB_A : "kategori"
    ASET ||--o{ KIB_B : "kategori"
    ASET ||--o{ KIB_C : "kategori"
    ASET ||--o{ KIB_D : "kategori"
    ASET ||--o{ KIB_E : "kategori"
    ASET ||--o| KIB_F : "kategori"
```

### 8.5. Modul Re-Design

| Modul | Perubahan Utama |
| --- | --- |
| **Autentikasi** | Login email/NIS/NIP + password bcrypt, MFA opsional, forgot password, role-based policies (Spatie Permission), session Redis |
| **Master Data** | CRUD dengan validation, import Excel, unique constraints, histori perubahan |
| **Akademik** | Mapel -> TP/LM -> Asesmen -> Sumatif -> Proyek -> Raport otomatis dengan deskripsi raport generator |
| **Jadwal** | Algoritma penjadwalan dengan cek bentrok guru/ruang/kelas |
| **Keuangan** | Tagihan otomatis per siswa, partial payment, kwitansi PDF, laporan real-time, integrasi gateway (opsional) |
| **Disiplin** | Workflow pelanggaran -> pembinaan -> notifikasi ortu -> laporan point |
| **Presensi** | QR/RFID/Face recognition ready, cek keterlambatan otomatis, izin online dengan approval |
| **Inventaris** | Klasifikasi KIB A–F, pergerakan aset, depresiasi sederhana |
| **Laporan** | Dashboard interactive, drill-down, scheduled reports |
| **Filebox** | Dokumen management dengan kategori, version, approval |

### 8.6. Security Hardening

| Aspek | Implementasi |
| --- | --- |
| Password | `bcrypt` dengan cost ≥ 12, password policy, reset via signed URL |
| SQL Injection | **Eloquent ORM** / Query Builder, **prepared statements** |
| XSS | Output escaping, Content Security Policy |
| CSRF | Laravel CSRF token |
| Session | Redis, `secure`, `httponly`, `samesite`, timeout 30 menit |
| RBAC | Spatie Laravel Permission, Gate/Policy per resource |
| Audit | Middleware audit log: who, what, old/new value, timestamp |
| HTTPS | Wajib di production, HSTS header |
| Rate Limit | Throttle login & API endpoints |
| File Upload | Validasi tipe, size, storage private, virus scan (opsional) |

### 8.7. Migration Strategy (Data Lama → Sistem Baru)

```mermaid
flowchart LR
    A[Backup DB SISFOKOL v7] --> B[Data Cleansing & Deduplication]
    B --> C[Mapping Skema Lama ke Baru]
    C --> D[ETL Script Python/Laravel Seeder]
    D --> E[Staging Environment]
    E --> F[Validasi Data & Reconciliation]
    F --> G[UAT dengan Stakeholder]
    G --> H[Go-Live Fase 1]
    H --> I[Parallel Run 1 Semester]
```

**Langkah Detail:**

1. **Backup penuh** database dan file upload.
2. **Data cleansing**: perbaiki NIS/NIP duplikat, normalisasi kelas/tapel, konversi varchar→decimal.
3. **Mapping tabel**: buat dokumen mapping dari 75 tabel lama ke tabel baru.
4. **ETL script**: baca data lama, transformasi, insert ke DB baru dengan transaction.
5. **Rekonsiliasi**: bandingkan jumlah siswa, guru, total keuangan, total nilai.
6. **UAT**: simulasi 1 semester penuh.
7. **Go-live**: switch DNS, matikan sistem lama setelah stabil.

### 8.8. Roadmap Implementasi (6 Fase)

| Fase | Durasi | Deliverable |
| --- | --- | --- |
| **1. Discovery & Blueprint** | 4 minggu | Dokumen ini, SRS, arsitektur, ERD |
| **2. Core Platform** | 6 minggu | Auth, RBAC, master data, migration tool |
| **3. Akademik & Raport** | 8 minggu | Jadwal, penilaian Kurmer, raport |
| **4. Keuangan & Disiplin** | 6 minggu | Keuangan, tabungan, pelanggaran, prestasi |
| **5. Presensi & Inventory** | 5 minggu | QR presensi, absensi, izin, KIB |
| **6. Deployment & UAT** | 5 minggu | Staging, UAT, training, go-live, monitoring |

**Total estimasi:** 26–30 minggu (7–8 bulan).

### 8.9. Saran untuk SMP Islam Terpadu

1. **Jangan memodifikasi SISFOKOL v7 secara besar-besaran** untuk kebutuhan produksi; biaya maintenance akan lebih tinggi dari rebuild.
2. Gunakan SISFOKOL sebagai **referensi fitur dan alur bisnis**, bukan fondasi kode.
3. Prioritaskan **modul akademik dan keuangan** untuk go-live pertama, karena paling kritis.
4. Libatkan **TU, Bendahara, Wali Kelas, dan Guru** sejak tahap blueprint untuk validasi alur.
5. Rencanakan **pelatihan dan change management** minimal 4 sesi sebelum go-live.
6. Tetapkan **kebijakan keamanan data** (siapa yang boleh akses nilai, keuangan, presensi).

---

## 9. Kesimpulan & Rekomendasi

SISFOKOL v7.00 adalah sistem yang **kaya fitur** dan **sangat relevan** dengan kebutuhan operasional sekolah Indonesia. Namun, dari sisi **teknik, keamanan, dan arsitektur**, sistem ini sudah **usang** dan mengandung risiko signifikan.

| Rekomendasi | Prioritas |
| --- | --- |
| Lakukan **rebuild total** dengan Laravel/modern PHP framework | Tinggi |
| Desain ulang database dengan **InnoDB + FK + normalisasi** | Tinggi |
| Implementasi **keamanan modern** (bcrypt, HTTPS, RBAC, audit log) | Tinggi |
| Buat **API REST** untuk mobile dan integrasi | Sedang |
| Susun **test automation** (unit, feature, UAT) | Tinggi |
| Rencanakan **migrasi data** bertahap dan UAT ketat | Tinggi |
| Dokumentasi **user manual & SOP** per role | Sedang |

Dengan pendekatan blueprint di atas, SMP Islam Terpadu akan memiliki sistem informasi yang **siap digunakan jangka panjang**, **mudah dikembangkan**, dan **aman untuk data siswa serta keuangan sekolah**.

---

## 10. Lampiran

*Lampiran berikut di-generate otomatis dari inspeksi repositori dan dump SQL.*

### 10.1. Daftar Lengkap 75 Tabel

> Akan diisi oleh script `append_analisis.py` dari `schema_sisfokol_v7.json`.

### 10.2. Daftar Menu per Role (Template)

> Akan diisi oleh script `append_analisis.py` dari `menu_extract.txt`.

### 10.3. Referensi File

- `schema_sisfokol_v7.md` — Skema lengkap seluruh tabel dan kolom.
- `schema_sisfokol_v7.json` — Representasi JSON skema untuk tooling.
- `menu_extract.txt` — Ekstraksi menu dari template HTML.
- `sisfokol-v7.00-code-smartoffice/` — Kode sumber referensi.


---

### 10.1. Daftar Lengkap Tabel per Kategori

| Kategori | Jumlah | Tabel |
| --- | --- | --- |
| Master Data & Autentikasi | 24 | `a_profil`, `adminx`, `m_user`, `m_siswa`, `m_tapel`, `m_kelas`, `m_hari`, `m_jam`, `m_ruang`, `m_pegawai`, `m_mapel`, `m_mapel_jns`, `m_mapel_deskripsi`, `m_gurubk`, `m_walikelas`, `m_bendahara`, `m_sarpras`, `m_ks`, `m_piket`, `m_ekstra`, `m_waktu`, `m_waktu_jadwal`, `m_keu_siswa`, `m_pembinaan` |
| Akademik, Penilaian & Raport | 26 | `jadwal`, `kurmer_mapel_tp`, `kurmer_mapel_lm`, `kurmer_asesmen_formatif`, `kurmer_nilai_asesmen_formatif`, `kurmer_nilai_asesmen_formatif_detail`, `kurmer_nilai_asesmen_sumatif`, `kurmer_nilai_asesmen_sumatif_detail`, `kurmer_proyek`, `kurmer_proyek_detail`, `kurmer_nilai_proyek`, `kurmer_nilai_proyek_proses`, `siswa_nilai_bln`, `siswa_nilai_smt`, `siswa_nilai_thn`, `siswa_mapel_absensi`, `siswa_soal`, `siswa_tugas`, `siswa_soal_nilai`, `siswa_saran`, `siswa_raport_catatan`, `siswa_raport_sikap`, `siswa_raport_kenaikan`, `siswa_raport_rangking`, `rev_guru_agenda`, `rev_guru_absensi` |
| Keuangan Siswa | 4 | `siswa_bayar`, `siswa_bayar_tagihan`, `siswa_bayar_rincian`, `wa_tagihan_siswa` |
| Disiplin, Prestasi & BK | 5 | `m_bk_point_jenis`, `m_bk_point`, `m_bk_prestasi`, `siswa_pelanggaran`, `siswa_prestasi` |
| Presensi, Absensi & Izin | 4 | `user_presensi`, `user_absensi`, `user_ijin`, `user_piket` |
| Inventaris & Aset | 8 | `m_kib_jenis`, `m_kib_kode`, `inv_kib_a`, `inv_kib_b`, `inv_kib_c`, `inv_kib_d`, `inv_kib_e`, `inv_kib_f` |
| Filebox & Log | 3 | `user_filebox`, `user_log_login`, `user_log_entri` |
| Ekstrakurikuler | 1 | `siswa_ekstra` |

#### 10.1.1. Detail Kolom per Tabel (75 Tabel)

Berikut ringkasan kolom setiap tabel. Untuk detail lengkap, lihat `schema_sisfokol_v7.md`.

##### Master Data & Autentikasi

**a_profil** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `postdate` | datetime NOT NULL |
| `lat_x` | longtext NOT NULL |
| `lat_y` | longtext NOT NULL |
| `alamat_googlemap` | longtext NOT NULL |

**adminx** (3 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `usernamex` | varchar(100) DEFAULT NULL |
| `passwordx` | varchar(100) DEFAULT NULL |

**m_user** (20 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `usernamex` | varchar(100) DEFAULT NULL |
| `passwordx` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nomor` | varchar(100) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `jabatan` | varchar(100) DEFAULT NULL |
| `tipe` | varchar(100) DEFAULT NULL |
| `nowa` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `qrcode` | varchar(100) DEFAULT NULL |
| `postdate_last_login` | datetime DEFAULT NULL |
| `jml_hadir` | varchar(5) DEFAULT NULL |
| `jml_telat` | varchar(5) DEFAULT NULL |
| `tapel_kd` | varchar(50) DEFAULT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_kd` | varchar(50) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |

**m_siswa** (25 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `usernamex` | varchar(100) DEFAULT NULL |
| `passwordx` | varchar(100) DEFAULT NULL |
| `kode` | varchar(50) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `passwordx_ortu` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `nourut` | varchar(5) DEFAULT NULL |
| `qrcode` | varchar(100) DEFAULT NULL |
| `jml_ekstra` | varchar(5) DEFAULT NULL |
| `jml_absen_sakit` | varchar(5) DEFAULT NULL |
| `jml_absen_ijin` | varchar(5) DEFAULT NULL |
| `jml_absen_alpha` | varchar(5) DEFAULT NULL |
| `subtotal_nominal` | varchar(15) DEFAULT NULL |
| `subtotal_setor` | varchar(15) DEFAULT NULL |
| `subtotal_belum` | varchar(15) DEFAULT NULL |
| `nowa` | varchar(100) DEFAULT NULL |
| `jml_pelanggaran` | varchar(5) DEFAULT NULL |
| `subtotal_pelanggaran` | varchar(5) DEFAULT NULL |
| `jml_presensi` | varchar(5) DEFAULT NULL |
| `jml_prestasi` | varchar(5) DEFAULT NULL |
| `subtotal_prestasi` | varchar(5) DEFAULT NULL |
| `subtotal_akhir` | varchar(5) DEFAULT NULL |

**m_tapel** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `aktif` | enum('true','false') DEFAULT 'false' |

**m_kelas** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | char(1) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_hari** (3 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | char(1) DEFAULT NULL |
| `hari` | varchar(100) DEFAULT NULL |

**m_jam** (2 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `jam` | char(2) DEFAULT NULL |

**m_ruang** (4 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | varchar(10) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_pegawai** (13 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `usernamex` | longtext DEFAULT NULL |
| `passwordx` | longtext DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `jabatan` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `jml_absen_sakit` | varchar(5) DEFAULT '0' |
| `jml_absen_ijin` | varchar(5) DEFAULT '0' |
| `jml_absen_alpha` | varchar(5) DEFAULT '0' |
| `jml_mengajar` | varchar(5) DEFAULT '0' |
| `nowa` | varchar(100) DEFAULT NULL |
| `jml_presensi` | varchar(5) DEFAULT NULL |

**m_mapel** (20 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `kkm` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `pegawai_kd` | varchar(50) DEFAULT NULL |
| `pegawai_kode` | varchar(100) DEFAULT NULL |
| `pegawai_nama` | varchar(100) DEFAULT NULL |
| `rpp_postdate` | datetime DEFAULT NULL |
| `rpp_acc` | enum('true','false') DEFAULT 'false' |
| `rpp_acc_postdate` | datetime DEFAULT NULL |
| `rpp_acc_ket` | longtext DEFAULT NULL |
| `silabus_postdate` | datetime DEFAULT NULL |
| `silabus_acc` | enum('true','false') DEFAULT 'false' |
| `silabus_acc_postdate` | datetime DEFAULT NULL |
| `silabus_acc_ket` | longtext DEFAULT NULL |

**m_mapel_jns** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | varchar(1) DEFAULT NULL |
| `no_sub` | varchar(5) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_mapel_deskripsi** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `kode` | varchar(50) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `smt1_p_isi` | longtext DEFAULT NULL |
| `smt1_k_isi` | longtext DEFAULT NULL |
| `smt2_p_isi` | longtext DEFAULT NULL |
| `smt2_k_isi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_gurubk** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_walikelas** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel_kd` | varchar(50) DEFAULT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_kd` | varchar(100) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_bendahara** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_sarpras** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_ks** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_piket** (8 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `usernamex` | varchar(100) DEFAULT NULL |
| `passwordx` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `jabatan` | varchar(100) DEFAULT NULL |
| `qrcode` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_ekstra** (6 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `nama` | varchar(100) DEFAULT NULL |
| `pegawai_kd` | varchar(50) DEFAULT NULL |
| `pegawai_kode` | varchar(100) DEFAULT NULL |
| `pegawai_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_waktu** (6 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `masuk_jam` | varchar(2) DEFAULT NULL |
| `masuk_menit` | varchar(2) DEFAULT NULL |
| `pulang_jam` | varchar(2) DEFAULT NULL |
| `pulang_menit` | varchar(2) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_waktu_jadwal** (6 kolom)

| Kolom | Definisi |
| --- | --- |
| `nourut` | varchar(5) NOT NULL |
| `hari_no` | varchar(10) DEFAULT NULL |
| `hari_nama` | varchar(100) DEFAULT NULL |
| `jam_ke` | varchar(10) DEFAULT NULL |
| `waktu` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |

**m_keu_siswa** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `thn` | varchar(4) DEFAULT NULL |
| `bln` | varchar(2) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `nominal` | varchar(15) DEFAULT '0' |

**m_pembinaan** (5 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `nama` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `pembina_kode` | varchar(100) DEFAULT NULL |
| `pembina_nama` | varchar(100) DEFAULT NULL |

##### Akademik, Penilaian & Raport

**jadwal** (11 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `hari` | varchar(100) DEFAULT NULL |
| `hari_no` | varchar(1) DEFAULT NULL |
| `jam_ke` | varchar(5) DEFAULT NULL |
| `waktu` | varchar(100) DEFAULT NULL |
| `mapel_kode` | varchar(50) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime NOT NULL |

**kurmer_mapel_tp** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `tp_kode` | varchar(5) DEFAULT NULL |
| `tp_nama` | longtext DEFAULT NULL |

**kurmer_mapel_lm** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `smt` | varchar(5) DEFAULT NULL |
| `lm_kode` | varchar(5) DEFAULT NULL |
| `lm_nama` | longtext DEFAULT NULL |

**kurmer_asesmen_formatif** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `desk_tinggi` | longtext DEFAULT NULL |
| `desk_rendah` | longtext DEFAULT NULL |

**kurmer_nilai_asesmen_formatif** (13 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `kktp` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(10) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `desk_tinggi` | longtext DEFAULT NULL |
| `desk_rendah` | longtext DEFAULT NULL |

**kurmer_nilai_asesmen_formatif_detail** (14 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `kktp` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(10) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tp_kode` | varchar(5) DEFAULT NULL |
| `tp_nama` | longtext DEFAULT NULL |
| `tp_nilai` | varchar(100) DEFAULT NULL |

**kurmer_nilai_asesmen_sumatif** (16 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `kktp` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(10) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `lm_na` | varchar(5) DEFAULT NULL |
| `as_non_tes` | varchar(5) DEFAULT NULL |
| `as_tes` | varchar(5) DEFAULT NULL |
| `as_na` | varchar(5) DEFAULT NULL |
| `nil_raport` | varchar(5) DEFAULT NULL |

**kurmer_nilai_asesmen_sumatif_detail** (14 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `kktp` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(10) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `lm_kode` | varchar(5) DEFAULT NULL |
| `lm_nama` | longtext DEFAULT NULL |
| `lm_nilai` | varchar(5) DEFAULT '0' |

**kurmer_proyek** (7 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `no` | varchar(1) DEFAULT NULL |
| `judul` | longtext DEFAULT NULL |
| `isi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**kurmer_proyek_detail** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `proyek_no` | varchar(1) DEFAULT NULL |
| `proyek_judul` | longtext DEFAULT NULL |
| `proyek_isi` | longtext DEFAULT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `dimensi` | longtext DEFAULT NULL |
| `elemen` | longtext DEFAULT NULL |
| `sub_elemen` | longtext DEFAULT NULL |
| `capaian_fase` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**kurmer_nilai_proyek** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `proyek_kode` | varchar(5) DEFAULT NULL |
| `dimensi_kode` | varchar(5) DEFAULT NULL |
| `siswa_kode` | varchar(50) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `nilai` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**kurmer_nilai_proyek_proses** (8 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `proyek_kode` | varchar(5) DEFAULT NULL |
| `siswa_kode` | varchar(50) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `catatan` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**siswa_nilai_bln** (17 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `mapel_no` | varchar(5) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `thn` | varchar(4) DEFAULT NULL |
| `bln` | varchar(2) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nilai` | varchar(5) DEFAULT '0' |
| `kategori` | varchar(100) DEFAULT NULL |
| `postdate` | datetime NOT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**siswa_nilai_smt** (26 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `mapel_no` | varchar(5) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `p_bln_rata` | varchar(5) DEFAULT NULL |
| `k_bln_rata` | varchar(5) DEFAULT NULL |
| `p_ph_nilai` | varchar(5) DEFAULT NULL |
| `k_ph_nilai` | varchar(5) DEFAULT NULL |
| `p_pts_nilai` | varchar(5) DEFAULT NULL |
| `k_pts_nilai` | varchar(5) DEFAULT NULL |
| `p_pas_nilai` | varchar(5) DEFAULT NULL |
| `k_pas_nilai` | varchar(5) DEFAULT NULL |
| `p_na` | varchar(5) DEFAULT NULL |
| `p_na_pred` | varchar(5) DEFAULT NULL |
| `k_na` | varchar(5) DEFAULT NULL |
| `k_na_pred` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |
| `p_isi` | longtext DEFAULT NULL |
| `k_isi` | longtext DEFAULT NULL |

**siswa_nilai_thn** (21 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `mapel_no` | varchar(5) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `p_na_smt1` | varchar(5) DEFAULT NULL |
| `p_na_smt2` | varchar(5) DEFAULT NULL |
| `k_na_smt1` | varchar(5) DEFAULT NULL |
| `k_na_smt2` | varchar(5) DEFAULT NULL |
| `p_pat_nilai` | varchar(5) DEFAULT NULL |
| `k_pat_nilai` | varchar(5) DEFAULT NULL |
| `p_na` | varchar(5) DEFAULT NULL |
| `k_na` | varchar(5) DEFAULT NULL |
| `postdate` | datetime NOT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |
| `p_na_pred` | varchar(5) DEFAULT NULL |
| `k_na_pred` | varchar(5) DEFAULT NULL |

**siswa_mapel_absensi** (17 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel_kd` | varchar(50) DEFAULT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_kd` | varchar(50) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `mapel_kd` | varchar(50) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `pertemuan` | varchar(2) DEFAULT NULL |
| `tanggal` | date DEFAULT NULL |
| `absensi` | varchar(50) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**siswa_soal** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `kd_guru_mapel` | varchar(50) DEFAULT NULL |
| `jadwal_kd` | varchar(50) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `soal_kd` | varchar(50) DEFAULT NULL |
| `jawab` | varchar(1) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `kunci` | varchar(1) DEFAULT NULL |
| `benar` | enum('true','false') NOT NULL DEFAULT 'false' |

**siswa_tugas** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `kd_guru_mapel` | varchar(50) DEFAULT NULL |
| `tugas_kd` | varchar(50) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `filex` | longtext DEFAULT NULL |
| `nilai` | varchar(10) DEFAULT NULL |
| `nilai_postdate` | datetime DEFAULT NULL |
| `nilai_ket` | longtext DEFAULT NULL |

**siswa_soal_nilai** (14 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `kd_guru_mapel` | varchar(50) DEFAULT NULL |
| `jadwal_kd` | varchar(50) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `jml_benar` | varchar(3) DEFAULT NULL |
| `jml_salah` | varchar(3) DEFAULT NULL |
| `waktu_mulai` | datetime DEFAULT NULL |
| `waktu_proses` | datetime DEFAULT NULL |
| `waktu_akhir` | datetime DEFAULT NULL |
| `skor` | varchar(5) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `waktu_selesai` | datetime DEFAULT NULL |
| `jml_soal_dikerjakan` | varchar(10) DEFAULT NULL |
| `selesai` | enum('true','false') NOT NULL DEFAULT 'false' |

**siswa_saran** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel_kd` | varchar(50) DEFAULT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_kd` | varchar(50) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `saran` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**siswa_raport_catatan** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `isi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**siswa_raport_sikap** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `spiritual_predikat` | varchar(100) DEFAULT NULL |
| `spiritual_isi` | longtext DEFAULT NULL |
| `sosial_predikat` | varchar(100) DEFAULT NULL |
| `sosial_isi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**siswa_raport_kenaikan** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `status` | varchar(100) DEFAULT NULL |
| `baru_tapel` | varchar(100) DEFAULT NULL |
| `baru_kelas` | varchar(100) DEFAULT NULL |
| `postdate` | datetime NOT NULL |

**siswa_raport_rangking** (13 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `total_p` | varchar(5) DEFAULT NULL |
| `rata_p` | varchar(5) DEFAULT NULL |
| `total_k` | varchar(5) DEFAULT NULL |
| `rata_k` | varchar(5) DEFAULT NULL |
| `total` | varchar(5) DEFAULT NULL |
| `rangking` | varchar(2) DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |

**rev_guru_agenda** (21 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `pegawai_kd` | varchar(50) DEFAULT NULL |
| `pegawai_kode` | varchar(100) DEFAULT NULL |
| `pegawai_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `tglnya` | date DEFAULT NULL |
| `jamnya` | longtext DEFAULT NULL |
| `pertemuan_ke` | varchar(5) DEFAULT NULL |
| `namanya` | longtext DEFAULT NULL |
| `indikatornya` | longtext DEFAULT NULL |
| `catatan` | longtext DEFAULT NULL |
| `tindak_lanjut` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `daftar_siswa_absen` | longtext DEFAULT NULL |
| `wk_catatan` | longtext DEFAULT NULL |
| `wk_postdate` | datetime DEFAULT NULL |
| `wk_presensi` | varchar(1) DEFAULT NULL |

**rev_guru_absensi** (17 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `pegawai_kd` | varchar(50) DEFAULT NULL |
| `pegawai_kode` | varchar(100) DEFAULT NULL |
| `pegawai_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(1) DEFAULT NULL |
| `mapel_kode` | varchar(100) DEFAULT NULL |
| `mapel_nama` | varchar(100) DEFAULT NULL |
| `tglnya` | date DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `siswa_kelamin` | varchar(1) DEFAULT NULL |
| `absensi` | varchar(1) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `respon_siswa` | longtext DEFAULT NULL |

##### Keuangan Siswa

**siswa_bayar** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_tapel` | varchar(100) DEFAULT NULL |
| `siswa_kelas` | varchar(100) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `tgl_bayar` | date DEFAULT NULL |
| `nominal_tagihan` | varchar(15) DEFAULT NULL |
| `nominal_bayar` | varchar(15) DEFAULT NULL |
| `nominal_kurang` | varchar(15) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**siswa_bayar_tagihan** (19 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_tapel` | varchar(100) DEFAULT NULL |
| `siswa_kelas` | varchar(100) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `item_kd` | varchar(50) DEFAULT NULL |
| `item_nama` | varchar(100) DEFAULT NULL |
| `item_tapel` | varchar(100) DEFAULT NULL |
| `item_smt` | varchar(100) DEFAULT NULL |
| `item_kelas` | varchar(100) DEFAULT NULL |
| `item_thn` | varchar(4) DEFAULT NULL |
| `item_bln` | varchar(2) DEFAULT NULL |
| `item_nominal` | varchar(15) DEFAULT NULL |
| `nominal_bayar` | varchar(15) DEFAULT NULL |
| `nominal_kurang` | varchar(15) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `lunas_status` | enum('true','false') DEFAULT 'false' |
| `lunas_postdate` | datetime DEFAULT NULL |

**siswa_bayar_rincian** (18 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `bayar_kd` | varchar(50) DEFAULT NULL |
| `bayar_kode` | varchar(100) DEFAULT NULL |
| `bayar_tgl` | date DEFAULT NULL |
| `siswa_tapel` | varchar(100) DEFAULT NULL |
| `siswa_kelas` | varchar(100) DEFAULT NULL |
| `siswa_kode` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `item_kd` | varchar(50) DEFAULT NULL |
| `item_nama` | varchar(100) DEFAULT NULL |
| `item_tapel` | varchar(100) DEFAULT NULL |
| `item_smt` | varchar(100) DEFAULT NULL |
| `item_kelas` | varchar(100) DEFAULT NULL |
| `item_thn` | varchar(4) DEFAULT NULL |
| `item_bln` | varchar(2) DEFAULT NULL |
| `item_nominal` | varchar(15) DEFAULT NULL |
| `nominal_bayar` | varchar(15) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**wa_tagihan_siswa** (8 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `siswa_nis` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `siswa_nowa` | varchar(100) DEFAULT NULL |
| `terkirim` | enum('true','false') DEFAULT 'false' |
| `nominal` | varchar(15) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

##### Disiplin, Prestasi & BK

**m_bk_point_jenis** (3 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `jenis` | varchar(100) NOT NULL |
| `no` | varchar(2) NOT NULL |

**m_bk_point** (8 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `jenis_kd` | varchar(50) DEFAULT NULL |
| `jenis_nama` | varchar(100) DEFAULT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `point` | varchar(5) DEFAULT NULL |
| `sanksi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**m_bk_prestasi** (4 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `point` | varchar(5) DEFAULT NULL |

**siswa_pelanggaran** (26 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tgl` | date NOT NULL |
| `jenis_kd` | varchar(50) DEFAULT NULL |
| `jenis_kode` | varchar(100) DEFAULT NULL |
| `jenis_nama` | longtext DEFAULT NULL |
| `point_kd` | varchar(50) DEFAULT NULL |
| `point_kode` | varchar(50) DEFAULT NULL |
| `point_nama` | longtext DEFAULT NULL |
| `point_nilai` | varchar(10) DEFAULT NULL |
| `point_sanksi` | longtext DEFAULT NULL |
| `postdate` | datetime NOT NULL |
| `piket_kd` | varchar(50) DEFAULT NULL |
| `piket_kode` | varchar(100) DEFAULT NULL |
| `piket_nama` | varchar(100) DEFAULT NULL |
| `piket_jabatan` | varchar(100) DEFAULT NULL |
| `sahya` | enum('true','false') DEFAULT 'false' |
| `sahya_tgl` | datetime DEFAULT NULL |
| `bina_tgl` | date DEFAULT NULL |
| `bina_kd` | varchar(50) DEFAULT NULL |
| `bina_nama` | longtext DEFAULT NULL |
| `bina_ket` | longtext DEFAULT NULL |

**siswa_prestasi** (19 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel_nama` | varchar(100) DEFAULT NULL |
| `kelas_nama` | varchar(100) DEFAULT NULL |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tgl` | date NOT NULL |
| `point_kd` | varchar(50) DEFAULT NULL |
| `point_kode` | varchar(50) DEFAULT NULL |
| `point_nama` | longtext DEFAULT NULL |
| `point_nilai` | varchar(10) DEFAULT NULL |
| `point_ket` | longtext DEFAULT NULL |
| `postdate` | datetime NOT NULL |
| `piket_kd` | varchar(50) DEFAULT NULL |
| `piket_kode` | varchar(100) DEFAULT NULL |
| `piket_nama` | varchar(100) DEFAULT NULL |
| `piket_jabatan` | varchar(100) DEFAULT NULL |
| `sahya` | enum('true','false') NOT NULL DEFAULT 'false' |
| `sahya_tgl` | date DEFAULT NULL |

##### Presensi, Absensi & Izin

**user_presensi** (16 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `user_kelas` | varchar(100) DEFAULT NULL |
| `user_tapel` | varchar(100) DEFAULT NULL |
| `tanggal` | date DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `status` | varchar(100) DEFAULT NULL |
| `ket` | longtext DEFAULT NULL |
| `telat_ket` | varchar(100) DEFAULT NULL |
| `telat_jam` | varchar(5) DEFAULT NULL |
| `telat_menit` | varchar(5) DEFAULT NULL |
| `dibaca` | enum('true','false') DEFAULT 'false' |
| `dibaca_postdate` | datetime DEFAULT NULL |

**user_absensi** (14 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `user_kelas` | varchar(100) DEFAULT NULL |
| `user_tapel` | varchar(100) DEFAULT NULL |
| `tanggal` | date DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `piket_kd` | varchar(50) DEFAULT NULL |
| `piket_kode` | varchar(100) DEFAULT NULL |
| `piket_nama` | varchar(100) DEFAULT NULL |
| `piket_jabatan` | varchar(100) DEFAULT NULL |

**user_ijin** (18 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `user_kelas` | varchar(100) DEFAULT NULL |
| `user_tapel` | varchar(100) DEFAULT NULL |
| `tanggal` | date DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `status` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `piket_kd` | varchar(50) DEFAULT NULL |
| `piket_kode` | varchar(100) DEFAULT NULL |
| `piket_nama` | varchar(100) DEFAULT NULL |
| `piket_jabatan` | varchar(100) DEFAULT NULL |
| `sahya` | enum('true','false') DEFAULT 'false' |
| `sahya_tgl` | date DEFAULT NULL |
| `sahya_qrcode` | varchar(100) DEFAULT NULL |

**user_piket** (10 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tanggal` | date DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `catatan` | longtext DEFAULT NULL |
| `catatan_postdate` | datetime DEFAULT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `postdate_last_login` | datetime DEFAULT NULL |

##### Inventaris & Aset

**m_kib_jenis** (3 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `nourut` | varchar(2) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |

**m_kib_kode** (9 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `golongan` | varchar(10) DEFAULT NULL |
| `bidang` | varchar(10) DEFAULT NULL |
| `kelompok` | varchar(10) DEFAULT NULL |
| `kelompok_sub` | varchar(10) DEFAULT NULL |
| `kelompok_sub_sub` | varchar(10) DEFAULT NULL |
| `kode` | varchar(100) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_a** (16 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `luas` | varchar(100) DEFAULT NULL |
| `tahun_ada` | varchar(4) DEFAULT NULL |
| `alamat` | longtext DEFAULT NULL |
| `status_hak` | varchar(100) DEFAULT NULL |
| `status_sertifikat_tgl` | varchar(100) DEFAULT NULL |
| `status_sertifikat_nomor` | varchar(100) DEFAULT NULL |
| `penggunaan` | varchar(100) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_b** (20 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `jumlah` | varchar(100) DEFAULT NULL |
| `satuan` | varchar(100) DEFAULT NULL |
| `merk_type` | varchar(100) DEFAULT NULL |
| `ukuran_cc` | varchar(100) DEFAULT NULL |
| `bahan` | varchar(100) DEFAULT NULL |
| `tahun_beli` | varchar(4) DEFAULT NULL |
| `nomor_pabrik` | varchar(100) DEFAULT NULL |
| `nomor_rangka` | varchar(100) DEFAULT NULL |
| `nomor_mesin` | varchar(100) DEFAULT NULL |
| `nomor_polisi` | varchar(100) DEFAULT NULL |
| `nomor_bpkb` | varchar(100) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_c** (20 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `kondisi` | varchar(100) DEFAULT NULL |
| `kontruksi_tingkat` | varchar(100) DEFAULT NULL |
| `kontruksi_beton` | varchar(100) DEFAULT NULL |
| `luas_lantai` | varchar(100) DEFAULT NULL |
| `alamat` | longtext DEFAULT NULL |
| `dokumen_tgl` | varchar(100) DEFAULT NULL |
| `dokumen_nomor` | varchar(100) DEFAULT NULL |
| `tanah_luas` | varchar(100) DEFAULT NULL |
| `tanah_status` | varchar(100) DEFAULT NULL |
| `tanah_kode` | varchar(100) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `tahun_ada` | varchar(4) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_d** (20 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `kontruksi` | varchar(100) DEFAULT NULL |
| `panjang` | varchar(100) DEFAULT NULL |
| `lebar` | varchar(100) DEFAULT NULL |
| `luas` | varchar(100) DEFAULT NULL |
| `lokasi` | longtext DEFAULT NULL |
| `dokumen_tgl` | varchar(100) DEFAULT NULL |
| `dokumen_nomor` | varchar(100) DEFAULT NULL |
| `tanah_status` | varchar(100) DEFAULT NULL |
| `tanah_kode` | varchar(100) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `tahun_ada` | varchar(4) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `kondisi` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_e** (19 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `buku_judul` | longtext DEFAULT NULL |
| `buku_spek` | longtext DEFAULT NULL |
| `corak_asal` | varchar(100) DEFAULT NULL |
| `corak_pencipta` | varchar(100) DEFAULT NULL |
| `corak_bahan` | varchar(100) DEFAULT NULL |
| `hewan_jenis` | varchar(100) DEFAULT NULL |
| `hewan_ukuran` | varchar(100) DEFAULT NULL |
| `jumlah` | varchar(100) DEFAULT NULL |
| `tahun_cetak` | varchar(4) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `tahun_beli` | varchar(4) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**inv_kib_f** (18 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(100) NOT NULL |
| `per_tahun` | varchar(4) DEFAULT NULL |
| `barang_kode` | varchar(100) DEFAULT NULL |
| `barang_nama` | varchar(100) DEFAULT NULL |
| `register` | varchar(100) DEFAULT NULL |
| `kontruksi_tingkat` | varchar(100) DEFAULT NULL |
| `kontruksi_beton` | varchar(100) DEFAULT NULL |
| `luas` | varchar(100) DEFAULT NULL |
| `alamat` | longtext DEFAULT NULL |
| `dokumen_tgl` | varchar(100) DEFAULT NULL |
| `dokumen_nomor` | varchar(100) DEFAULT NULL |
| `mulai_tgl` | varchar(100) DEFAULT NULL |
| `tanah_status` | varchar(100) DEFAULT NULL |
| `tanah_kode` | varchar(100) DEFAULT NULL |
| `asal_usul` | varchar(100) DEFAULT NULL |
| `harga` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

##### Filebox & Log

**user_filebox** (11 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(100) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_posisi` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `judul` | varchar(100) DEFAULT NULL |
| `kategori` | varchar(100) DEFAULT NULL |
| `ket` | longtext DEFAULT NULL |
| `filex` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

**user_log_login** (12 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_posisi` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `ipnya` | varchar(100) DEFAULT NULL |
| `dibaca` | enum('true','false') NOT NULL DEFAULT 'false' |
| `dibaca_postdate` | datetime DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `lat_x` | varchar(100) DEFAULT NULL |
| `lat_y` | varchar(100) DEFAULT NULL |

**user_log_entri** (10 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `user_kd` | varchar(50) DEFAULT NULL |
| `user_kode` | varchar(100) DEFAULT NULL |
| `user_nama` | varchar(100) DEFAULT NULL |
| `user_posisi` | varchar(100) DEFAULT NULL |
| `user_jabatan` | varchar(100) DEFAULT NULL |
| `ket` | longtext DEFAULT NULL |
| `dibaca` | enum('true','false') NOT NULL DEFAULT 'false' |
| `dibaca_postdate` | datetime DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

##### Ekstrakurikuler

**siswa_ekstra** (13 kolom)

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `siswa_kd` | varchar(50) DEFAULT NULL |
| `siswa_nis` | varchar(100) DEFAULT NULL |
| `siswa_nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `smt` | varchar(100) DEFAULT NULL |
| `ekstra_kd` | varchar(50) DEFAULT NULL |
| `ekstra_nama` | varchar(100) DEFAULT NULL |
| `predikat` | varchar(100) DEFAULT NULL |
| `ket` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `entri_oleh` | varchar(100) DEFAULT NULL |


---

### 10.2. Daftar Menu per Role (Template)

Berikut adalah ekstraksi menu dari file HTML template di folder `template/`.

```

=== adm.html ===

- BERANDA (/adm/index.php)
- SETTING (#)
  - Ganti Password (/adm/s/pass.php)
- HISTORY (#)
  - History Login (/adm/h/login.php)
- MASTER (#)
  - Data Tahun Pelajaran (/adm/m/tapel.php)
  - Data Kelas (/adm/m/kelas.php)
  - Data Ruang (/adm/m/ruang.php)
  - Data Jenis Pelanggaran (/adm/m/jenis.php)
  - Data Pelanggaran (/adm/m/pelanggaran.php)
  - Data Pembinaan (/adm/m/pembinaan.php)
  - Data Prestasi (/adm/m/prestasi.php)
- USER AKSES (#)
  - Data Pegawai (/adm/m/pegawai.php)
  - Data Guru Mapel (/adm/m/guru.php)
  - Data Guru BK (/adm/m/bk.php)
  - Data Wali Kelas (/adm/m/wk.php)
  - Data Bendahara (/adm/m/bendahara.php)
  - Data Sarpas (/adm/m/sarpras.php)
  - Data Kepala Sekolah (/adm/m/ks.php)
  - Data Siswa (/adm/m/siswa.php)
  - Data Piket (/adm/m/piket.php)
- AKADEMIK (#)
  - Data Mapel (/adm/akad/mapel.php)
  - Deskripsi Mapel (/adm/akad/mapel_desc.php)
  - RPP dan Silabus (/adm/akad/rpp_silabus.php)
- JADWAL (#)
  - Set Jadwal (/adm/jw/jadwal.php)
  - Lap. Per Mapel (/adm/jw/lap_mapel.php)
  - Lap. Per Guru (/adm/jw/lap_guru.php)
- PIKET HARIAN (#)
  - Petugas Piket (/adm/ph/piket.php)
  - Catatan Kejadian (/adm/ph/catatan.php)
- PRESENSI (#)
  - Set Waktu (/adm/ps/waktu.php)
  - Entri Kehadiran (/adm/ps/presensi.php)
  - Entri Manual Kehadiran (/adm/ps/presensi_manual.php)
  - Entri Pulang (/adm/ps/pulang.php)
  - Entri Manual Pulang (/adm/ps/pulang_manual.php)
  - History Presensi (/adm/ps/history.php)
  - Lap. Terlambat (/adm/ps/lap_telat.php)
  - Lap. Pulang (/adm/ps/lap_pulang.php)
  - Lap. Per Tanggal (/adm/ps/lap_tgl.php)
  - Lap. Per Bulan (/adm/ps/lap_bln.php)
  - Lap. Per Tahun (/adm/ps/lap_thn.php)
  - Lap. Per Siswa (/adm/ps/lap_siswa.php)
  - Lap. Per Pegawai (/adm/ps/lap_pegawai.php)
- ABSENSI (#)
  - Entri Absensi (/adm/ab/absensi.php)
  - Lap. Per Tanggal (/adm/ab/lap_tgl.php)
  - Lap. Per Bulan (/adm/ab/lap_bln.php)
  - Lap. Per Tahun (/adm/ab/lap_thn.php)
  - Lap. Per Siswa (/adm/ab/lap_siswa.php)
  - Lap. Per Pegawai (/adm/ab/lap_pegawai.php)
- PELANGGARAN (#)
  - Pelanggaran (/adm/pl/pelanggaran.php)
  - Lap. Per Tanggal (/adm/pl/lap_tgl.php)
  - Lap. Per Bulan (/adm/pl/lap_bln.php)
  - Lap. Per Tahun (/adm/pl/lap_thn.php)
  - Lap. Per Kelas (/adm/pl/lap_kelas.php)
  - Lap. Per Pelanggaran (/adm/pl/lap_pelanggaran.php)
- PEMBINAAN (#)
  - Belum Dibina (/adm/pb/belum.php)
  - Pembinaan (/adm/pb/pembinaan.php)
  - Lap. Per Tanggal (/adm/pb/lap_tgl.php)
  - Lap. Per Bulan (/adm/pb/lap_bln.php)
  - Lap. Per Tahun (/adm/pb/lap_thn.php)
  - Lap. Per Kelas (/adm/pb/lap_kelas.php)
  - Lap. Per Pembinaan (/adm/pb/lap_pembinaan.php)
- PRESTASI (#)
  - Data Prestasi (/adm/pt/prestasi.php)
  - Prestasi Siswa (/adm/pt/prestasi_siswa.php)
  - Lap. Per Tanggal (/adm/pt/lap_tgl.php)
  - Lap. Per Bulan (/adm/pt/lap_bln.php)
  - Lap. Per Tahun (/adm/pt/lap_thn.php)
  - Lap. Per Kelas (/adm/pt/lap_kelas.php)
  - Lap. Per Prestasi (/adm/pt/lap_prestasi.php)
  - Lap. Per Siswa (/adm/pt/lap_siswa.php)
- EKSTRA (#)
  - Data Ekstra (/adm/ek/ekstra.php)
  - Ekstra Siswa (/adm/ek/ekstra_siswa.php)
  - Lap. Per Ekstra (/adm/ek/lap_ekstra.php)
  - Lap. Per Siswa (/adm/ek/lap_nilai.php)
- KEUANGAN SISWA (#)
  - Item Pembayaran (/adm/keu/item.php)
  - Tunggakan (/adm/keu/tunggakan.php)
  - Pembayaran (/adm/keu/nota.php)
  - History Bayar (/adm/keu/history.php)
  - Lunas (/adm/keu/lunas.php)
  - Lap. Per Tanggal (/adm/keu/lap_tgl.php)
  - Lap. Per Bulan (/adm/keu/lap_bln.php)
  - Lap. Per Tahun (/adm/keu/lap_thn.php)
- TABUNGAN SISWA (#)
  - Set Debet/Kredit/Saldo (/adm/nabung/set.php)
  - Entri Siswa (/adm/nabung/siswa.php)
  - Lap. Harian (/adm/nabung/lap_harian.php)
  - Lap. Bulanan (/adm/nabung/lap_bulanan.php)
- INVENTARIS (#)
  - Daftar Kode Barang (/adm/inv/m_brg.php)
  - K.I.B (/adm/inv/sarpras.php)
  - Rekap Buku Inventaris (/adm/inv/lap_rekap.php)
  - K.I.R (/adm/inv/kir.php)

=== admbdh.html ===

- BERANDA (/admbdh/index.php)
- SETTING (#)
  - Ganti Password (/admbdh/s/pass.php)
- HISTORY (#)
  - History Login (/admbdh/h/login.php)
  - History Entri (/admbdh/h/entri.php)
  - History Presensi (/admbdh/h/presensi.php)
- KEUANGAN SISWA (#)
  - Item Pembayaran (/admbdh/keu/item.php)
  - Tunggakan (/admbdh/keu/tunggakan.php)
  - Pembayaran (/admbdh/keu/nota.php)
  - History Bayar (/admbdh/keu/history.php)
  - Lunas (/admbdh/keu/lunas.php)
  - Lap. Per Tanggal (/admbdh/keu/lap_tgl.php)
  - Lap. Per Bulan (/admbdh/keu/lap_bln.php)
  - Lap. Per Tahun (/admbdh/keu/lap_thn.php)
- TABUNGAN SISWA (#)
  - Set Debet/Kredit/Saldo (/admbdh/nabung/set.php)
  - Entri Siswa (/admbdh/nabung/siswa.php)
  - Lap. Harian (/admbdh/nabung/lap_harian.php)
  - Lap. Bulanan (/admbdh/nabung/lap_bulanan.php)

=== admbk.html ===

- BERANDA (/admbk/index.php)
- SETTING (#)
  - Ganti Password (/admbk/s/pass.php)
- HISTORY (#)
  - History Login (/admbk/h/login.php)
  - History Entri (/admbk/h/entri.php)
  - History Presensi (/admbk/h/presensi.php)
- MASTER (#)
  - Data Jenis Pelanggaran (/admbk/m/jenis.php)
  - Data Pelanggaran (/admbk/m/pelanggaran.php)
  - Data Pembinaan (/admbk/m/pembinaan.php)
  - Data Prestasi (/admbk/m/prestasi.php)
- PRESENSI (#)
  - History Presensi (/admbk/ps/history.php)
  - Lap. Terlambat (/admbk/ps/lap_telat.php)
  - Lap. Per Tanggal (/admbk/ps/lap_tgl.php)
  - Lap. Per Bulan (/admbk/ps/lap_bln.php)
  - Lap. Per Tahun (/admbk/ps/lap_thn.php)
  - Lap. Per Siswa (/admbk/ps/lap_siswa.php)
- ABSENSI (#)
  - Lap. Per Tanggal (/admbk/ab/lap_tgl.php)
  - Lap. Per Bulan (/admbk/ab/lap_bln.php)
  - Lap. Per Tahun (/admbk/ab/lap_thn.php)
  - Lap. Per Siswa (/admbk/ab/lap_siswa.php)
- PELANGGARAN (#)
  - Pelanggaran (/admbk/pl/pelanggaran.php)
  - Lap. Per Tanggal (/admbk/pl/lap_tgl.php)
  - Lap. Per Bulan (/admbk/pl/lap_bln.php)
  - Lap. Per Tahun (/admbk/pl/lap_thn.php)
  - Lap. Per Kelas (/admbk/pl/lap_kelas.php)
  - Lap. Per Pelanggaran (/admbk/pl/lap_pelanggaran.php)
- PEMBINAAN (#)
  - Belum Dibina (/admbk/pb/belum.php)
  - Pembinaan (/admbk/pb/pembinaan.php)
  - Lap. Per Tanggal (/admbk/pb/lap_tgl.php)
  - Lap. Per Bulan (/admbk/pb/lap_bln.php)
  - Lap. Per Tahun (/admbk/pb/lap_thn.php)
  - Lap. Per Kelas (/admbk/pb/lap_kelas.php)
  - Lap. Per Pembinaan (/admbk/pb/lap_pembinaan.php)
- PRESTASI (#)
  - Data Prestasi (/admbk/pt/prestasi.php)
  - Prestasi Siswa (/admbk/pt/prestasi_siswa.php)
  - Lap. Per Tanggal (/admbk/pt/lap_tgl.php)
  - Lap. Per Bulan (/admbk/pt/lap_bln.php)
  - Lap. Per Tahun (/admbk/pt/lap_thn.php)
  - Lap. Per Kelas (/admbk/pt/lap_kelas.php)
  - Lap. Per Prestasi (/admbk/pt/lap_prestasi.php)
  - Lap. Per Siswa (/admbk/pt/lap_siswa.php)

=== admgr.html ===

- BERANDA (/admgr/index.php)
- SETTING (#)
  - Ganti Password (/admgr/s/pass.php)
- HISTORY (#)
  - History Login (/admgr/h/login.php)
  - History Entri (/admgr/h/entri.php)
  - History Presensi (/admgr/h/presensi.php)
- PENILAIAN (#)
  - Data Tujuan Pembelajaran (/admgr/kurmer/m_tp.php)
  - Data Lingkup Materi (/admgr/kurmer/m_lm.php)
  - Data Deskripsi Formatif (/admgr/kurmer/m_formatif.php)
  - Assesmen Formatif (/admgr/kurmer/nil_formatif.php)
  - Assesmen Sumatif (/admgr/kurmer/nil_sumatif.php)
- JURNAL MENGAJAR (#)
  - Entri (/admgr/pm/entri.php)
  - Lap. Agenda (/admgr/pm/lap_agenda.php)
  - Lap. Absensi Siswa (/admgr/pm/lap_absensi.php)
- RPP SILABUS (/admgr/rs/rs.php)
- Jadwal Mengajar (/admgr/jwl/mengajar.php)

=== adminv.html ===

- BERANDA (/adminv/index.php)
- SETTING (#)
  - Ganti Password (/adminv/s/pass.php)
- HISTORY (#)
  - History Login (/adminv/h/login.php)
  - History Entri (/adminv/h/entri.php)
  - History Presensi (/adminv/h/presensi.php)
- INVENTARIS (#)
  - Daftar Kode Barang (/adminv/inv/m_brg.php)
  - K.I.B (/adminv/inv/sarpras.php)
  - Rekap Buku Inventaris (/adminv/inv/lap_rekap.php)

=== admks.html ===

- BERANDA (/admks/index.php)
- SETTING (#)
  - Ganti Password (/admks/s/pass.php)
- HISTORY (#)
  - History Login (/admks/h/login.php)
  - History Entri (/admks/h/entri.php)
  - History Presensi (/admks/h/presensi.php)
- AKADEMIK (#)
  - Data Mapel (/admks/akad/mapel.php)
  - Deskripsi Mapel (/admks/akad/mapel_desc.php)
  - RPP dan Silabus (/admks/akad/rpp_silabus.php)
- JADWAL (#)
  - Lap. Per Hari (/admks/jw/jadwal.php)
  - Lap. Per Mapel (/admks/jw/lap_mapel.php)
  - Lap. Per Guru (/admks/jw/lap_guru.php)
- PIKET HARIAN (#)
  - Petugas Piket (/admks/ph/piket.php)
  - Catatan Kejadian (/admks/ph/catatan.php)
- PRESENSI (#)
  - Lap. Terlambat (/admks/ps/lap_telat.php)
  - Lap. Per Tanggal (/admks/ps/lap_tgl.php)
  - Lap. Per Bulan (/admks/ps/lap_bln.php)
  - Lap. Per Tahun (/admks/ps/lap_thn.php)
  - Lap. Per Siswa (/admks/ps/lap_siswa.php)
  - Lap. Per Pegawai (/admks/ps/lap_pegawai.php)
- ABSENSI (#)
  - Lap. Per Tanggal (/admks/ab/lap_tgl.php)
  - Lap. Per Bulan (/admks/ab/lap_bln.php)
  - Lap. Per Tahun (/admks/ab/lap_thn.php)
  - Lap. Per Siswa (/admks/ab/lap_siswa.php)
  - Lap. Per Pegawai (/admks/ab/lap_pegawai.php)
- PELANGGARAN (#)
  - Lap. Per Tanggal (/admks/pl/lap_tgl.php)
  - Lap. Per Bulan (/admks/pl/lap_bln.php)
  - Lap. Per Tahun (/admks/pl/lap_thn.php)
  - Lap. Per Kelas (/admks/pl/lap_kelas.php)
  - Lap. Per Pelanggaran (/admks/pl/lap_pelanggaran.php)
- PEMBINAAN (#)
  - Lap. Per Tanggal (/admks/pb/lap_tgl.php)
  - Lap. Per Bulan (/admks/pb/lap_bln.php)
  - Lap. Per Tahun (/admks/pb/lap_thn.php)
  - Lap. Per Kelas (/admks/pb/lap_kelas.php)
  - Lap. Per Pembinaan (/admks/pb/lap_pembinaan.php)
- PRESTASI (#)
  - Lap. Per Tanggal (/admks/pt/lap_tgl.php)
  - Lap. Per Bulan (/admks/pt/lap_bln.php)
  - Lap. Per Tahun (/admks/pt/lap_thn.php)
  - Lap. Per Kelas (/admks/pt/lap_kelas.php)
  - Lap. Per Prestasi (/admks/pt/lap_prestasi.php)
  - Lap. Per Siswa (/admks/pt/lap_siswa.php)
- EKSTRA (#)
  - Lap. Per Ekstra (/admks/ek/lap_ekstra.php)
  - Lap. Per Siswa (/admks/ek/lap_nilai.php)
- RAPORT (#)
  - Cetak Raport (/admks/nil/raport.php)
- JURNAL MENGAJAR (#)
  - Lap. Agenda (/admks/pm/lap_agenda.php)
  - Lap. Absensi Siswa (/admks/pm/lap_absensi.php)
- KEUANGAN SISWA (#)
  - Tunggakan (/admks/keu/tunggakan.php)
  - History Bayar (/admks/keu/history.php)
  - Lunas (/admks/keu/lunas.php)
  - Lap. Per Tanggal (/admks/keu/lap_tgl.php)
  - Lap. Per Bulan (/admks/keu/lap_bln.php)
  - Lap. Per Tahun (/admks/keu/lap_thn.php)
- INVENTARIS (#)
  - Per Rekap (/admks/inv/lap_rekap.php)

=== admpiket.html ===

- BERANDA (/admpiket/index.php)
- SETTING (#)
  - Ganti Password (/admpiket/s/pass.php)
- HISTORY (#)
  - Login (/admpiket/h/login.php)
  - Entri (/admpiket/h/entri.php)
  - History Presensi (/admpiket/h/presensi.php)
- PIKET HARIAN (#)
  - Catatan Kejadian (/admpiket/ph/catatan.php)
- PELANGGARAN (#)
  - Pelanggaran (/admpiket/pl/pelanggaran.php)
  - Lap. Per Tanggal (/admpiket/pl/lap_tgl.php)
  - Lap. Per Bulan (/admpiket/pl/lap_bln.php)
  - Lap. Per Tahun (/admpiket/pl/lap_thn.php)
  - Lap. Per Kelas (/admpiket/pl/lap_kelas.php)
  - Lap. Per Pelanggaran (/admpiket/pl/lap_pelanggaran.php)
- PRESENSI (#)
  - Entri Kehadiran (/admpiket/ps/entri.php)
  - Entri Kehadiran Manual (/admpiket/ps/entri_manual.php)
  - Entri Pulang (/admpiket/ps/pulang.php)
  - Entri Pulang Manual (/admpiket/ps/pulang_manual.php)
  - Presensi Harian (/admpiket/ps/presensi.php)
  - Lap. Terlambat (/admpiket/ps/lap_telat.php)
  - Lap. Pulang (/admpiket/ps/lap_pulang.php)
  - Lap. Per Tanggal (/admpiket/ps/lap_tgl.php)
  - Lap. Per Bulan (/admpiket/ps/lap_bln.php)
  - Lap. Per Tahun (/admpiket/ps/lap_thn.php)
  - Lap. Per Siswa (/admpiket/ps/lap_siswa.php)
  - Lap. Per Guru (/admpiket/ps/lap_guru.php)
- ABSENSI (#)
  - Absensi Harian (/admpiket/ab/absensi.php)
  - Lap. Per Tanggal (/admpiket/ab/lap_tgl.php)
  - Lap. Per Bulan (/admpiket/ab/lap_bln.php)
  - Lap. Per Tahun (/admpiket/ab/lap_thn.php)
  - Lap. Per Siswa (/admpiket/ab/lap_siswa.php)
  - Lap. Per Guru (/admpiket/ab/lap_guru.php)
- IJIN MASUK PULANG (#)
  - Entri Ijin (/admpiket/im/ijin.php)
  - Cek QrCode Surat Ijin (/admpiket/im/cek.php)
  - Lap. Per Tanggal (/admpiket/im/lap_tgl.php)
  - Lap. Per Bulan (/admpiket/im/lap_bln.php)
  - Lap. Per Tahun (/admpiket/im/lap_thn.php)
  - Lap. Per Siswa (/admpiket/im/lap_siswa.php)
  - Lap. Per Guru (/admpiket/im/lap_guru.php)

=== admsw.html ===

- BERANDA (/admsw/index.php)
- SETTING (#)
  - Ganti Password (/admsw/s/pass.php)
- HISTORY (#)
  - History Login (/admsw/h/login.php)
  - History Entri (/admsw/h/entri.php)
  - History Presensi (/admsw/h/presensi.php)
- KELAS HARI INI (/admsw/k/hariini.php)
- PELANGGARAN (/admsw/d/pelanggaran.php)
- PEMBINAAN (/admsw/d/pembinaan.php)
- PRESTASI (/admsw/d/prestasi.php)
- EKSTRA (/admsw/d/ekstra.php)
- RAPORT (/admsw/d/raport.php)
- KEUANGAN SISWA (#)
  - Tunggakan (/admsw/keu/tunggakan.php)
  - History Bayar (/admsw/keu/history.php)
  - Lunas (/admsw/keu/lunas.php)
- Jadwal Pelajaran (/admsw/d/jadwal.php)

=== admwk.html ===

- BERANDA (/admwk/index.php)
- SETTING (#)
  - Ganti Password (/admwk/s/pass.php)
- HISTORY (#)
  - History Login (/admwk/h/login.php)
  - History Entri (/admwk/h/entri.php)
  - History Presensi (/admwk/h/presensi.php)
- PENILAIAN (#)
  - Data Proyek (/admwk/kurmer/m_proyek.php)
  - Nilai Proyek (/admwk/kurmer/nil_proyek.php)
  - Nilai Proses (/admwk/kurmer/nil_proses.php)
  - Cetak Raport Asesmen (/admwk/kurmer/raport_asesmen.php)
  - Cetak Raport Proyek (/admwk/kurmer/raport_proyek.php)
- KEUANGAN SISWA (#)
  - Tunggakan (/admwk/keu/tunggakan.php)
  - History Bayar (/admwk/keu/history.php)
  - Lunas (/admwk/keu/lunas.php)
  - Tabungan (/admwk/keu/tabungan.php)
- GURU MENGAJAR (#)
  - Mengajar Hari Ini (/admwk/gm/jadwal.php)
  - Lap. Presensi Guru (/admwk/gm/lap_guru.php)
  - Lap. Presensi Siswa (/admwk/gm/lap_siswa.php)
  - Lap. FeedBack/Respon Siswa (/admwk/gm/lap_respon.php)
- JADWAL (#)
  - Per Hari (/admwk/jw/jadwal.php)
  - Per Mapel (/admwk/jw/lap_mapel.php)
  - Per Guru (/admwk/jw/lap_guru.php)
- PRESENSI (#)
  - History Presensi (/admwk/ps/history.php)

=== admortu.html ===

- BERANDA (/admortu/index.php)
- SETTING (#)
  - Ganti Password (/admortu/s/pass.php)
- HISTORY (#)
  - History Login (/admortu/h/login.php)
  - History Entri (/admortu/h/entri.php)
- Data Pelanggaran (/admortu/d/pelanggaran.php)
- Data Prestasi (/admortu/d/prestasi.php)
- Data Presensi (/admortu/d/presensi.php)
```

---

### 10.3. Referensi File Tambahan

- `schema_sisfokol_v7.md` — Dokumentasi skema lengkap seluruh tabel.
- `schema_sisfokol_v7.json` — Representasi JSON skema untuk tooling/ETL.
- `menu_extract.txt` — Ekstraksi menu template mentah.
- `sisfokol-v7.00-code-smartoffice/` — Kode sumber referensi asli.
