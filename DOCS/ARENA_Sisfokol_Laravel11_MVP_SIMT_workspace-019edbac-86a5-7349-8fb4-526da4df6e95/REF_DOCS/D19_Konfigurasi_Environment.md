# D18. Struktur Kode & Coding Standard

---

## Struktur Folder Proyek

### Opsi A: Struktur Laravel (Rekomendasi untuk Pengembangan Ulang)

```
smp-it-sis/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Akademik/
│   │   │   ├── Keuangan/
│   │   │   └── Api/
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── AuditLogMiddleware.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── Siswa.php
│   │   ├── Guru.php
│   │   ├── Kelas.php
│   │   ├── Nilai.php
│   │   ├── Absensi.php
│   │   └── Pembayaran.php
│   ├── Services/
│   │   ├── RaporService.php
│   │   ├── HitungNilaiService.php
│   │   └── NotifikasiService.php
│   └── Helpers/
│       └── FormatHelper.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── admin/
│   │   ├── guru/
│   │   ├── siswa/
│   │   ├── ortu/
│   │   └── layouts/
│   ├── js/
│   └── sass/
├── routes/
│   ├── web.php
│   └── api.php
├── public/
│   └── assets/
├── storage/
│   ├── app/reports/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
├── docs/
├── .env.example
├── composer.json
├── phpunit.xml
└── README.md
```

### Opsi B: Struktur Repo SISFOKOL v7.00 (PHP Native)

```
sisfokol-v7.00-code-smartoffice/
├── adm/                 # Modul admin sekolah
├── admbdh/              # Modul bendahara
├── admbk/               # Modul bimbingan konseling
├── admgr/               # Modul guru
├── adminv/              # Modul perpustakaan
├── admks/               # Modul kesiswaan
├── admpiket/            # Modul piket/presensi
├── admsw/               # Modul siswa
├── admwk/               # Modul wali kelas
├── db/                  # File database/schema
├── filebox/             # Penyimpanan file upload
├── img/                 # Aset gambar
├── inc/                 # File include/fungsi umum
├── expire.php           # Utility sesi/expire
└── README.md
```

Struktur di atas mengelompokkan fitur berdasarkan role pengguna. Jika mengadaptasi SISFOKOL, pertahankan konvensi folder tersebut dan tambahkan dokumentasi untuk setiap modul.

## Coding Standard

1. **Bahasa & Standar**
   - Kode PHP mengikuti PSR-12.
   - Nama class PascalCase, method camelCase, konstanta UPPER_SNAKE_CASE.
   - File blade menggunakan snake_case dengan prefix folder role.

2. **Keamanan**
   - Gunakan `Request` validation untuk semua input.
   - Hindari SQL raw; gunakan Eloquent/Query Builder.
   - Escape output blade dengan `{{ }}`.
   - Password hashing menggunakan `Hash::make()` (bcrypt).

3. **Database**
   - Gunakan migration untuk setiap perubahan skema.
   - Foreign key dengan `onDelete`/`onUpdate` yang jelas.
   - Index pada kolom yang sering di-query (nis, kelas_id, tanggal).

4. **Logging & Audit**
   - Setiap aksi create/update/delete wajib masuk audit log via service.
   - Gunakan `Log::info()` untuk event penting.

5. **Testing**
   - Unit test untuk service perhitungan nilai dan keuangan.
   - Feature test untuk endpoint kritis (login, input nilai, cetak rapor).

6. **Dokumentasi Kode**
   - Docblock untuk setiap class dan method public.
   - README untuk setup environment dan deployment.
