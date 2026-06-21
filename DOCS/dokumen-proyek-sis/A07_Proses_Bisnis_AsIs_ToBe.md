# A7. Proses Bisnis (As-Is & To-Be)

---

## 1. Pendaftaran Siswa Baru

### As-Is
```mermaid
flowchart LR
    A[Orang Tua Mengisi Formulir Kertas] --> B[TU Memasukkan ke Excel]
    B --> C{Validasi Manual}
    C -->|Diterima| D[Arsip Berkas]
    C -->|Ditolak| E[Perbaikan Berkas]
    E --> A
```

### To-Be
```mermaid
flowchart LR
    A[Orang Tua Mengisi Formulir Online] --> B[TU Verifikasi Data]
    B --> C{Validasi Otomatis}
    C -->|Diterima| D[Generate NIS & Akun Siswa]
    C -->|Ditolak| E[Notifikasi Perbaikan]
    E --> A
    D --> F[Arsip Digital]
```

## 2. Input Nilai

### As-Is
```mermaid
flowchart LR
    A[Guru Mengisi Buku Nilai] --> B[TU Mengumpulkan]
    B --> C[TU Mengolah di Excel]
    C --> D[Wali Kelas Cek Validasi]
    D --> E[Cetak Rapor Manual]
```

### To-Be
```mermaid
flowchart LR
    A[Guru Login Sistem] --> B[Input Nilai UH/PTS/PAS]
    B --> C[Hitung Nilai Akhir Otomatis]
    C --> D[Wali Kelas Validasi]
    D --> E[Cetak Rapor Digital]
```

## 3. Cetak Rapor

### As-Is
```mermaid
flowchart LR
    A[TU Kumpulkan Nilai] --> B[Wali Kelas Susun Rapor]
    B --> C[Kepala Sekolah Tandatangani]
    C --> D[Cetak & Bagikan]
```

### To-Be
```mermaid
flowchart LR
    A[Sistem Kompilasi Nilai] --> B[Wali Kelas Cek Deskripsi]
    B --> C[Kepala Sekolah Validasi Digital]
    C --> D[Cetak Rapor Massal]
    D --> E[Bagi ke Portal Siswa/Orang Tua]
```

## 4. Absensi Harian

### As-Is
```mermaid
flowchart LR
    A[Guru Panggil Nama] --> B[Catat di Buku]
    B --> C[TU Rekap Bulanan]
    C --> D[Lapor Kepala Sekolah]
```

### To-Be
```mermaid
flowchart LR
    A[Guru/Wali Kelas Input Absensi] --> B[Sistem Hitung Rekap]
    B --> C[Notifikasi Ortu jika Absensi Tidak Wajar]
    C --> D[Dashboard Kepala Sekolah Real-Time]
```

## 5. Pembayaran SPP

### As-Is
```mermaid
flowchart LR
    A[Orang Tua Bayar ke Sekolah] --> B[Bendahara Catat di Buku]
    B --> C[Cetak Kwitansi Manual]
    C --> D[TU Susun Laporan Bulanan]
```

### To-Be
```mermaid
flowchart LR
    A[Orang Tua Bayar Online/Offline] --> B[Bendahara Input Pembayaran]
    B --> C[Sistem Update Status Tagihan]
    C --> D[Cetak Kwitansi Digital]
    D --> E[Laporan Keuangan Otomatis]
```

## 6. Monitoring Kepala Sekolah

### As-Is
```mermaid
flowchart LR
    A[Kepala Sekolah Minta Laporan] --> B[TU/Kesiswaan Susun Laporan]
    B --> C[Presentasi Manual]
    C --> D[Keputusan Berdasarkan Laporan Tertunda]
```

### To-Be
```mermaid
flowchart LR
    A[Kepala Sekolah Buka Dashboard] --> B[Dashboard Real-Time]
    B --> C[Analisis Data Akademik/Keuangan/Kehadiran]
    C --> D[Keputusan Cepat Berbasis Data]
```
