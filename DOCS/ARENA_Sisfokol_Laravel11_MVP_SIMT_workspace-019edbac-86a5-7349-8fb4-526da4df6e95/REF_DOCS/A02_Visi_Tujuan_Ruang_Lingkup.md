# A1. Project Charter
## Sistem Informasi Sekolah SMP Islam Terpadu

---

### 1. Informasi Proyek

| Atribut | Keterangan |
| --- | --- |
| Nama Proyek | Sistem Informasi Sekolah SMP Islam Terpadu |
| Kode Proyek | SMP-IT-SIS-2026 |
| Pemilik Proyek | Kepala Sekolah SMP Islam Terpadu |
| Project Manager | Tim IT Sekolah / Vendor Pengembang |
| Tanggal Mulai | 1 Juli 2026 |
| Target Go-Live | 1 Januari 2027 |
| Anggaran (Estimasi) | Rp 350.000.000 – Rp 450.000.000 |
| Status | Disetujui untuk dieksekusi |

### 2. Tujuan Proyek

Membangun sistem informasi terpadu yang mengintegrasikan pengelolaan data akademik, keuangan, kepegawaian, dan komunikasi sekolah secara daring, sehingga mendukung pengambilan keputusan berbasis data, transparansi, dan efisiensi operasional.

### 3. Ruang Lingkup (High-Level)

- Pengelolaan data master (siswa, guru, kelas, mata pelajaran, tahun ajaran).
- Manajemen akademik (jadwal, absensi, penilaian, rapor).
- Manajemen keuangan (SPP, infaq, laporan keuangan).
- Portal siswa, guru, wali kelas, dan orang tua.
- Laporan dan dashboard untuk kepala sekolah.

### 4. Stakeholder Kunci

| No | Stakeholder | Peran dalam Proyek | Otoritas / Keputusan |
| --- | --- | --- | --- |
| 1 | Kepala Sekolah | Penyandang dana & penentu arah strategis | Menyetujui scope, anggaran, go-live |
| 2 | Wakil Kepala Sekolah | Koordinator akademik & kurikulum | Menyetujui modul akademik |
| 3 | Tata Usaha | Pengelola data master & administrasi | Validasi data siswa dan guru |
| 4 | Bendahara | Pengelola modul keuangan | Menyetujui alur pembayaran |
| 5 | Guru | Pengguna input nilai & absensi | UAT modul akademik |
| 6 | Wali Kelas | Pengguna laporan dan kewaliankelas | UAT rapor dan absensi |
| 7 | Siswa | Pengguna portal akademik | UAT portal siswa |
| 8 | Orang Tua | Pengguna monitoring anak | UAT portal orang tua |
| 9 | Tim IT / Vendor | Pengembang dan implementasi | Keputusan teknis |
| 10 | Pengawas Sekolah | Penasihat & pengawas kualitas | Rekomendasi penerimaan |

### 5. Estimasi Waktu & Anggaran

| Fase | Durasi | Bulan | Estimasi Biaya (IDR) |
| --- | --- | --- | --- |
| Inisiasi & Perencanaan | 4 minggu | Juli 2026 | Rp 25.000.000 |
| Desain Awal & Detail | 6 minggu | Agu – Sep 2026 | Rp 60.000.000 |
| Pengembangan | 12 minggu | Sep – Des 2026 | Rp 180.000.000 |
| Pengujian & UAT | 4 minggu | Des 2026 | Rp 40.000.000 |
| Deployment & Training | 4 minggu | Des 2026 – Jan 2027 | Rp 45.000.000 |
| **Total** | **30 minggu** | | **Rp 350.000.000** |

### 6. Kriteria Kesuksesan

1. 100% modul prioritas High dapat digunakan sesuai SRS.
2. UAT diterima oleh minimal 80% stakeholder pengguna.
3. Data migrasi dari Excel berhasil tanpa kehilangan data kritis.
4. Sistem dapat diakses 24/7 dengan uptime > 98%.
5. Dokumentasi pengguna lengkap dan diserahkan.

### 7. Risiko Awal

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Resistensi pengguna terhadap perubahan | Menunda adopsi | Sosialisasi dan pelatihan bertahap |
| Kualitas data tidak konsisten | Laporan tidak valid | Data cleansing sebelum migrasi |
| Keterbatasan bandwidth | Akses lambat | Optimasi gambar & caching, jadwal akses |
| Kehilangan data | Fatal | Backup otomatis & audit log |

### 8. Persetujuan

| Jabatan | Nama | Tanda Tangan | Tanggal |
| --- | --- | --- | --- |
| Kepala Sekolah | .................... | ................ | ........ |
| Project Manager | .................... | ................ | ........ |
