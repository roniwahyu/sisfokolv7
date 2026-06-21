# 13 — Data Dictionary (Kamus Data)
### Proyek: Sistem Informasi Sekolah SMP Islam Terpadu

## 1. Pendahuluan

Dokumen ini mendefinisikan **lebih dari 30 field** kunci pada tabel utama basis data `sisfokol_v7` (75 tabel). Setiap entri menjelaskan nama kolom, tipe data, panjang, kunci, wajib/opsional, nilai default, dan deskripsi. Daftar ini berfokus pada entitas inti yang paling sering dipakai.

## 2. Kamus Data per Tabel

### 2.1 Tabel `m_siswa` (Master Siswa)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 1 | `kd` | varchar | 50 | PK | No | — | NIS / kode unik siswa |
| 2 | `usernamex` | varchar | 100 | — | Yes | NULL | Username login siswa |
| 3 | `passwordx` | varchar | 100 | — | Yes | NULL | Password siswa (hash) |
| 4 | `passwordx_ortu` | varchar | 100 | — | Yes | NULL | Password login orang tua |
| 5 | `kode` | varchar | 50 | — | Yes | NULL | Kode alternatif/NISN |
| 6 | `nama` | varchar | 100 | — | Yes | NULL | Nama lengkap siswa |
| 7 | `tapel` | varchar | 100 | FK logis | Yes | NULL | Tahun pelajaran aktif |
| 8 | `kelas` | varchar | 100 | FK logis | Yes | NULL | Kelas siswa |
| 9 | `nourut` | varchar | 5 | — | Yes | NULL | Nomor urut dalam kelas |
| 10 | `qrcode` | varchar | 100 | — | Yes | NULL | Token QR presensi |
| 11 | `nowa` | varchar | 100 | — | Yes | NULL | Nomor WhatsApp ortu |
| 12 | `postdate` | datetime | — | — | Yes | NULL | Waktu entri data |
| 13 | `jml_absen_sakit` | varchar | 5 | — | Yes | NULL | Rekap hari sakit |
| 14 | `jml_absen_ijin` | varchar | 5 | — | Yes | NULL | Rekap hari ijin |
| 15 | `jml_absen_alpha` | varchar | 5 | — | Yes | NULL | Rekap hari alpha |

### 2.2 Tabel `m_pegawai` (Master Pegawai/Guru)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 16 | `kd` | varchar | 50 | PK | No | — | NIP / kode pegawai |
| 17 | `usernamex` | longtext | — | — | Yes | NULL | Username login pegawai |
| 18 | `passwordx` | longtext | — | — | Yes | NULL | Password (hash) |
| 19 | `nama` | varchar | 100 | — | Yes | NULL | Nama lengkap pegawai |
| 20 | `jabatan` | varchar | 100 | — | Yes | NULL | Jabatan (Guru/WK/BK, dll) |
| 21 | `nowa` | varchar | 100 | — | Yes | NULL | Nomor WhatsApp |

### 2.3 Tabel `m_mapel` (Master Mata Pelajaran)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 22 | `kd` | varchar | 50 | PK | No | — | Kode mapel |
| 23 | `tapel` | varchar | 100 | FK logis | Yes | NULL | Tahun pelajaran |
| 24 | `kelas` | varchar | 100 | FK logis | Yes | NULL | Kelas pemilik mapel |
| 25 | `kode` | varchar | 100 | — | Yes | NULL | Kode mapel (label) |
| 26 | `nama` | longtext | — | — | Yes | NULL | Nama mata pelajaran |
| 27 | `kkm` | varchar | 5 | — | Yes | NULL | Kriteria Ketuntasan Minimal |
| 28 | `pegawai_kd` | varchar | 50 | FK logis | Yes | NULL | Guru pengampu |

### 2.4 Tabel `siswa_nilai_smt` (Nilai Semester — Kurmer)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 29 | `kd` | varchar | 50 | PK | No | — | Kode record nilai |
| 30 | `siswa_kode` | varchar | 100 | FK logis | Yes | NULL | Kode siswa |
| 31 | `mapel_kode` | varchar | 100 | FK logis | Yes | NULL | Kode mapel |
| 32 | `smt` | varchar | 100 | — | Yes | NULL | Semester (1/2) |
| 33 | `p_ph_nilai` | varchar | 5 | — | Yes | NULL | Nilai Pengetahuan PH |
| 34 | `p_pts_nilai` | varchar | 5 | — | Yes | NULL | Nilai Pengetahuan PTS |
| 35 | `p_pas_nilai` | varchar | 5 | — | Yes | NULL | Nilai Pengetahuan PAS |
| 36 | `p_na` | varchar | 5 | — | Yes | NULL | Nilai Akhir Pengetahuan |
| 37 | `p_na_pred` | varchar | 5 | — | Yes | NULL | Predikat Nilai Akhir |
| 38 | `entri_oleh` | varchar | 100 | — | Yes | NULL | Pengguna penginput |

### 2.5 Tabel `siswa_bayar_tagihan` (Tagihan Siswa)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 39 | `kd` | varchar | 50 | PK | No | — | Kode tagihan |
| 40 | `siswa_kd` | varchar | 50 | FK logis | Yes | NULL | Kode siswa |
| 41 | `item_kd` | varchar | 50 | FK logis | Yes | NULL | Item keuangan |
| 42 | `item_nominal` | varchar | 15 | — | Yes | NULL | Nominal tagihan |
| 43 | `item_thn` | varchar | 4 | — | Yes | NULL | Tahun tagihan |
| 44 | `item_bln` | varchar | 2 | — | Yes | NULL | Bulan tagihan |

### 2.6 Tabel `siswa_bayar` (Transaksi Pembayaran)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 45 | `kd` | varchar | 50 | PK | No | — | Kode pembayaran |
| 46 | `siswa_kd` | varchar | 50 | FK logis | Yes | NULL | Kode siswa |
| 47 | `tgl_bayar` | date | — | — | Yes | NULL | Tanggal bayar |
| 48 | `nominal_tagihan` | varchar | 15 | — | Yes | NULL | Nominal yang ditagih |
| 49 | `nominal_bayar` | varchar | 15 | — | Yes | NULL | Nominal dibayar |
| 50 | `nominal_kurang` | varchar | 15 | — | Yes | NULL | Sisa/tunggakan |

### 2.7 Tabel `user_presensi` (Presensi Pegawai)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 51 | `kd` | varchar | 50 | PK | No | — | Kode presensi |
| 52 | `user_kd` | varchar | 50 | FK logis | Yes | NULL | Kode pegawai |
| 53 | `tanggal` | date | — | — | Yes | NULL | Tanggal presensi |
| 54 | `status` | varchar | 100 | — | Yes | NULL | Hadir/Telat/dll |
| 55 | `telat_menit` | varchar | 5 | — | Yes | NULL | Menit keterlambatan |
| 56 | `dibaca` | enum | — | — | Yes | 'false' | Status dibaca notifikasi |

### 2.8 Tabel `m_tapel` & `m_kelas` (Master)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 57 | `m_tapel.kd` | varchar | 50 | PK | No | — | Kode tahun pelajaran |
| 58 | `m_tapel.tapel` | varchar | 100 | — | Yes | NULL | Label tapel (2026/2027) |
| 59 | `m_tapel.aktif` | enum | — | — | Yes | 'false' | Status tapel aktif |
| 60 | `m_kelas.kd` | varchar | 50 | PK | No | — | Kode kelas |
| 61 | `m_kelas.nama` | varchar | 100 | — | Yes | NULL | Nama kelas (7A, 8B, dll) |

### 2.9 Tabel `siswa_pelanggaran` (BK)
| No | Field | Tipe | Panjang | Kunci | Null | Default | Deskripsi |
|----|-------|------|---------|-------|------|---------|-----------|
| 62 | `siswa_kd` | varchar | 50 | FK logis | — | — | Kode siswa |
| 63 | `jenis` | varchar | 100 | — | — | — | Jenis pelanggaran |
| 64 | `poin` | varchar | 5 | — | — | — | Poin pelanggaran |

## 3. Ringkasan Konvensi

| Konvensi | Penjelasan |
|----------|------------|
| Suffix `_kd` | Kolom kunci/relasi |
| Suffix `_nama` | Nama tampilan |
| `postdate` | Timestamp entri |
| Tipe `varchar` untuk angka | Perlu validasi numerik di aplikasi |
| `enum('true','false')` | Bendera status boolean |

## 4. Penutup

Kamus data ini mencakup **64 field** dari tabel-tabel inti (melampaui syarat minimal 30) dan menjadi rujukan bagi pengembang, DBA, dan QA dalam memahami struktur data SISFOKOL yang dikustomisasi untuk SMP Islam Terpadu.
