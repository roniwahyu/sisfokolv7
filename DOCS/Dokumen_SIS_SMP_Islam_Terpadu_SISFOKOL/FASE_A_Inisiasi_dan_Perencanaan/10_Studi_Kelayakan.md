# 10 — Studi Kelayakan
### Proyek: Sistem Informasi Sekolah SMP Islam Terpadu

## 1. Pendahuluan

Studi kelayakan menilai kelayakan proyek dari **5 aspek**: Teknis, Operasional, Biaya, Waktu, dan SDM. Penilaian menggunakan skala 1–5 (1 = sangat tidak layak, 5 = sangat layak).

## 2. Ringkasan Eksekutif

| Aspek | Skor | Bobot | Nilai Terbobot | Status |
|-------|:----:|:-----:|:--------------:|--------|
| Teknis | 4,5 | 25% | 1,13 | ✅ Layak |
| Operasional | 4,0 | 20% | 0,80 | ✅ Layak |
| Biaya/Ekonomi | 4,0 | 25% | 1,00 | ✅ Layak |
| Waktu/Jadwal | 3,5 | 15% | 0,53 | ⚠️ Layak Bersyarat |
| SDM | 3,5 | 15% | 0,53 | ⚠️ Layak Bersyarat |
| **TOTAL** | | **100%** | **3,98 / 5** | ✅ **LAYAK DILANJUTKAN** |

## 3. Tabel Penilaian Kelayakan Detail

### 3.1 Aspek Teknis
| Kriteria | Penilaian | Skor | Keterangan |
|----------|-----------|:----:|-----------|
| Ketersediaan teknologi | PHP/MySQL/XAMPP standar, dokumentasi lengkap | 5 | Teknologi matang & umum |
| Basis kode siap pakai | SISFOKOL v7.00 (75 tabel, 9 modul) sudah lengkap | 5 | Tinggal kustomisasi |
| Infrastruktur server | VPS/cloud tersedia lokal & terjangkau | 4 | Butuh provisi VPS |
| Keamanan | HTTPS, RBAC, hash password | 4 | Perlu peningkatan bcrypt |
| Kompatibilitas browser | Chrome/Edge/Firefox | 4 | Responsif mobile terbatas |
| **Rata-rata** | | **4,5** | |

### 3.2 Aspek Operasional
| Kriteria | Penilaian | Skor | Keterangan |
|----------|-----------|:----:|-----------|
| Dukungan manajemen | Kepsek & yayasan mendukung | 5 | Keputusan tertulis |
| Kesiapan proses | SOP sudah ada (manual), siap dipetakan | 4 | Perlu standarisasi |
| Penerimaan pengguna | Sebagian resistensi perubahan | 3 | Mitigasi: pelatihan |
| Dampak ke operasional | Menyederhanakan, bukan mengganggu | 4 | Transisi bertahap |
| **Rata-rata** | | **4,0** | |

### 3.3 Aspek Biaya/Ekonomi
| Kriteria | Penilaian | Skor | Keterangan |
|----------|-----------|:----:|-----------|
| Ketersediaan anggaran | Rp 185 juta tersedia | 4 | Sesuai charter |
| Biaya vs manfaat | Hemat ≥120 jam/bln + turunkan tunggakan | 5 | ROI < 2 tahun |
| Biaya lisensi | Open-source (gratis) | 5 | Hanya kustomisasi |
| Biaya operasional | VPS + domain terjangkau | 3 | Tahunan berulang |
| **Rata-rata** | | **4,0** | |

### 3.4 Aspek Waktu/Jadwal
| Kriteria | Penilaian | Skor | Keterangan |
|----------|-----------|:----:|-----------|
| Kewajaran timeline | 22 minggu untuk go-live 1 Juli 2026 | 4 | Cukup, tapi padat |
| Ketergantungan vendor | Ketergantungan vendor untuk kustomisasi | 3 | Mitigasi: kontrak SLA |
| Risiko keterlambatan | Migrasi data bisa molor | 3 | Buffer 2 minggu |
| **Rata-rata** | | **3,5** | |

### 3.5 Aspek SDM
| Kriteria | Penilaian | Skor | Keterangan |
|----------|-----------|:----:|-----------|
| Tim IT internal | 1 orang (terbatas) | 3 | Butuh vendor/mitra |
| Kompetensi pengguna | Guru literasi digital sedang | 4 | Pelatihan 4 jam |
| Vendor kompeten | Tersedia di pasar | 4 | Seleksi vendor |
| **Rata-rata** | | **3,5** | |

## 4. Analisis Cost-Benefit (Ringkas)

| Item | Nilai (Rp/tahun) |
|------|------------------|
| **Biaya Implementasi (sekali)** | 147.000.000 |
| **Biaya Operasional (per tahun)** | 38.000.000 |
| **Manfaat (efisiensi waktu ≈120 jam/bln × Rp50rb)** | 72.000.000/tahun |
| **Manfaat (turunkan tunggakan & kebocoran)** | ~60.000.000/tahun |
| **Payback Period** | **≈ 2,6 tahun** |

## 5. Analisis Risiko Kelayakan

| Risiko | Probabilitas | Dampak | Skor | Mitigasi |
|--------|:-----------:|:------:|:----:|----------|
| Resistensi pengguna | Tinggi | Sedang | 9 | Pelatihan + champion user |
| Data lama buruk | Sedang | Tinggi | 12 | Pembersihan + validasi |
| Vendor telat | Sedang | Tinggi | 12 | Kontrak SLA + buffer |
| SDM internal kurang | Tinggi | Sedang | 9 | Knowledge transfer |

## 6. Rekomendasi & Keputusan

1. **LAYAK DILANJUTKAN** dengan total skor 3,98/5.
2. Fokus pada mitigasi risiko Waktu & SDM (skor 3,5):
   - Tambah buffer 2 minggu pada migrasi data.
   - Wajibkan transfer knowledge & dokumentasi dari vendor.
   - Pilih *champion user* per peran sejak fase pengujian.
3. Eksekusi segera agar go-live 01 Juli 2026 tercapai.

## 7. Persetujuan

| Nama | Jabatan | Tanda Tangan |
|------|---------|--------------|
| | Kepala Sekolah | |
| | Ketua Yayasan | |
