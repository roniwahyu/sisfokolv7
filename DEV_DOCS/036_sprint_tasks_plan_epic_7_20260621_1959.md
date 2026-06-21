# DEV_DOCS-036: Detail Tugas Sprint — Epic 7: Finance Module (Keuangan & Tabungan Siswa)

- **Tanggal:** 2026-06-21 19:59
- **Status:** ⏳ PENDING (Menunggu Persetujuan/Instruksi Mulai)
- **Penulis:** Antigravity (Google DeepMind)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith

---

## 📅 CHECKLIST TUGAS DETAIL SPRINT — EPIC 7: FINANCE MODULE

### 🏃‍♂️ Epic 7: Finance Module (Keuangan & Tabungan Siswa)

- **Task 1: Migrations & Models (5 Finance Tables & Models)**
  - [ ] Buat direktori migrasi di `app/Modules/Finance/Database/Migrations`
  - [ ] Buat migrasi `item_pembayaran` table (master pos tagihan sekolah)
  - [ ] Buat migrasi `tagihan_siswa` table (piutang tagihan individual siswa)
  - [ ] Buat migrasi `pembayaran` table (header kwitansi/nota transaksi)
  - [ ] Buat migrasi `pembayaran_rincian` table (rincian item per kwitansi)
  - [ ] Buat migrasi `tabungan_siswa` table (rekening simpanan mandiri siswa)
  - [ ] Jalankan migrasi database (`php83 artisan migrate`)
  - [ ] Buat model `ItemPembayaran` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `TagihanSiswa` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `Pembayaran` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `PembayaranRincian` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat model `TabunganSiswa` dengan `BelongsToTenant` & `TracksAuditColumns`
  - [ ] Buat event `PaymentReceived` di `app/Modules/Finance/Events/PaymentReceived.php`

- **Task 2: PembayaranService & KwitansiGenerator (CRITICAL — Locking & Transactions)**
  - [ ] Buat file unit test `tests/Feature/Finance/PembayaranServiceTest.php`
  - [ ] Buat generator sequence nota atomik `app/Modules/Finance/Services/KwitansiGenerator.php`
  - [ ] Buat service utama `app/Modules/Finance/Services/PembayaranService.php`
    * Dekap alur pembayaran di dalam `DB::transaction`
    * Terapkan row-level pessimistic locking (`lockForUpdate()`) pada baris `tagihan_siswa` yang diakses
    * Hitung sisa tagihan, catat rincian pembayaran, update status lunas, pemicu event `PaymentReceived`
  - [ ] Jalankan test `PembayaranServiceTest.php` dan pastikan 100% lulus (green)

- **Task 3: TagihanGeneratorService & Scheduled Command (Idempotent Billing)**
  - [ ] Buat file unit test `tests/Feature/Finance/TagihanGeneratorTest.php`
  - [ ] Buat service generator tagihan `app/Modules/Finance/Services/TagihanGeneratorService.php` (idempotent, cegah duplikasi data)
  - [ ] Buat console command `app/Console/Commands/GenerateTagihanCommand.php` untuk memicu generate via CLI/Scheduler
  - [ ] Daftarkan penjadwalan command di `routes/console.php` agar berjalan otomatis setiap awal bulan
  - [ ] Jalankan test `TagihanGeneratorTest.php` dan pastikan 100% lulus (green)

- **Task 4: TabunganMutasiService (Savings Operations)**
  - [ ] Buat file unit test `tests/Feature/Finance/TabunganMutasiTest.php`
  - [ ] Buat service transaksi tabungan `app/Modules/Finance/Services/TabunganMutasiService.php`
    * Tangani fungsi setor (deposit)
    * Tangani fungsi tarik (withdrawal) dengan validasi batas saldo penarikan yang ketat
  - [ ] Jalankan test `TabunganMutasiTest.php` dan pastikan 100% lulus (green)

- **Task 5: Controllers, Routes, & Views (Clean Tailwind UI)**
  - [ ] Buat berkas request validator (`StoreItemPembayaranRequest`, `GenerateTagihanRequest`, `BayarTagihanRequest`)
  - [ ] Buat controller CRUD (`ItemPembayaranController`, `TagihanSiswaController`, `PembayaranController`, `TabunganSiswaController`, `LaporanKeuanganController`)
  - [ ] Daftarkan rute-rute modul keuangan di `app/Modules/Finance/routes.php`
  - [ ] Rancang view kasir pembayaran, cetak bukti kwitansi PDF, dan riwayat mutasi tabungan
  - [ ] Buat kebijakan otorisasi (Policies) untuk mengamankan data finansial per tenant

- **Task 6: Final Verification**
  - [ ] Jalankan seeder demo untuk memverifikasi entri pembayaran di browser
  - [ ] Eksekusi seluruh test suite aplikasi (`php83 artisan test`) untuk memastikan status 100% hijau
