# ═══════════════════════════════════════════════════════════════
# 📄 DOC-04: COMPLETE DATA DICTIONARY & ERD
# SISFOKOL v7.12 → Laravel 11 Multi-Tenant Micro SaaS
# ═══════════════════════════════════════════════════════════════

**Tanggal:** 18 Juni 2026
**Cakupan:** 75 tabel legacy (856 kolom) → ~50 tabel baru (tenant-aware)
**Metode:** Column-by-column extraction dari db/sisfokol_v7.sql

---

# DAFTAR ISI

```
PART 1 — LEGACY DATA DICTIONARY (75 Tabel, 856 Kolom)
PART 2 — CRITICAL ISSUES PER TABLE
PART 3 — TARGET SCHEMA (Laravel Migrations)
PART 4 — COLUMN MAPPING LEGACY → TARGET
PART 5 — RELATIONSHIP & FK MAP
PART 6 — INDEX STRATEGY
PART 7 — DATA TRANSFORMATION RULES
PART 8 — MIGRATION SQL SCRIPTS
```

---

# ═══════════════════════════════════════════════════════════════
# PART 1 — LEGACY DATA DICTIONARY (Lengkap 75 Tabel)
# ═══════════════════════════════════════════════════════════════

## Legenda Simbol

```
🔑  = Primary Key
⚠️  = Masalah/Anti-pattern ditemukan
🔗  = Logical FK (tidak enforced di DB)
📛  = Kolom berisi data ter-encode (cegah/nosql)
💀  = Kolom tipe salah (VARCHAR untuk angka/uang)
🗑️  = Kolom denormalisasi (duplikasi data dari tabel lain)
```

---

## GRUP 1: AUTHENTICATION & PLATFORM (3 Tabel)

### 1. `adminx` — Admin Users
```
Engine: MyISAM | Charset: latin1 | Columns: 3

🔑 kd          varchar(50)   PK, MD5 hash
   usernamex    varchar(100)  Username admin
   passwordx    varchar(100)  ⚠️ MD5 hash password

Masalah: Tabel terpisah dari pegawai/siswa. Tidak scalable.
Target:  MERGE → users (role='admin-sekolah')
```

### 2. `a_profil` — Profil Sekolah
```
Engine: MyISAM | Charset: latin1 | Columns: 5

🔑 kd               varchar(50)   PK, MD5 hash
   postdate          datetime      Waktu update
   lat_x             longtext      ⚠️ Latitude (harusnya DECIMAL)
   lat_y             longtext      ⚠️ Longitude (harusnya DECIMAL)
   alamat_googlemap  longtext      Alamat google maps

Target: MERGE → schools.lat, schools.lng, schools.address
```

### 3. `m_user` — Master User (Presensi)
```
Engine: MyISAM | Charset: utf8mb4 | Columns: 20

🔑 kd               varchar(50)   PK, MD5 hash
   usernamex         varchar(100)  Username
   passwordx         varchar(100)  ⚠️ MD5 hash
   kode              varchar(100)  NIS/NIP
   nomor             varchar(100)  Nomor urut
   nama              varchar(100)  Nama lengkap
   tapel             varchar(100)  📛 Tahun pelajaran (encoded)
   kelas             varchar(100)  📛 Kelas
   jabatan           varchar(100)  Jabatan (Guru/Siswa/TU)
   tipe              varchar(100)  Tipe user
   nowa              varchar(100)  Nomor WA
   postdate          datetime      Waktu buat
   qrcode            varchar(100)  QR code string
   postdate_last_login datetime    Login terakhir
   jml_hadir         varchar(5)    💀 Jumlah hadir (harusnya INT)
   jml_telat         varchar(5)    💀 Jumlah telat
   tapel_kd          varchar(50)   🔗 FK ke m_tapel
   tapel_nama        varchar(100)  🗑️ Denormalisasi
   kelas_kd          varchar(50)   🔗 FK ke m_kelas
   kelas_nama        varchar(100)  🗑️ Denormalisasi

Masalah: Duplikasi data pegawai+siswa untuk presensi QR.
Target:  HAPUS. Presensi langsung referensi pegawai/siswa via polymorphic.
```

---

## GRUP 2: MASTER DATA CORE (12 Tabel)

### 4. `m_pegawai` — Master Pegawai/Guru
```
Engine: MyISAM | Charset: utf8 | Columns: 13

🔑 kd               varchar(50)   PK, MD5 hash
   usernamex         longtext      ⚠️ Username (harusnya varchar)
   passwordx         longtext      ⚠️ MD5 hash password
   nama              varchar(100)  Nama lengkap
   kode              varchar(100)  NIP/Kode pegawai
   jabatan           varchar(100)  Jabatan (Guru/KS/TU)
   postdate          datetime      Waktu buat
   jml_absen_sakit   varchar(5)    💀🗑️ Counter denormalisasi
   jml_absen_ijin    varchar(5)    💀🗑️ Counter denormalisasi
   jml_absen_alpha   varchar(5)    💀🗑️ Counter denormalisasi
   jml_mengajar      varchar(5)    💀🗑️ Counter denormalisasi
   nowa              varchar(100)  Nomor WA
   jml_presensi      varchar(5)    💀🗑️ Counter denormalisasi

Target: pegawai (id, school_id, nip, nama, jabatan, nowa, foto,
        created_at, updated_at, deleted_at)
        + users (auth terpisah)
Note:   SEMUA counter columns DIHAPUS → hitung via query aggregate
```

### 5. `m_siswa` — Master Siswa
```
Engine: MyISAM | Charset: latin1 | Columns: 25

🔑 kd                    varchar(50)   PK, MD5 hash
   usernamex              varchar(100)  Username (default=NIS)
   passwordx              varchar(100)  ⚠️ MD5 hash
   kode                   varchar(50)   NIS
   nama                   varchar(100)  Nama lengkap
   postdate               datetime      Waktu buat
   passwordx_ortu         varchar(100)  ⚠️ Password ortu (MD5)
   tapel                  varchar(100)  📛 Tahun pelajaran (encoded "/" → "xgmringx")
   kelas                  varchar(100)  📛 Nama kelas
   nourut                 varchar(5)    No urut di kelas
   qrcode                 varchar(100)  QR code string
   jml_ekstra             varchar(5)    💀🗑️ Counter
   jml_absen_sakit        varchar(5)    💀🗑️ Counter
   jml_absen_ijin         varchar(5)    💀🗑️ Counter
   jml_absen_alpha        varchar(5)    💀🗑️ Counter
   subtotal_nominal       varchar(15)   💀🗑️ Total tagihan
   subtotal_setor         varchar(15)   💀🗑️ Total bayar
   subtotal_belum         varchar(15)   💀🗑️ Total tunggakan
   nowa                   varchar(100)  Nomor WA ortu
   jml_pelanggaran        varchar(5)    💀🗑️ Counter
   subtotal_pelanggaran   varchar(5)    💀🗑️ Point pelanggaran
   jml_presensi           varchar(5)    💀🗑️ Counter
   jml_prestasi           varchar(5)    💀🗑️ Counter
   subtotal_prestasi      varchar(5)    💀🗑️ Point prestasi
   subtotal_akhir         varchar(5)    💀🗑️ Skor akhir (prestasi-pelanggaran)

Target: siswa (id, school_id, nis, nisn, nama, kelas_id FK, tapel_id FK,
        no_urut, nowa, qrcode, foto, created_at, updated_at, deleted_at)
Note:   15 dari 25 kolom adalah COUNTER DENORMALISASI → DIHAPUS SEMUA
        Semua dihitung real-time via query/accessor
```

### 6. `m_kelas` — Master Kelas
```
Engine: MyISAM | Charset: latin1 | Columns: 5

🔑 kd       varchar(50)   PK
   no        char(1)       Tingkat (1-6 atau 10-12)
   nama      varchar(100)  Nama kelas (I A, X IPA 1)
   kelas     varchar(100)  ⚠️ Tidak digunakan (selalu NULL)
   postdate  datetime      Waktu buat

Target: kelas (id, school_id, tingkat TINYINT, nama, fase CHAR(1) NULL,
        kapasitas INT NULL, created_at, updated_at)
```

### 7. `m_tapel` — Tahun Pelajaran
```
Engine: MyISAM | Charset: latin1 | Columns: 5

🔑 kd       varchar(50)   PK
   nama      varchar(100)  Nama tapel "2022/2023" 📛 (/ encoded)
   tapel     varchar(100)  ⚠️ Selalu NULL, tidak dipakai
   postdate  datetime      Waktu buat
   aktif     enum('true','false')  Status aktif

Target: tahun_pelajaran (id, school_id, nama, semester TINYINT,
        tgl_mulai DATE, tgl_selesai DATE, is_active BOOLEAN,
        created_at, updated_at)
Note:   Legacy menyimpan tapel sebagai string "2022xgmringx2023"
        dengan semester sebagai parameter terpisah di setiap query
```

### 8. `m_ruang` — Master Ruang
```
Engine: MyISAM | Charset: latin1 | Columns: 4

🔑 kd       varchar(50)   PK
   no        varchar(10)   Nomor ruang
   nama      varchar(100)  Nama ruang
   postdate  datetime

Target: ruang (id, school_id, kode, nama, kapasitas INT NULL,
        created_at, updated_at)
```

### 9. `m_mapel` — Master Mata Pelajaran + Guru Assignment
```
Engine: MyISAM | Charset: latin1 | Columns: 20

🔑 kd               varchar(50)   PK
   tapel             varchar(100)  📛 Tahun pelajaran
   kelas             varchar(100)  📛 Kelas
   jenis             varchar(100)  Kelompok mapel (A/B/C)
   no                varchar(5)    Nomor urut mapel
   kode              varchar(100)  Kode mapel (BINDO, MTK, dll)
   nama              longtext      Nama mapel
   kkm               varchar(5)    💀 KKM (harusnya DECIMAL)
   postdate          datetime
   pegawai_kd        varchar(50)   🔗 FK ke m_pegawai
   pegawai_kode      varchar(100)  🗑️ Denormalisasi NIP
   pegawai_nama      varchar(100)  🗑️ Denormalisasi nama guru
   rpp_postdate      datetime      Tanggal upload RPP
   rpp_acc           enum          Status approve RPP
   rpp_acc_postdate  datetime      Tanggal approve
   rpp_acc_ket       longtext      Catatan approve
   silabus_postdate  datetime      Tanggal upload silabus
   silabus_acc       enum          Status approve silabus
   silabus_acc_postdate datetime
   silabus_acc_ket   longtext

⚠️ MASALAH KRITIS: Tabel ini mencampur 3 concern:
   1. Master Mapel (kode, nama, jenis)
   2. Guru Assignment (pegawai_kd, per tapel+kelas)
   3. RPP/Silabus Approval (rpp_*, silabus_*)

Target: SPLIT MENJADI 3 TABEL:
  mapel (id, school_id, kode, nama, kelompok, created_at, updated_at)
  guru_mapel (id, school_id, pegawai_id FK, mapel_id FK, kelas_id FK,
              tapel_id FK, kkm DECIMAL(5,2), created_at, updated_at)
  → RPP approval pindah ke fileboxes.status
```

### 10. `m_mapel_jns` — Jenis/Kelompok Mata Pelajaran
```
Engine: MyISAM | Charset: latin1 | Columns: 5

🔑 kd       varchar(50)   PK
   no        varchar(1)    Kode (A/B/C)
   no_sub    varchar(5)    Sub-kode
   jenis     varchar(100)  Nama jenis (Wajib A, Wajib B, Peminatan)
   postdate  datetime

Target: jenis_mapel (id, school_id, kode, nama, created_at, updated_at)
```

### 11. `m_mapel_deskripsi` — Deskripsi Mapel untuk Raport
```
Engine: MyISAM | Charset: latin1 | Columns: 12

🔑 kd           varchar(50)   PK
   tapel         varchar(100)  📛
   kelas         varchar(100)  📛
   jenis         varchar(100)  Kelompok
   no            varchar(5)    Nomor urut
   kode          varchar(50)   Kode mapel
   nama          varchar(100)  Nama mapel
   smt1_p_isi    longtext      Deskripsi Pengetahuan semester 1
   smt1_k_isi    longtext      Deskripsi Keterampilan semester 1
   smt2_p_isi    longtext      Deskripsi Pengetahuan semester 2
   smt2_k_isi    longtext      Deskripsi Keterampilan semester 2
   postdate      datetime

Target: MERGE → raport_nilai.deskripsi_p, deskripsi_k
```

### 12. `m_walikelas` — Assignment Wali Kelas
```
Engine: MyISAM | Charset: latin1 | Columns: 9

🔑 kd           varchar(50)   PK
   tapel_kd      varchar(50)   🔗 FK ke m_tapel
   tapel_nama    varchar(100)  🗑️ Denormalisasi
   kelas_kd      varchar(100)  🔗 FK ke m_kelas
   kelas_nama    varchar(100)  🗑️ Denormalisasi
   peg_kd        varchar(50)   🔗 FK ke m_pegawai
   peg_kode      varchar(100)  🗑️ Denormalisasi NIP
   peg_nama      varchar(100)  🗑️ Denormalisasi nama
   postdate      datetime

Target: wali_kelas (id, school_id, pegawai_id FK, kelas_id FK,
        tapel_id FK, created_at, updated_at)
Note:   6 dari 9 kolom adalah denormalisasi → HAPUS
```

### 13-15. Role Assignment Tables

```
m_ks (5 cols)         — Kepala Sekolah → peg_kd FK ke m_pegawai
m_gurubk (5 cols)     — Guru BK        → peg_kd FK ke m_pegawai
m_bendahara (5 cols)  — Bendahara      → peg_kd FK ke m_pegawai
m_sarpras (5 cols)    — Sarpras        → peg_kd FK ke m_pegawai
m_piket (8 cols)      — Petugas Piket  → kode FK ke m_pegawai (+ auth sendiri)

Semua punya pattern sama:
  kd, peg_kd, peg_kode🗑️, peg_nama🗑️, postdate
  
m_piket EXTRA: usernamex, passwordx⚠️, qrcode, jabatan

Target: SEMUA → Spatie roles & permissions
        m_piket → piket_jadwal (karena ada jadwal hari piket)
```

---

## GRUP 3: AKADEMIK — JADWAL & WAKTU (4 Tabel)

### 16. `jadwal` — Jadwal Pelajaran
```
Engine: MyISAM | Charset: latin1 | Columns: 11

🔑 kd           varchar(50)   PK
   tapel         varchar(100)  📛 Tahun pelajaran
   smt           varchar(1)    Semester
   kelas         varchar(100)  📛 Nama kelas
   hari          varchar(100)  Nama hari
   hari_no       varchar(1)    Nomor hari (1=Senin)
   jam_ke        varchar(5)    Jam ke-berapa
   waktu         varchar(100)  Range waktu "07:00-07:45"
   mapel_kode    varchar(50)   🔗 Kode mapel
   mapel_nama    varchar(100)  🗑️ Denormalisasi
   postdate      datetime

Target: jadwal (id, school_id, tapel_id FK, semester TINYINT,
        kelas_id FK, hari ENUM, jam_ke TINYINT,
        waktu_mulai TIME, waktu_selesai TIME,
        guru_mapel_id FK, ruang_id FK NULL,
        created_at, updated_at)
```

### 17. `m_hari` — Master Hari
```
Columns: 3 (kd, no, hari)
Target:  HAPUS → gunakan ENUM('senin','selasa',...,'sabtu') di jadwal
```

### 18. `m_jam` — Master Jam
```
Columns: 2 (kd, jam)
Target:  HAPUS → jam_ke langsung di jadwal sebagai TINYINT
```

### 19. `m_waktu` — Setting Waktu Presensi
```
Columns: 6 (kd, masuk_jam, masuk_menit, pulang_jam, pulang_menit, postdate)
Target:  MERGE → settings (key='presensi_waktu', value=JSON{masuk,pulang})
```

### 20. `m_waktu_jadwal` — Waktu per Jam Pelajaran
```
Columns: 6 (nourut PK, hari_no, hari_nama, jam_ke, waktu, ket)
Target:  MERGE → jadwal.waktu_mulai, jadwal.waktu_selesai
         atau settings (key='jam_pelajaran', value=JSON[...])
```

---

## GRUP 4: AKADEMIK — PRESENSI & ABSENSI (5 Tabel)

### 21. `user_presensi` — Presensi Kehadiran
```
Engine: MyISAM | Charset: latin1 | Columns: 16

🔑 kd              varchar(50)   PK
   user_kd          varchar(50)   🔗 FK ke pegawai/siswa
   user_kode        varchar(100)  🗑️ NIS/NIP
   user_nama        varchar(100)  🗑️ Nama
   user_jabatan     varchar(100)  🗑️ Jabatan
   user_kelas       varchar(100)  🗑️ Kelas
   user_tapel       varchar(100)  🗑️📛 Tapel
   tanggal          date          Tanggal presensi (⚠️ kadang NULL)
   postdate         datetime      Waktu record
   status           varchar(100)  Status: MASUK/PULANG
   ket              longtext      Keterangan
   telat_ket        varchar(100)  TERLAMBAT atau -
   telat_jam        varchar(5)    💀 Jam telat (harusnya INT)
   telat_menit      varchar(5)    💀 Menit telat
   dibaca           enum          Sudah dibaca notif
   dibaca_postdate  datetime      Waktu dibaca

Target: presensi (id, school_id, presensable_type, presensable_id,
        tanggal DATE, jam_masuk TIME, jam_pulang TIME NULL,
        status ENUM('hadir','sakit','ijin','alpha','telat'),
        telat_menit SMALLINT DEFAULT 0, keterangan TEXT NULL,
        metode ENUM('qrcode','manual'), pencatat_id FK NULL,
        tapel_id FK, created_at, updated_at)
Note:   10 dari 16 kolom adalah denormalisasi → HAPUS
        Polymorphic: presensable = Pegawai|Siswa
```

### 22. `user_absensi` — Ketidakhadiran
```
Columns: 14 (similar denormalized pattern)
Target:  MERGE → presensi (status = 'sakit'|'ijin'|'alpha')
         atau tetap tabel sendiri: absensi (id, school_id, ...)
```

### 23. `user_ijin` — Surat Ijin Meninggalkan
```
Engine: MyISAM | Charset: latin1 | Columns: 18

🔑 kd              varchar(50)   PK
   user_kd          varchar(50)   🔗 FK ke user
   user_kode        varchar(100)  🗑️
   user_nama        varchar(100)  🗑️
   user_jabatan     varchar(100)  🗑️
   user_kelas       varchar(100)  🗑️
   user_tapel       varchar(100)  🗑️📛
   tanggal          date          Tanggal ijin (⚠️ kadang NULL)
   postdate         datetime
   status           varchar(100)  Sakit/Ijin/Pulang Awal
   ket              varchar(100)  Keterangan
   piket_kd         varchar(50)   🔗 FK petugas piket
   piket_kode       varchar(100)  🗑️
   piket_nama       varchar(100)  🗑️
   piket_jabatan    varchar(100)  🗑️
   sahya            enum          Disahkan/belum
   sahya_tgl        date          Tanggal sahkan
   sahya_qrcode     varchar(100)  QR code verifikasi

Target: surat_ijin (id, school_id, pemohon_type, pemohon_id,
        tanggal DATE, status ENUM('sakit','ijin','pulang_awal'),
        alasan TEXT, pencatat_id FK, disetujui BOOLEAN,
        disetujui_at TIMESTAMP NULL, qrcode, pdf_path,
        created_at, updated_at)
```

### 24. `user_piket` — Catatan Piket Harian
```
Columns: 10
Target: piket_catatan (id, school_id, piket_jadwal_id FK,
        tanggal DATE, catatan TEXT, created_at, updated_at)
```

### 25. `rev_guru_absensi` — Absensi Mapel (Jurnal Guru)
```
Columns: 17 (pegawai, mapel, siswa, tanggal, absensi H/S/I/A)
Target: absensi_mapel (id, jurnal_id FK, siswa_id FK,
        status ENUM('hadir','sakit','ijin','alpha'), keterangan)
```

### 26. `rev_guru_agenda` — Agenda Mengajar (Jurnal Guru)
```
Columns: 21 (pegawai, mapel, tanggal, jam, pertemuan, materi, dll)
Target: jurnal_mengajar (id, school_id, guru_mapel_id FK, jadwal_id FK NULL,
        tanggal DATE, jam TEXT, pertemuan_ke TINYINT,
        materi TEXT, indikator TEXT, catatan TEXT, tindak_lanjut TEXT,
        wk_catatan TEXT NULL, wk_postdate TIMESTAMP NULL,
        created_at, updated_at)
```

---

## GRUP 5: AKADEMIK — PENILAIAN & RAPORT (13 Tabel)

### 27. `siswa_nilai_bln` — Nilai Bulanan
```
Columns: 17

🔑 kd           varchar(50)
   siswa_kode    varchar(100)  🗑️
   siswa_nama    varchar(100)  🗑️
   tapel         varchar(100)  📛
   kelas         varchar(100)  📛
   smt           varchar(100)  Semester
   jenis         varchar(100)  Kelompok mapel
   mapel_no      varchar(5)    Nomor urut mapel
   mapel_kode    varchar(100)  Kode mapel
   mapel_nama    varchar(100)  🗑️ Nama mapel
   thn           varchar(4)    Tahun
   bln           varchar(2)    Bulan
   kode          varchar(100)  ⚠️ Duplicate of siswa_kode
   nilai         varchar(5)    💀 Nilai (harusnya DECIMAL)
   kategori      varchar(100)  P (Pengetahuan) / K (Keterampilan)
   postdate      datetime
   entri_oleh    varchar(100)  Nama yang mengentri

Target: nilai_bulanan (id, school_id, siswa_id FK, guru_mapel_id FK,
        tapel_id FK, semester TINYINT, tahun YEAR, bulan TINYINT,
        kategori ENUM('pengetahuan','keterampilan'),
        nilai DECIMAL(5,2), pencatat_id FK NULL,
        created_at, updated_at)
```

### 28. `siswa_nilai_smt` — Nilai Semester
```
Columns: 26

Key columns:
   siswa_kode, siswa_nama🗑️, tapel📛, kelas📛, smt, jenis,
   mapel_no, mapel_kode, mapel_nama🗑️,
   p_bln_rata    varchar(5)  💀 Rata-rata bulanan P
   k_bln_rata    varchar(5)  💀 Rata-rata bulanan K
   p_ph_nilai    varchar(5)  💀 Penilaian Harian P
   k_ph_nilai    varchar(5)  💀 Penilaian Harian K
   p_pts_nilai   varchar(5)  💀 PTS P
   k_pts_nilai   varchar(5)  💀 PTS K
   p_pas_nilai   varchar(5)  💀 PAS P
   k_pas_nilai   varchar(5)  💀 PAS K
   p_na          varchar(5)  💀 Nilai Akhir P
   p_na_pred     varchar(5)  Predikat P (A/B/C/D)
   k_na          varchar(5)  💀 Nilai Akhir K
   k_na_pred     varchar(5)  Predikat K
   p_isi         longtext    Deskripsi P
   k_isi         longtext    Deskripsi K
   postdate, entri_oleh

Target: nilai_semester (id, school_id, siswa_id FK, guru_mapel_id FK,
        tapel_id FK, semester, rata_bln_p DECIMAL, rata_bln_k DECIMAL,
        ph_p DECIMAL, ph_k DECIMAL, pts_p DECIMAL, pts_k DECIMAL,
        pas_p DECIMAL, pas_k DECIMAL,
        na_p DECIMAL, predikat_p CHAR(1),
        na_k DECIMAL, predikat_k CHAR(1),
        deskripsi_p TEXT, deskripsi_k TEXT,
        pencatat_id FK NULL, created_at, updated_at)
```

### 29. `siswa_nilai_thn` — Nilai Tahunan
```
Columns: 21 (similar to smt + p_na_smt1/smt2, p_pat_nilai)

Target: nilai_tahunan (id, school_id, siswa_id FK, guru_mapel_id FK,
        tapel_id FK, na_p_smt1 DECIMAL, na_p_smt2 DECIMAL,
        na_k_smt1 DECIMAL, na_k_smt2 DECIMAL,
        pat_p DECIMAL, pat_k DECIMAL,
        na_p DECIMAL, na_k DECIMAL,
        predikat_p CHAR(1), predikat_k CHAR(1),
        pencatat_id FK NULL, created_at, updated_at)
```

### 30-33. `siswa_raport_*` — Komponen Raport (4 Tabel)

```
siswa_raport_sikap (12 cols):
  siswa_kode, tapel📛, kelas📛, smt,
  spiritual_predikat, spiritual_isi, sosial_predikat, sosial_isi
  → raport_sikap (id, raport_id FK, ...)

siswa_raport_catatan (9 cols):
  siswa_kode, tapel📛, kelas📛, smt, isi (catatan wali kelas)
  → raport_catatan (id, raport_id FK, catatan TEXT, ...)

siswa_raport_kenaikan (9 cols):
  siswa_kode, tapel📛, kelas📛, status, baru_tapel, baru_kelas
  → raport_kenaikan (id, raport_id FK, status ENUM('naik','tinggal','lulus'),
     ke_kelas VARCHAR NULL)

siswa_raport_rangking (13 cols):
  siswa_kode, tapel📛, kelas📛, smt,
  total_p💀, rata_p💀, total_k💀, rata_k💀, total💀, rangking💀
  → raport_ranking (id, raport_id FK, ranking SMALLINT,
     jumlah_siswa SMALLINT, rata_rata DECIMAL)
```

### 34. `siswa_mapel_absensi` — Absensi per Mata Pelajaran
```
Columns: 17 (siswa, tapel, kelas, mapel, pertemuan, tanggal, absensi H/S/I/A)
Target:  → absensi_mapel (sudah di-cover via jurnal_mengajar)
```

### 35. `siswa_saran` — Saran/Catatan untuk Siswa
```
Columns: 12 (siswa, tapel, kelas, smt, saran)
Target:  → MERGE ke raport_catatan atau tabel sendiri jika berbeda use-case
```

---

## GRUP 6: KURIKULUM MERDEKA (10 Tabel)

### 36-37. Master TP & LM
```
kurmer_mapel_tp (9 cols):  Tujuan Pembelajaran per mapel
  tapel📛, kelas📛, kode(mapel), nama(mapel)🗑️, smt, tp_kode, tp_nama
  
kurmer_mapel_lm (9 cols):  Lingkup Materi per mapel
  tapel📛, kelas📛, kode(mapel), nama(mapel)🗑️, smt, lm_kode, lm_nama

Target:
  kurmer_tp (id, school_id, guru_mapel_id FK, tapel_id FK,
             semester, kode VARCHAR(5), nama TEXT, created_at, updated_at)
  kurmer_lm (id, school_id, guru_mapel_id FK, tapel_id FK,
             semester, kode VARCHAR(5), nama TEXT, created_at, updated_at)
```

### 38. `kurmer_asesmen_formatif` — Master Asesmen Formatif
```
Columns: 9 (tapel, kelas, kode, nama, smt, desk_tinggi, desk_rendah)

Target: kurmer_asesmen (id, school_id, guru_mapel_id FK, tapel_id FK,
        semester, tipe ENUM('formatif','sumatif'), kktp DECIMAL NULL,
        deskripsi_tinggi TEXT, deskripsi_rendah TEXT,
        created_at, updated_at)
```

### 39-42. Nilai Asesmen (Formatif + Sumatif — Header & Detail)
```
kurmer_nilai_asesmen_formatif (13 cols):     Header per siswa
kurmer_nilai_asesmen_formatif_detail (14):   Detail per TP
kurmer_nilai_asesmen_sumatif (16 cols):      Header per siswa + aggregates
kurmer_nilai_asesmen_sumatif_detail (14):    Detail per LM

Target (normalized):
  kurmer_nilai_asesmen (id, asesmen_id FK, siswa_id FK,
      -- sumatif only:
      lm_na DECIMAL NULL, as_non_tes DECIMAL NULL,
      as_tes DECIMAL NULL, as_na DECIMAL NULL, nil_raport DECIMAL NULL,
      created_at, updated_at)
      
  kurmer_nilai_asesmen_detail (id, nilai_asesmen_id FK,
      referensi_type ENUM('tp','lm'),
      referensi_id FK, -- tp_id atau lm_id
      nilai DECIMAL(5,2), keterangan TEXT NULL)
```

### 43-45. Proyek P5
```
kurmer_proyek (7 cols):          Master proyek (judul, isi)
kurmer_proyek_detail (12 cols):  Dimensi × elemen × sub_elemen × target
kurmer_nilai_proyek (9 cols):    Nilai per siswa × dimensi
kurmer_nilai_proyek_proses (8):  Catatan proses per siswa

Target:
  kurmer_proyek (id, school_id, kelas_id FK, tapel_id FK,
      semester, no TINYINT, judul TEXT, deskripsi TEXT,
      created_at, updated_at)

  kurmer_proyek_dimensi (id, proyek_id FK, no TINYINT,
      dimensi TEXT, elemen TEXT, sub_elemen TEXT, target TEXT)

  kurmer_nilai_proyek (id, proyek_id FK, siswa_id FK,
      created_at, updated_at)

  kurmer_nilai_proyek_detail (id, nilai_proyek_id FK,
      dimensi_id FK, nilai DECIMAL(5,2), keterangan TEXT NULL)

  kurmer_catatan_proses (id, proyek_id FK, siswa_id FK,
      catatan TEXT, created_at, updated_at)
```

---

## GRUP 7: KEUANGAN SISWA (6 Tabel)

### 46. `m_keu_siswa` — Item Pembayaran
```
Columns: 9 (tapel📛, smt, kelas📛, thn, bln, nama, nominal💀)

Target: keu_item (id, school_id, tapel_id FK, semester TINYINT,
        kelas_id FK NULL, tahun YEAR, bulan TINYINT NULL,
        nama VARCHAR(100), nominal DECIMAL(12,2),
        created_at, updated_at)
```

### 47. `siswa_bayar_tagihan` — Tagihan Siswa
```
Columns: 19 (heavy denormalization)
  siswa_kd🔗, siswa_tapel🗑️, siswa_kelas🗑️, siswa_kode🗑️, siswa_nama🗑️,
  item_kd🔗, item_nama🗑️, item_tapel🗑️, item_smt🗑️, item_kelas🗑️,
  item_thn🗑️, item_bln🗑️, item_nominal💀,
  nominal_bayar💀, nominal_kurang💀,
  lunas_status enum, lunas_postdate

Target: tagihan (id, school_id, siswa_id FK, keu_item_id FK,
        nominal DECIMAL(12,2), terbayar DECIMAL(12,2) DEFAULT 0,
        sisa DECIMAL(12,2),
        status ENUM('belum','sebagian','lunas') DEFAULT 'belum',
        lunas_at TIMESTAMP NULL, created_at, updated_at)
Note:   12 dari 19 kolom adalah denormalisasi → HAPUS
```

### 48. `siswa_bayar` — Header Pembayaran
```
Columns: 12 (siswa denorm, kode transaksi, tgl, nominal)

Target: pembayaran (id, school_id, siswa_id FK,
        kode_transaksi VARCHAR(50) UNIQUE,
        tanggal DATE, total DECIMAL(12,2),
        pencatat_id FK, keterangan TEXT NULL,
        created_at, updated_at)
```

### 49. `siswa_bayar_rincian` — Detail Pembayaran
```
Columns: 18 (heavy denormalization per item)

Target: pembayaran_detail (id, pembayaran_id FK, tagihan_id FK,
        jumlah DECIMAL(12,2), created_at)
Note:   15 dari 18 kolom denormalisasi → HAPUS
```

### 50. `m_tabungan` — Setting Tabungan
```
Columns: 5 (min_debet💀, max_kredit💀, min_saldo💀)

Target: → settings (key='tabungan_config', value=JSON{min_setor,max_tarik,min_saldo})
        Atau: tabungan_setting (id, school_id, min_setor DECIMAL, max_tarik DECIMAL,
              min_saldo DECIMAL, created_at)
```

### 51. `wa_tagihan_siswa` — Antrian WA Notifikasi
```
Columns: 8 (kelas, siswa_nis, siswa_nama, siswa_nowa, terkirim enum, nominal💀)

Target: wa_notifications (id, school_id, siswa_id FK,
        pesan TEXT, nominal DECIMAL(12,2),
        status ENUM('pending','sent','failed'),
        sent_at TIMESTAMP NULL, created_at)
```

---

## GRUP 8: BK — BIMBINGAN KONSELING (5 Tabel)

### 52. `m_bk_point_jenis` — Jenis Pelanggaran
```
Columns: 3 (kd, no, jenis)
Target: bk_jenis (id, school_id, kode, nama, created_at, updated_at)
```

### 53. `m_bk_point` — Point Pelanggaran
```
Columns: 8 (jenis_kd🔗, jenis_nama🗑️, no, nama, point💀, sanksi)
Target: bk_point (id, school_id, jenis_id FK, kode, nama TEXT,
        point SMALLINT, sanksi TEXT, created_at, updated_at)
```

### 54. `m_bk_prestasi` — Master Prestasi
```
Columns: 4 (no, nama, point💀)
Target: bk_prestasi_master (id, school_id, kode, nama TEXT,
        point SMALLINT, created_at, updated_at)
```

### 55. `siswa_pelanggaran` — Catatan Pelanggaran
```
Columns: 26 (HEAVIEST denormalization in entire system)

Key data: siswa_kd🔗, tgl, jenis_kd🔗, point_kd🔗, point_nilai💀,
          point_sanksi, piket_kd🔗, sahya enum, bina_*
Denormalized: tapel_nama🗑️, kelas_nama🗑️, siswa_nis🗑️, siswa_nama🗑️,
              jenis_kode🗑️, jenis_nama🗑️, point_kode🗑️, point_nama🗑️,
              piket_kode🗑️, piket_nama🗑️, piket_jabatan🗑️

Target: pelanggaran (id, school_id, siswa_id FK, tapel_id FK,
        tanggal DATE, bk_point_id FK, point_nilai SMALLINT,
        pencatat_type, pencatat_id FK,
        disahkan BOOLEAN DEFAULT false, disahkan_at TIMESTAMP NULL,
        bina_tanggal DATE NULL, bina_pembina_id FK NULL,
        bina_nama TEXT NULL, bina_keterangan TEXT NULL,
        created_at, updated_at, deleted_at)
Note:   14 dari 26 kolom adalah denormalisasi → HAPUS
```

### 56. `siswa_prestasi` — Catatan Prestasi
```
Columns: 19 (same denorm pattern)
Target: prestasi (id, school_id, siswa_id FK, tapel_id FK,
        tanggal DATE, bk_prestasi_id FK NULL, nama TEXT,
        point SMALLINT, keterangan TEXT,
        pencatat_type, pencatat_id FK,
        disahkan BOOLEAN, disahkan_at TIMESTAMP NULL,
        created_at, updated_at)
```

### 57. `m_pembinaan` — Master Jenis Pembinaan
```
Columns: 5 (nama, pembina_kode🗑️, pembina_nama🗑️)
Target:  → jika perlu: bk_jenis_pembinaan (id, school_id, nama, created_at)
         → atau langsung free text di pelanggaran.bina_nama
```

---

## GRUP 9: INVENTARIS (8 Tabel)

### 58-63. `inv_kib_a` sampai `inv_kib_f` — KIB A-F

```
KIB A (Tanah):      16 cols — luas, alamat, status_hak, sertifikat
KIB B (Peralatan):  20 cols — jumlah, satuan, merk, no_pabrik/rangka/mesin/polisi/bpkb
KIB C (Gedung):     20 cols — tingkat, beton, luas_lantai, dokumen
KIB D (Jalan):      20 cols — konstruksi, panjang, lebar, luas, lokasi
KIB E (Aset Lain):  19 cols — buku_judul/spek, corak, hewan_jenis
KIB F (Konstruksi): 18 cols — tingkat, beton, mulai_tgl

Shared columns (all KIB): kd, per_tahun, barang_kode, barang_nama,
                           register, asal_usul, harga💀, ket, postdate

Target: SINGLE TABLE with JSON:
  inventaris (id, school_id, tipe ENUM('a','b','c','d','e','f'),
      per_tahun YEAR, kode_barang VARCHAR(100), nama_barang VARCHAR(100),
      register VARCHAR(100), asal_usul VARCHAR(100),
      harga DECIMAL(15,2), kondisi VARCHAR(100), keterangan TEXT NULL,
      detail JSON,  -- type-specific fields
      created_at, updated_at, deleted_at)

  Detail JSON examples:
    KIB A: {"luas":"500","alamat":"Jl...","status_hak":"Milik","sertifikat_tgl":"..."}
    KIB B: {"jumlah":"10","satuan":"Unit","merk":"HP","no_pabrik":"..."}
```

### 64. `m_kib_jenis` — Jenis KIB
```
Columns: 3 (kd, no, jenis)
Target: → ENUM di inventaris.tipe (atau config constant)
```

### 65. `m_kib_kode` — Hierarki Kode Barang
```
Columns: 9 (golongan, bidang, kelompok, sub, sub_sub, kode, nama)
Target: inventaris_kode (id, school_id, level TINYINT,
        kode VARCHAR(50), nama VARCHAR(100),
        parent_id FK NULL (self-referencing), created_at, updated_at)
```

---

## GRUP 10: EKSTRA & LAIN-LAIN (4 Tabel)

### 66. `m_ekstra` — Master Ekstrakurikuler
```
Columns: 6 (nama, pegawai_kd🔗, pegawai_kode🗑️, pegawai_nama🗑️)
Target: ekstrakurikuler (id, school_id, nama, pembina_id FK,
        hari VARCHAR NULL, waktu VARCHAR NULL, created_at, updated_at)
```

### 67. `siswa_ekstra` — Ekstra per Siswa
```
Columns: 13 (siswa, tapel, kelas, ekstra — all denormalized)
Target: siswa_ekstra (id, siswa_id FK, ekstra_id FK, tapel_id FK,
        predikat VARCHAR(20) NULL, keterangan TEXT NULL,
        created_at, updated_at)
```

### 68-69. `siswa_soal` + `siswa_soal_nilai` — Bank Soal
```
siswa_soal (9 cols): guru_mapel, jadwal, siswa, soal, jawab, kunci, benar
siswa_soal_nilai (14 cols): jml_benar, jml_salah, skor, waktu

Target: (DEFERRED — low priority for MVP)
  soal, soal_jawaban, soal_nilai (future module)
```

### 70. `siswa_tugas` — Tugas Siswa
```
Columns: 9 (guru_mapel, siswa, tugas, file, nilai)
Target: (DEFERRED — incorporate into Google Drive Filebox module)
```

---

## GRUP 11: LOG & AUDIT (3 Tabel)

### 71. `user_log_login` — Log Login
```
Columns: 12 (user info🗑️, ip, lat/lng, dibaca, postdate)
Target: → Spatie ActivityLog (automatic)
        atau: login_log (id, school_id, user_id FK, ip, user_agent,
              lat DECIMAL, lng DECIMAL, created_at)
```

### 72. `user_log_entri` — Log Aktivitas Menu
```
Columns: 10 (user info🗑️, ket, dibaca, postdate)
Target: → Spatie ActivityLog (automatic, replaces both tables)
```

### 73. `user_filebox` — Metadata File Upload
```
Columns: 11 (user info🗑️, judul, kategori, ket, filex, postdate)
Target: fileboxes (id, school_id, user_id FK,
        kategori ENUM('rpp','silabus','materi','soal','tugas','arsip','jurnal'),
        judul VARCHAR, keterangan TEXT NULL,
        gdrive_file_id VARCHAR NULL, gdrive_url VARCHAR NULL,
        local_path VARCHAR NULL, mime_type VARCHAR NULL, file_size INT NULL,
        status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
        approved_by FK NULL, approved_at TIMESTAMP NULL,
        created_at, updated_at, deleted_at)
```

---

# ═══════════════════════════════════════════════════════════════
# PART 2 — DENORMALIZATION AUDIT SUMMARY
# ═══════════════════════════════════════════════════════════════

```
TOTAL LEGACY COLUMNS: 856

COLUMN CLASSIFICATION:
━━━━━━━━━━━━━━━━━━━━━
🔑 Primary Keys (kd):            75 columns
🗑️ Denormalized (redundant data): ~280 columns (33%!)
💀 Wrong type (VARCHAR for nums):  ~85 columns (10%)
📛 Encoded data (cegah/nosql):     ~45 columns (5%)
✅ Legitimate data columns:        ~371 columns (43%)

TOP OFFENDERS (most denormalized tables):
  siswa_pelanggaran:  14 of 26 denormalized (54%)
  siswa_prestasi:     11 of 19 denormalized (58%)
  siswa_bayar_rincian: 15 of 18 denormalized (83%)
  siswa_bayar_tagihan: 12 of 19 denormalized (63%)
  user_presensi:      10 of 16 denormalized (63%)
  user_absensi:       10 of 14 denormalized (71%)
```

---

# ═══════════════════════════════════════════════════════════════
# PART 3 — TARGET TABLE SUMMARY
# ═══════════════════════════════════════════════════════════════

```
LEGACY 75 TABLES → TARGET ~50 TABLES

PLATFORM (non-tenant):
  1. schools
  2. school_modules  
  3. platform_users

CORE MODULE (always active):
  4. users
  5. pegawai
  6. siswa
  7. kelas
  8. tahun_pelajaran
  9. ruang
  10. settings
  (Spatie: roles, permissions, model_has_roles, model_has_permissions,
           role_has_permissions → 5 tables auto)

AKADEMIK MODULE (always active):
  11. mapel
  12. jenis_mapel
  13. guru_mapel
  14. wali_kelas
  15. jadwal
  16. presensi
  17. surat_ijin
  18. jurnal_mengajar
  19. absensi_mapel
  20. ekstrakurikuler
  21. siswa_ekstra
  22. piket_jadwal
  23. piket_catatan
  24. nilai_bulanan
  25. nilai_semester
  26. nilai_tahunan
  27. raport
  28. raport_sikap
  29. raport_catatan
  30. raport_kenaikan
  31. raport_ranking

KURIKULUM MERDEKA (plug-n-play):
  32. kurmer_tp
  33. kurmer_lm
  34. kurmer_asesmen
  35. kurmer_nilai_asesmen
  36. kurmer_nilai_asesmen_detail
  37. kurmer_proyek
  38. kurmer_proyek_dimensi
  39. kurmer_nilai_proyek
  40. kurmer_nilai_proyek_detail
  41. kurmer_catatan_proses

KEUANGAN SISWA (plug-n-play):
  42. keu_item
  43. tagihan
  44. pembayaran
  45. pembayaran_detail
  46. tabungan_transaksi
  47. wa_notifications

BK (plug-n-play):
  48. bk_jenis
  49. bk_point
  50. pelanggaran
  51. prestasi

INVENTARIS (plug-n-play):
  52. inventaris
  53. inventaris_kode

FILEBOX (plug-n-play):
  54. fileboxes

AUDIT (via Spatie):
  55. activity_log (auto)
  56. notifications (Laravel built-in)

TOTAL: ~56 tables (including Spatie auto-tables)
vs LEGACY: 75 tables → 25% REDUCTION with BETTER normalization
```

---

# ═══════════════════════════════════════════════════════════════
# PART 4 — CRITICAL DATA TRANSFORMATION RULES
# ═══════════════════════════════════════════════════════════════

## 4.1 ID Transformation

```
LEGACY:  kd = MD5(random+timestamp)  →  varchar(50)
TARGET:  id = auto_increment          →  BIGINT UNSIGNED

PROCESS:
  1. Create mapping table: legacy_id_map (legacy_table, legacy_kd, new_id)
  2. Migrate each table sequentially
  3. For each row: INSERT → get new auto-increment ID → store mapping
  4. For FK columns: lookup mapping table → replace legacy_kd with new_id
```

## 4.2 Character Decoding

```php
// MUST run on ALL text/varchar columns during migration
function decodeLegacy(string $value): string {
    $map = [
        'xgmringx'    => '/',    // CRITICAL: tapel "2022/2023"
        'xtkeongx'    => '@',    // emails
        'xstrix'      => '-',    // dates, names with dash
        'xstripbwhx'  => '_',    // usernames
        'xpsijix'     => "'",   // apostrophes
        'xpersenx'    => '%',
        'xgwahx'      => '_',
        'x1smdgan1x'  => '1=1',
        'xpentungx'   => '!',
        'xkkirix'     => '<',
        'xkkananx'    => '>',
        'xkkurix'     => '(',
        'xkkurnanx'   => ')',
        'xkommax'     => ';',
    ];
    return str_replace(array_keys($map), array_values($map), $value);
}
```

## 4.3 Type Conversions

```
VARCHAR → DECIMAL(5,2):   nilai, kkm, rata, na, skor
VARCHAR → DECIMAL(12,2):  nominal, harga, bayar, kurang, saldo
VARCHAR → DECIMAL(9,6):   lat_x, lat_y (coordinates)
VARCHAR → SMALLINT:        point_nilai, jml_*, telat_menit, jam_ke
VARCHAR → TINYINT:         no_urut, semester, bulan, tingkat
VARCHAR → YEAR:            tahun, per_tahun, tahun_beli
VARCHAR → DATE:            tgl (already date type in some tables)
VARCHAR → TIME:            masuk_jam+masuk_menit → TIME
ENUM('true','false') → BOOLEAN
MD5 password → bcrypt (users MUST reset password)
```

## 4.4 Tenant ID Injection

```sql
-- EVERY migrated row gets school_id = {target_school_id}
-- Example:
INSERT INTO pegawai (school_id, nip, nama, jabatan, ...)
SELECT {SCHOOL_ID}, kode, nama, jabatan, ...
FROM legacy.m_pegawai;
```

---

# ═══════════════════════════════════════════════════════════════
# PART 5 — RELATIONSHIP MAP (TARGET)
# ═══════════════════════════════════════════════════════════════

```
schools ─────────────────────────────────────────────────┐
  │ 1:N                                                  │
  ├── users (polymorphic: userable → pegawai|siswa)      │
  ├── pegawai ─┬── guru_mapel ──── mapel                 │
  │            ├── wali_kelas ──── kelas                  │
  │            ├── piket_jadwal                            │
  │            └── jurnal_mengajar                         │
  ├── siswa ───┬── nilai_bulanan                          │
  │            ├── nilai_semester                          │
  │            ├── nilai_tahunan                           │
  │            ├── raport ──┬── raport_sikap               │
  │            │            ├── raport_catatan              │
  │            │            ├── raport_kenaikan             │
  │            │            └── raport_ranking              │
  │            ├── presensi (polymorphic)                   │
  │            ├── surat_ijin (polymorphic)                 │
  │            ├── tagihan ──── pembayaran_detail           │
  │            ├── pembayaran ── pembayaran_detail          │
  │            ├── tabungan_transaksi                       │
  │            ├── pelanggaran                              │
  │            ├── prestasi                                 │
  │            ├── siswa_ekstra ── ekstrakurikuler          │
  │            ├── kurmer_nilai_asesmen                     │
  │            └── kurmer_nilai_proyek                      │
  ├── kelas                                                │
  ├── tahun_pelajaran                                      │
  ├── ruang                                                │
  ├── mapel ──── jenis_mapel                               │
  ├── jadwal                                               │
  ├── keu_item ── tagihan                                  │
  ├── bk_jenis ── bk_point ── pelanggaran                 │
  ├── inventaris ── inventaris_kode                         │
  ├── fileboxes                                            │
  ├── kurmer_asesmen                                       │
  ├── kurmer_proyek ── kurmer_proyek_dimensi               │
  ├── settings                                             │
  └── school_modules                                       │
       (semua tabel scoped by school_id)──────────────────┘
```

---

# ═══════════════════════════════════════════════════════════════
# PART 6 — INDEX STRATEGY
# ═══════════════════════════════════════════════════════════════

```
INDEXES YANG WAJIB DIBUAT (Performance Critical):

COMPOSITE INDEXES:
  presensi:            (school_id, tanggal, presensable_type, presensable_id)
  nilai_bulanan:       (school_id, siswa_id, guru_mapel_id, tapel_id, semester, bulan)
  nilai_semester:      (school_id, siswa_id, tapel_id, semester)
  tagihan:             (school_id, siswa_id, status)
  pelanggaran:         (school_id, siswa_id, tapel_id)
  jadwal:              (school_id, tapel_id, semester, kelas_id, hari)
  guru_mapel:          (school_id, pegawai_id, tapel_id)
  siswa:               (school_id, kelas_id, tapel_id)

UNIQUE INDEXES:
  users:               (school_id, username)
  pegawai:             (school_id, nip)
  siswa:               (school_id, nis)
  wali_kelas:          (school_id, kelas_id, tapel_id)
  presensi:            (school_id, presensable_type, presensable_id, tanggal) 
                       -- 1x per hari
  pembayaran:          (school_id, kode_transaksi)

FOREIGN KEYS (InnoDB):
  Semua _id columns → REFERENCES parent(id) ON DELETE CASCADE/RESTRICT
  school_id → REFERENCES schools(id) ON DELETE CASCADE
```

---

# ═══════════════════════════════════════════════════════════════
# PART 7 — MIGRATION EXECUTION ORDER
# ═══════════════════════════════════════════════════════════════

```
MIGRATION ORDER (Laravel artisan migrate):
Dependencies must be created first.

BATCH 1 — Platform:
  001_create_schools_table
  002_create_platform_users_table
  003_create_school_modules_table

BATCH 2 — Core (no FK to other modules):
  010_create_users_table
  011_create_pegawai_table
  012_create_siswa_table
  013_create_kelas_table
  014_create_tahun_pelajaran_table
  015_create_ruang_table
  016_create_settings_table
  017_setup_spatie_permissions

BATCH 3 — Akademik (depends on Core):
  020_create_jenis_mapel_table
  021_create_mapel_table
  022_create_guru_mapel_table
  023_create_wali_kelas_table
  024_create_jadwal_table
  025_create_presensi_table
  026_create_surat_ijin_table
  027_create_jurnal_mengajar_table
  028_create_absensi_mapel_table
  029_create_ekstrakurikuler_table
  030_create_siswa_ekstra_table
  031_create_piket_jadwal_table
  032_create_piket_catatan_table
  033_create_nilai_bulanan_table
  034_create_nilai_semester_table
  035_create_nilai_tahunan_table
  036_create_raport_tables (raport, sikap, catatan, kenaikan, ranking)

BATCH 4 — Plug-n-Play Modules:
  040_create_kurmer_tables (tp, lm, asesmen, nilai, proyek)
  050_create_keuangan_tables (item, tagihan, pembayaran, tabungan)
  060_create_bk_tables (jenis, point, pelanggaran, prestasi)
  070_create_inventaris_tables
  080_create_fileboxes_table
  090_create_wa_notifications_table

BATCH 5 — Audit:
  095_create_activity_log_table (Spatie)
  096_create_notifications_table (Laravel)
```

---

**TOTAL STATISTICS:**

| Metric | Legacy | Target | Change |
|--------|:------:|:------:|:------:|
| Tables | 75 | ~56 | -25% |
| Columns | 856 | ~420 | -51% |
| Denormalized columns removed | — | ~280 | -33% of original |
| Wrong type columns fixed | — | ~85 | -10% of original |
| Foreign Keys | 0 | ~60+ | ∞ improvement |
| Indexes | 75 (PK only) | ~130+ | 73% more |
| Engine | MyISAM | InnoDB | Transaction support |
| Charset | Mixed | UTF8MB4 | Consistent |
| Tenant support | None | school_id everywhere | Multi-tenant ready |

---

**End of DOC-04**
