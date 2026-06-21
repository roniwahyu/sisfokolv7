# 02 — Visi, Tujuan, dan Ruang Lingkup
### Proyek: Sistem Informasi Sekolah SMP Islam Terpadu

## 1. Visi Proyek

> *"Mewujudkan SMP Islam Terpadu sebagai sekolah cerdas (Smart School) yang mengelola seluruh administrasi akademik dan non-akademik secara terintegrasi, akurat, transparan, dan * accountable* melalui satu sistem informasi terpadu, sehingga fokus utama dapat tertuju pada peningkatan mutu pendidikan dan pembentukan akhlak qurani peserta didik."*

## 2. Misi

1. Menyatukan data siswa, pegawai, akademik, keuangan, dan inventaris dalam basis data tunggal.
2. Mempermudah seluruh peran sekolah dalam bekerja melalui otomatisasi proses.
3. Meningkatkan transparansi keuangan siswa dan keterlibatan orang tua.
4. Mendukung implementasi Kurikulum Merdeka secara utuh.

## 3. Tujuan (Goals) — SMART

| Kode | Tujuan | Indikator (KPI) | Target |
|------|--------|------------------|--------|
| G-01 | Mengganti pengelolaan manual dengan sistem terintegrasi | % proses yang terdigitalisasi | 90% dalam 6 bulan |
| G-02 | Mempercepat penerbitan rapor | Waktu cetak rapor per kelas | ≤ 1 hari kerja |
| G-03 | Mengurangi tunggakan SPP | % tunggakan terhadap tagihan | turun ≥ 30%/semester |
| G-04 | Akurasi data presensi | Selisih rekap manual vs sistem | ≤ 1% |
| G-05 | Transparansi keuangan | Akses laporan real-time oleh Kepsek & Yayasan | 100% |
| G-06 | Kepuasan pengguna | Skor UAT kepuasan peran | ≥ 4,0 dari 5 |

## 4. Ruang Lingkup (Scope)

### 4.1 In-Scope (Yang dikerjakan)
1. **Akademik**: master data mapel/kelas/tapel, penilaian Kurikulum Merdeka (formatif & sumatif), rapor, jadwal, jurnal mengajar guru, filebox RPP & Silabus.
2. **Kesiswaan**: presensi QR Code siswa, absensi/ijin, pelanggaran & poin BK, prestasi, petugas piket.
3. **Keuangan**: tagihan & pembayaran, tunggakan, tabungan siswa, cetak kuitansi, notifikasi WA tagihan.
4. **Inventaris**: sarana prasarana (KIB A–F), peminjaman, cetak kartu pegawai & siswa.
5. **Pengguna**: 9 peran dengan hak akses berlapis (RBAC), ganti password, profil sekolah.

### 4.2 Out-of-Scope (Yang TIDAK dikerjakan di fase ini)
- Aplikasi mobile *native* (akses via *mobile browser*).
- Payment gateway / Virtual Account otomatis (fase 2).
- LMS/e-learning penuh.
- Akuntansi yayasan gabungan multi-unit.

## 5. Batasan & Asumsi (Constraints & Assumptions)

**Batasan:**
- Anggaran maksimal Rp 185.000.000.
- Go-live paling lambat 01 Juli 2026 (awal TA 2026/2027).
- Tim IT internal terbatas (1 orang) → bergantung vendor untuk kustomisasi.
- Platform harus tetap berbasis PHP/MySQL agar mudah dirawat lokal.

**Asumsi:**
- Data lama (Excel) tersedia dan dapat diekspor.
- Jaringan internet & listrik sekolah stabil.
- Manajemen sekolah siap menetapkan kebijakan penggunaan wajib.

## 6. Tabel Modul Fase 1 vs Fase 2

> Pendekatan bertahap: **Fase 1 (Go-Live 2026)** berisi modul inti yang sudah tersedia di SISFOKOL v7.00; **Fase 2 (Roadmap 2027)** berisi pengembangan lanjutan.

| Kategori | Modul | Fase 1 (2026) | Fase 2 (2027) | Modul Kode SISFOKOL |
|----------|-------|:---:|:---:|----------------------|
| Akademik | Master Mapel, Kelas, Tahun Pelajaran | ✅ | | `adm/m`, `admks/akad` |
| Akademik | Penilaian Kurikulum Merdeka (Formatif/Sumatif) | ✅ | | `admgr/kurmer`, `admwk/kurmer` |
| Akademik | Cetak Rapor | ✅ | | `admwk/nil`, `admks/nil` |
| Akademik | Jadwal Pelajaran | ✅ | | `adm/jw`, `admks/jw` |
| Akademik | Jurnal Mengajar Guru | ✅ | | `admgr/pm` |
| Akademik | Filebox RPP & Silabus | ✅ | | `adm/im`, `admgr`, `admks/im` |
| Kesiswaan | Presensi QR Code (siswa & pegawai) | ✅ | | `adm/ab`, `admpiket/ab` |
| Kesiswaan | Absensi & Ijin Guru | ✅ | | `user_absensi`, `user_ijin` |
| Kesiswaan | Petugas Piket | ✅ | | `admpiket` |
| BK | Bimbingan Konseling (poin, prestasi, pembinaan) | ✅ | | `admbk` |
| Keuangan | Tagihan, Pembayaran, Tunggakan | ✅ | | `admbdh/keu`, `adm/keu` |
| Keuangan | Tabungan Siswa | ✅ | | `adm/nabung`, `admbdh/nabung` |
| Keuangan | Notifikasi WA Tagihan | ✅ | | `wa_tagihan_siswa` |
| Inventaris | Sarana Prasarana (KIB A–F) | ✅ | | `adminv/inv`, `adm/inv` |
| Pengguna | 9 Peran + RBAC | ✅ | | `adm`, `admks`, dll. |
| Siswa | Portal Siswa | ✅ | | `admsw` |
| Orang Tua | Portal Orang Tua (via akun ortu) | ✅ | | `m_siswa.passwordx_ortu` |
| **Keuangan** | **Payment Gateway / VA** | | ✅ | *custom* |
| **Akademik** | **Bank Soal & Ujian Online (CBT)** | | ✅ | `siswa_soal*` (perlu kembangkan) |
| **Akademik** | **Modul Tahfidz/Al-Qur'an Tracking** | | ✅ | *custom (sesuai Islam Terpadu)* |
| **Komunikasi** | **Aplikasi Mobile Native** | | ✅ | *custom* |
| **Akademik** | **PPDB Online** | | ✅ | *custom* |

## 7. Deliverable Fase 1

1. Aplikasi SIS produksi dengan 9 peran.
2. Basis data terisi (master + migrasi).
3. 31 dokumen rekayasa perangkat lunak.
4. Buku panduan pengguna per peran.
5. Pelatihan 4 sesi untuk seluruh stakeholder.

## 8. Penutup

Dokumen visi ini menjadi rujukan utama seluruh pengambilan keputusan proyek. Setiap perubahan lingkup harus dievaluasi terhadap visi, tujuan, dan anggaran yang tertuang di sini serta pada **Dokumen 01 — Project Charter**.
