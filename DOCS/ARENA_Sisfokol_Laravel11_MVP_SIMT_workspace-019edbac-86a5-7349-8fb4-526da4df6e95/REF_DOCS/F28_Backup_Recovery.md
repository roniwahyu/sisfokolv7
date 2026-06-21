# F27. SOP Operasional Sistem

---

## SOP Harian Penggunaan Sistem

### 1. Tata Usaha (TU)

```mermaid
flowchart TD
    A[Login Sistem] --> B{Cek Pengumuman / Tugas?}
    B -->|Ya| C[Tangani Administrasi]
    B -->|Tidak| D[Kelola Data Master]
    D --> E[Update Data Siswa/Guru jika ada perubahan]
    E --> F[Backup/Export Data Mingguan]
    F --> G[Logout]
```

Tugas harian:
- Login setiap pagi.
- Memverifikasi data siswa baru yang masuk.
- Memastikan data master tetap akurat.
- Melakukan export cadangan data setiap akhir pekan.

### 2. Guru / Wali Kelas

```mermaid
flowchart TD
    A[Login Sistem] --> B[Lihat Jadwal Hari Ini]
    B --> C[Mengajar]
    C --> D[Input Absensi Kelas]
    D --> E[Input Nilai sesuai Jadwal]
    E --> F[Rekap Absensi & Nilai]
    F --> G[Logout]
```

Tugas harian:
- Input absensi setelah jam pelajaran selesai.
- Input nilai setelah ujian/penilaian.
- Wali kelas memantau rekap absensi dan nilai kelas.

### 3. Bendahara

```mermaid
flowchart TD
    A[Login Sistem] --> B[Cek Tagihan Hari Ini]
    B --> C[Menerima Pembayaran]
    C --> D[Input Pembayaran ke Sistem]
    D --> E[Cetak Kwitansi]
    E --> F[Rekap Pembayaran Harian]
    F --> G[Logout]
```

Tugas harian:
- Mencatat pembayaran SPP/infaq setiap transaksi.
- Mencetak kwitansi.
- Membuat rekap harian dan laporan mingguan.

### 4. Kepala Sekolah / Wakasek

```mermaid
flowchart TD
    A[Login Sistem] --> B[Lihat Dashboard]
    B --> C[Analisis Laporan Akademik/Keuangan/Kehadiran]
    C --> D[Ambil Keputusan / Tindak Lanjut]
    D --> E[Validasi Rapor / Pengumuman]
    E --> F[Logout]
```

Tugas harian:
- Memantau dashboard setiap pagi.
- Memvalidasi rapor dan kebijakan penting.
- Mengeluarkan pengumuman jika diperlukan.

### 5. Siswa / Orang Tua

```mermaid
flowchart TD
    A[Login Portal] --> B[Lihat Jadwal / Nilai / Tagihan]
    B --> C[Unduh Rapor / Kwitansi]
    C --> D[Hubungi Wali Kelas / Sekolah jika perlu]
    D --> E[Logout]
```

Tugas harian:
- Siswa memantau jadwal dan nilai.
- Orang tua memantau tagihan dan perkembangan anak.

## Prosedur Jika Terjadi Kendala

1. Coba logout dan login kembali.
2. Clear cache browser atau gunakan browser lain.
3. Hubungi admin IT sekolah melalui channel resmi.
4. Jika data penting salah, ajukan perbaikan melalui TU/Wali Kelas.
