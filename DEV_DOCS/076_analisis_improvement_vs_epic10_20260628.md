# DEV_DOCS-076: Analisis — Improvement vs Lanjut Epic 10

- **Tanggal:** 2026-06-28
- **Penulis:** ZCode Agent
- **Proyek:** SISFOKOL v7 → Laravel 11 (`sisfokol-laravel/`)
- **Jenis:** Dev Report — Analisis Keputusan
- **Pemicu:** Setelah eksekusi Role Waka (073-075), evaluasi langkah berikutnya: perbaikan improvement vs lanjut Epic 10
- **Status:** ✅ Disimpan. **Keputusan user: kerjakan Opsi A (Improvement).**

---

## 1. Konteks

Setelah Role Waka selesai & ter-merge (PR #16), ada dua jalur pekerjaan:
- **Opsi A — Improvement**: tutup gap teknis pre-existing yang ditemukan selama sesi Waka
- **Opsi B — Lanjut Epic 10**: scaffold 8 plugin (`AbsensiGuru, Rapor, Spp, Ppdb, Ekstrakurikuler, Bk, Perpustakaan, Inventaris`)

**Keputusan user (2026-06-28):** Kerjakan **Opsi A** dulu.

---

## 2. Opsi A — Improvement (3 Gap Pre-Existing, Berbasis Bukti)

Semua gap ditemukan **secara nyata** selama eksekusi Waka, bukan spekulasi.

### I1 — `AuthServiceProvider` tidak di-load (KRITIS)

- **Bukti:** `bootstrap/providers.php` hanya mendaftar `AppServiceProvider`, `ModuleServiceProvider`, `PluginRegistryServiceProvider`, `ImpersonateServiceProvider`. `AuthServiceProvider` **tidak ada** di list.
- **Dampak:** seluruh `$policies` map (termasuk `TabunganSiswa=>TabunganPolicy`) + `Gate::before` di `AuthServiceProvider` = **dead code**. Banyak policy modul tidak ter-enforce → **celah otorisasi nyata**.
- **Ditemukan saat:** Task 3 Waka — test `Gate::allows('viewAny', TabunganSiswa::class)` gagal padahal permission ada.
- **Effort:** Kecil (1 baris di `bootstrap/providers.php` + verifikasi).
- **ROI:** Besar — mengaktifkan kembali semua policy modul.

### I2 — Dual-migration `student_savings` vs `tabungan_siswa`

- **Bukti:** `database/migrations/0001_01_01_700008_create_student_savings_table.php` (table `student_savings`) vs `app/Modules/Finance/Database/Migrations/.../create_tabungan_siswa_table.php` (table `tabungan_siswa`, yang dipakai model `TabunganSiswa`).
- **Dampak:** `DemoSeeder` crash `Table 'student_savings' doesn't exist` → test DB corrupt → test suite fail sampai di-reset manual.
- **Ditemukan saat:** Task 2 Waka — test run DemoSeeder gagal.
- **Effort:** Kecil (hapus salah satu migration duplikat).
- **ROI:** Sedang — fix test isolation + DemoSeeder.

### I3 — `phpunit.xml` pakai MySQL test DB (bukan SQLite `:memory:`)

- **Bukti:** report 069/072 — `phpunit.xml` punya `<env name="DB_DATABASE" value="sisfokol_laravel_test"/>` (MySQL). Pemindaian migration tidak menemukan fitur MySQL-specific.
- **Dampak:** test lambat (~138 detik untuk 156 test) + rentan korupsi state test DB.
- **Effort:** Kecil (uncomment SQLite `:memory:` di phpunit.xml + verifikasi).
- **ROI:** Rendah-Sedang — kecepatan & isolasi test.

---

## 3. Opsi B — Lanjut Epic 10 (8 Plugin Scaffold)

- **Prasyarat terpenuhi:** plugin infra (Epic 4) ✅, pattern referensi (`Kurikulum` plugin) ada.
- **Realita:** baru 1 dari 8 plugin ada (`Kurikulum`). 7 lain belum disentuh.
- **Sinergi dengan Waka:** 2 plugin Epic 10 (`Ppdb`, `Bk`) langsung mengisi bidang Waka yang "ditunda" di spec 073.
- **Catatan honest:** "8 plugin scaffold" = pekerjaan besar. Sebaiknya di-decompose — mulai dari 1-2 plugin pilot (mis. `Ppdb`/`Bk` yang sinergi Waka), bukan 8 sekaligus.
- **Risiko jika dilakukan sebelum I1:** plugin baru mungkin juga punya policy dead-code → akumulasi bug otorisasi.

---

## 4. Rekomendasi & Urutan

**Urutan rasional: I1 → I2 → I3.**

1. **I1 dulu** (cepat & kritis) — fix pondasi otorisasi sebelum menambah plugin baru.
2. **I2 + I3** (bundle "cleanup test infra", 1 sesi).
3. Setelah improvement selesai, **Epic 10 lebih aman** dikerjakan (policy plugin baru pasti ter-enforce).

---

## 5. Keputusan

**User memilih Opsi A (Improvement).** Eksekusi mengikuti urutan I1 → I2 → I3.

> Catatan: I1 melibatkan perubahan `bootstrap/providers.php` yang berdampak ke seluruh policy registration → akan melalui brainstorming singkat sebelum implementasi (sesuai prinsip: pekerjaan kreatif/berdampak luas butuh desain dulu).

---

## 6. Referensi

- Spec/Plan/Eksekusi Waka: `DEV_DOCS/073`, `074`, `075`
- Bukti I1: `bootstrap/providers.php`, `app/Providers/AuthServiceProvider.php`
- Bukti I2: 2 migration file (path di §2)
- Bukti I3: `phpunit.xml`, report `069`, `072`

---

*Analisis berbasis bukti. Keputusan improvement akan dieksekusi sesuai urutan I1→I2→I3 dengan TDD.*
