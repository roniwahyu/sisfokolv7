# 05 — Software Requirements Specification (SRS)
### Sistem Informasi Sekolah SMP Islam Terpadu (berbasis SISFOKOL v7.00)

| Item | Keterangan |
|------|-----------|
| **Versi SRS** | 1.0 |
| **Standar Acuan** | IEEE 830-1998 + adaptasi Agile |
| **Platform Dasar** | SISFOKOL v7.00 (Code: SmartOffice) |
| **Status** | Draft for Review |

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini merumuskan kebutuhan fungsional dan non-fungsional sistem informasi sekolah yang akan diimplementasikan di SMP Islam Terpadu berdasarkan platform SISFOKOL v7.00. Dokumen menjadi rujukan tunggal bagi pengembang, QA, dan stakeholder.

### 1.2 Ruang Lingkup
Sistem web multi-peran yang mengelola akademik, kesiswaan, keuangan siswa, BK, dan inventaris, dapat diakses 9 peran pengguna.

### 1.3 Definisi & Singkatan
| Istilah | Arti |
|---------|------|
| SIS | Sistem Informasi Sekolah |
| RBAC | Role-Based Access Control |
| Kurmer | Kurikulum Merdeka |
| KIB | Kartu Inventaris Barang |
| TA/Tapel | Tahun Ajaran / Tahun Pelajaran |
| KKM | Kriteria Ketuntasan Minimal |
| QR | Quick Response Code |
| TU | Tata Usaha |
| BK | Bimbingan Konseling |

### 1.4 Pengguna Sistem
Admin/TU, Kepala Sekolah, Wakil Kepala, Guru Mapel, Wali Kelas, Guru BK, Bendahara, Petugas Piket, Sarpras, Siswa, Orang Tua.

## 2. Deskripsi Umum

### 2.1 Perspektif Produk
Sistem adalah aplikasi web monolitik (PHP Native) berbasis klien-server 3-tier, dipakai internal sekolah, dengan modul terpisah per peran (sesuai struktur folder SISFOKOL).

### 2.2 Fungsi Produk (Ringkas)
Autentikasi per peran → dashboard → modul sesuai hak akses → entri/proses data → cetak/ekspor laporan.

### 2.3 Karakteristik Pengguna
| Pengguna | Pendidikan Literasi Digital | Frekuensi Pakai |
|----------|----------------------------|------------------|
| Guru/Wali Kelas | Sedang | Harian–mingguan |
| Bendahara/TU | Sedang–Tinggi | Harian |
| Siswa/Ortu | Rendah–Sedang | Mingguan |
| Kepala Sekolah | Sedang | Mingguan (dashboard) |

### 2.4 Batasan
- Berbasis PHP 8.2.4 + MySQL/MariaDB (XAMPP).
- Antarmuka mengikuti template SISFOKOL (responsif terbatas).
- Tidak ada modul e-learning penuh & payment gateway di Fase 1.

## 3. Tabel Kebutuhan Fungsional

> Format: **FR-x** | Kategori | Deskripsi | Prioritas (MoSCoW)

| Kode | Kategori | Deskripsi Kebutuhan Fungsional | Aktor Utama | Modul SISFOKOL | Prioritas |
|------|----------|-------------------------------|-------------|----------------|-----------|
| FR-01 | Autentikasi | Login dengan username/password per peran, validasi role, session 3600 dtk | Semua | `login.php` | Must |
| FR-02 | Autentikasi | Logout & penghancuran session | Semua | `logout.php` | Must |
| FR-03 | Autentikasi | Ganti password sendiri (self-service) | Semua | `*/h` (ganti pass) | Must |
| FR-04 | Master Data | CRUD data siswa (NIS, nama, kelas, ortu, WA) | Admin/TU | `adm/m` → `m_siswa` | Must |
| FR-05 | Master Data | Impor/Ekspor data siswa via Excel | Admin/TU | `adm/m` | Must |
| FR-06 | Master Data | CRUD pegawai & jabatan | Admin/TU | `adm/m` → `m_pegawai` | Must |
| FR-07 | Master Data | CRUD kelas, tahun pelajaran, mapel, KKM | Admin/TU | `adm/m` | Must |
| FR-08 | Master Data | CRUD walikelas & petugas piket per tapel | Admin/TU | `adm/m`, `adm/ph` | Must |
| FR-09 | Akademik | Input nilai asesmen formatif & sumatif (Kurmer) | Guru/WK | `admgr/kurmer`, `admwk/kurmer` | Must |
| FR-10 | Akademik | Hitung nilai akhir (NA) & predikat otomatis | Guru/WK | `siswa_nilai_smt` | Must |
| FR-11 | Akademik | Cetak rapor lengkap (nilai, sikap, absensi, catatan) | Wali Kelas/KS | `admwk/nil` | Must |
| FR-12 | Akademik | Pengaturan jadwal pelajaran per kelas | Admin/WK | `adm/jw` | Must |
| FR-13 | Akademik | Jurnal mengajar guru (agenda harian) | Guru | `admgr/pm` | Should |
| FR-14 | Akademik | Unggah & approval RPP/Silabus (filebox) | Guru/KS | `admgr`, `admks/im` | Should |
| FR-15 | Kesiswaan | Presensi siswa via QR Code | Piket/WK | `adm/ab`, `admpiket/ab` | Must |
| FR-16 | Kesiswaan | Presensi pegawai hadir/pulang (autocomplete NIP) | Piket | `admpiket/ab` | Must |
| FR-17 | Kesiswaan | Rekap kehadiran harian/bulanan per kelas | WK/Piket | `adm/ab` | Must |
| FR-18 | Kesiswaan | Catatan kejadian & ijin/absensi guru | Piket/Admin | `user_ijin`, `admpiket` | Should |
| FR-19 | BK | Entri poin pelanggaran & jenis pelanggaran | Guru BK | `admbk/pl` | Should |
| FR-20 | BK | Entri prestasi siswa & catatan pembinaan | Guru BK | `admbk/ps` | Should |
| FR-21 | Keuangan | Buat tagihan siswa per item/tapel/smt | Bendahara | `admbdh/keu` | Must |
| FR-22 | Keuangan | Input pembayaran & cetak kuitansi | Bendahara | `admbdh/keu` | Must |
| FR-23 | Keuangan | Tampil & rekap tunggakan otomatis | Bendahara/KS | `siswa_bayar*` | Must |
| FR-24 | Keuangan | Entri & mutasi tabungan siswa | Bendahara | `admbdh/nabung` | Should |
| FR-25 | Keuangan | Notifikasi tagihan via WhatsApp | Bendahara/Sistem | `wa_tagihan_siswa` | Should |
| FR-26 | Inventaris | CRUD sarana prasarana (KIB A–F) | Sarpras | `adminv/inv` | Should |
| FR-27 | Inventaris | Impor/Ekspor inventaris Excel + cetak kartu | Sarpras/Admin | `adminv/inv` | Could |
| FR-28 | Laporan | Dashboard rekap sekolah (nilai, tunggakan, BK) | Kepsek | `admks` | Must |
| FR-29 | Laporan | Cetak kartu siswa & pegawai (QR) | Admin | `adm/m` | Could |
| FR-30 | Siswa | Portal siswa: nilai, jadwal, tagihan, tabungan | Siswa | `admsw` | Must |
| FR-31 | Ortu | Portal orang tua: pantau nilai/absensi/tagihan | Ortu | `m_siswa.passwordx_ortu` | Must |
| FR-32 | Profil | Konfigurasi profil sekolah & tampilan | Admin | `adm/s`, `inc/config.php` | Should |

## 4. Tabel Kebutuhan Non-Fungsional

| Kode | Kategori | Deskripsi | Metrik / Target | Prioritas |
|------|----------|-----------|-----------------|-----------|
| NFR-01 | Kinerja (Performance) | Waktu respon halaman ≤ 3 detik pada koneksi 5 Mbps | P95 < 3 dtk | Must |
| NFR-02 | Kinerja | Login diproses ≤ 2 detik | < 2 dtk | Must |
| NFR-03 | Skalabilitas | Mendukung ≥ 1.000 siswa & 100 pegawai tanpa degradasi | beban uji | Must |
| NFR-04 | Keamanan | Kata sandi disimpan terenkripsi (hash) | SHA/md5+salt → *upgrade bcrypt* | Must |
| NFR-05 | Keamanan | Hak akses berlapis per peran (RBAC) | 0 akses silang | Must |
| NFR-06 | Keamanan | Audit log aktivitas & login (`user_log_*`) | log lengkap | Should |
| NFR-07 | Keamanan | Proteksi injeksi SQL (prepared statement mysqli) | 0 injeksi berhasil | Must |
| NFR-08 | Keamanan | Enkripsi transmisi HTTPS (TLS) | SSL aktif | Must |
| NFR-09 | Keandalan (Reliability) | Uptime ≥ 99% (jam operasional) | monitoring | Must |
| NFR-10 | Keandalan | Backup harian otomatis + prosedur restore | RPO ≤ 24 jam | Must |
| NFR-11 | Kebergunaan (Usability) | Antarmuka dapat dipelajari pelatihan ≤ 4 jam per peran | sesi 4 jam | Must |
| NFR-12 | Kebergunaan | Bahasa antarmuka: Bahasa Indonesia | 100% ID | Must |
| NFR-13 | Kompatibilitas | Berjalan di Chrome, Edge, Firefox (versi 2 thn terakhir) | 3 browser | Must |
| NFR-14 | Kompatibilitas | Responsif dasar pada mobile browser | layak pakai | Should |
| NFR-15 | Pemeliharaan | Kode mengikuti standar PSR + komentar PHP | review code | Should |
| NFR-16 | Pemeliharaan | Konfigurasi terpisah dari kode (`inc/config.php`) | file env | Must |
| NFR-17 | Portabilitas | Deploy pada XAMPP/LAMP standar (PHP 8.2.4, MySQL) | 1 env standar | Must |
| NFR-18 | Legal/Lisensi | Basis open-source SISFOKOL, modifikasi atribusi sesuai lisensi | patuh lisensi | Must |

## 5. Kebutuhan Antarmuka

### 5.1 Antarmuka Pengguna (UI)
Web responsif berbasis template SISFOKOL (HTML/CSS/JS). Tabel data, form, tombol cetak, modal konfirmasi. Warna dapat dikonfigurasi via `inc/config.php`.

### 5.2 Antarmuka Hardware
Tablet/HP untuk pemindaian QR Code presensi; printer untuk kuitansi/rapor; (opsional) scanner QR.

### 5.3 Antarmuka Komunikasi
- HTTP/HTTPS antara browser–server.
- Koneksi PHP `mysqli` ke MySQL/MariaDB.
- Integrasi API WhatsApp (sosmedsekolah) untuk notifikasi tagihan.

### 5.4 Antarmuka Perangkat Lunak Eksternal
- WhatsApp API (opsional, terdokumentasi di `inc/config.php`).
- Microsoft Excel (impor/ekspor `.xls`).

## 6. Aturan Bisnis (Business Rules)

| Kode | Aturan |
|------|--------|
| BR-R01 | NA siswa dihitung otomatis: bobot PH, PTS, PAS (Rumus Kurmer baku) |
| BR-R02 | Kenaikan kelas: NA ≥ KKM & kriteria sikap (diset Wali Kelas/KS) |
| BR-R03 | Tunggakan = total tagihan − total bayar per siswa |
| BR-R04 | Siswa belum lunas → status "Tunggakan" tampil di dashboard |
| BR-R05 | Poin pelanggaran BK terakumulasi mempengaruhi penilaian sikap |
| BR-R06 | Hanya Wali Kelas/KS yang dapat menyetujui & cetak rapor final |
| BR-R07 | Petugas piket hanya dapat login sesuai penugasan hari/tanggal |

## 7. Daftar Lampiran

- Lampiran A: Use Case & User Story → **Dokumen 08**
- Lampiran B: ERD & Data Dictionary → **Dokumen 12 & 13**
- Lampiran C: UML → **Dokumen 14**
- Lampiran D: Matriks RBAC → **Dokumen 06**
