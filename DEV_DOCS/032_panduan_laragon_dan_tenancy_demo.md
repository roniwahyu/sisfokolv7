# DEV_DOCS-032: Panduan Laragon, Arsitektur Multi-Tenancy, & Skenario Demo

- **Tanggal:** 2026-06-21 11:00
- **Status:** Panduan Pengguna & Pengembang
- **Aplikasi:** sisfokol-laravel (Laravel 11 Modular Monolith)

---

## 1. ⚡ Menjalankan Aplikasi di Laragon

Laragon mempermudah pengembangan lokal dengan fitur Auto Virtual Hosts. Berikut langkah-langkah untuk menjalankan aplikasi:

### 1.1 Virtual Host Otomatis Laragon
Laragon secara default memetakan setiap folder di dalam `D:\laragon\www\` menjadi domain `.test`. 
Karena kode aplikasi Laravel berada di subfolder `sisfokol-laravel`, Laragon akan mendeteksi keberadaan berkas `sisfokol-laravel/public/index.php` dan secara otomatis membuat Virtual Host berikut:
* **URL Utama:** `http://sisfokol-laravel.test`
* **Direktori Root:** `D:\laragon\www\sisfokolv7\sisfokol-laravel\public`

> [!NOTE]
> Jika domain `.test` tidak dapat diakses atau terjadi kendala DNS lokal, Anda dapat menggunakan server bawaan Laravel sebagai alternatif:
> ```powershell
> # Jalankan dari folder sisfokol-laravel
> php83 artisan serve
> ```
> Akses melalui `http://127.0.0.1:8000`.

### 1.2 Konfigurasi Environment & Database
Pastikan file `d:\laragon\www\sisfokolv7\sisfokol-laravel\.env` Anda telah disesuaikan dengan kredensial database Laragon Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisfokol_laravel
DB_USERNAME=root
DB_PASSWORD=
```

---

## 2. 🏛️ Implementasi Multi-Tenancy (ADR-003)

Sistem multi-tenancy pada **SISFOKOL v7** menggunakan strategi **Single Database (Shared Database)** dengan pemisahan logis menggunakan kolom `tenant_id` pada setiap tabel domain sekolah.

### 2.1 Resolusi Tenant berbasis Auth (Auth-Based Resolution)
Meskipun tabel `tenants` memiliki kolom `domain` (yang disiapkan untuk pemetaan domain unik sekolah/white-labeling di masa mendatang), resolusi tenant aktif pada Fase 1 diselesaikan secara dinamis melalui sesi pengguna yang login.

Alur resolusinya adalah sebagai berikut:
1. Pengguna mengakses halaman login terpusat (`http://sisfokol-laravel.test/login`).
2. Setelah berhasil login, middleware `ResolveTenant` (`app/Http/Middleware/ResolveTenant.php`) akan memproses request:
   ```php
   if (auth()->check()) {
       $user = auth()->user();
       if ($user->tenant_id !== null) {
           $this->context->set(
               tenantId: $user->tenant_id,
               branchId: $user->branch_id,
               settings: $settings,
           );
       }
   }
   ```
3. `TenantContext` (Singleton di `app/Support/TenantContext.php`) akan menyimpan ID tenant yang aktif untuk siklus request tersebut.

### 2.2 Scoping Query Otomatis (`BelongsToTenant` Trait)
Setiap model database sekolah (seperti `Siswa`, `Kelas`, `Mapel`, `Jadwal`, dan tabel nilai evaluasi) menggunakan trait `App\Models\Traits\BelongsToTenant`:
* **Global Scope:** Secara otomatis menambahkan klausul `WHERE table.tenant_id = current_tenant_id` pada setiap query Eloquent.
* **Auto-Fill:** Secara otomatis mengisi kolom `tenant_id` dengan ID tenant yang sedang aktif ketika membuat data baru (`creating` event).

### 2.3 Bypass SuperAdmin
Jika pengguna yang login adalah `SuperAdmin` (di mana kolom `tenant_id` bernilai `NULL`), context tenant tetap kosong (`uninitialized`). Global scope `tenant` tidak akan diterapkan, sehingga SuperAdmin memiliki akses global penuh untuk melihat dan mengelola seluruh data tenant di dalam database tunggal tersebut.

---

## 3. 🎬 Skenario Demo Fitur (Step-by-Step)

Gunakan daftar akun hasil seeding berikut untuk mendemonstrasikan alur kerja aplikasi:

| Peran (Role) | Username | Password | Penjelasan Demo |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | `superadmin` | `SuperAdmin#2026` | Kelola Tenant (Sekolah), kelola cabang (Branch), audit platform global. |
| **Admin Sekolah** | `admin.sekolah`| `demo1234` | Kelola data sekolah, atur konfigurasi bobot nilai, kelola pengguna sekolah. |
| **Guru Mapel** | `guru.demo` | `demo1234` | Input nilai formatif & sumatif kelas binaan. |
| **Wali Kelas** | `walikelas.demo`| `demo1234` | Rekap kehadiran kelas, cetak rapor PDF siswa. |
| **Siswa** | `siswa.2024001`| `demo1234` | Lihat riwayat kehadiran pribadi, unduh rapor PDF. |

### 3.1 Skenario 1: Input Nilai Realtime (Guru)
1. Buka `http://sisfokol-laravel.test/login` dan masuk menggunakan akun Guru (`guru.demo` / `demo1234`).
2. Masuk ke modul **Evaluasi (Evaluation)** -> **Input Nilai (Grade Entry)**.
3. Pilih Kelas **Kelas X-A** dan Mata Pelajaran **Matematika**, lalu klik **Cari**.
4. Grid penilaian berbasis **Alpine.js** akan dirender:
   * Masukkan nilai Formatif (bobot 40%) dan Sumatif (bobot 60%) untuk siswa.
   * Nilai Akhir (NA) siswa akan dihitung secara realtime di browser saat Anda mengetik.
   * Sistem akan melakukan **auto-save** via AJAX ketika kursor berpindah kolom.

### 3.2 Skenario 2: Cetak Rapor Resmi (Wali Kelas)
1. Buka halaman login dan masuk sebagai Wali Kelas (`walikelas.demo` / `demo1234`).
2. Arahkan ke modul **Evaluasi (Evaluation)** -> **Cetak Rapor (Rapor Generator)**.
3. Pilih **Kelas X-A** untuk melihat daftar siswa di kelas Anda.
4. Klik tombol **Detail/Lihat Rapor** pada siswa tertentu (contoh: *Andi Pratama*).
5. Anda akan disuguhkan halaman review nilai rapor sebelum dicetak.
6. Klik **Download Rapor (PDF)** untuk mengunduh berkas rapor resmi berformat PDF yang bersih dan rapi (dihasilkan oleh DomPDF dengan kalkulasi bobot presisi).
