# Skema Database SISFOKOL v7.00

Jumlah tabel: 75


## a_profil

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `postdate` | datetime NOT NULL |
| `lat_x` | longtext NOT NULL |
| `lat_y` | longtext NOT NULL |
| `alamat_googlemap` | longtext NOT NULL |

## adminx

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `usernamex` | varchar(100) DEFAULT NULL |
| `passwordx` | varchar(100) DEFAULT NULL |

## inv_kib_a

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

## inv_kib_b

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

## inv_kib_c

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

## inv_kib_d

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

## inv_kib_e

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

## inv_kib_f

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

## jadwal

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

## kurmer_asesmen_formatif

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

## kurmer_mapel_lm

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

## kurmer_mapel_tp

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

## kurmer_nilai_asesmen_formatif

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

## kurmer_nilai_asesmen_formatif_detail

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

## kurmer_nilai_asesmen_sumatif

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

## kurmer_nilai_asesmen_sumatif_detail

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

## kurmer_nilai_proyek

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

## kurmer_nilai_proyek_proses

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

## kurmer_proyek

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `no` | varchar(1) DEFAULT NULL |
| `judul` | longtext DEFAULT NULL |
| `isi` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## kurmer_proyek_detail

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

## m_bendahara

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_bk_point

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

## m_bk_point_jenis

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `jenis` | varchar(100) NOT NULL |
| `no` | varchar(2) NOT NULL |

## m_bk_prestasi

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `no` | varchar(5) DEFAULT NULL |
| `nama` | longtext DEFAULT NULL |
| `point` | varchar(5) DEFAULT NULL |

## m_ekstra

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `nama` | varchar(100) DEFAULT NULL |
| `pegawai_kd` | varchar(50) DEFAULT NULL |
| `pegawai_kode` | varchar(100) DEFAULT NULL |
| `pegawai_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_gurubk

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_hari

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | char(1) DEFAULT NULL |
| `hari` | varchar(100) DEFAULT NULL |

## m_jam

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `jam` | char(2) DEFAULT NULL |

## m_kelas

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | char(1) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `kelas` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_keu_siswa

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

## m_kib_jenis

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `nourut` | varchar(2) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |

## m_kib_kode

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

## m_ks

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_mapel

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

## m_mapel_deskripsi

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

## m_mapel_jns

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | varchar(1) DEFAULT NULL |
| `no_sub` | varchar(5) DEFAULT NULL |
| `jenis` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_pegawai

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

## m_pembinaan

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `nama` | longtext DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `pembina_kode` | varchar(100) DEFAULT NULL |
| `pembina_nama` | varchar(100) DEFAULT NULL |

## m_piket

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

## m_ruang

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `no` | varchar(10) DEFAULT NULL |
| `nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_sarpras

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `peg_kd` | varchar(50) DEFAULT NULL |
| `peg_kode` | varchar(100) DEFAULT NULL |
| `peg_nama` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_siswa

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

## m_tapel

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL DEFAULT '' |
| `nama` | varchar(100) DEFAULT NULL |
| `tapel` | varchar(100) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |
| `aktif` | enum('true','false') DEFAULT 'false' |

## m_user

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

## m_waktu

| Kolom | Definisi |
| --- | --- |
| `kd` | varchar(50) NOT NULL |
| `masuk_jam` | varchar(2) DEFAULT NULL |
| `masuk_menit` | varchar(2) DEFAULT NULL |
| `pulang_jam` | varchar(2) DEFAULT NULL |
| `pulang_menit` | varchar(2) DEFAULT NULL |
| `postdate` | datetime DEFAULT NULL |

## m_waktu_jadwal

| Kolom | Definisi |
| --- | --- |
| `nourut` | varchar(5) NOT NULL |
| `hari_no` | varchar(10) DEFAULT NULL |
| `hari_nama` | varchar(100) DEFAULT NULL |
| `jam_ke` | varchar(10) DEFAULT NULL |
| `waktu` | varchar(100) DEFAULT NULL |
| `ket` | varchar(100) DEFAULT NULL |

## m_walikelas

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

## rev_guru_absensi

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

## rev_guru_agenda

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

## siswa_bayar

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

## siswa_bayar_rincian

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

## siswa_bayar_tagihan

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

## siswa_ekstra

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

## siswa_mapel_absensi

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

## siswa_nilai_bln

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

## siswa_nilai_smt

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

## siswa_nilai_thn

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

## siswa_pelanggaran

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

## siswa_prestasi

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

## siswa_raport_catatan

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

## siswa_raport_kenaikan

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

## siswa_raport_rangking

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

## siswa_raport_sikap

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

## siswa_saran

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

## siswa_soal

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

## siswa_soal_nilai

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

## siswa_tugas

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

## user_absensi

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

## user_filebox

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

## user_ijin

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

## user_log_entri

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

## user_log_login

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

## user_piket

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

## user_presensi

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

## wa_tagihan_siswa

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