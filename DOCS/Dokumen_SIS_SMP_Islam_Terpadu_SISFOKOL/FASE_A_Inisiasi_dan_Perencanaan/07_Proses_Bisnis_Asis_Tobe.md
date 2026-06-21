# 07 — Proses Bisnis (As-Is & To-Be)
### Proyek: Sistem Informasi Sekolah SMP Islam Terpadu

## 1. Pendahuluan

Dokumen ini memetakan proses bisnis sekolah pada kondisi **As-Is** (saat ini, manual) dan **To-Be** (setelah sistem). Lima proses inti dipetakan dalam bentuk *flowchart*/BPMN: (1) Pendaftaran/Entri Siswa Baru, (2) Input Nilai & Raport, (3) Presensi Kehadiran, (4) Pembayaran & Tunggakan SPP, (5) Pencatatan Pelanggaran BK.

## 2. Proses 1 — Pendaftaran & Entri Data Siswa Baru

### 2.1 As-Is (Manual)
```mermaid
flowchart LR
    A[Calon siswa daftar] --> B[Isi formulir kertas]
    B --> C[TU ketik ulang di Excel]
    C --> D{Cek duplikat manual}
    D -- Ada --> C
    D -- Tidak --> E[Bagikan data ke BK & Bendahara manual]
    E --> F[Bendahara catat tagihan di buku]
```
**Masalah:** duplikasi input, data tidak sinkron antar bagian, lambat.

### 2.2 To-Be (Sistem)
```mermaid
flowchart LR
    A[Calon siswa daftar] --> B[Admin input/impor di SIS]
    B --> C[Sistem validasi NIS unik]
    C --> D{Duplikat?}
    D -- Ya --> C
    D -- Tidak --> E[Generate akun + QR Code]
    E --> F[Auto-buat tagihan & slot BK]
    F --> G[Data tersinkron semua modul]
```
**Manfaat:** sekali input, data menyebar ke BK, keuangan, presensi; QR otomatis.

## 3. Proses 2 — Input Nilai & Cetak Rapor

### 3.1 As-Is
```mermaid
flowchart LR
    A[Guru isi nilai di Excel] --> B[Wali kelas rekap manual]
    B --> C[Hitung rata-rata & NA manual]
    C --> D[Ketik di format rapor Word]
    D --> E[Cetak & tanda tangan]
```

### 3.2 To-Be
```mermaid
flowchart LR
    A[Guru Mapel input nilai formatif/sumatif] --> B[Sistem hitung NA+predikat Kurmer]
    B --> C[Wali Kelas lengkapi sikap & catatan]
    C --> D[Kepsek review/approve]
    D --> E[Cetak rapor PDF otomatis]
    E --> F[Tersedia di portal ortu]
```

## 4. Proses 3 — Presensi Kehadiran Siswa

### 4.1 As-Is
```mermaid
flowchart LR
    A[Petugas absen manual di buku kelas] --> B[Hitung hadir/sakit/ijin/alpha]
    B --> C[Wali kelas rekap akhir bulan]
    C --> D[Laporkan ke TU]
```

### 4.2 To-Be
```mermaid
flowchart LR
    A[Siswa scan QR Code] --> B[Sistem catat jam hadir/pulang]
    B --> C{Validasi jadwal piket}
    C -- Valid --> D[Status: Hadir]
    C -- Terlambat --> E[Status: Telat]
    C -- Tidak scan --> F[Status: Alpha]
    D --> G[Rekap real-time]
    E --> G
    F --> G
    G --> H[Notifikasi ortu bila alpha]
```

## 5. Proses 4 — Pembayaran SPP & Tunggakan

### 5.1 As-Is
```mermaid
flowchart LR
    A[Siswa/ortu bayar di loket] --> B[Bendahara catat di buku kas]
    B --> C[Tulis kuitansi tangan]
    C --> D[Akhir bulan rekap tunggakan manual]
    D --> E[Sulit menagih tepat sasaran]
```

### 5.2 To-Be
```mermaid
flowchart LR
    A[Ortu cek tagihan di portal] --> B[Bayar ke Bendahara]
    B --> C[Input pembayaran di SIS]
    C --> D[Cetak kuitansi otomatis]
    D --> E[Sistem update status lunas]
    E --> F{Tunggakan?}
    F -- Ya --> G[Auto-notif WA ke ortu]
    F -- Tidak --> H[Status Lunas]
    G --> I[Dashboard tunggakan real-time]
```

## 6. Proses 5 — Pencatatan Pelanggaran & BK

### 6.1 As-Is
```mermaid
flowchart LR
    A[Siswa melanggar] --> B[Guru catat di buku BK]
    B --> C[BK kumpulkan catatan]
    C --> D[Sulit rekap poin per siswa]
```

### 6.2 To-Be
```mermaid
flowchart LR
    A[Siswa melanggar] --> B[Guru/Piket input kejadian]
    B --> C[BK entri jenis + poin]
    C --> D[Sistem akumulasi poin siswa]
    D --> E{Poin ambang batas?}
    E -- Ya --> F[Notifikasi Wali Kelas & Ortu]
    E -- Tidak --> G[Pantau]
    F --> H[Tindak lanjut pembinaan]
```

## 7. Perbandingan As-Is vs To-Be (Ringkas)

| Proses | Waktu As-Is | Waktu To-Be | Risiko Kesalahan As-Is | To-Be |
|--------|-------------|-------------|------------------------|-------|
| Entri Siswa Baru | 30–45 mnt | 5–10 mnt | Duplikasi tinggi | Validasi otomatis |
| Input Nilai & Rapor | Berhari-hari | ≤ 1 hari | Salah hitung | Otomatis Kurmer |
| Presensi | 15 mnt/kelas | Real-time | Manipulatif | QR terverifikasi |
| Pembayaran SPP | Manual, rekap lama | Seketika + WA | Salah catat | Auto kuitansi |
| Pelanggaran BK | Tersebar | Terkonsolidasi | Sulit rekap | Akumulasi poin |

## 8. BPMN Tingkat Tinggi (To-Be — Input Nilai)

```mermaid
flowchart TB
    subgraph GM[Guru Mapel - admgr]
      GM1[Input Nilai Asesmen]
    end
    subgraph SYS[Sistem]
      S1[Hitung NA & Predikat]
      S2[Validasi KKM]
    end
    subgraph WK[Wali Kelas - admwk]
      WK1[Lengkapi Sikap & Catatan]
    end
    subgraph KS[Kepala Sekolah - admks]
      KS1[Review & Approve Rapor]
    end
    GM1 --> S1 --> S2 --> WK1 --> KS1 --> OUT[Cetak Rapor & Portal Ortu]
```

## 9. Penutup

Lima proses inti di atas menunjukkan bahwa otomatisasi via SIS menghemat waktu signifikan, mengurangi kesalahan, dan meningkatkan transparansi serta keterlibatan orang tua.
