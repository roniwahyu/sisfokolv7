# ═══════════════════════════════════════════════════════════════
# 📄 DOC-03: BUSINESS PROCESS & WORKFLOW MAP
# SISFOKOL v7.12 — Complete Transfer Documentation
# ═══════════════════════════════════════════════════════════════

**Tanggal:** 18 Juni 2026  
**Tujuan:** Mendokumentasikan SELURUH alur proses bisnis, state machine,
user journey, dan decision tree yang ter-embed dalam 442 file PHP legacy
agar dapat direplikasi 100% ke Laravel 11 Multi-Tenant SaaS.

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN A — SYSTEM-WIDE WORKFLOWS
# ═══════════════════════════════════════════════════════════════

## A.1 Master Authentication Flow (login.php — 990 lines)

```
┌──────────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION FLOW (LEGACY)                      │
│                                                                      │
│  User membuka browser → index.php → redirect → login.php            │
│                                                                      │
│  ┌─────────────────────┐                                            │
│  │  Form Login:        │                                            │
│  │  1. Pilih Tipe Role │ ← Dropdown: 9 options                     │
│  │  2. Username        │                                            │
│  │  3. Password        │ ← MD5 hashed client-side? No. Server-side │
│  └─────────┬───────────┘                                            │
│            │ POST btnOK                                              │
│            ▼                                                         │
│  ┌─────────────────────┐                                            │
│  │  Validasi Empty     │ ← if tipe/username/password kosong → alert │
│  └─────────┬───────────┘                                            │
│            │                                                         │
│            ▼                                                         │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  SWITCH berdasarkan TIPE:                                    │   │
│  │                                                              │   │
│  │  tp01 (Guru Mapel)   → query m_pegawai JOIN m_mapel         │   │
│  │  tp02 (Siswa)        → query m_siswa                         │   │
│  │  tp03 (Wali Kelas)   → query m_pegawai JOIN m_walikelas     │   │
│  │  tp04 (Kepsek)       → query m_pegawai JOIN m_ks            │   │
│  │  tp06 (Admin)        → query adminx                          │   │
│  │  tp033 (Piket)       → query m_piket (standalone tabel)     │   │
│  │       └── SPECIAL: cek tabel m_piket per hari+tanggal       │   │
│  │  tp011 (Guru BK)     → query m_pegawai JOIN m_gurubk        │   │
│  │  tp042 (Bendahara)   → query m_pegawai JOIN m_bendahara     │   │
│  │  tp041 (Sarpras)     → query m_pegawai JOIN m_sarpras       │   │
│  │                                                              │   │
│  │  Password comparison: md5(input) == db.passwordx            │   │
│  └──────────────────────┬───────────────────────────────────────┘   │
│                         │                                            │
│            ┌────────────┴────────────┐                               │
│            │                         │                               │
│       LOGIN GAGAL              LOGIN SUKSES                         │
│       pekem(pesan)             │                                    │
│       → redirect login.php    │                                     │
│                                ▼                                     │
│                    ┌──────────────────────┐                          │
│                    │  SET SESSION vars    │                          │
│                    │  (berbeda per role!) │                          │
│                    │                      │                          │
│                    │  kd1_session         │                          │
│                    │  tipe_session        │                          │
│                    │  no1_session         │                          │
│                    │  nm1_session         │                          │
│                    │  hajirobe_session    │ ← random MD5 token      │
│                    │  janiskd             │ ← folder role           │
│                    │  + role-specific:    │                          │
│                    │    guru_session      │                          │
│                    │    siswa_session     │                          │
│                    │    wk_session        │                          │
│                    │    ks_session        │                          │
│                    │    adm_session       │                          │
│                    │    bk_session        │                          │
│                    │    bdh_session       │                          │
│                    │    sarpras_session   │                          │
│                    └──────────┬───────────┘                          │
│                               │                                      │
│                    ┌──────────▼───────────┐                          │
│                    │  INSERT user_log_login│                         │
│                    │  (kd, user_kd, nama, │                          │
│                    │   posisi, ip, lat/lng,│                         │
│                    │   postdate)           │                          │
│                    └──────────┬───────────┘                          │
│                               │                                      │
│                    ┌──────────▼───────────┐                          │
│                    │  REDIRECT ke panel:  │                          │
│                    │  admgr/index.php     │ ← Guru Mapel            │
│                    │  admsw/index.php     │ ← Siswa                 │
│                    │  admwk/index.php     │ ← Wali Kelas            │
│                    │  admks/index.php     │ ← Kepsek                │
│                    │  adm/index.php       │ ← Admin                 │
│                    │  admpiket/index.php  │ ← Piket                 │
│                    │  admbk/index.php     │ ← Guru BK              │
│                    │  admbdh/index.php    │ ← Bendahara            │
│                    │  adminv/index.php    │ ← Sarpras              │
│                    └─────────────────────┘                          │
│                                                                      │
│  SPECIAL CASE — PIKET LOGIN:                                        │
│  Piket memiliki auth ganda:                                         │
│  1. Cek credential di m_piket                                       │
│  2. Cek apakah hari ini = jadwal piket petugas tsb                 │
│  3. Jika bukan hari piketnya → TOLAK login                         │
└──────────────────────────────────────────────────────────────────────┘

LARAVEL TARGET:
→ 1 LoginController, 1 LoginRequest
→ Breeze auth + Sanctum token
→ Role detection via Spatie, no tipe dropdown
→ Polymorphic user: userable_type = Pegawai|Siswa
→ Piket schedule check via PiketMiddleware
→ Activity log via Spatie ActivityLog
```

## A.2 Session Check Flow (inc/cek/*.php — 10 files)

```
SETIAP halaman yang dilindungi menjalankan:
┌─────────────────────────────────────────────────┐
│ require("inc/cek/{role}.php")                   │
│                                                 │
│ 1. Ambil session variables                      │
│ 2. Query DB: cocokkan kd + username + password  │
│ 3. Jika tidak cocok → redirect + pesan error    │
│ 4. Jika cocok → set notification counters:      │
│    - i_loker1: jumlah user_log_entri unread     │
│    - i_loker2: jumlah user_log_login unread      │
│    - i_loker3: jumlah presensi hari ini          │
│    - i_loker4: jumlah absensi hari ini           │
│    - i_loker5: jumlah tunggakan belum lunas      │
│ 5. Set log entri variables (kuz_kd, kuz_nama..) │
└─────────────────────────────────────────────────┘

LARAVEL TARGET:
→ auth middleware (built-in)
→ RoleMiddleware (Spatie)
→ Notification counts via ViewComposer atau Livewire
→ Activity log via observer/middleware
```

## A.3 Page Rendering Flow (Setiap PHP file)

```
┌─────────────────────────────────────────────────────────────┐
│                 LEGACY PAGE LIFECYCLE                        │
│                                                             │
│  1. session_start()                                         │
│  2. require config, fungsi, koneksi, cek/role, paging       │
│  3. $tpl = LoadTpl("template/{role}.html")                  │
│     └── HTML template dengan {placeholder}                  │
│  4. Process POST buttons (btnSMP, btnBTL, btnCARI, etc.)    │
│  5. ob_start() → echo HTML+PHP → $isi = ob_get_contents()  │
│  6. require("inc/niltpl.php")                               │
│     └── ParseVal($tpl, [judul, isi, sumber, versi, ...])   │
│     └── str_replace {var} dengan nilai                      │
│  7. INSERT user_log_entri (audit trail)                     │
│  8. echo $konten (final output)                             │
│  9. xclose($koneksi)                                        │
│                                                             │
│  BUTTON PATTERNS (routing via POST button names):           │
│  ├── btnBR    → Baru (new record form)                      │
│  ├── btnSMP   → Simpan (save/insert/update)                 │
│  ├── btnBTL   → Batal (cancel, redirect back)               │
│  ├── btnCARI  → Cari (search)                               │
│  ├── btnHPS   → Hapus (delete)                              │
│  ├── btnEDT   → Edit (edit form)                            │
│  ├── btnIM    → Import (show import form)                   │
│  ├── btnIMX   → Import Execute (process import)             │
│  ├── btnOK    → OK/Login                                    │
│  └── btnSMPx  → Simpan variant (secondary save)            │
│                                                             │
│  STATE via GET parameter ?s=xxx:                            │
│  ├── s=baru   → Show create form                            │
│  ├── s=detail → Show detail view                            │
│  ├── s=edit   → Show edit form                              │
│  ├── s=hapus  → Confirm delete                              │
│  ├── s=import → Show import form                            │
│  └── (empty)  → List/index view                             │
│                                                             │
│  LARAVEL TARGET:                                            │
│  → RESTful routes: index/create/store/show/edit/update/     │
│    destroy                                                   │
│  → Filament Resource: auto-generates all CRUD               │
│  → Livewire for interactive pages                           │
│  → Blade layouts replace LoadTpl/ParseVal                   │
└─────────────────────────────────────────────────────────────┘
```

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN B — MODULE-SPECIFIC WORKFLOWS
# ═══════════════════════════════════════════════════════════════

## B.1 MODUL CORE — Master Data Workflows

### B.1.1 Pegawai CRUD + Import/Export

```
WORKFLOW: Kelola Data Pegawai
Actors: Admin
Source: adm/m/pegawai.php (1170 lines)

FLOW:
  INDEX:
    → Query m_pegawai ORDER BY nama ASC
    → Paginasi ($limit = 5 per halaman)
    → Tampilkan: NIP, Nama, Jabatan, No.WA, Foto
    → Tombol: Baru, Cari, Import, Export, Cetak QR
    
  CREATE (btnBR → s=baru):
    → Form: NIP/Kode, Nama, Jabatan, Username, Password, No.WA
    → Password di-hash MD5 saat simpan
    → Insert ke m_pegawai
    → Buat folder: filebox/pegawai/{md5_kd}/
    
  UPDATE (s=edit):
    → Pre-fill form dari DB
    → Update m_pegawai WHERE kd = $kd
    → CASCADING UPDATE ke tabel terkait:
      • m_mapel (pegawai_kode, pegawai_nama)
      • m_walikelas (peg_kode, peg_nama)
      • user_presensi (user_nama)
      • user_absensi (user_nama)
      ⚠️ DENORMALISASI — nama diupdate di ~10 tabel!
    
  DELETE (btnHPS):
    → DELETE FROM m_pegawai WHERE kd = $kd
    → Hapus file foto fisik
    → TIDAK cascade delete ke tabel lain ⚠️
    
  IMPORT EXCEL (btnIM → s=import → btnIMX):
    → Upload file Excel via PhpSpreadsheet
    → Parse baris 2+ (skip header)
    → Delimiter: semicolon ";"
    → Kolom: No, NIP, Username, Nama, Jabatan, NoWA
    → Per baris: cek existing (UPDATE if exists, INSERT if new)
    → Password default = MD5(username)
    
  EXPORT EXCEL:
    → Generate .xls dengan header
    → Content-type: application/vnd.ms-excel
    
  CETAK QR CODE (i_qrcode.php):
    → Generate QR dari NIP/Kode
    → Simpan ke filebox/qrcode/
    → Show kartu pegawai dengan QR

LARAVEL TARGET:
  → Filament PegawaiResource dengan:
    - Table columns + SearchFilter + Pagination
    - CreatePage + EditPage (Form schema)
    - Import/Export via Filament ImportAction + ExportAction
    - Foto via FileUpload column
    - QR via custom Action
  → API: GET/POST/PUT/DELETE /api/v1/pegawai
  → NO cascading denormalization (proper FK relations)
```

### B.1.2 Siswa CRUD + Import/Export

```
WORKFLOW: Kelola Data Siswa
Actors: Admin
Source: adm/m/siswa.php (1246 lines)

FLOW: (Similar to Pegawai with additions)
  
  EXTRA FIELDS:
    → NIS, NISN, Kelas, Tahun Pelajaran, No Urut
    → No.WA Ortu, QR Code
    → Summary: jml_presensi, jml_absen_*, subtotal_nominal/setor/belum
    → subtotal_pelanggaran, subtotal_prestasi, subtotal_akhir
    
  BUSINESS RULES:
    → Siswa terikat ke Tapel + Kelas (combo filter)
    → Username default = NIS
    → Password default = MD5(NIS)
    → passwordx_ortu: separate password for parent access
    → subtotal_akhir = subtotal_prestasi - subtotal_pelanggaran
    
  CASCADING UPDATE pada edit:
    → siswa_bayar_tagihan, siswa_pelanggaran, siswa_prestasi
    → siswa_raport_*, siswa_nilai_*, user_presensi
    ⚠️ Massive denormalization
    
LARAVEL TARGET:
  → Filament SiswaResource
  → BelongsTo: Kelas, TahunPelajaran
  → Computed attributes via Accessors (subtotals)
  → NO denormalized subtotal columns (use query aggregates)
```

---

## B.2 MODUL AKADEMIK — Workflows

### B.2.1 Presensi Kehadiran (QR + Manual)

```
WORKFLOW: Entri Presensi Harian
Actors: Admin, Petugas Piket
Sources: adm/ps/presensi.php, admpiket/ps/presensi.php,
         adm/ps/presensi_manual.php, adm/ps/pulang.php

═══ PRESENSI MASUK (QR Scan) ═══

  ┌─────────────┐     ┌──────────────┐     ┌────────────────┐
  │ Scan QR     │────▶│ Lookup NIS/  │────▶│ Cek sudah      │
  │ Code siswa/ │     │ NIP di       │     │ presensi hari  │
  │ pegawai     │     │ m_user       │     │ ini?           │
  └─────────────┘     └──────────────┘     └───────┬────────┘
                                                    │
                                          ┌─────────┴──────────┐
                                          │                    │
                                     BELUM ADA              SUDAH ADA
                                          │                    │
                                          ▼                    ▼
                                   ┌──────────────┐    ┌──────────────┐
                                   │ Hitung telat:│    │ Tampilkan:   │
                                   │ waktu_skrg - │    │ "Sudah hadir │
                                   │ m_waktu.     │    │  jam XX:XX"  │
                                   │ masuk_jam    │    └──────────────┘
                                   └──────┬───────┘
                                          │
                              ┌───────────┴───────────┐
                              │                       │
                         TEPAT WAKTU              TERLAMBAT
                              │                       │
                              ▼                       ▼
                    ┌──────────────┐       ┌──────────────────┐
                    │ status=MASUK │       │ status=MASUK      │
                    │ telat_ket=-  │       │ telat_ket=TERLAMBAT│
                    │ telat_jam=0  │       │ telat_jam=X       │
                    │ telat_menit=0│       │ telat_menit=Y     │
                    └──────┬───────┘       └──────┬───────────┘
                           │                      │
                           └──────────┬───────────┘
                                      ▼
                           ┌──────────────────┐
                           │ INSERT INTO      │
                           │ user_presensi    │
                           │ + UPDATE m_siswa │
                           │   jml_presensi   │
                           │ + UPDATE m_pegawai│
                           │   jml_presensi   │
                           └──────────────────┘

  BUSINESS RULES - TELAT:
  ┌─────────────────────────────────────────────────────┐
  │ Setting: m_waktu = {masuk_jam, masuk_menit,         │
  │                     pulang_jam, pulang_menit}        │
  │                                                     │
  │ Waktu_batas_masuk = masuk_jam : masuk_menit         │
  │ Waktu_saat_ini   = jam_sekarang : menit_sekarang    │
  │                                                     │
  │ if waktu_saat_ini > waktu_batas_masuk:              │
  │   telat_jam   = jam_sekarang - masuk_jam            │
  │   telat_menit = menit_sekarang - masuk_menit        │
  │   telat_ket   = "TERLAMBAT"                         │
  │ else:                                               │
  │   telat_ket = "-" (tepat waktu)                     │
  │                                                     │
  │ Duplikasi check: 1 user HANYA bisa presensi masuk   │
  │ 1x per hari (cek tanggal+user_kd)                   │
  └─────────────────────────────────────────────────────┘

═══ PRESENSI PULANG ═══
  
  → Cari user yang sudah presensi masuk hari ini
  → UPDATE user_presensi SET status = 'PULANG', jam_pulang
  → Hanya bisa pulang jika sudah masuk hari itu

═══ PRESENSI MANUAL ═══
  
  → Autocomplete NIS/NIP/Nama
  → Pilih user → INSERT presensi (bypass QR)
  → Digunakan jika QR scanner bermasalah

LARAVEL TARGET:
  → Livewire ScanPresensi (camera integration)
  → PresensiService.checkin(user, method)
  → PresensiService.checkout(user)
  → Business rules di Service, not Controller
  → Config waktu via Settings (per tenant)
```

### B.2.2 Absensi & Surat Ijin

```
WORKFLOW: Entri Absensi Ketidakhadiran
Actors: Admin, Piket, Guru BK
Source: adm/ab/absensi.php, adm/im/ijin.php

═══ ABSENSI ═══
  → Pilih user (pegawai/siswa)
  → Input: tanggal, keterangan (Sakit/Ijin/Alpha)
  → INSERT INTO user_absensi
  → UPDATE m_siswa SET jml_absen_sakit/ijin/alpha +=1
  
═══ SURAT IJIN MENINGGALKAN ═══

  ┌────────────┐    ┌───────────────┐    ┌──────────────┐
  │ Pilih      │───▶│ Input:        │───▶│ INSERT INTO  │
  │ User       │    │ - Tanggal     │    │ user_ijin    │
  │ (NIS/NIP)  │    │ - Status      │    │              │
  └────────────┘    │ - Keterangan  │    │ Generate:    │
                    │ - Piket yg    │    │ - QR Code    │
                    │   mencatat    │    │ - PDF Surat  │
                    └───────────────┘    └──────────────┘
  
  Status options: Sakit, Ijin, Pulang Awal, Terlambat
  
  OUTPUT:
  → PDF Surat Ijin (DomPDF) dengan:
    - Kop sekolah, data siswa/pegawai
    - Alasan, tanggal, tanda tangan
  → QR Code verifikasi (simpan ke filebox/qrcode/)
  
LARAVEL TARGET:
  → Filament IjinResource
  → IjinService.create() → auto-generate PDF + QR
  → Storage: PDF → local/S3, QR → generated on-the-fly
```

### B.2.3 Penilaian Nilai (Bulanan → Semester → Tahunan)

```
WORKFLOW: Entri & Kalkulasi Nilai
Actors: Admin, Guru Mapel
Sources: adm/pen/bln.php, adm/pen/smt.php, adm/pen/thn.php,
         adm/nil/mapel.php

═══ FLOW PENILAIAN ═══

  ┌─────────────────────────────────────────────────────────────────┐
  │                                                                 │
  │   STEP 1: ENTRI NILAI BULANAN (per Mapel × Kelas × Bulan)     │
  │   ┌──────────────────────────────────┐                         │
  │   │ Filter:                          │                         │
  │   │ - Tahun Pelajaran               │                         │
  │   │ - Kelas                          │                         │
  │   │ - Semester (1/2)                 │                         │
  │   │ - Mata Pelajaran                 │                         │
  │   │ - Bulan                          │                         │
  │   │                                  │                         │
  │   │ Grid siswa × (Pengetahuan, Keterampilan):                 │
  │   │ ┌────┬────────────┬──────┬───────┐                        │
  │   │ │NIS │ Nama       │ P    │ K     │                        │
  │   │ ├────┼────────────┼──────┼───────┤                        │
  │   │ │001 │ Agus       │ [85] │ [90]  │ ← input per siswa     │
  │   │ │002 │ Budi       │ [78] │ [82]  │                        │
  │   │ └────┴────────────┴──────┴───────┘                        │
  │   │                                                            │
  │   │ Save: DELETE existing → INSERT all rows (bulk replace)    │
  │   └──────────────────────────────────┘                         │
  │                           │                                     │
  │                           ▼                                     │
  │   STEP 2: ENTRI NILAI SEMESTER                                 │
  │   ┌──────────────────────────────────────────────────┐         │
  │   │ Grid siswa × komponen nilai:                     │         │
  │   │                                                  │         │
  │   │ PENGETAHUAN (P):                                │         │
  │   │ ┌──────┬──────┬──────┬──────┬──────┬──────┐    │         │
  │   │ │ Rata │  PH  │ PTS  │ PAS  │  NA  │Predi.│    │         │
  │   │ │ Bln  │      │      │      │      │      │    │         │
  │   │ ├──────┼──────┼──────┼──────┼──────┼──────┤    │         │
  │   │ │ auto │ [80] │ [75] │ [85] │ auto │ auto │    │         │
  │   │ └──────┴──────┴──────┴──────┴──────┴──────┘    │         │
  │   │                                                  │         │
  │   │ KETERAMPILAN (K): (same structure)              │         │
  │   │                                                  │         │
  │   │ AUTO-CALCULATED:                                │         │
  │   │ Rata_Bln = AVG(nilai_bulanan per mapel)         │         │
  │   │ NA = (Rata_Bln + PH + PTS + PAS) / 4           │         │
  │   │ Predikat = xpredikat(NA):                       │         │
  │   │   90-100 = A                                    │         │
  │   │   80-89  = B                                    │         │
  │   │   70-79  = C                                    │         │
  │   │   ≤69    = D                                    │         │
  │   └──────────────────────────────────────────────────┘         │
  │                           │                                     │
  │                           ▼                                     │
  │   STEP 3: ENTRI NILAI TAHUNAN (similar, aggregates 2 semester)│
  │                                                                 │
  └─────────────────────────────────────────────────────────────────┘

BUSINESS RULES — GRADING:
  ┌─────────────────────────────────────────────────┐
  │ function xpredikat($nilai):                     │
  │   90-100 → "A" (Sangat Baik)                   │
  │   80-89  → "B" (Baik)                          │
  │   70-79  → "C" (Cukup)                         │
  │   ≤69    → "D" (Kurang)                        │
  │                                                 │
  │ Rata-rata Bulanan = SUM(nilai_bln) / COUNT      │
  │                                                 │
  │ Nilai Akhir Semester:                           │
  │   NA_P = (rata_bln_P + PH_P + PTS_P + PAS_P)/4│
  │   NA_K = (rata_bln_K + PH_K + PTS_K + PAS_K)/4│
  │                                                 │
  │ Save pattern: DELETE all → INSERT all           │
  │ (destructive overwrite, no versioning)          │
  └─────────────────────────────────────────────────┘

LARAVEL TARGET:
  → Livewire EntriNilai (interactive grid)
  → NilaiService.simpanNilaiBulanan(data[])
  → NilaiService.hitungNilaiSemester(siswa, tapel, smt)
  → Auto-calculate NA + Predikat in Service
  → Database transaction (atomic save)
```

### B.2.4 Raport — Generate & Cetak PDF

```
WORKFLOW: Generate Raport Siswa
Actors: Admin, Wali Kelas (generate); Siswa, Kepsek (view)
Sources: adm/nil/raport.php, adm/nil/raport_pdf.php (1083 lines)

═══ RAPORT COMPONENTS ═══

  ┌──────────────────────────────────────────────────────────┐
  │ RAPORT = Kumpulan dari 5 sub-tabel:                     │
  │                                                          │
  │ 1. siswa_raport_sikap                                   │
  │    → Spiritual (predikat + deskripsi)                    │
  │    → Sosial (predikat + deskripsi)                       │
  │    Predikat options: Sangat Baik, Baik, Cukup, Kurang   │
  │                                                          │
  │ 2. siswa_nilai_smt (per mapel)                          │
  │    → NA Pengetahuan + Predikat                           │
  │    → NA Keterampilan + Predikat                          │
  │    → Deskripsi P + K                                     │
  │                                                          │
  │ 3. siswa_raport_catatan                                 │
  │    → Catatan wali kelas (free text)                      │
  │                                                          │
  │ 4. siswa_raport_kenaikan                                │
  │    → Status: Naik ke kelas X / Tinggal / Lulus           │
  │                                                          │
  │ 5. siswa_raport_rangking                                │
  │    → Ranking, Jumlah Siswa, Rata-rata kelas             │
  │                                                          │
  │ + Data presensi:                                         │
  │    → Sakit: X hari, Ijin: X hari, Alpha: X hari         │
  │    (diambil dari m_siswa.jml_absen_*)                    │
  └──────────────────────────────────────────────────────────┘

═══ RAPORT PDF GENERATION ═══

  Library: DomPDF
  Flow:
    1. Query semua data siswa + nilai + sikap + catatan + kenaikan
    2. Query Wali Kelas & Kepala Sekolah (nama + NIP)
    3. Build HTML table layout:
       - Header: nama sekolah, alamat, data siswa
       - Section A: SIKAP (Spiritual + Sosial)
       - Section B: PENGETAHUAN & KETERAMPILAN
         └── Loop per mapel (jenis + no + nama + NA_P + Pred_P + NA_K + Pred_K)
       - Section C: EKSTRA KURIKULER
       - Section D: PRESENSI (Sakit/Ijin/Alpha)
       - Section E: CATATAN WALI KELAS
       - Section F: KENAIKAN KELAS
       - TTD: Wali Kelas + Kepala Sekolah
    4. DomPDF render HTML → stream PDF

  PAPER SIZE: Default (A4 portrait)
  
LARAVEL TARGET:
  → RaportService.generate(siswa, tapel, semester)
  → Return structured data array
  → Blade view: resources/views/pdf/raport.blade.php
  → PDF via barryvdh/laravel-dompdf
  → Filament custom Action: "Cetak Raport" button
```

### B.2.5 Jadwal Pelajaran

```
WORKFLOW: Kelola Jadwal Pelajaran
Actors: Admin
Source: adm/jw/jadwal.php

  ENTRI:
    Filter: Tapel → Semester → Kelas → Hari
    Grid: Jam ke-1..ke-N → pilih Mapel (dropdown dari m_mapel)
    
    Save: DELETE jadwal WHERE tapel+smt+kelas+hari
          INSERT per jam_ke (bulk replace)
    
  BUSINESS RULES:
    → Jadwal per Kelas × Hari × Jam
    → Mapel terikat ke Guru (otomatis dari m_mapel)
    → TIDAK ada conflict detection di legacy! ⚠️
    → Waktu dari m_waktu_jadwal (jam_ke → waktu mulai/selesai)
    
LARAVEL TARGET:
  → Filament JadwalResource dengan Repeater form
  → JadwalService.save() + conflict detection
  → Validation: guru tidak bentrok di jam yang sama
```

---

## B.3 MODUL KURIKULUM MERDEKA — Workflows

### B.3.1 Asesmen Formatif & Sumatif

```
WORKFLOW: Entri Nilai Kurikulum Merdeka
Actors: Guru Mapel
Sources: admgr/kurmer/nil_formatif.php (1515 lines),
         admgr/kurmer/nil_sumatif.php (1580 lines)

═══ PREREQUISITE DATA ═══

  Sebelum entri nilai, Guru harus setup:
  1. Tujuan Pembelajaran (TP) → admgr/kurmer/m_tp.php
     → Per mapel × kelas × tapel × semester
     → Kode TP + Nama TP
     
  2. Lingkup Materi (LM) → admgr/kurmer/m_lm.php
     → Per mapel × kelas × tapel × semester
     → Kode LM + Nama LM
     
  3. Master Asesmen Formatif → admgr/kurmer/m_formatif.php
     → Per mapel × kelas × tapel × semester
     → Deskripsi tinggi + Deskripsi rendah
     → KKTP (Kriteria Ketercapaian Tujuan Pembelajaran)

═══ FORMATIF FLOW ═══

  Filter: Tapel → Kelas → Mapel → Semester
  
  Grid per siswa:
  ┌────┬──────┬──────┬──────┬──────┬──────┐
  │NIS │ Nama │ TP-1 │ TP-2 │ TP-3 │ ...  │ ← Nilai per TP
  ├────┼──────┼──────┼──────┼──────┼──────┤
  │001 │ Agus │ [85] │ [90] │ [78] │      │
  └────┴──────┴──────┴──────┴──────┴──────┘
  
  Save: 
    DELETE kurmer_nilai_asesmen_formatif WHERE tapel+kelas+smt+kode
    DELETE kurmer_nilai_asesmen_formatif_detail WHERE same filter
    
    Per siswa:
      INSERT kurmer_nilai_asesmen_formatif (header)
      Per TP: INSERT kurmer_nilai_asesmen_formatif_detail (detail per TP)

═══ SUMATIF FLOW ═══

  Same structure but:
  - Nilai per LM (bukan per TP)
  - Extra fields: as_non_tes, as_tes
  - Detail per LM bukan per TP
  
  Grid per siswa:
  ┌────┬──────┬────────┬────────┬──────┬──────┬──────┐
  │NIS │ Nama │Non-Tes │  Tes   │ LM-1 │ LM-2 │ ...  │
  ├────┼──────┼────────┼────────┼──────┼──────┼──────┤
  │001 │ Agus │  [85]  │  [90]  │ [78] │ [82] │      │
  └────┴──────┴────────┴────────┴──────┴──────┴──────┘

LARAVEL TARGET:
  → Livewire EntriNilaiFormatif (complex grid)
  → Livewire EntriNilaiSumatif  
  → KurmerService.simpanFormatif(data[])
  → KurmerService.simpanSumatif(data[])
  → Transaction-based save (atomic)
```

### B.3.2 Proyek P5 (Projek Penguatan Profil Pelajar Pancasila)

```
WORKFLOW: Penilaian Proyek P5
Actors: Wali Kelas
Sources: admwk/kurmer/m_proyek.php, admwk/kurmer/nil_proyek.php

═══ SETUP PROYEK ═══

  Wali Kelas membuat proyek:
  → Tema proyek (free text)
  → Detail: Dimensi × Elemen × Sub-Elemen × Target
  → Per kelas × tapel × semester

═══ PENILAIAN PROSES ═══

  Per siswa × per dimensi proyek:
  → Nilai proses (0-100 atau deskriptif)
  → Simpan ke kurmer_nilai_proyek_proses

═══ PENILAIAN AKHIR ═══

  Per siswa:
  → Nilai per dimensi proyek
  → Simpan ke kurmer_nilai_proyek

═══ RAPORT PROYEK PDF ═══
  → Tampilkan per dimensi × elemen × sub-elemen
  → Nilai dan deskripsi per siswa

LARAVEL TARGET:
  → Filament ProyekResource (setup)
  → Livewire EntriNilaiProyek (assessment grid)
  → KurmerService.simpanNilaiProyek()
  → PDF: resources/views/pdf/raport-proyek.blade.php
```

---

## B.4 MODUL KEUANGAN SISWA — Workflows

### B.4.1 Complete Payment Flow

```
WORKFLOW: Alur Keuangan Siswa End-to-End
Actors: Admin, Bendahara (entri); Siswa, Wali Kelas, Kepsek (view)
Sources: adm/keu/item.php, nota.php, tunggakan.php, tunggakan_wa.php,
         adm/nabung/siswa.php

═══ STEP 1: SETUP ITEM PEMBAYARAN ═══

  Admin/Bendahara membuat item:
  ┌────────────────────────────────────────┐
  │ Item Pembayaran:                       │
  │ - Tahun Pelajaran (2025/2026)          │
  │ - Semester (1/2)                       │
  │ - Kelas (I A, I B, ...)               │
  │ - Tahun (2025)                         │
  │ - Bulan (01-12)                        │
  │ - Nama Item (SPP, Seragam, Buku, dll)  │
  │ - Nominal (Rp xxx.xxx)                 │
  └────────────────────────────────────────┘
  
  → INSERT INTO m_keu_siswa

═══ STEP 2: GENERATE TAGIHAN ═══

  Auto-generate (di tunggakan.php):
  → Loop m_keu_siswa (semua item)
    → Loop m_siswa (semua siswa di kelas yang cocok)
      → Cek: sudah ada tagihan untuk item+siswa ini?
      → Jika belum → INSERT INTO siswa_bayar_tagihan
        ├── nominal_tagihan = item.nominal
        ├── nominal_bayar = 0
        ├── nominal_kurang = item.nominal
        └── lunas_status = 'false'

═══ STEP 3: ENTRI PEMBAYARAN ═══

  Flow: nota.php
  ┌─────────────┐    ┌──────────────────┐    ┌───────────────┐
  │ Cari siswa  │───▶│ Tampilkan semua  │───▶│ Entri nominal │
  │ (by NIS)    │    │ tagihan BELUM    │    │ bayar per     │
  │             │    │ LUNAS            │    │ item tagihan  │
  └─────────────┘    └──────────────────┘    └───────┬───────┘
                                                      │
                                                      ▼
  ┌────────────────────────────────────────────────────────────┐
  │ KALKULASI PER TAGIHAN:                                    │
  │                                                            │
  │ total_terbayar = SUM(rincian_bayar_sebelumnya) + bayar_ini │
  │ sisa = nominal_tagihan - total_terbayar                    │
  │                                                            │
  │ if sisa <= 0:                                              │
  │   lunas_status = 'true'                                    │
  │   lunas_postdate = now()                                   │
  │ else:                                                      │
  │   lunas_status = 'false'                                   │
  │                                                            │
  │ UPDATE siswa_bayar_tagihan SET                             │
  │   nominal_bayar = total_terbayar,                          │
  │   nominal_kurang = sisa,                                   │
  │   lunas_status = status                                    │
  │                                                            │
  │ INSERT siswa_bayar (header pembayaran)                     │
  │ INSERT siswa_bayar_rincian (per item detail)               │
  │                                                            │
  │ UPDATE m_siswa SET                                         │
  │   subtotal_setor = SUM(all bayar),                         │
  │   subtotal_belum = SUM(all sisa)                           │
  └────────────────────────────────────────────────────────────┘

═══ STEP 4: CETAK NOTA ═══

  → nota_pdf.php
  → DomPDF: Kop sekolah + Data siswa + Rincian pembayaran
  → Kode transaksi: {timestamp}{NIS}

═══ STEP 5: KIRIM WA TUNGGAKAN ═══

  → tunggakan_wa.php
  → Loop siswa yang punya tunggakan
  → INSERT ke wa_tagihan_siswa
  → i_proses_wa.php: kirim via external WA API
    ├── URL API dari config ($sumberya)
    ├── APIKEY dari config ($apikey)  
    └── Format pesan: "Yth Ortu {nama}, tunggakan Rp {nominal}"

═══ TABUNGAN SISWA ═══

  → adm/nabung/siswa.php
  DEBET (Setor):
    → Cek min_debet dari m_tabungan
    → if jml_uang < min_debet → TOLAK
    → INSERT transaksi + UPDATE saldo
  KREDIT (Tarik):
    → Cek max_kredit dari m_tabungan
    → Cek min_saldo (saldo - tarik >= min_saldo)
    → if jml_uang > max_kredit → TOLAK
    → if saldo_akhir < min_saldo → TOLAK
    → INSERT transaksi + UPDATE saldo
  CETAK STRUK (siswa_prt.php)

LARAVEL TARGET:
  → KeuanganService.generateTagihan(item, kelas)
  → KeuanganService.bayar(siswa, items[], nominal[])
    └── DB::transaction() — atomic
  → TabunganService.setor(siswa, jumlah) 
  → TabunganService.tarik(siswa, jumlah)
    └── Validation rules in FormRequest
  → WhatsAppJob (queued) for notifications
  → PDF via Blade + DomPDF
```

---

## B.5 MODUL BK — Workflows

### B.5.1 Pelanggaran, Pembinaan, Prestasi

```
WORKFLOW: Siklus BK Lengkap
Actors: Admin, Guru BK, Piket (entri); Siswa (view)
Sources: adm/pl/pelanggaran.php, adm/pb/pembinaan.php, adm/pt/prestasi.php

═══ ENTRI PELANGGARAN ═══

  ┌─────────────────────────────────────────────────────────┐
  │ 1. Cari siswa by NIS                                    │
  │ 2. Pilih Jenis Pelanggaran (dropdown: m_bk_point_jenis) │
  │ 3. Pilih Point Pelanggaran (dropdown: m_bk_point)       │
  │    └── Auto-fill: nama, point_nilai, sanksi             │
  │ 4. Input tanggal pelanggaran                            │
  │ 5. Auto-set pencatat (piket/BK yang login)              │
  │ 6. INSERT siswa_pelanggaran                             │
  │    └── sahya = 'true' (langsung disahkan)               │
  │                                                          │
  │ POST-INSERT:                                             │
  │ → Hitung total point pelanggaran siswa:                  │
  │   SELECT SUM(point_nilai) FROM siswa_pelanggaran         │
  │   WHERE siswa_kd = $kd                                   │
  │ → UPDATE m_siswa SET                                     │
  │   jml_pelanggaran = COUNT(pelanggaran),                  │
  │   subtotal_pelanggaran = SUM(point_nilai)                │
  │                                                          │
  │ SKOR AKHIR:                                              │
  │ subtotal_akhir = subtotal_prestasi - subtotal_pelanggaran│
  │ → UPDATE m_siswa SET subtotal_akhir                      │
  └─────────────────────────────────────────────────────────┘

═══ PEMBINAAN (Tindak Lanjut) ═══
  → Linked ke pelanggaran (opsional)
  → Input: tanggal, pembina, jenis pembinaan, keterangan
  → INSERT siswa_pelanggaran UPDATE bina_tgl, bina_nama, bina_ket

═══ PRESTASI ═══
  → Input: tanggal, nama prestasi, point (positif)
  → INSERT siswa_prestasi
  → UPDATE m_siswa: jml_prestasi, subtotal_prestasi
  → Recalculate subtotal_akhir

BUSINESS RULES — BK SCORING:
  ┌────────────────────────────────────────────────┐
  │ Pelanggaran:                                   │
  │  Jenis → Jenis memiliki banyak Point           │
  │  Point = { kode, nama, point_nilai, sanksi }   │
  │  Contoh: Terlambat = 5 point                   │
  │          Merokok = 50 point                     │
  │          Bolos = 25 point                       │
  │                                                │
  │ Skor Siswa:                                    │
  │  Total Pelanggaran = SUM(all point_nilai)      │
  │  Total Prestasi = SUM(all prestasi point)      │
  │  Skor Akhir = Prestasi - Pelanggaran           │
  │  (bisa negatif = siswa bermasalah)             │
  └────────────────────────────────────────────────┘

LARAVEL TARGET:
  → BKService.catatPelanggaran(siswa, bk_point)
  → BKService.catatPrestasi(siswa, data)
  → Auto-recalculate skor via Observer
  → Skor computed, NOT stored (query aggregate)
```

---

## B.6 MODUL INVENTARIS — Workflows

### B.6.1 KIB A-F Management

```
WORKFLOW: Kelola Inventaris Aset Sekolah
Actors: Admin, Sarpras
Source: adm/inv/sarpras.php (3561 lines)

═══ 6 TIPE KIB ═══

  KIB A: Tanah         → luas, alamat, hak, sertifikat
  KIB B: Peralatan     → jumlah, satuan, merk, no.pabrik, no.polisi
  KIB C: Gedung        → tingkat, beton, luas_lantai, dokumen
  KIB D: Jalan/Irigasi → konstruksi, panjang×lebar, lokasi
  KIB E: Aset Lain     → buku(judul,spek), hewan(jenis), corak(pencipta)
  KIB F: Konstruksi    → tingkat, beton, luas, mulai_tgl

═══ FLOW ═══

  1. Pilih Tipe KIB (tab/dropdown)
  2. CRUD per tipe (form fields berbeda per tipe!)
  3. Import Excel per tipe
  4. Export Excel per tipe
  5. Rekapitulasi (total per tipe, total harga)
  6. Kode barang hierarki (m_kib_kode)

═══ IMPORT PATTERN ═══
  → Upload Excel → PhpSpreadsheet parse
  → Pre-display data di HTML table (editable)
  → Confirm → Bulk INSERT

LARAVEL TARGET:
  → 1 InventarisResource with KIB tipe filter
  → JSON column for type-specific fields
  → OR 6 separate Filament Resources (simpler)
  → Import/Export via Filament actions
```

---

## B.7 MODUL FILEBOX — Workflows

### B.7.1 RPP/Silabus Upload + Approval

```
WORKFLOW: Upload & Approval Dokumen
Actors: Guru Mapel (upload), Kepala Sekolah (approve)
Source: admgr/rs/rs.php

═══ FLOW ═══

  GURU UPLOAD:
  1. Pilih mapel (auto-filtered by guru's assignments)
  2. Upload file (RPP/Silabus)
  3. Save ke filebox/arsip/{guru_kd}/{file_kd}/
  4. UPDATE m_mapel SET rpp_postdate = now()

  KEPSEK REVIEW:
  1. View list RPP/Silabus yang diupload
  2. Download & review
  3. Approve/Reject:
     → UPDATE m_mapel SET rpp_acc = 'true'/'false'
     → rpp_acc_postdate, rpp_acc_ket (keterangan)
  
  Same flow for Silabus (silabus_acc, silabus_acc_postdate)

LARAVEL TARGET:
  → Google Drive upload via GoogleDriveService
  → Status workflow: draft → submitted → approved/rejected
  → Notification to Kepsek when new upload
  → Filament: custom Approve/Reject actions
```

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN C — STATE MACHINES & STATUS TRANSITIONS
# ═══════════════════════════════════════════════════════════════

## C.1 State Machine Diagrams

```
═══ TAGIHAN/PEMBAYARAN STATUS ═══

  [belum_bayar] ──bayar_sebagian──▶ [sebagian] ──bayar_sisa──▶ [lunas]
       │                                │                          │
       └────────bayar_penuh─────────────┴──────────────────────────┘
       
  Database: lunas_status ENUM('true','false')
  Laravel:  status ENUM('belum','sebagian','lunas')

═══ PELANGGARAN STATUS ═══

  [dicatat] ──disahkan──▶ [sah] ──dibina──▶ [sudah_dibina]
  
  Database: sahya ENUM('true','false'), bina_tgl, bina_nama

═══ RPP/SILABUS APPROVAL ═══

  [uploaded] ──review──▶ [approved]
       │                      
       └──review──▶ [rejected]
       
  Database: rpp_acc ENUM('true','false'), rpp_acc_ket

═══ PRESENSI STATUS ═══

  [belum] ──scan/manual──▶ [masuk] ──pulang──▶ [pulang]
  
  Status values: MASUK, PULANG (not enum, varchar)
  Telat: telat_ket = "TERLAMBAT" or "-"

═══ TABUNGAN TRANSAKSI ═══

  Tipe: DEBET (setor) | KREDIT (tarik)
  Constraints: min_debet, max_kredit, min_saldo
  No approval flow — langsung proses
```

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN D — BUSINESS RULES ENGINE (EXTRACTED)
# ═══════════════════════════════════════════════════════════════

## D.1 Semua Formula & Kalkulasi

```php
// ═══ FROM inc/fungsi.php ═══

// 1. GRADING PREDICATE (Predikat Nilai)
// Source: xpredikat($str) - line 609
function xpredikat($nilai) {
    if ($nilai >= 90 && $nilai <= 100) return "A";  // Sangat Baik
    if ($nilai >= 80 && $nilai <= 89)  return "B";  // Baik
    if ($nilai >= 70 && $nilai <= 79)  return "C";  // Cukup
    if ($nilai <= 69)                  return "D";  // Kurang
}
// NOTE: Threshold harus configurable per tenant di SaaS!

// 2. NUMBER TO WORDS (Terbilang) — for Nota Keuangan
// Source: xongkof($str) - line 700-900
// Converts: 1500000 → "SATU JUTA LIMA RATUS RIBU"
// Supports: 1-7 digits
// Used in: nota_pdf.php, pembayaran cetak

// 3. CURRENCY FORMAT
// Source: xduit($str) 
// Converts: 1500000 → "1.500.000"

// 4. LATE CALCULATION (Keterlambatan)
// Source: presensi.php
// Formula:
//   batas = m_waktu.masuk_jam + m_waktu.masuk_menit
//   if jam_sekarang > masuk_jam:
//     telat_jam = jam_sekarang - masuk_jam
//     telat_menit = menit_sekarang - masuk_menit
//   elif jam_sekarang == masuk_jam && menit_sekarang > masuk_menit:
//     telat_jam = 0
//     telat_menit = menit_sekarang - masuk_menit

// 5. BK SCORING
// Source: pelanggaran.php, prestasi.php
// Formula:
//   total_pelanggaran = SUM(siswa_pelanggaran.point_nilai)
//   total_prestasi = SUM(siswa_prestasi.point)
//   skor_akhir = total_prestasi - total_pelanggaran

// 6. SEMESTER CALCULATION
// Source: pen/smt.php
// Formula:
//   rata_bln = AVG(siswa_nilai_bln.p_nilai) per mapel
//   NA_P = (rata_bln + PH + PTS + PAS) / 4
//   NA_K = (rata_bln + PH + PTS + PAS) / 4
//   Predikat = xpredikat(NA)

// 7. TAGIHAN CALCULATION
// Source: keu/nota.php
// Formula:
//   total_terbayar = SUM(siswa_bayar_rincian.nominal_bayar) per tagihan
//   sisa = tagihan.nominal - total_terbayar
//   lunas = (sisa <= 0)

// 8. TABUNGAN RULES
// Source: nabung/siswa.php
// Rules:
//   SETOR: jumlah >= min_debet (from m_tabungan)
//   TARIK: jumlah <= max_kredit
//          saldo_akhir >= min_saldo
//   saldo_akhir = saldo_sebelumnya ± jumlah

// 9. TAPEL SEMESTER YEAR MAPPING
// Source: Multiple files
// Rule:
//   tapel = "2025/2026"
//   if semester == 1: tahun_aktif = 2025
//   if semester == 2: tahun_aktif = 2026
```

## D.2 Validation Rules (Extracted from Legacy)

```
VALIDATION RULES PER ENTITY:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PEGAWAI:
  - NIP/Kode: required, unique
  - Nama: required
  - Username: required, unique
  - Password: required (min length NOT enforced in legacy!)
  - Jabatan: required

SISWA:
  - NIS/Kode: required, unique
  - Nama: required
  - Kelas: required (dropdown)
  - Tapel: required (dropdown)
  - Username: required (default = NIS)
  
NILAI:
  - Range: 0-100 (NOT validated in legacy! Free text varchar)
  - Format: numeric (JavaScript numbersonly() client-side)
  
PEMBAYARAN:
  - Nominal > 0
  - Nominal <= sisa_tagihan (NOT validated in legacy!)
  
TABUNGAN SETOR:
  - jumlah >= m_tabungan.min_debet
  
TABUNGAN TARIK:
  - jumlah <= m_tabungan.max_kredit
  - (saldo - jumlah) >= m_tabungan.min_saldo

PRESENSI:
  - 1x per user per hari (duplicate check)
  - Pulang hanya jika sudah masuk

PELANGGARAN:
  - Siswa harus exist
  - Jenis + Point harus dipilih
  - Tanggal required

INVENTARIS:
  - Kode barang required
  - Nama barang required
  - Form fields berbeda per tipe KIB
```

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN E — LAPORAN & REPORTING MAP
# ═══════════════════════════════════════════════════════════════

## E.1 Master Report List

```
SEMUA LAPORAN YANG ADA (dari filesystem analysis):

PRESENSI:
  ├── Laporan per Tanggal        (lap_tgl.php)
  ├── Laporan per Bulan          (lap_bln.php)  
  ├── Laporan per Tahun          (lap_thn.php)
  ├── Laporan per Pegawai        (lap_pegawai.php)
  ├── Laporan per Siswa          (lap_siswa.php)
  ├── Laporan Keterlambatan      (lap_telat.php)
  └── Laporan Pulang             (lap_pulang.php)

ABSENSI:
  ├── Laporan per Tanggal        
  ├── Laporan per Bulan          
  ├── Laporan per Tahun          
  ├── Laporan per Guru           
  └── Laporan per Siswa          

KEUANGAN:
  ├── Laporan per Tanggal        
  ├── Laporan per Bulan          
  ├── Laporan per Tahun          
  ├── Tunggakan (view)           
  └── Lunas (view)               

PELANGGARAN:
  ├── Laporan per Tanggal        
  ├── Laporan per Bulan          
  ├── Laporan per Tahun          
  ├── Laporan per Kelas          
  ├── Laporan per Jenis          
  └── Belum Disahkan (view)      

PEMBINAAN: (same filter structure)
PRESTASI: (same filter structure)
EKSTRA: (per ekstra, per nilai)
JADWAL: (per guru, per mapel)
INVENTARIS: (rekapitulasi per tipe KIB)
NILAI: (per kelas, per semester)
TABUNGAN: (per hari, per bulan)

COMMON FILTER PATTERN:
  ┌──────────────────────────────────────────┐
  │ Filter UI (reusable component):          │
  │ ├── Tanggal: date picker                 │
  │ ├── Bulan: month picker                  │
  │ ├── Tahun: year dropdown                 │
  │ ├── Kelas: dropdown (from m_kelas)       │
  │ ├── Siswa: search autocomplete           │
  │ └── Pegawai: search autocomplete         │
  │                                          │
  │ Output: HTML table + Export Excel button  │
  └──────────────────────────────────────────┘

LARAVEL TARGET:
  → 1 Reusable Livewire LaporanFilter component
  → Filament custom Pages for complex reports
  → Export via Maatwebsite Excel
  → ~45 legacy lap_*.php files → ~8 Filament Pages with tab filters
```

---

# ═══════════════════════════════════════════════════════════════
# BAGIAN F — DATA MIGRATION CRITICAL RULES
# ═══════════════════════════════════════════════════════════════

## F.1 Character Encoding Reverse-Map

```
LEGACY uses custom encoding (cegah/balikin functions).
Data in DB contains encoded strings that MUST be decoded:

ENCODING MAP (cegah → stored value):
  '    → xpsijix
  %    → xpersenx
  @    → xtkeongx
  _    → xgwahx
  1=1  → x1smdgan1x
  /    → xgmringx        ← CRITICAL! Tapel "2022/2023" stored as "2022xgmringx2023"
  !    → xpentungx
  <    → xkkirix
  >    → xkkananx
  (    → xkkurix
  )    → xkkurnanx
  ;    → xkommax
  -    → xstrix           ← CRITICAL! Dates, NIP with dashes

MIGRATION MUST:
  1. Run balikin() equivalent on ALL varchar/text columns
  2. Special attention: tapel columns contain "xgmringx" (= "/")
  3. Special attention: email columns contain "xtkeongx" (= "@")
  4. Dates stored with "xstrix" need conversion back to "-"
  
PHP MIGRATION FUNCTION:
  function decodeLegacy($str) {
      return str_replace(
          ['xpsijix','xpersenx','xtkeongx','xgwahx','x1smdgan1x',
           'xgmringx','xpentungx','xkkirix','xkkananx','xkkurix',
           'xkkurnanx','xkommax','xstrix','xstripbwhx'],
          ["'", '%', '@', '_', '1=1', '/', '!', '<', '>', '(', 
           ')', ';', '-', '_'],
          $str
      );
  }
```

## F.2 ID Transformation Rules

```
LEGACY ID:  MD5 hash string (varchar 50)
TARGET ID:  Auto-increment BIGINT + optional UUID

MAPPING TABLE NEEDED:
  ┌──────────────┬──────────────────────────────────┬──────┐
  │ Table        │ Legacy KD (MD5)                  │ NewID│
  ├──────────────┼──────────────────────────────────┼──────┤
  │ m_pegawai    │ 202cb962ac59075b964b07152d234b70 │ 1    │
  │ m_pegawai    │ 289dff07669d7a23de0ef88d2f7129e7 │ 2    │
  │ m_siswa      │ f09cd63b1a5f9cfe368fb9d8d6e1134f │ 1    │
  └──────────────┴──────────────────────────────────┴──────┘

ALL FK references must be remapped:
  siswa_bayar.siswa_kd → look up mapping → set siswa_id
  siswa_pelanggaran.siswa_kd → look up mapping → set siswa_id
  ... (repeat for every FK column)
```

---

*Dokumen ini berisi ~100% business logic yang ter-extract dari 442 file PHP legacy.
Setiap workflow yang didokumentasikan di sini HARUS diimplementasikan di Laravel 11 
untuk memastikan feature parity penuh.*

**End of DOC-03**
