# ADR-002: Rebuild Total ke Laravel 11 Modular Monolith (Bukan Modifikasi SISFOKOL v7)

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)
- **Supersedi:** —

## Konteks

Analisis `DOCS/analisis-sisfokol/analisis-sisfokol-v7.md` menemukan kelemahan teknis kritis pada SISFOKOL v7.00:

- **Keamanan:** MD5 tanpa salt, SQL injection (query di-concatenate), tanpa CSRF, tanpa prepared statement
- **Arsitektur:** PHP native prosedural, HTML/PHP/JS/SQL bercampur dalam satu file
- **Database:** engine **MyISAM** (tanpa transaksi/FK), PK berupa hash MD5 `varchar(50)`, numerik disimpan sebagai `varchar`, denormalisasi tinggi
- **Tidak ada** testing, audit log, soft delete, API layer

Dokumen tersebut merekomendasikan: *"SISFOKOL v7.00 lebih baik dijadikan referensi domain & fitur daripada dijadikan fondasi teknis langsung."*

## Keputusan

**Rebuild total** sebagai aplikasi Laravel 11 baru di folder `sisfokol-laravel/`, **bukan** memodifikasi kode SISFOKOL. SISFOKOL hanya menjadi referensi:
- **Business logic & domain knowledge** → diadopsi
- **Struktur tabel/data** → dinormalisasi ulang (3NF, InnoDB, BIGINT PK, FK)
- **Kode** → tidak digunakan sebagai fondasi

## Konsekuensi

- ✅ Bebas dari hutang teknis legacy; bisa pakai modern security (bcrypt, RBAC, audit, ORM)
- ✅ Skema database normal → integritas referensial & performa lebih baik
- ✅ Bisa multi-tenant & plugin system (tidak mungkin di arsitektur lama)
- ❌ Butuh **migrasi data** ETL dari DB lama → DB baru (biaya satu kali)
- ❌ Estimasi effort besar: dokumen menyebut 26–30 minggu untuk scope penuh; karena itu dipecah ke Fase (lihat ADR-004)
