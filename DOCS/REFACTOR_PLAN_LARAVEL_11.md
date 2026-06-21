# RENCANA REFACTORING SISFOKOL v7.00 → LARAVEL 11
**Proyek:** SISFOKOL v7.00 Code:SmartOffice  
**Sumber:** https://gitlab.com/hajirodeon/sisfokol-v7.00-code-smartoffice  
**Tujuan:** Refactor/Migrasi ke Laravel 11 Framework  
**Tanggal Analisis:** 17 Juni 2026  
**Analis:** Profesor NLP/AI/Software Engineering + Senior Web/Software Engineer + Kepala Sekolah/Guru (Konteks Pendidikan Indonesia)

---

## 1. EXECUTIVE SUMMARY

SISFOKOL v7.00 adalah Sistem Informasi Sekolah berbasis **PHP Native Prosedural 8.2** dengan MySQL/MariaDB. Aplikasi ini sangat kaya fitur (Smart Office) dengan 9 peran pengguna dan puluhan modul. Namun, arsitekturnya yang sudah berusia lebih dari 10 tahun dengan pola kode prosedural dan duplikasi tinggi membuatnya sulit untuk:
- **Dipelihara** (maintenance)
- **Dikembangkan lebih lanjut**
- **Diamankan** (security hardening)
- **Diintegrasikan** dengan sistem lain (API/Third-party)
- **Dideploy dengan DevOps modern**

Refactor ke **Laravel 11** adalah langkah strategis untuk:
1. Menerapkan **MVC/Modern PHP Architecture**
2. Meningkatkan **security by default** (CSRF, Bcrypt, Prepared Statement, Validation)
3. Memudahkan **pengembangan fitur baru** (Eloquent, Migration, Queue, Event, Testing)
4. Menyiapkan **API Backend** untuk aplikasi mobile/whatsApp gateway
5. Meningkatkan **kualitas kode dan dokumentasi**

---

## 2. CURRENT STATE ANALYSIS (HASIL SURVEY REPO)

### 2.1. Statistik Repositori
| Item | Nilai | Keterangan |
|------|-------|------------|
| Total Ukuran | ~328 MB | Banyak asset gambar/template |
| File PHP | ~1.675 | Prosedural, tersebar di folder role |
| File JS | ~5.221 | Banyak jQuery legacy |
| File CSS | ~1.077 | AdminLTE 3 + custom |
| File SQL | 3 | `sisfokol_v7.sql` utama 1.4 MB |
| Tabel Database | 75 | Mix master & transaksi |
| Folder Role | 9 | adm, admgr, admsw, admwk, admks, admbdh, admbk, admpiket, adminv |
| File PHP di Folder Role | 415 | CRUD + laporan per role |
| Total Query SQL | ~2.468 | Banyak `SELECT *` dan raw SQL |
| INSERT | 205 | Langsung `mysqli_query` |
| UPDATE | 190 | Tanpa transaction/validation |
| DELETE | 173 | Soft delete tidak ada |
| SELECT * FROM | 1.900 | Inefficient query pattern |

### 2.2. Struktur Role & Modul
| Role | Folder | Jumlah File PHP | Modul Utama |
|------|--------|-----------------|-------------|
| Administrator | `adm/` | 135 | Master, Akademik, Jadwal, Piket, Presensi, Absensi, Pelanggaran, Pembinaan, Prestasi, Ekstra, Keuangan, Tabungan, Inventaris |
| Guru Mapel | `admgr/` | 22 | Kurikulum Merdeka (TP, LM, Asesmen), Jurnal, Nilai, Jadwal |
| Siswa | `admsw/` | 23 | Presensi, Jadwal, Nilai, Keuangan, Data Pribadi |
| Wali Kelas | `admwk/` | 38 | Kurmer Proyek, Nilai, Raport, Absensi, Keuangan, Tabungan |
| Kepala Sekolah | `admks/` | 62 | Monitoring & Laporan Keseluruhan |
| Bendahara | `admbdh/` | 24 | Keuangan Siswa, Tabungan, Laporan |
| Guru BK | `admbk/` | 57 | BK, Absensi, Izin, Pelanggaran, Prestasi |
| Petugas Piket | `admpiket/` | 44 | Piket, Absensi, Izin, Kejadian |
| Sarpras | `adminv/` | 10 | Inventaris KIB A-F, KIR |

### 2.3. Teknologi & Arsitektur Existing
- **Bahasa:** PHP 8.2.4 native prosedural
- **Database:** MySQL/MariaDB (MyISAM engine, latin1 charset)
- **Template:** Custom string-replacement + AdminLTE 3
- **Frontend:** jQuery, Bootstrap, Chart.js, DataTables
- **Auth:** Session + MD5 password (❌ tidak aman)
- **Security:** Custom escaping `cegah()`, `nosql()`, `balikin()` (❌ error-prone)
- **Routing:** File-based, tidak ada routing table
- **Upload:** Filebox direct upload
- **QR Code:** Library QRCode kustom untuk presensi/kartu/izin
- **Export:** PHPExcel (kemungkinan versi lama) / CSV / PDF
- **Email/WhatsApp:** API gateway external (sosmedsekolah.com)

### 2.4. Critical Issues yang Ditemukan
| # | Issue | Dampak | Solusi di Laravel |
|---|-------|--------|-------------------|
| 1 | Password MD5 | Mudah di-crack | Bcrypt via Hash::make() |
| 2 | SQL Injection potential | Custom escaping, raw query | Eloquent/Query Builder + prepared statement |
| 3 | XSS potential | Output langsung tanpa escape | Blade `{{ }}` auto escape |
| 4 | No CSRF protection | Vulnerable to CSRF | `@csrf` middleware |
| 5 | No validation layer | Validasi manual di setiap file | Form Request + Validation |
| 6 | No soft delete | Data terhapus permanen | Eloquent SoftDeletes |
| 7 | MyISAM + latin1 | No FK, no transaction, charset issue | InnoDB + utf8mb4 |
| 8 | Duplikasi kode tinggi | 415 file role, banyak copy-paste | Controller, Service, Repository, Component |
| 9 | No API layer | Sulit integrasi mobile/WA | Laravel API Resources + Sanctum |
| 10 | No testing | Regresi sulit | PHPUnit, Feature Test |
| 11 | No queue | WA/email blocking | Laravel Queue + Jobs |
| 12 | No RBAC formal | Role check di setiap file | Spatie Laravel Permission |
| 13 | `kd` varchar(50) PK manual | Collision risk, tidak auto-increment | UUID/BigInteger auto-increment |
| 14 | No migration | Schema sulit di-versioning | Laravel Migration + Seeder |
| 15 | File upload tidak terstruktur | Security & backup sulit | Storage disk + validation |

---

## 3. TARGET ARCHITECTURE (LARAVEL 11)

### 3.1. Stack Teknologi Target
| Layer | Teknologi | Catatan |
|-------|-----------|---------|
| Framework | Laravel 11.x | PHP 8.2+ required |
| Language | PHP 8.2+ | Match existing requirement |
| Database | MySQL 8.0 / MariaDB 10.6+ | InnoDB, utf8mb4_unicode_ci |
| ORM | Eloquent | Model per domain |
| Auth | Laravel Breeze / Jetstream / Fortify | Bcrypt + session sanctum |
| RBAC | Spatie Laravel Permission | Role & Permission granular |
| Frontend | Blade + AdminLTE 3 / Livewire | Pertahankan UI familiar dulu |
| API | Laravel Sanctum | Untuk mobile/WA gateway |
| Queue | Redis/Database | WhatsApp, email, report |
| Export | Laravel Excel (Maatwebsite) | Pengganti PHPExcel |
| PDF | DomPDF / Laravel Snappy | Cetak raport, nota, kartu |
| QR | `simplesoftwareio/simple-qrcode` | Pengganti library lama |
| Search | Laravel Scout (optional) | Jika data besar |
| Testing | PHPUnit, Pest | Feature & Unit test |
| Logging | Laravel Log + Activity Log | Pengganti user_log_login/entri |
| Audit | `owen-it/laravel-auditing` | Track perubahan data sensitif |
| Backup | `spatie/laravel-backup` | Otomatis backup DB & file |

### 3.2. Struktur Aplikasi Laravel
```
sisfokol-laravel/
├── app/
│   ├── Console/Commands/          # Command custom (import data lama, report)
│   ├── Http/
│   │   ├── Controllers/             # Web Controller per modul
│   │   ├── Controllers/Api/         # API Controller
│   │   ├── Requests/                # Form Request Validation
│   │   ├── Middleware/
│   │   └── Resources/               # API Resources
│   ├── Models/                      # Eloquent Models (75+ tabel)
│   ├── Services/                    # Business Logic Layer
│   ├── Repositories/                # Data Access Layer (optional)
│   ├── Policies/                    # Authorization Policies
│   ├── Jobs/                        # Queue Jobs (WA, PDF, Excel)
│   ├── Mail/                        # Email notifications
│   ├── Notifications/               # Notifikasi (database, broadcast)
│   ├── Providers/
│   └── View/Components/             # Reusable Blade components
├── config/
├── database/
│   ├── migrations/                  # Semua migration dari 75 tabel
│   ├── seeders/                     # Data awal (admin, role, permission)
│   └── factories/                   # Factory untuk testing
├── resources/
│   ├── views/                       # Blade layouts & views
│   │   ├── layouts/                 # AdminLTE wrapper
│   │   ├── admin/                   # Modul admin
│   │   ├── guru/                    # Guru mapel
│   │   ├── wali/                    # Wali kelas
│   │   ├── siswa/                   # Siswa
│   │   ├── bendahara/
│   │   ├── bk/
│   │   ├── piket/
│   │   ├── sarpras/
│   │   └── ks/
│   ├── js/                          # Vite + Alpine/Livewire
│   ├── css/                         # Sass/Tailwind opsional
│   └── lang/                        # Bahasa Indonesia
├── routes/
│   ├── web.php                      # Route per role
│   ├── api.php                      # API routes
│   └── console.php
├── storage/app/public/              # Uploads (filebox, foto, qr)
├── tests/
│   ├── Feature/                     # Test per modul
│   └── Unit/
├── .env.example
├── composer.json
├── artisan
└── phpunit.xml
```

### 3.3. Domain-Driven Module Structure
Aplikasi akan dibagi menjadi **Bounded Context/Domain** sesuai konteks sekolah:

1. **Identity & Access Management** (User, Role, Permission, Auth, Audit)
2. **Master Data** (Sekolah, Tahun Pelajaran, Kelas, Ruang, Mapel, Ekstra, Jenis Pelanggaran, Pembinaan, Prestasi)
3. **Kepegawaian** (Pegawai, Guru, BK, Wali Kelas, Bendahara, Sarpras, Kepala Sekolah, Piket)
4. **Kesiswaan** (Siswa, Kelas, Naik Kelas, Alumni)
5. **Akademik** (Jadwal, Kurikulum Merdeka: TP, LM, Proyek, Asesmen Formatif & Sumatif)
6. **Presensi & Absensi** (Presensi QR, Absensi, Izin, Jurnal Guru)
7. **Kedisiplinan** (Pelanggaran, Pembinaan, Prestasi, BK)
8. **Keuangan** (Item Pembayaran, Tunggakan, Bayar, Nota, Laporan, Tabungan)
9. **Inventaris** (KIB A-F, KIR, Barang, Kode Barang)
10. **Filebox & Document** (RPP, Silabus, Kartu, Lampiran)
11. **Reporting & Analytics** (Dashboard, Chart, Laporan, Export)
12. **Communication** (WhatsApp, Email, Notification)

---

## 4. DATABASE MIGRATION STRATEGY

### 4.1. Prinsip Transformasi Database
1. **Engine**: MyISAM → **InnoDB** (transactional, FK, row-level lock)
2. **Charset**: latin1 → **utf8mb4_unicode_ci** (support emoji, karakter Jawa, simbol)
3. **Primary Key**: `kd` varchar(50) manual → **bigint unsigned auto_increment** atau **UUID** untuk data legacy
4. **Timestamps**: tambah `created_at`, `updated_at`, `deleted_at` (soft delete)
5. **Foreign Keys**: definisikan relasi eksplisit di migration
6. **Normalisasi**: pecah beberapa tabel yang redundancy tinggi
7. **Auditing**: tabel audit untuk data keuangan, nilai, presensi

### 4.2. Mapping 75 Tabel ke Domain (Ringkasan)

#### A. Identity & Access (4 tabel)
| Tabel Lama | Tabel Baru | Keterangan |
|------------|------------|------------|
| `adminx` | `users` | Merge semua user ke satu tabel dengan `role` |
| `m_user` | `users` | Merge |
| `user_log_login` | `login_logs` | Activity log |
| `user_log_entri` | `activity_logs` | Audit trail (better pakai package auditing) |

**Rekomendasi:** Semua pengguna (admin, guru, siswa, piket, dll) disatukan di `users` dengan `userable_type` & `userable_id` (polymorphic) atau `role` via Spatie. Data spesifik (NIP, NIS, jabatan) tetap di tabel master masing-masing.

#### B. Master Data (15 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `a_profil` | `school_profiles` | Singleton |
| `m_tapel` | `academic_years` | - |
| `m_kelas` | `classrooms` | tapel_id |
| `m_ruang` | `rooms` | - |
| `m_hari` | `days` | Enum/Seeder |
| `m_jam` | `hours` | - |
| `m_waktu` | `time_slots` | - |
| `m_waktu_jadwal` | `schedule_time_slots` | composite |
| `m_mapel` | `subjects` | teacher_id |
| `m_mapel_jns` | `subject_types` | - |
| `m_mapel_deskripsi` | `subject_descriptions` | subject_id |
| `m_ekstra` | `extracurriculars` | - |
| `m_bk_point_jenis` | `violation_types` | - |
| `m_bk_point` | `violation_points` | violation_type_id |
| `m_bk_prestasi` | `achievement_types` | - |
| `m_pembinaan` | `counseling_types` | - |
| `m_kib_jenis` | `inventory_types` | - |
| `m_kib_kode` | `inventory_codes` | inventory_type_id |

#### C. Kepegawaian (8 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `m_pegawai` | `employees` | user_id (one-to-one) |
| `m_gurubk` | `counselor_teachers` | employee_id |
| `m_walikelas` | `homeroom_teachers` | employee_id, classroom_id |
| `m_bendahara` | `treasurers` | employee_id |
| `m_sarpras` | `inventory_officers` | employee_id |
| `m_ks` | `school_principals` | employee_id |
| `m_piket` | `picket_officers` | bisa employee_id atau standalone |
| `m_mapel` (relasi pegawai) | pivot `employee_subject` | many-to-many |

#### D. Kesiswaan (1 tabel utama)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `m_siswa` | `students` | user_id, classroom_id, academic_year_id |
| `siswa_ekstra` | `student_extracurricular` | pivot |

#### E. Akademik & Jadwal (3 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `jadwal` | `schedules` | classroom_id, subject_id, teacher_id, day_id, time_slot_id |
| `rev_guru_agenda` | `teacher_agendas` | schedule_id, teacher_id, date |
| `kurmer_*` | `curriculum_merdeka_*` | Lihat bagian Kurmer |

#### F. Kurikulum Merdeka (11 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `kurmer_mapel_tp` | `curriculum_competencies` | subject_id, phase, code |
| `kurmer_mapel_lm` | `curriculum_learning_materials` | competency_id |
| `kurmer_asesmen_formatif` | `formative_assessments` | subject_id, classroom_id |
| `kurmer_nilai_asesmen_formatif` | `formative_assessment_scores` | assessment_id, student_id |
| `kurmer_nilai_asesmen_formatif_detail` | `formative_assessment_score_details` | score_id, material_id |
| `kurmer_nilai_asesmen_sumatif` | `summative_assessment_scores` | subject_id, student_id |
| `kurmer_nilai_asesmen_sumatif_detail` | `summative_assessment_score_details` | score_id |
| `kurmer_proyek` | `projects` | classroom_id, subject_id |
| `kurmer_proyek_detail` | `project_details` | project_id, competency_id |
| `kurmer_nilai_proyek` | `project_scores` | project_id, student_id |
| `kurmer_nilai_proyek_proses` | `project_process_scores` | project_score_id |

#### G. Presensi & Absensi (5 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `user_presensi` | `attendances` | user_id, date, time, type (in/out), source (qr/manual) |
| `user_absensi` | `absences` | user_id, date, type (sakit/ijin/alpha), note |
| `user_ijin` | `permits` | user_id, date, type (in/out), reason, status |
| `siswa_mapel_absensi` | `subject_attendances` | schedule_id, student_id, date, status |
| `rev_guru_absensi` | `teacher_attendance_summaries` | teacher_id, date |

#### H. Kedisiplinan & BK (4 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `siswa_pelanggaran` | `student_violations` | student_id, violation_point_id, officer_id |
| `siswa_prestasi` | `student_achievements` | student_id, achievement_type_id |
| `siswa_saran` | `student_suggestions` | student_id |
| `m_bk_point` | `violation_points` | master |

#### I. Nilai & Raport Legacy (7 tabel)
| Tabel Lama | Tabel Baru | Keterangan |
|------------|------------|------------|
| `siswa_nilai_bln` | `student_monthly_scores` | Legacy, tetap dipertahankan |
| `siswa_nilai_smt` | `student_semester_scores` | Legacy |
| `siswa_nilai_thn` | `student_yearly_scores` | Legacy |
| `siswa_raport_catatan` | `report_notes` | - |
| `siswa_raport_kenaikan` | `student_promotions` | - |
| `siswa_raport_rangking` | `student_rankings` | - |
| `siswa_raport_sikap` | `student_attitude_reports` | - |

#### J. Keuangan (7 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `m_keu_siswa` | `payment_items` | academic_year_id, classroom_id |
| `siswa_bayar_tagihan` | `student_bills` | student_id, payment_item_id |
| `siswa_bayar` | `student_payments` | student_id, bill_id, treasurer_id |
| `siswa_bayar_rincian` | `student_payment_details` | payment_id |
| `siswa_tabungan` | `student_savings` | student_id, type (debit/credit) |
| `m_tabungan` | `savings_settings` | master limit |
| `wa_tagihan_siswa` | `wa_bill_notifications` | bill_id, status (queue/sent/failed) |

#### K. Inventaris (7 tabel)
| Tabel Lama | Tabel Baru | Relasi |
|------------|------------|--------|
| `inv_kib_a` | `inventory_land` | inventory_code_id |
| `inv_kib_b` | `inventory_equipment` | inventory_code_id |
| `inv_kib_c` | `inventory_buildings` | inventory_code_id |
| `inv_kib_d` | `inventory_roads` | inventory_code_id |
| `inv_kib_e` | `inventory_others` | inventory_code_id |
| `inv_kib_f` | `inventory_constructions` | inventory_code_id |
| `user_filebox` | `documents` | user_id, category |

### 4.3. Migration Strategy Detail
#### Fase A: Schema Migration (Strangler Fig Pattern)
1. Buat migration baru di Laravel dengan struktur yang sudah didesain ulang.
2. Buat **data migration script** (custom Artisan command) yang membaca database lama dan menulis ke database baru.
3. Jalankan **side-by-side**: aplikasi lama tetap jalan sampai semua fitur Laravel siap.
4. Setiap tabel migrasi perlu mapping field, transformasi charset, dan normalisasi.

#### Fase B: Data Transformation Rules
- **Password**: Saat migrasi, set password default/force reset karena MD5 tidak bisa reverse ke Bcrypt. Pengguna login pertama kali wajib reset password.
- **Tanggal**: Format `Y:m:d H:i:s` di lama → `Y-m-d H:i:s` standar Laravel.
- **Kode/KD**: `md5(random)` lama → UUIDv4 atau bigint auto-increment. **Butuh mapping tabel** agar relasi tetap terjaga.
- **Uang**: Field `varchar` harga → `decimal(15,2)`.
- **Boolean**: string `'true'`/`'false'` → tinyint 1/0.
- **Kelas**: string seperti `X IPA 1` → relasi ke `classrooms`.

### 4.4. Seeder & Data Awal
Buat DatabaseSeeder untuk:
1. Roles & Permissions (Spatie)
2. Default admin user
3. School profile
4. Academic year aktif
5. Days, hours, time slots
6. Subject types, violation types, achievement types
7. Inventory types & codes

---

## 5. AUTHENTICATION & AUTHORIZATION (RBAC)

### 5.1. User Model Consolidation
Semua user disatukan ke `App\Models\User`:
```php
users
- id (bigint, AI)
- username (unique)
- email (nullable)
- password (bcrypt)
- email_verified_at
- remember_token
- role_id (FK) atau pakai Spatie
- userable_type (polymorphic: Employee, Student, PicketOfficer)
- userable_id
- is_active
- last_login_at
- last_login_ip
- created_at, updated_at, deleted_at
```

### 5.2. Roles & Permissions (Spatie)
**Roles:**
- `superadmin` (Administrator)
- `kepala-sekolah`
- `guru-mapel`
- `wali-kelas`
- `guru-bk`
- `bendahara`
- `sarpras`
- `petugas-piket`
- `siswa`

**Permissions** (contoh granular):
- `master.tahun-pelajaran.*`
- `master.kelas.*`
- `master.siswa.*`
- `akademik.jadwal.*`
- `akademik.kurmer.*`
- `presensi.*`
- `absensi.*`
- `pelanggaran.*`
- `pembinaan.*`
- `prestasi.*`
- `keuangan.pembayaran.*`
- `keuangan.tabungan.*`
- `inventaris.*`
- `laporan.*`
- `pengaturan.*`

### 5.3. Route Middleware
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(...)
Route::middleware(['auth', 'role:guru-mapel'])->prefix('guru')->group(...)
Route::middleware(['auth', 'role:wali-kelas'])->prefix('wali-kelas')->group(...)
// dst
```

### 5.4. Password Security
- Reset password wajib untuk semua user saat migrasi.
- Password minimal 8 karakter, kombinasi huruf & angka.
- Bcrypt cost factor default Laravel.
- Login throttling via `Illuminate\Foundation\Configuration\Middleware::throttleApi`.

---

## 6. FRONTEND & UI STRATEGY

### 6.1. Pertahankan AdminLTE 3 (Fase 1)
Agar transisi tidak mengagetkan pengguna, gunakan **AdminLTE 3** sebagai layout utama.
- Convert template `adm.html`, `admgr.html`, `admwk.html`, dst menjadi **Blade Layout** `layouts/adminlte.blade.php`.
- Sidebar menu dinamis berdasarkan role & permission.
- Gunakan `View::composer` untuk data shared (notifikasi, profil sekolah, menu).

### 6.2. Komponen Blade Reusable
Buat komponen untuk pola yang sering muncul:
- `x-data-table` (paging, search, export)
- `x-info-box`
- `x-chart`
- `x-form-input`
- `x-modal`
- `x-alert`

### 6.3. Livewire untuk Interaktivitas
Pertimbangkan **Livewire 3** untuk:
- Entri nilai real-time
- Presensi scanning QR
- Pencarian siswa/pegawai autocomplete
- Form pembayaran dinamis

### 6.4. Mobile-First Gradual
Setelah core stabil, migrasi ke:
- Vite + Alpine.js + Tailwind (opsional)
- atau pertahankan Bootstrap + jQuery untuk meminimalkan risiko user adoption.

---

## 7. MODUL REFACTOR ROADMAP

### 7.1. Fase 1: Foundation & Identity (0-2 bulan)
| Modul | Deliverable |
|-------|-------------|
| Setup Laravel 11 | Project skeleton, Docker/sail, CI/CD |
| Database Migration | Migration 75+ tabel, seeder awal |
| Auth & RBAC | Login, logout, role, permission, password reset |
| School Profile | Pengaturan sekolah, tapel, kelas, ruang |
| Dashboard | Widget berdasarkan role |
| Master Pegawai | CRUD pegawai, guru, bk, wali, bendahara, sarpras, ks, piket |
| Master Siswa | CRUD siswa, import Excel, foto, QR kartu |
| Logging & Audit | Ganti user_log_login/entri dengan activity log |

### 7.2. Fase 2: Akademik & Presensi (2-4 bulan)
| Modul | Deliverable |
|-------|-------------|
| Master Mapel | Mapel, deskripsi, RPP/Silabus (Filebox) |
| Jadwal Pelajaran | Set jadwal, laporan per mapel/guru/kelas |
| Presensi QR | Scan hadir/pulang, laporan keterlambatan |
| Absensi | Entri sakit/ijin/alpha, laporan |
| Izin | Izin masuk/pulang, QR code, PDF |
| Jurnal Guru | Agenda mengajar per jadwal |
| Kurikulum Merdeka | TP, LM, Asesmen Formatif/Sumatif, Proyek |

### 7.3. Fase 3: Kedisiplinan & Keuangan (4-6 bulan)
| Modul | Deliverable |
|-------|-------------|
| Pelanggaran | Point, entri, laporan |
| Pembinaan | Status belum/sudah dibina |
| Prestasi | Data prestasi & ekstrakurikuler |
| Item Pembayaran | Set biaya per kelas/tapel |
| Tunggakan & Bayar | Entri pembayaran, nota, PDF |
| Tabungan | Debet/kredit, saldo, limit |
| WA Gateway | Kirim tagihan via queue |

### 7.4. Fase 4: Inventaris & Go-Live (6-8 bulan)
| Modul | Deliverable |
|-------|-------------|
| Inventaris KIB A-F | CRUD, import, laporan rekap |
| KIR | Kartu inventaris ruangan |
| Filebox | Upload dokumen RPP/silabus |
| Laporan & Export | Semua laporan di PDF/Excel |
| Data Migration | Migrasi data dari sistem lama |
| UAT & Training | User Acceptance Test, pelatihan pengguna |
| Go-Live | Cutover, monitoring, rollback plan |

---

## 8. API & INTEGRASI

### 8.1. API Endpoints (Laravel Sanctum)
| Endpoint | Fungsi |
|----------|--------|
| `POST /api/login` | Mobile login |
| `GET /api/jadwal` | Jadwal hari ini |
| `POST /api/presensi` | Scan QR presensi |
| `GET /api/nilai` | Nilai siswa |
| `GET /api/keuangan` | Tunggakan & riwayat bayar |
| `GET /api/raport` | Raport digital |
| `POST /api/wa/webhook` | Webhook status WA |

### 8.2. WhatsApp Integration
- Gunakan **Queue Job** untuk mengirim tagihan.
- Simpan status pengiriman di `wa_bill_notifications`.
- Support webhook dari provider (sosmedsekolah.com atau lainnya).

### 8.3. QR Code Strategy
- Generate QR untuk kartu siswa/pegawai/piket saat create/update.
- Simpan file di `storage/app/public/qrcodes/{type}/{id}.png`.
- Endpoint scan validasi via API.

---

## 9. SECURITY HARDENING

### 9.1. Wajib Dilakukan
1. **Bcrypt password** untuk semua user.
2. **CSRF token** di semua form.
3. **Authorization Policy** per model.
4. **Rate limiting** login & API.
5. **XSS protection** via Blade auto-escape.
6. **SQL Injection elimination** via Eloquent/Query Builder.
7. **File upload validation** (type, size, scan virus opsional).
8. **HTTPS enforcement** di production.
9. **Sensitive data encryption** (NISN, NIK, no rekening jika ada).
10. **Session security** (secure cookie, session timeout, regenerate ID).

### 9.2. Compliance Pendidikan Indonesia
- **Data Privacy**: Lindungi data siswa sesuai UU ITE & PDP.
- **Backup**: Minimal daily backup database + file.
- **Audit Trail**: Log perubahan nilai, keuangan, presensi.
- **Access Log**: Siapa mengakses data raport/keuangan.

---

## 10. TESTING & QUALITY ASSURANCE

### 10.1. Testing Strategy
- **Unit Test**: Service layer, helper function, repository.
- **Feature Test**: Login per role, CRUD master, pembayaran, presensi.
- **Browser Test** (Dusk): Alur critical path (login → entri nilai → cetak raport).
- **Database Test**: Migration rollback, seeder, data integrity.
- **Security Test**: SQL injection attempt, XSS attempt, unauthorized access.

### 10.2. Static Analysis
- **Laravel Pint**: Code style.
- **PHPStan**: Static analysis level 8/9.
- **Rector**: Automated refactoring.
- **SonarQube**: Code quality & security scanning.

---

## 11. DEPLOYMENT & DEVOPS

### 11.1. Environment
- **Development**: Docker/Sail + PHP 8.2 + MySQL 8 + Redis.
- **Staging**: VPS/Cloud dengan SSL.
- **Production**: VPS/Cloud (AWS Lightsail, DigitalOcean, IDCloudHost) atau shared hosting yang support PHP 8.2+.

### 11.2. CI/CD Pipeline (GitHub Actions/GitLab CI)
1. `composer install` + `composer validate`
2. `php artisan test`
3. `php artisan pint --test`
4. `php artisan migrate --force` (staging)
5. Deploy ke staging/production
6. Clear cache, optimize

### 11.3. Runbook Production
```bash
php artisan down
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

---

## 12. DATA MIGRATION PLAN (DETAIL)

### 12.1. Pre-Migration Checklist
- [ ] Backup full database lama & file.
- [ ] Install Laravel 11 baru di environment terpisah.
- [ ] Jalankan migration + seeder.
- [ ] Buat mapping tabel lama → baru (spreadsheet).
- [ ] Siapkan data cleaning script.
- [ ] Tetapkan password reset strategy.

### 12.2. Migration Script (Custom Artisan)
```php
php artisan migrate:legacy --source=mysql_sisfokol_v7 --target=mysql_sisfokol_laravel --dry-run
php artisan migrate:legacy --source=mysql_sisfokol_v7 --target=mysql_sisfokol_laravel
```

### 12.3. Data Verification
- [ ] Count comparison per tabel.
- [ ] Sample integrity check (relasi siswa-kelas, guru-mapel, pembayaran-rincian).
- [ ] Test login dengan user dari data lama (setelah reset password).
- [ ] Report discrepancies.

### 12.4. Rollback Plan
- Database lama tetap ada selama UAT.
- Aplikasi lama bisa diaktifkan kembali dengan DNS switch.
- Dokumentasi cutover time window.

---

## 13. TEAM & ESTIMASI

### 13.1. Tim Ideal
| Role | Jumlah | Tugas |
|------|--------|-------|
| Tech Lead / Senior Laravel | 1 | Arsitektur, code review, DevOps |
| Backend Engineer (Laravel) | 2 | Model, migration, controller, service |
| Frontend Engineer | 1 | Blade, AdminLTE, Livewire |
| Database Engineer | 1 | Migration, data cleaning, optimization |
| QA Engineer | 1 | Testing, UAT |
| Business Analyst (Sekolah) | 1 | Validasi fitur, training, SOP |
| Project Manager | 1 | Jadwal, stakeholder management |

### 13.2. Estimasi Waktu
| Fase | Durasi | Effort (man-month) |
|------|--------|-------------------|
| Fase 1: Foundation | 2 bulan | 10 MM |
| Fase 2: Akademik & Presensi | 2 bulan | 12 MM |
| Fase 3: Kedisiplinan & Keuangan | 2 bulan | 12 MM |
| Fase 4: Inventaris & Go-Live | 2 bulan | 10 MM |
| **Total** | **8 bulan** | **44 MM** |

*Estimasi untuk tim 5-6 developer full-time. Bisa diparalelkan beberapa modul.*

### 13.3. Risiko & Mitigasi
| Risiko | Mitigasi |
|--------|----------|
| Data loss saat migrasi | Backup triple, dry-run, rollback plan |
| User resistance (guru/staff) | Training bertahap, UI tetap familiar |
| Feature parity tidak 100% | Prioritaskan fitur critical, sisanya iterasi |
| Performance issue setelah go-live | Indexing, query optimization, caching |
| Security vulnerability | Audit security, penetration testing |
| Scope creep | MoSCoW prioritization, agile sprint |

---

## 14. REKOMENDASI TEKNIS PENTING

### 14.1. Jangan Lakukan Big Bang
Gunakan **Strangler Fig Pattern**: migrasi modul per modul, bukan seluruh aplikasi sekaligus. Modul yang lebih sederhana (Master, Dashboard) dulu, modul kompleks (Keuangan, Kurmer) belakangan.

### 14.2. Pertahankan Data Legacy
Jangan hapus database lama. Buat **read-only archive** atau **migration history** agar bisa audit data lama.

### 14.3. Gunakan UUID untuk Data Lama
Karena `kd` lama berupa hash/string, pertimbangkan untuk:
- Membuat `legacy_id` column di tabel baru untuk menyimpan `kd` lama.
- Menggunakan UUID untuk record baru.
- BigInteger auto-increment untuk performa query.

### 14.4. Hilangkan Duplikasi Kode
Setiap fitur CRUD di 9 role seharusnya menjadi **satu controller/service** dengan **authorization check**. Jangan buat 9 file terpisah untuk fitur yang sama.

### 14.5. Dokumentasi & Knowledge Transfer
- Buat API documentation (Scribe/Postman).
- Buat user manual per role (PDF/video).
- Adopsi ADR (Architecture Decision Records).

---

## 15. CONCLUSION

Refactoring SISFOKOL v7.00 ke Laravel 11 adalah investasi jangka panjang yang **sangat direkomendasikan**. Dengan arsitektur modern, keamanan yang lebih kuat, dan kemudahan pengembangan, sistem ini akan lebih siap menghadapi kebutuhan pendidikan Indonesia di era digital.

Kunci keberhasilan:
1. **Perencanaan matang** (dokumen ini).
2. **Tim yang kompeten** dan paham domain sekolah.
3. **Migrasi data yang hati-hati**.
4. **Pelatihan pengguna yang intensif**.
5. **Pendekatan iteratif**, bukan big bang.

**Langkah Selanjutnya:**
1. Persetujuan stakeholder & alokasi budget.
2. Setup repository Laravel 11 baru.
3. Mulai Fase 1: Foundation & Identity.
4. Sprint planning 2 mingguan dengan deliverable yang jelas.

---

## APPENDIX A: Daftar Tabel Database Legacy

```
a_profil, adminx, inv_kib_a, inv_kib_b, inv_kib_c, inv_kib_d, inv_kib_e, inv_kib_f,
jadwal, kurmer_asesmen_formatif, kurmer_mapel_lm, kurmer_mapel_tp,
kurmer_nilai_asesmen_formatif, kurmer_nilai_asesmen_formatif_detail,
kurmer_nilai_asesmen_sumatif, kurmer_nilai_asesmen_sumatif_detail,
kurmer_nilai_proyek, kurmer_nilai_proyek_proses, kurmer_proyek, kurmer_proyek_detail,
m_bendahara, m_bk_point, m_bk_point_jenis, m_bk_prestasi, m_ekstra, m_gurubk, m_hari,
m_jam, m_kelas, m_keu_siswa, m_kib_jenis, m_kib_kode, m_ks, m_mapel, m_mapel_deskripsi,
m_mapel_jns, m_pegawai, m_pembinaan, m_piket, m_ruang, m_sarpras, m_siswa, m_tapel,
m_user, m_waktu, m_waktu_jadwal, m_walikelas, rev_guru_absensi, rev_guru_agenda,
siswa_bayar, siswa_bayar_rincian, siswa_bayar_tagihan, siswa_ekstra, siswa_mapel_absensi,
siswa_nilai_bln, siswa_nilai_smt, siswa_nilai_thn, siswa_pelanggaran, siswa_prestasi,
siswa_raport_catatan, siswa_raport_kenaikan, siswa_raport_rangking, siswa_raport_sikap,
siswa_saran, siswa_soal, siswa_soal_nilai, siswa_tugas, user_absensi, user_filebox,
user_ijin, user_log_entri, user_log_login, user_piket, user_presensi, wa_tagihan_siswa
```

Total: **75 tabel**

---

*Dokumen ini disusun berdasarkan analisis repository SISFOKOL v7.00 dan pengalaman praktis di bidang Software Engineering, NLP/AI, serta pendidikan Indonesia. Dapat digunakan sebagai blueprint untuk kick-off proyek refactoring.*
