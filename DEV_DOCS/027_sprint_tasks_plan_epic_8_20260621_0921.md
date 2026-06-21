# DEV_DOCS-027: Detail Tugas Sprint 3 — Epic 8: Presence Module (Presensi & Kehadiran)

- **Tanggal:** 2026-06-21 09:21
- **Status:** ⏳ PENDING (Menunggu Persetujuan/Instruksi Mulai)
- **Penulis:** Antigravity (Google DeepMind)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith

---

## 📅 CHECKLIST TUGAS DETAIL SPRINT 3 (Fase 2)

### 🏃‍♂️ Epic 8: Presence Module (Presensi & Kehadiran)

- **Task 1: Migrations (3 Presence Tables)**
  - [ ] Buat direktori migrasi di `app/Modules/Presence/Database/Migrations`
  - [ ] Buat migrasi `presensi` table (kehadiran datang/pulang untuk siswa & pegawai)
  - [ ] Buat migrasi `absensi` table (ketidakhadiran sakit/izin/alpha)
  - [ ] Buat migrasi `izin` table (surat izin masuk/pulang siswa)
  - [ ] Jalankan migrasi database (`php83 artisan migrate`)

- **Task 2: Models & Events (3 Models with Tenant Isolation)**
  - [ ] Buat model `Presensi` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `Absensi` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `Izin` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat event `PresenceRecorded` di `app/Modules/Presence/Events/PresenceRecorded.php`
  - [ ] Buat observer `PresensiObserver` di `app/Modules/Presence/Observers/PresensiObserver.php`
  - [ ] Daftarkan observer di `EventServiceProvider`

- **Task 3: PresensiRuleEngine & QrScannerService**
  - [ ] Buat file unit test `tests/Feature/Presence/QrScanTest.php`
  - [ ] Buat engine `app/Modules/Presence/Services/PresensiRuleEngine.php` (hitung telat/jenis kehadiran via tenant_settings)
  - [ ] Buat scanner `app/Modules/Presence/Services/QrScannerService.php` (decode QR, validasi entitas, cegah duplikasi)
  - [ ] Jalankan test `QrScanTest.php` dan pastikan lulus

- **Task 4: IzinApprovalService & Views**
  - [ ] Buat file unit test `tests/Feature/Presence/IzinApprovalTest.php`
  - [ ] Buat service `app/Modules/Presence/Services/IzinApprovalService.php` (workflow pending → approved/rejected)
  - [ ] Buat controllers (`PresensiController`, `AbsensiController`, `IzinController`, `LaporanPresensiController`)
  - [ ] Buat rute-rute di `app/Modules/Presence/routes.php`
  - [ ] Buat view dashboard scan form, rekap presensi, izin list, dan template PDF surat izin dengan generator QR Code
  - [ ] Jalankan test `IzinApprovalTest.php` dan pastikan lulus

- **Task 5: Final Verification & Test Suite Execution**
  - [ ] Jalankan seluruh test suite aplikasi (termasuk akademik & rbac) dan pastikan 100% lulus (hijau)
