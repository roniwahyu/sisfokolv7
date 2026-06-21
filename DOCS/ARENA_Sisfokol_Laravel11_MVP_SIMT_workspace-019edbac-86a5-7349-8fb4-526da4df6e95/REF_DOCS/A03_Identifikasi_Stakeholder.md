# A2. Visi, Tujuan, dan Ruang Lingkup

---

## 1. Visi

> Menjadikan SMP Islam Terpadu sebagai sekolah yang mengelola seluruh proses pendidikan, administrasi, dan keuangan secara digital, transparan, dan berbasis data, guna meningkatkan mutu layanan pendidikan Islami.

## 2. Misi

1. Mengintegrasikan data akademik dan non-akademik dalam satu platform.
2. Meningkatkan akurasi dan kecepatan pelaporan kepada stakeholder.
3. Memfasilitasi komunikasi aktif antara sekolah, siswa, dan orang tua.
4. Mendukung pengambilan keputusan kepala sekolah berdasarkan data real-time.

## 3. Tujuan Proyek (SMART)

| No | Tujuan | Indikator | Target |
| --- | --- | --- | --- |
| 1 | Terintegrasinya data siswa dan guru | Jumlah data duplikat | 0% duplikat pada go-live |
| 2 | Efisiensi proses penilaian | Waktu input nilai | Dari 2 minggu jadi 3 hari |
| 3 | Transparansi pembayaran | Akses riwayat pembayaran | 100% orang tua dapat mengakses |
| 4 | Peningkatan absensi | Waktu rekap absensi | Dari 1 minggu jadi 1 hari |
| 5 | Kecepatan pembuatan rapor | Waktu cetak rapor | Dari 5 hari jadi 1 hari |

## 4. Ruang Lingkup Proyek (In-Scope)

- Modul manajemen pengguna dan RBAC.
- Modul data master (siswa, guru, kelas, mapel, tahun ajaran).
- Modul jadwal pelajaran.
- Modul absensi siswa dan guru.
- Modul penilaian (UH, PTS, PAS, nilai akhlak/sikap).
- Modul rapor dan cetak rapor.
- Modul pembayaran SPP & infaq (integrasi manual/QRIS).
- Portal siswa, guru, wali kelas, dan orang tua.
- Dashboard dan laporan untuk kepala sekolah.

## 5. Batasan Proyek (Out-of-Scope)

- Sistem tidak mengelola kepegawaian ASN secara penuh (sinkronisasi simpel saja).
- Tidak menggantikan sistem pembayaran bank resmi; hanya pencatatan.
- Tidak menyediakan e-learning penuh; hanya distribusi materi ringan.
- Tidak terintegrasi langsung dengan Dapodik Kemendikbudristek (fase 1).

## 6. Modul Fase 1 vs Fase 2

| Modul | Fase 1 (Go-Live) | Fase 2 (Pengembangan Berkelanjutan) |
| --- | :---: | :---: |
| Data Master | ✅ | ✅ (penambahan atribut) |
| Absensi | ✅ | ✅ (RFID/face recognition) |
| Penilaian & Rapor | ✅ | ✅ (kurikulum merdeka full) |
| Pembayaran SPP | ✅ | ✅ (integrasi gateway) |
| Jadwal | ✅ | ✅ (otomatisasi jadwal) |
| Portal Orang Tua | ✅ | ✅ (chat notifikasi) |
| E-Learning | ❌ | ✅ |
| Integrasi Dapodik | ❌ | ✅ |
| E-PPDB | ❌ | ✅ |
| Perpustakaan Digital | ❌ | ✅ |

## 7. Asumsi & Kendala

- Asumsi: Koneksi internet tersedia di kantor dan kelas; perangkat komputer/HP cukup.
- Kendala: Kapasitas SDM IT sekolah terbatas; perlu pelatihan berkelanjutan.
