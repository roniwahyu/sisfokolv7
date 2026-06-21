# E22. Test Case & Scenario

---

| No | ID | Modul | Skenario Uji | Langkah | Data Uji | Expected Result | Prioritas | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | TC-001 | Login | Login dengan kredensial valid | 1. Buka halaman login<br>2. Masukkan username & password benar<br>3. Klik login | user:guru, pass:benar | Berhasil masuk ke dashboard sesuai role | High | Pass |
| 2 | TC-002 | Login | Login dengan password salah | 1. Masukkan username benar<br>2. Password salah<br>3. Login | pass:salah | Tampil pesan error, tidak masuk | High | Pass |
| 3 | TC-003 | RBAC | Guru mengakses laporan keuangan | Login sebagai guru, buka menu keuangan | role:guru | Akses ditolak / redirect 403 | High | Pass |
| 4 | TC-004 | Data Master | TU menambah siswa baru | Buka form siswa, isi data, simpan | NIS unik | Data tersimpan, muncul di daftar | High | Pass |
| 5 | TC-005 | Data Master | Validasi NIS duplikat | Input NIS yang sudah ada | NIS duplikat | Muncul pesan error validasi | High | Pass |
| 6 | TC-006 | Data Master | TU mengubah data guru | Edit no_hp guru | no_hp baru | Data terupdate | Medium | Pass |
| 7 | TC-007 | Jadwal | Membuat jadwal bentrok | Input guru sama di jam sama | data bentrok | Sistem menolak dengan pesan | Medium | Pass |
| 8 | TC-008 | Absensi | Guru mengabsen kelas | Pilih kelas, pilih status, simpan | kelas 7-A | Absensi tersimpan per siswa | High | Pass |
| 9 | TC-009 | Absensi | Rekap absensi bulanan | Pilih bulan, generate rekap | Agustus 2026 | Tabel rekap muncul akurat | High | Pass |
| 10 | TC-010 | Nilai | Input nilai UH | Pilih kelas & mapel, input nilai | 80 | Nilai tersimpan | High | Pass |
| 11 | TC-011 | Nilai | Hitung nilai akhir otomatis | Input UH, PTS, PAS | 80, 85, 90 | Nilai akhir sesuai bobot | High | Pass |
| 12 | TC-012 | Nilai | Validasi nilai di luar rentang | Input nilai 110 | 110 | Error rentang 0-100 | Medium | Pass |
| 13 | TC-013 | Rapor | Generate draft rapor | Pilih kelas & semester | 7-A Ganjil | Draft tampil dengan data | High | Pass |
| 14 | TC-014 | Rapor | Cetak rapor PDF | Klik cetak | - | PDF terunduh dengan format benar | High | Pass |
| 15 | TC-015 | Keuangan | Bendahara input pembayaran SPP | Pilih siswa, bulan, jumlah | Rp 250.000 | Status tagihan lunas | High | Pass |
| 16 | TC-016 | Keuangan | Cetak kwitansi | Klik cetak kwitansi | - | PDF kwitansi tercetak | Medium | Pass |
| 17 | TC-017 | Keuangan | Orang tua melihat tagihan | Login ortu, buka tagihan | - | Tagihan anak tampil | High | Pass |
| 18 | TC-018 | Portal Siswa | Siswa melihat jadwal | Login siswa | - | Jadwal kelas tampil | High | Pass |
| 19 | TC-019 | Portal Orang Tua | Orang tua melihat nilai anak | Login ortu | - | Nilai anak tampil | High | Pass |
| 20 | TC-020 | Dashboard | Kepala sekolah melihat dashboard | Login kepala sekolah | - | Chart & ringkasan tampil | High | Pass |
| 21 | TC-021 | Audit | Setiap update nilai tercatat log | Update nilai | - | Audit log bertambah | High | Pass |
| 22 | TC-022 | Notifikasi | Notifikasi tagihan terkirim | Trigger tagihan baru | - | Notifikasi masuk | Medium | Pass |
| 23 | TC-023 | RBAC | Siswa mengakses nilai siswa lain | Coba akses URL nilai lain | - | Akses ditolak | High | Pass |
| 24 | TC-024 | Keamanan | SQL Injection pada form login | Input ' OR 1=1 -- | - | Ditolak, tidak login | High | Pass |
| 25 | TC-025 | Keamanan | XSS pada input nama | Input `<script>alert(1)</script>` | - | Disanitasi, tidak dieksekusi | High | Pass |
| 26 | TC-026 | Backup | Backup otomatis berjalan | Tunggu jadwal backup | - | File backup tercipta | Medium | Pass |
| 27 | TC-027 | Mobile | Tampilan responsif di HP | Buka di perangkat mobile | - | Layout sesuai layar | Medium | Pass |
| 28 | TC-028 | Reset Password | Lupa password via email | Klik lupa password, masukkan email | email valid | Email reset terkirim | Medium | Pass |
| 29 | TC-029 | Pengumuman | Kepala sekolah membuat pengumuman | Buat pengumuman, simpan | - | Pengumuman muncul di dashboard | Medium | Pass |
| 30 | TC-030 | Laporan | Export rekap nilai ke Excel | Klik export | - | File Excel terunduh | Medium | Pass |
| 31 | TC-031 | Laporan | Laporan keuangan bulanan | Pilih bulan | September 2026 | Total sesuai pembayaran | High | Pass |
| 32 | TC-032 | Migrasi | Import data siswa dari Excel | Upload template | data 100 siswa | Data masuk tanpa error | High | Pass |

## Cara Menjalankan Test

1. Siapkan environment testing dengan data seed.
2. Jalankan test case sesuai urutan prioritas.
3. Catat actual result dan status.
4. Bug yang ditemukan masuk ke bug tracker.
