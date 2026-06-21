# 01 — Project Charter
### Proyek: Sistem Informasi Sekolah (SIS) SMP Islam Terpadu

| Item | Keterangan |
|------|-----------|
| **Kode Proyek** | SIS-SMPIT-2026 |
| **Nama Proyek** | Implementasi Sistem Informasi Sekolah SMP Islam Terpadu |
| **Platform Dasar** | SISFOKOL v7.00 (Code: SmartOffice) — PHP Native 8.2.4 + MySQL/MariaDB |
| **Project Manager** | Wakil Kepala Sekolah Bidang Kurikulum |
| **Sponsor Eksekutif** | Kepala Sekolah (Yayasan) |
| **Tanggal Mulai** | 02 Februari 2026 |
| **Tanggal Target Go-Live** | 01 Juli 2026 (Awal Tahun Ajaran 2026/2027) |
| **Estimasi Durasi** | 22 minggu (≈ 5,5 bulan) |
| **Estimasi Anggaran** | Rp 185.000.000 (capex + opex tahun pertama) |
| **Status** | Disetujui untuk Eksekusi |

---

## 1. Gambaran Umum Proyek

SMP Islam Terpadu saat ini mengelola administrasi akademik dan non-akademik secara **semi-manual** (Microsoft Excel, buku catatan, lembar fotokopi). Praktik ini menimbulkan risiko kesalahan input, duplikasi data, kesulitan pelacakan tunggakan SPP, keterlambatan penerbitan rapor, serta minimnya keterlibatan orang tua. Proyek ini mengimplementasikan platform **SISFOKOL v7.00** yang sudah teruji (75 tabel, 9 modul peran) dan menyesuaikannya dengan identitas dan kebutuhan sekolah Islam terpadu, mencakup penilaian Kurikulum Merdeka, presensi QR Code, keuangan siswa, inventaris, serta bimbingan konseling.

**Tujuan strategis:** Mewujudkan *Smart Office* sekolah yang saling terhubung antar bagian (TU, Kurikulum, Kesiswaan, Bendahara, Sarpras) sehingga data tunggal, akurat, dan dapat diakses peran sesuai kewenangan.

## 2. Lingkup Ringkas (In-Scope)

1. Penilaian mata pelajaran & cetak rapor Kurikulum Merdeka (asesmen formatif/sumatif).
2. Presensi kehadiran siswa & pegawai berbasis QR Code.
3. Manajemen keuangan siswa: tagihan, pembayaran, tunggakan, tabungan.
4. Bimbingan konseling: poin pelanggaran, prestasi, pembinaan.
5. Inventaris/sarana prasarana (KIB A–F).
6. Jadwal pelajaran, jurnal mengajar guru, absensi/ijin guru.
7. Filebox RPP & Silabus, petugas piket, notifikasi tagihan via WhatsApp.

## 3. Di Luar Lingkup (Out-of-Scope)

- Pembangunan aplikasi mobile *native* (akses melalui *mobile browser* responsif).
- Integrasi pembayaran *payment gateway* otomatis (tahap berikutnya).
- Modul e-learning/LMS penuh (hanya filebox RPP/Silabus).
- Sistem akuntansi yayasan menyeluruh (hanya keuangan siswa).

## 4. Tabel Stakeholder (Ringkas)

| ID | Stakeholder | Peran dalam Proyek | Tingkat Pengaruh | Tingkat Kepentingan | Strategi |
|----|-------------|--------------------|------------------|--------------------|----------|
| SH-01 | Kepala Sekolah | Sponsor & pengambil keputusan | Tinggi | Tinggi | Kelola rapat (Manage Closely) |
| SH-02 | Wakil Kepala | Project Manager | Tinggi | Tinggi | Kelola rapat |
| SH-03 | Tata Usaha (TU) | Master data, verifikasi | Sedang | Tinggi | Inform & konsultasi |
| SH-04 | Bendahara | Modul keuangan | Tinggi | Tinggi | Kelola rapat |
| SH-05 | Guru Mapel | Input nilai & jurnal | Rendah | Tinggi | Inform |
| SH-06 | Wali Kelas | Rapor & absensi kelas | Sedang | Tinggi | Konsultasi |
| SH-07 | Guru BK | Modul BK | Sedang | Sedang | Konsultasi |
| SH-08 | Siswa | Pengguna akhir presensi | Rendah | Sedang | Inform |
| SH-09 | Orang Tua/Wali | Monitoring tagihan & nilai | Rendah | Tinggi | Inform |
| SH-10 | Tim IT/Vendor | Implementasi teknis | Tinggi | Sedang | Kelola rapat |

> Detail lengkap stakeholder tersedia pada **Dokumen 03 — Identifikasi Stakeholder**.

## 5. Tabel Estimasi Waktu & Anggaran

### 5.1 Estimasi Waktu (Timeline Tingkat Tinggi)

| Fase | Kegiatan Utama | Durasi | Mulai | Selesai |
|------|----------------|--------|-------|---------|
| A | Inisiasi & Perencanaan | 3 minggu | 02 Feb 2026 | 21 Feb 2026 |
| B | Desain Awal (Arsitektur & DB) | 2 minggu | 23 Feb 2026 | 06 Mar 2026 |
| C | Desain Detail (UML, UI/UX, Laporan) | 3 minggu | 09 Mar 2026 | 27 Mar 2026 |
| D | Pengembangan & Kustomisasi | 7 minggu | 30 Mar 2026 | 17 May 2026 |
| E | Pengujian & Perbaikan | 3 minggu | 18 May 2026 | 05 Jun 2026 |
| F | Migrasi Data & Deployment | 2 minggu | 08 Jun 2026 | 19 Jun 2026 |
| G | Pelatihan & Go-Live | 2 minggu | 22 Jun 2026 | 01 Jul 2026 |
| **Total** | | **22 minggu** | | |

### 5.2 Estimasi Anggaran

| Kategori | Komponen | Estimasi (Rp) |
|----------|----------|---------------|
| Lisensi & Kustomisasi | Adaptasi SISFOKOL, modul Islam, *custom code* | 45.000.000 |
| Infrastruktur | Server/Cloud (VPS 2 tahun) + domain + SSL | 25.000.000 |
| Perangkat Presensi | Tablet/HP + scanner QR untuk piket | 12.000.000 |
| Migrasi Data | Input awal & konversi Excel | 10.000.000 |
| Pelatihan | 4 sesi × 8 role | 15.000.000 |
| Pengujian & UAT | QA, *device testing* | 8.000.000 |
| Dokumentasi & Cetak | Buku manual, banner sosialisasi | 5.000.000 |
| Kontingensi (15%) | Cadangan tak terduga | 27.000.000 |
| **Cadangan Operasional** | Maintenance tahun pertama | 38.000.000 |
| | **TOTAL** | **185.000.000** |

## 6. Deliverable Utama

- Aplikasi web SIS yang dapat diakses lintas peran (9 peran).
- Basis data master lengkap & termigrasi.
- 31 dokumen rekayasa perangkat lunak (paket ini).
- Buku panduan pengguna per peran.
- Sistem berjalan di lingkungan produksi (go-live).

## 7. Kriteria Keberhasilan (Success Criteria)

1. Go-live tepat waktu paling lambat 01 Juli 2026.
2. 100% guru mampu input nilai mandiri setelah pelatihan.
3. Rapor semester ganjil TA 2026/2027 dicetak 100% via sistem.
4. Penurunan tunggakan SPP terlacak ≥ 30% dalam 1 semester.
5. Tidak ada *bug* *severity High* yang terbuka saat go-live.

## 8. Risiko Utama (Ringkas)

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Resistensi perubahan pengguna | Sedang | Pelatihan bertahap + *champion user* |
| Kualitas data Excel lama buruk | Tinggi | Validasi & pembersihan sebelum migrasi |
| Ketergantungan vendor | Sedang | Transfer knowledge + dokumentasi lengkap |

## 9. Persetujuan

| Nama | Jabatan | Tanda Tangan | Tanggal |
|------|---------|--------------|---------|
| ____________ | Kepala Sekolah | | |
| ____________ | Wakil Kepala (PM) | | |
| ____________ | Ketua Yayasan | | |
