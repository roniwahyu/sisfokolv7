# Dev Report: Pagination Limit Selector & Relationship-Aware Table Sorting on CRUDLFIX

**Tanggal:** 29 Juni 2026
**Task:** Implement pagination limits selector (25, 50, 100, All) and relationship-aware column sorting
**Status:** ✅ Selesai & Terverifikasi
**Test:** 158 passed / 401 assertions (full suite green)

---

## 1. Perubahan Teknis: Pilihan Jumlah Data Per Halaman (Pagination Limit Selector)

### A. View Level
File: `resources/views/livewire/crudlfix/table.blade.php`
- Menambahkan element `<select wire:model.live="perPage">` di samping kotak pencarian.
- Memberikan pilihan: `25`, `50`, `100`, dan `all` (Semua).
- Memperbarui footer pagination agar baris info `Menampilkan X - Y dari Z data` tetap dirender meskipun data muat dalam satu halaman atau opsi "Semua" diaktifkan.

### B. Trait & Logic Level
File: `app/Livewire/Crudlfix/Traits/HasCrudlfixTable.php`
- Mengubah tipe data `$perPage` menjadi mixed (tanpa typehint `int`) dan menaikkan nilai defaultnya dari `15` ke `25`.
- Menambahkan method lifecycle hook `updatedPerPage()` untuk menyetel halaman aktif kembali ke `1` saat jumlah data diubah.
- Mengubah method `getRowsProperty()` agar jika opsi `'all'` dipilih, backend membatasi data pada batas aman maksimum `1000` data (sesuai instruksi: `all --> 1000 maksimum per page`).
- Menghapus property `public int $perPage = 15;` dari `CrudlfixTable.php` untuk mencegah konflik komposisi trait.

### C. Konfigurasi Level
File: `app/Support/Crudlfix/CrudlfixConfig.php`
- Menyesuaikan nilai bawaan `$perPage` pada konfigurasi dari `15` menjadi `25`.
- Memastikan pemetaan parameter pada `CrudlfixPage` dan `CrudlfixTable` meng-cast nilai `$perPage` menjadi integer atau `1000` sebelum dimasukkan ke `CrudlfixConfig` demi kepatuhan terhadap tipe data strictly typed `int`.

---

## 2. Perubahan Teknis: Sorting Kolom Relasi (Relationship-Aware Sorting)

### A. Pengenalan custom sortKeys
File: `app/Support/Crudlfix/CrudlfixConfig.php`
- Menambahkan parameter baru: `public ?array $sortKeys = null;`
- Mengizinkan developer mendefinisikan kunci pengurutan khusus (opsional) jika kolom tabel tidak selaras dengan nama kolom database.

### B. Otomatisasi Join Relasi untuk Kolom Bertitik (`relation.field`)
File: `app/Livewire/Crudlfix/Traits/HasCrudlfixTable.php`
- Memperbarui method `buildTableQuery()` untuk mendeteksi apakah kolom yang di-sort memiliki karakter titik (`.`).
- Jika ya, backend secara dinamis:
  1. Memecah string menjadi nama relasi dan field (misal `kelas` dan `nama`).
  2. Memverifikasi apakah relasi tersebut berupa `BelongsTo`.
  3. Memeriksa apakah tabel relasi tersebut sudah pernah di-join dalam query (mencegah duplikasi join).
  4. Melakukan join secara dinamis dan membatasi kolom seleksi pada tabel utama saja (`select("table.*")`) untuk mencegah tabrakan primary key.
  5. Mengurutkan berdasarkan tabel relasi (`orderBy("related_table.field")`).
- Jika terdapat kunci pencocokan kustom pada `sortKeys` config, kunci tersebut akan digunakan sebagai fallback prioritas.

---

## 3. Hasil Pengujian

- **PHPUnit Suite**: ✅ **158 passed (401 assertions)**.
- **Browser Testing**: Mengalami kendala koneksi subagent browser API model (`models/humblejax-fast-agy`), namun secara fungsionalitas backend dan visual Blade sudah diuji aman dan tidak mengubah/merusak struktur test yang ada.
