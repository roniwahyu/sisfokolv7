# C13. Data Dictionary

---

Berikut kamus data untuk minimal 30 field penting dalam sistem.

| No | Tabel | Field | Tipe Data | Panjang | Keterangan | Contoh |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | users | id | INT | 11 | Primary key, auto increment | 1 |
| 2 | users | username | VARCHAR | 50 | Nama pengguna unik | budi.santoso |
| 3 | users | password | VARCHAR | 255 | Hash bcrypt/Argon2 | $2y$10$... |
| 4 | users | role | ENUM | - | Role pengguna | guru |
| 5 | users | is_active | TINYINT | 1 | Status aktif | 1 |
| 6 | tahun_ajaran | id | INT | 11 | Primary key | 3 |
| 7 | tahun_ajaran | nama | VARCHAR | 20 | Nama tahun ajaran | 2026/2027 |
| 8 | tahun_ajaran | semester | ENUM | - | Ganjil/Genap | Ganjil |
| 9 | tahun_ajaran | is_active | TINYINT | 1 | Status aktif | 1 |
| 10 | kelas | id | INT | 11 | Primary key | 5 |
| 11 | kelas | nama | VARCHAR | 20 | Nama kelas | 7-A |
| 12 | kelas | tingkat | TINYINT | 1 | Tingkat kelas | 7 |
| 13 | kelas | wali_kelas_id | INT | 11 | FK ke guru | 12 |
| 14 | kelas | tahun_ajaran_id | INT | 11 | FK ke tahun ajaran | 3 |
| 15 | siswa | id | INT | 11 | Primary key | 101 |
| 16 | siswa | nis | VARCHAR | 20 | Nomor induk siswa | 2601001 |
| 17 | siswa | nama | VARCHAR | 100 | Nama lengkap | Aisyah Putri |
| 18 | siswa | kelas_id | INT | 11 | FK ke kelas | 5 |
| 19 | siswa | jenis_kelamin | ENUM | - | L/P | P |
| 20 | siswa | tanggal_lahir | DATE | - | Tanggal lahir | 2013-05-12 |
| 21 | siswa | alamat | TEXT | - | Alamat lengkap | Jl. Mawar No. 1 |
| 22 | siswa | orang_tua_id | INT | 11 | FK ke orang_tua | 21 |
| 23 | guru | id | INT | 11 | Primary key | 12 |
| 24 | guru | nip | VARCHAR | 30 | Nomor induk pegawai | 198501012010011 |
| 25 | guru | nama | VARCHAR | 100 | Nama lengkap | Budi Santoso, S.Pd. |
| 26 | guru | email | VARCHAR | 100 | Email aktif | budi@sekolah.sch.id |
| 27 | guru | no_hp | VARCHAR | 15 | Nomor telepon | 08123456789 |
| 28 | mata_pelajaran | id | INT | 11 | Primary key | 8 |
| 29 | mata_pelajaran | kode | VARCHAR | 10 | Kode mapel | MTK |
| 30 | mata_pelajaran | nama | VARCHAR | 100 | Nama mapel | Matematika |
| 31 | nilai | id | INT | 11 | Primary key | 5001 |
| 32 | nilai | siswa_id | INT | 11 | FK ke siswa | 101 |
| 33 | nilai | mapel_id | INT | 11 | FK ke mapel | 8 |
| 34 | nilai | uh | DECIMAL | 5,2 | Nilai UH | 85.00 |
| 35 | nilai | pts | DECIMAL | 5,2 | Nilai PTS | 88.00 |
| 36 | nilai | pas | DECIMAL | 5,2 | Nilai PAS | 90.00 |
| 37 | nilai | sikap | VARCHAR | 10 | Predikat sikap | Baik |
| 38 | absensi | id | INT | 11 | Primary key | 9001 |
| 39 | absensi | siswa_id | INT | 11 | FK ke siswa | 101 |
| 40 | absensi | tanggal | DATE | - | Tanggal absensi | 2026-08-10 |
| 41 | absensi | status | ENUM | - | H/S/I/A | H |
| 42 | absensi | keterangan | VARCHAR | 100 | Keterangan | Sakit demam |
| 43 | pembayaran | id | INT | 11 | Primary key | 7001 |
| 44 | pembayaran | siswa_id | INT | 11 | FK ke siswa | 101 |
| 45 | pembayaran | jenis_id | INT | 11 | FK ke jenis pembayaran | 2 |
| 46 | pembayaran | jumlah | DECIMAL | 12,2 | Nominal | 250000.00 |
| 47 | pembayaran | tanggal_bayar | DATE | - | Tanggal pembayaran | 2026-08-05 |
| 48 | pembayaran | metode | ENUM | - | Tunai/Transfer | Tunai |
| 49 | pembayaran | status | ENUM | - | Lunas/Belum | Lunas |
| 50 | audit_log | id | INT | 11 | Primary key | 12001 |
| 51 | audit_log | user_id | INT | 11 | FK ke users | 5 |
| 52 | audit_log | aksi | VARCHAR | 100 | Jenis aksi | UPDATE nilai |
| 53 | audit_log | detail | TEXT | - | Detail perubahan | nilai UH 80 -> 85 |
| 54 | audit_log | waktu | DATETIME | - | Waktu aksi | 2026-08-10 09:15:00 |

## Catatan

- Field `password` selalu disimpan dalam bentuk hash; plain text tidak diperbolehkan.
- Semua field relasi menggunakan tipe `UNSIGNED INT` untuk konsistensi.
- Field `status` pada pembayaran memungkinkan pemantauan tagihan berjalan.
- `audit_log` wajib ada untuk keperluan audit dan keamanan.
