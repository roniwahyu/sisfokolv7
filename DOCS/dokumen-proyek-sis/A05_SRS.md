# A5. Software Requirements Specification (SRS)
## Sistem Informasi Sekolah SMP Islam Terpadu

---

## 1. Tujuan Dokumen

Dokumen ini menjelaskan kebutuhan fungsional dan non-fungsional sistem yang akan dikembangkan, sebagai acuan utama bagi tim pengembang, penguji, dan pengguna.

## 2. Ruang Lingkup

Sistem mencakup portal web untuk siswa, guru, wali kelas, orang tua, bendahara, tata usaha, wakasek, dan kepala sekolah. Sistem berjalan pada arsitektur client-server dengan basis data relasional.

## 3. Kebutuhan Fungsional (FR)

| Kode | Modul | Kebutuhan | Prioritas |
| --- | --- | --- | --- |
| FR-001 | Autentikasi | Sistem menyediakan login multi-role berdasarkan NIP/NIK/NIS dan password | High |
| FR-002 | Autentikasi | Sistem menyediakan lupa password melalui email/OTP | Medium |
| FR-003 | Data Master | Admin dapat mengelola data siswa (CRUD) | High |
| FR-004 | Data Master | Admin dapat mengelola data guru, kelas, mata pelajaran, dan tahun ajaran | High |
| FR-005 | Akademik | Sistem dapat mengelola jadwal pelajaran per kelas dan semester | High |
| FR-006 | Absensi | Guru/wali kelas dapat mencatat absensi harian siswa | High |
| FR-007 | Absensi | Sistem dapat menampilkan rekap absensi per semester | High |
| FR-008 | Penilaian | Guru dapat input nilai UH, PTS, PAS, dan nilai sikap/akhlak | High |
| FR-009 | Penilaian | Sistem menghitung nilai akhir dan konversi nilai otomatis | High |
| FR-010 | Rapor | Wali kelas dapat mencetak rapor semester | High |
| FR-011 | Rapor | Sistem menampilkan deskripsi rapor sesuai kurikulum | Medium |
| FR-012 | Keuangan | Bendahara dapat mencatat pembayaran SPP dan infaq | High |
| FR-013 | Keuangan | Orang tua/siswa dapat melihat tagihan dan status pembayaran | High |
| FR-014 | Keuangan | Sistem dapat mencetak kwitansi pembayaran | Medium |
| FR-015 | Laporan | Kepala sekolah dapat melihat dashboard jumlah siswa, guru, keuangan, dan absensi | High |
| FR-016 | Portal Siswa | Siswa dapat melihat jadwal, nilai, absensi, dan tagihan | High |
| FR-017 | Portal Orang Tua | Orang tua dapat memantau perkembangan anak dan tagihan | High |
| FR-018 | Notifikasi | Sistem mengirimkan notifikasi tagihan, absensi, dan pengumuman | Medium |
| FR-019 | Audit | Sistem mencatat log aktivitas pengguna | High |
| FR-020 | Pengaturan | Admin dapat mengatur tahun ajaran aktif, semester, dan kalender akademik | High |

## 4. Kebutuhan Non-Fungsional (NFR)

| Kode | Kategori | Kebutuhan | Target |
| --- | --- | --- | --- |
| NFR-001 | Keamanan | Enkripsi password menggunakan bcrypt/Argon2 | Wajib |
| NFR-002 | Keamanan | Semua komunikasi menggunakan HTTPS | Wajib |
| NFR-003 | Keamanan | RBAC dengan pembatasan akses per modul | Wajib |
| NFR-004 | Kinerja | Halaman utama dashboard memuat < 3 detik | Target |
| NFR-005 | Kinerja | Sistem mendukung 500 pengguna simultan | Target |
| NFR-006 | Ketersediaan | Uptime sistem minimal 98% per bulan | Target |
| NFR-007 | Skalabilitas | Arsitektur memungkinkan penambahan modul tanpa refactoring besar | Target |
| NFR-008 | Usability | Antarmuka responsif (mobile-friendly) | Wajib |
| NFR-009 | Usability | Manual pengguna tersedia per peran | Wajib |
| NFR-010 | Maintainability | Kode mengikuti standar coding dan didokumentasikan | Wajib |
| NFR-011 | Backup | Backup otomatis database harian | Wajib |
| NFR-012 | Audit | Log aktivitas disimpan minimal 12 bulan | Wajib |

## 5. Batasan & Asumsi Teknis

- Sistem diakses melalui browser modern (Chrome, Firefox, Edge, Safari).
- Database menggunakan MySQL/MariaDB 10.x atau PostgreSQL 13+.
- Backend dapat menggunakan PHP/Laravel/Node.js sesuai kebutuhan teknis sekolah.
- Server dapat di-*self-host* atau cloud VPS.
