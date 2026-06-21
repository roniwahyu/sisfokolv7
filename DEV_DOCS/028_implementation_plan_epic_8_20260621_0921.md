# DEV_DOCS-028: Rencana Implementasi — Epic 8: Presence Module (Presensi & Kehadiran)

- **Tanggal:** 2026-06-21 09:21
- **Status:** ⏳ MENUNGGU PERSETUJUAN
- **Penulis:** Antigravity (Google DeepMind)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith

---

## 🛡️ KEPUTUSAN ARSITEKTUR & DESAIN

1. **Struktur Data Multi-Tenant**:
   - Seluruh entitas presensi menggunakan trait `BelongsToTenant` dan `TracksAuditColumns` untuk pembatasan data per sekolah secara ketat.
   - Tabel presensi, absensi, dan izin mencatat relasi ke model `User` (untuk guru/staf) atau `Siswa`.

2. **Aturan Jam Masuk Dinamis per Sekolah (`PresensiRuleEngine`)**:
   - Jam masuk batas (threshold terlambat) tidak hardcoded melainkan dibaca dinamis dari tabel `tenant_settings` (contoh parameter: `jam_masuk_normal` = 07:00, `jam_masuk_toleransi` = 07:15).
   - Logic engine akan membandingkan waktu scan dengan toleransi ini untuk menandai status kehadiran secara otomatis (`tepat_waktu`, `terlambat`).

3. **Mesin Scanner QR Code & Anti-Spam**:
   - Penambahan `QrScannerService` untuk mengonversi teks QR menjadi identitas siswa/pegawai, memvalidasi kepemilikan tenant, dan mencegah scanning ganda dalam rentang waktu yang sama (mencegah *race condition* penambahan record kehadiran).

4. **Siklus Persetujuan Izin (Approval Workflow)**:
   - Pengajuan izin (sakit/keperluan) diproses lewat transisi state yang diawasi `IzinApprovalService`. Aksi persetujuan hanya boleh dilakukan oleh akun dengan role `picket-officer` (Guru Piket) atau `counselor` (Guru BK).

5. **UI Real-Time Scanner**:
   - Halaman scanner dirancang interaktif menggunakan library kamera (HTML5 QR Code) yang terintegrasi antarmuka Tailwind CSS gelap premium dan responsif.

---

## 📁 STRUKTUR FILE YANG AKAN DIBUAT/DIUBAH

```
app/
├── Modules/Presence/
│   ├── Database/Migrations/
│   │   ├── 2026_06_21_000100_create_presensi_table.php
│   │   ├── 2026_06_21_000101_create_absensi_table.php
│   │   └── 2026_06_21_000102_create_izin_table.php
│   │
│   ├── Models/
│   │   ├── Presensi.php
│   │   ├── Absensi.php
│   │   └── Izin.php
│   │
│   ├── Controllers/
│   │   ├── PresensiController.php
│   │   ├── AbsensiController.php
│   │   ├── IzinController.php
│   │   └── LaporanPresensiController.php
│   │
│   ├── Policies/
│   │   ├── PresensiPolicy.php
│   │   └── IzinPolicy.php
│   │
│   ├── Requests/
│   │   ├── StorePresensiRequest.php
│   │   └── StoreIzinRequest.php
│   │
│   ├── Services/
│   │   ├── PresensiRuleEngine.php
│   │   ├── QrScannerService.php
│   │   └── IzinApprovalService.php
│   │
│   ├── Observers/
│   │   └── PresensiObserver.php
│   │
│   └── routes.php
│
tests/Feature/Presence/
├── QrScanTest.php
└── IzinApprovalTest.php

resources/views/presence/
├── scan.blade.php          (Scanner real-time)
├── rekap.blade.php         (Rekapitulasi kehadiran)
├── izin/
│   ├── index.blade.php     (Daftar izin diajukan)
│   ├── create.blade.php    (Form pengajuan izin)
│   └── show.blade.php      (Detail & tombol approval)
```

---

## 📝 TAHAPAN IMPLEMENTASI

### Task 1: Migrasi Database Kehadiran (3 Tabel)
1. Membuat migrasi `presensi`, `absensi`, dan `izin` dengan constraint index pencarian tanggal dan foreign keys ke model target.
2. Menjalankan perintah migrasi.

### Task 2: Pembuatan Model & Observer
1. Mengintegrasikan trait tenancy dan audit.
2. Membuat model observer `PresensiObserver` untuk mencatatkan log audit kehadiran sukses.

### Task 3: QrScannerService & PresensiRuleEngine
1. Membuat unit test `tests/Feature/Presence/QrScanTest.php`.
2. Menulis core logic rule engine yang membandingkan timestamp scan terhadap settings tenant.
3. Menulis service scanner untuk pemrosesan teks enkripsi QR Code.

### Task 4: IzinApprovalService & Kontroler CRUD
1. Membuat unit test `tests/Feature/Presence/IzinApprovalTest.php`.
2. Menulis service persetujuan izin untuk penandaan status dan pencatatan staf penyetuju.
3. Membuat controllers dan mengamankan rute via `Gate::authorize()`.
4. Rancang tampilan halaman scan, form izin, dan daftar approval dengan glassmorphism UI.

---

## 📈 RENCANA VERIFIKASI

### Pengujian Otomatis
```powershell
php83 artisan test tests/Feature/Presence/QrScanTest.php
php83 artisan test tests/Feature/Presence/IzinApprovalTest.php
```

### Verifikasi Manual
1. Login sebagai `picket-officer`, buka halaman `/presence/scan`, lakukan simulasi pemindaian QR Code siswa. Pastikan jam masuk tercatat terlambat atau tepat waktu sesuai toleransi sistem.
2. Ajukan izin sakit untuk siswa di panel `/presence/izin/create`, upload file gambar surat dokter.
3. Login sebagai guru BK (`counselor`), setujui izin tersebut pada panel approval, lalu periksa apakah status berubah menjadi `disetujui` dan tercatat staf penyetujunya.
