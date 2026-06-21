# DEV_DOCS-035: Rencana Implementasi — Epic 7: Finance Module (Keuangan & Tabungan Siswa)

- **Tanggal:** 2026-06-21 19:59
- **Status:** ⏳ PENDING IMPLEMENTASI
- **Penulis:** Antigravity (Google DeepMind)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith

---

## 🛡️ KONTEKS & KEPUTUSAN ARSITEKTUR

### Skema & Relasi Database (5 Tabel Baru)
1. `item_pembayaran`: Master jenis pembayaran (seperti SPP bulanan, uang kegiatan, infaq) per tahun ajaran/tingkat kelas.
2. `tagihan_siswa`: Piutang tagihan per siswa per bulan/periode yang di-generate dari master item pembayaran.
3. `pembayaran`: Header bukti kwitansi transaksi pembayaran dari siswa.
4. `pembayaran_rincian`: Pembagian nominal pembayaran yang diterima ke masing-masing pos tagihan siswa.
5. `tabungan_siswa`: Rekening dan saldo tabungan yang dimiliki siswa untuk transaksi simpanan/penarikan.

### Keputusan Desain Utama (Kritis)
1. **Safety Transaksi (`PembayaranService::bayar()`)**:
   * Seluruh proses pembuatan kwitansi, rincian pembayaran, pembaruan sisa tagihan, dan mutasi saldo **wajib** didekap di dalam `DB::transaction`.
   * Baris tagihan (`tagihan_siswa`) yang sedang dibayar akan dikunci menggunakan **Pessimistic Locking (`lockForUpdate()`)** agar transaksi paralel (misal: wali murid dan kasir membayar tagihan yang sama pada detik yang sama) tidak menyebabkan kredit ganda atau nilai minus.
2. **Generasi Nomor Nota Secara Atomik**:
   * Format nota bukti pembayaran menggunakan: `INV-YYYYMMDD-XXXX` (di mana `XXXX` adalah sequence harian unik per tenant).
3. **Pembangkit Tagihan Rutin (`TagihanGeneratorService`)**:
   * Service yang dapat dipanggil secara terjadwal (Cron/Laravel Scheduler) setiap tanggal 1 awal bulan untuk meng-generate tagihan SPP bulanan bagi seluruh siswa aktif secara otomatis.
   * Dirancang secara idempotent (menggunakan indeks unik kombinasi `tenant_id`, `siswa_id`, `item_pembayaran_id`, `tahun_ajaran_id`, `bulan`) agar tidak pernah menghasilkan tagihan ganda bila dieksekusi berulang kali.
4. **Mutasi Tabungan Terproteksi (`TabunganMutasiService`)**:
   * Menangani operasi setor (deposit) dan tarik (withdrawal) dengan validasi kecukupan saldo yang ketat sebelum penarikan dieksekusi.

---

## 📁 STRUKTUR FILE YANG AKAN DIBUAT/DIUBAH

```
app/Modules/Finance/
├── Database/Migrations/
│   ├── 2026_06_20_000300_create_item_pembayaran_table.php
│   ├── 2026_06_20_000301_create_tagihan_siswa_table.php
│   ├── 2026_06_20_000302_create_pembayaran_table.php
│   ├── 2026_06_20_000303_create_pembayaran_rincian_table.php
│   └── 2026_06_20_000304_create_tabungan_siswa_table.php
│
├── Models/
│   ├── ItemPembayaran.php
│   ├── TagihanSiswa.php
│   ├── Pembayaran.php
│   ├── PembayaranRincian.php
│   └── TabunganSiswa.php
│
├── Services/
│   ├── PembayaranService.php       (proses bayar aman + locking)
│   ├── KwitansiGenerator.php       (sequence generator no_nota atomik)
│   ├── TagihanGeneratorService.php (auto-generate tagihan bulanan)
│   └── TabunganMutasiService.php   (mutasi saldo tabungan)
│
├── Controllers/
│   ├── ItemPembayaranController.php (kelola master pembayaran)
│   ├── TagihanSiswaController.php   (daftar tagihan siswa)
│   ├── PembayaranController.php     (kasir pembayaran & cetak kwitansi)
│   ├── TabunganSiswaController.php   (mutasi tabungan & cetak buku tabungan)
│   └── LaporanKeuanganController.php (rekap pemasukan harian/bulanan)
│
├── Policies/
│   ├── ItemPembayaranPolicy.php
│   ├── PembayaranPolicy.php
│   └── TabunganPolicy.php
│
├── Requests/
│   ├── StoreItemPembayaranRequest.php
│   ├── GenerateTagihanRequest.php
│   └── BayarTagihanRequest.php
│
├── Events/
│   └── PaymentReceived.php          (event pasca-pembayaran sukses)
│
└── routes.php

app/Console/Commands/
└── GenerateTagihanCommand.php       (artisan command terjadwal)

resources/views/finance/
├── item-pembayaran/
│   ├── index.blade.php
│   └── form.blade.php
├── tagihan/
│   ├── index.blade.php
│   └── generate.blade.php
├── pembayaran/
│   ├── index.blade.php              (antarmuka kasir penerimaan pembayaran)
│   ├── kwitansi.blade.php           (tampilan cetak nota/kwitansi PDF)
│   └── riwayat.blade.php
└── tabungan/
    ├── index.blade.php              (setor & tarik tabungan)
    └── riwayat.blade.php

tests/Feature/Finance/
├── PembayaranServiceTest.php
├── TagihanGeneratorTest.php
└── TabunganMutasiTest.php
```

---

## 📝 TAHAPAN IMPLEMENTASI

### Task 1: Migrasi Database & Pembuatan Model (5 Tabel)
* Membuat dan menjalankan 5 migrasi dengan kolom `tenant_id`, `created_by`, `updated_by`, dan soft deletes.
* Menulis 5 model Eloquent dengan menginjeksi trait `BelongsToTenant` dan `TracksAuditColumns`.
* Menjalankan perintah migrasi (`php83 artisan migrate`).

### Task 2: Implementasi Services & Core Business Logics (TDD)
* Menulis berkas unit test `PembayaranServiceTest.php` untuk memvalidasi:
  * Pembayaran parsial & lunas.
  * Proteksi race condition (pembayaran ganda konkuren).
  * Rollback database jika terjadi error di tengah jalan.
* Mengimplementasikan `KwitansiGenerator.php` dan `PembayaranService.php`.
* Menulis berkas unit test `TagihanGeneratorTest.php` untuk memvalidasi keandalan dan sifat idempotent dari generator tagihan.
* Mengimplementasikan `TagihanGeneratorService.php` dan `GenerateTagihanCommand.php` (didaftarkan ke penjadwalan bulanan).
* Menulis unit test `TabunganMutasiTest.php` dan mengimplementasikan `TabunganMutasiService.php` (termasuk validasi saldo minus).

### Task 3: Pembuatan Controllers, Policies, & Requests
* Membuat validator request untuk memvalidasi input pembayaran (nominal tidak boleh negatif, ID tagihan valid).
* Membuat Controller CRUD untuk item pembayaran, pencarian tagihan siswa, transaksi kasir, dan penarikan/setoran tabungan.
* Membuat kebijakan otorisasi (Policies) agar hanya pengguna dengan peran `finance` atau `admin` yang dapat mengutak-atik transaksi keuangan.

### Task 4: Antarmuka Views (Tailwind CSS + Alpine.js) & PDF Kwitansi
* Merancang halaman Kasir Pembayaran yang memungkinkan pencarian cepat siswa, memilih beberapa tagihan sekaligus, memasukkan nominal bayar, dan menghitung total kembalian secara instan.
* Menyusun template kwitansi resmi yang bersih menggunakan DomPDF untuk dicetak/diunduh.
* Merancang halaman transaksi Tabungan Siswa (form setor/tarik instan).

---

## 📈 RENCANA VERIFIKASI

### Pengujian Otomatis (Automated Tests)
```powershell
# Jalankan test spesifik finance
php83 artisan test tests/Feature/Finance/PembayaranServiceTest.php
php83 artisan test tests/Feature/Finance/TagihanGeneratorTest.php
php83 artisan test tests/Feature/Finance/TabunganMutasiTest.php

# Jalankan keseluruhan test suite aplikasi
php83 artisan test
```

### Verifikasi Manual (Manual Testing)
1. Login sebagai **Admin Sekolah** (`admin.sekolah`) -> masuk ke menu **Master Pembayaran** -> buat item pembayaran SPP baru senilai Rp 250.000.
2. Jalankan perintah generator tagihan secara manual melalui CLI:
   ```powershell
   php83 artisan tagihan:generate --tenant_id=1
   ```
3. Login sebagai **Bendahara** -> masuk ke menu **Kasir Pembayaran** -> cari siswa demo (misal: *Andi Pratama*) -> centang tagihan SPP -> masukkan nominal bayar -> klik Bayar -> pastikan status tagihan berubah menjadi Lunas dan file Kwitansi PDF dapat diunduh/dicetak dengan format rapi.
4. Buka menu **Tabungan Siswa** -> lakukan setoran Rp 100.000 -> lakukan penarikan Rp 50.000 -> pastikan sisa saldo tercatat Rp 50.000 dengan riwayat mutasi yang akurat.
