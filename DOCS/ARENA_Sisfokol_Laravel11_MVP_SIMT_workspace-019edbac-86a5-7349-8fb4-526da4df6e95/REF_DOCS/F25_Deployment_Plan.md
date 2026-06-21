# E24. User Acceptance Test (UAT)

---

## Form UAT

| Informasi | Keterangan |
| --- | --- |
| Nama Penguji | ................ |
| Role | ................ |
| Tanggal UAT | ................ |
| Versi Aplikasi | v1.0.0-beta |
| Environment | Staging / Production |

## Daftar Skenario UAT

| No | Skenario | Kriteria Diterima | Hasil (Pass/Fail) | Catatan |
| --- | --- | --- | --- | --- |
| 1 | Login sebagai Kepala Sekolah | Dapat masuk dan melihat dashboard |  | |
| 2 | Login sebagai Tata Usaha | Dapat kelola data siswa/guru |  | |
| 3 | Login sebagai Guru | Dapat input nilai dan absensi |  | |
| 4 | Login sebagai Wali Kelas | Dapat cetak rapor |  | |
| 5 | Login sebagai Bendahara | Dapat catat pembayaran |  | |
| 6 | Login sebagai Siswa | Dapat lihat nilai & jadwal |  | |
| 7 | Login sebagai Orang Tua | Dapat lihat tagihan & nilai anak |  | |
| 8 | Input data siswa baru | Data tersimpan dan muncul di daftar |  | |
| 9 | Input nilai dan hitung akhir | Nilai akhir benar |  | |
| 10 | Cetak rapor semester | PDF rapor sesuai format |  | |
| 11 | Rekap absensi bulanan | Rekap akurat |  | |
| 12 | Input pembayaran SPP | Status tagihan lunas |  | |
| 13 | Lihat laporan keuangan | Total pembayaran benar |  | |
| 14 | Akses data tidak berwenang | Akses ditolak |  | |
| 15 | Notifikasi tagihan | Notifikasi diterima |  | |

## Hasil UAT Agregat (Simulasi)

| Role | Jumlah Skenario | Pass | Fail | Persentase |
| --- | --- | --- | --- | --- |
| Kepala Sekolah | 15 | 14 | 1 | 93% |
| Tata Usaha | 15 | 15 | 0 | 100% |
| Guru | 12 | 12 | 0 | 100% |
| Wali Kelas | 12 | 11 | 1 | 92% |
| Bendahara | 10 | 10 | 0 | 100% |
| Siswa | 8 | 8 | 0 | 100% |
| Orang Tua | 8 | 8 | 0 | 100% |
| **Total** | **80** | **78** | **2** | **97.5%** |

## Keputusan UAT

| Keputusan | Keterangan |
| --- | --- |
| ✅ Diterima | Jika fail rate < 20% dan bug High/Critical = 0 |
| ❌ Ditolak | Jika fail rate >= 20% atau ada bug Critical |

Hasil simulasi menunjukkan **97.5% Pass**, dengan 2 bug minor yang dapat diperbaiki sebelum go-live. Proyek **dapat diterima untuk go-live** setelah perbaikan.

## Tanda Tangan Persetujuan

| Peran | Nama | Tanda Tangan | Tanggal |
| --- | --- | --- | --- |
| Kepala Sekolah | ........ | ........ | ........ |
| Project Manager | ........ | ........ | ........ |
| QA Lead | ........ | ........ | ........ |
