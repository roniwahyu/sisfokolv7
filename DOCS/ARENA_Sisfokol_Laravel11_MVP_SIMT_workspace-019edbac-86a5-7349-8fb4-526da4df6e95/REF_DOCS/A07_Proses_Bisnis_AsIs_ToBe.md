# A6. User Role dan Hak Akses (RBAC)

---

## Matriks Role vs Hak Akses

Legend:
- **C** = Create / Input
- **R** = Read / Lihat
- **U** = Update / Edit
- **D** = Delete / Hapus
- **P** = Print / Cetak
- **A** = Approve / Validasi
- **-** = Tidak memiliki akses

| Modul / Fitur | Kepala Sekolah | Wakasek | Tata Usaha | Guru | Wali Kelas | Bendahara | Siswa | Orang Tua |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| **Dashboard Executive** | R | R | R | - | - | R | - | - |
| **Data Siswa** | R | R | CRUD | R | R (kelasnya) | - | R (sendiri) | R (anaknya) |
| **Data Guru** | R | R | CRUD | R (sendiri) | R | - | R | - |
| **Data Kelas & Mapel** | R | R | CRUD | R | R | - | R | - |
| **Jadwal Pelajaran** | R | RU | CRUD | R | R | - | R | - |
| **Absensi Siswa** | R | R | R | C (mapelnya) | CRUD (kelasnya) | - | R (sendiri) | R (anaknya) |
| **Input Nilai** | R | R | R | CRU (mapelnya) | R (kelasnya) | - | R (sendiri) | R (anaknya) |
| **Cetak Rapor** | R | R | R | - | R,P (kelasnya) | - | R,P (sendiri) | R (anaknya) |
| **Pembayaran SPP** | R | - | - | - | - | CRUD | R (sendiri) | R (anaknya) |
| **Laporan Keuangan** | R | - | R | - | - | R,P | - | - |
| **Pengumuman** | CRUD | CRUD | CRUD | R | R | R | R | R |
| **Manajemen Pengguna** | CRUD | R | R | - | - | - | - | - |
| **Audit Log** | R | - | - | - | - | - | - | - |
| **Pengaturan Sistem** | CRUD | R | - | - | - | - | - | - |

## Keterangan Role

| Role | Deskripsi |
| --- | --- |
| Kepala Sekolah | Hak akses penuh untuk monitoring, pengaturan, dan laporan. |
| Wakasek | Akses akademik, kedisiplinan, dan pengumuman. |
| Tata Usaha | CRUD data master, administrasi, dan pengguna. |
| Guru | Input nilai dan absensi untuk kelas/mapel yang diampu. |
| Wali Kelas | Kelola data kelasnya, termasuk rapor dan absensi. |
| Bendahara | Kelola pembayaran, kwitansi, dan laporan keuangan. |
| Siswa | Melihat data akademik dan keuangan sendiri. |
| Orang Tua | Melihat data anak kandung/wali serta tagihan. |

## Aturan Keamanan RBAC

1. Setiap pengguna hanya memiliki satu role utama.
2. Akses data terbatas pada unit/wali kelas/mapel yang menjadi tanggung jawabnya.
3. Setiap aksi create/update/delete dicatat dalam audit log.
4. Role dapat diubah oleh Kepala Sekolah atau Tata Usaha dengan persetujuan tertulis.
