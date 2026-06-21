# ANALISIS KESIAPAN SISFOKOL v7.00 UNTUK DIADAPTASI MENJADI MULTI-TENANT SAAS
## Menggunakan Laravel Multi-Tenant SaaS Starter sebagai Boilerplate

---

## 1. RINGKASAN EKSEKUTIF

### Profil Repository Target: SISFOKOL v7.00 (Code: SmartOffice)
- **Penulis:** Agus Muhajir, S.Kom.
- **Teknologi:** PHP Native (non-framework), MySQL/MariaDB, Template HTML statis
- **Total File:** ~13,537 file (sekitar 1,675 file PHP, 408 HTML template, 5,221 JS, 1,077 CSS)
- **Tabel Database:** 75 tabel
- **Role Pengguna:** 12 role (Administrator, Guru Mapel, Siswa, Wali Kelas, Kepala Sekolah, Piket, Bendahara, Sarpras, Guru BK, dan lainnya)
- **Modul Utama:** Akademik, Keuangan, Absensi, Kesiswaan, Penilaian, Inventaris, Perpustakaan, Kurikulum Merdeka, Ekstrakurikuler, Pelanggaran & Pembinaan, Nabung, Ijin Pegawai

### Profil Boilerplate: Laravel Multi-Tenant SaaS Starter
- **Teknologi:** Laravel 12, React 18 + Inertia.js, Tailwind CSS, stancl/tenancy v3.9
- **PHP:** ^8.4 (sangat modern)
- **Pola Multi-Tenant:** Separate database per tenant (via Stancl/Tenancy)
- **Fitur Inti:** Tenant CRUD, Admin Panel, User Registration per tenant, Impersonation, API + Web routes terpisah

---

## 2. ANALISIS ARSITEKTURAL: GAP YANG SANGAT BESAR

### 2.1. Arsitektur SISFOKOL v7.00 — PHP Native (Procedural)

```
sisfokol-v7.00/
├── inc/
│   ├── config.php          ← Hardcoded credentials ($xhostname, $xdatabase, $xusername, $xpassword)
│   ├── koneksi.php         ← mysqli_connect() langsung
│   ├── fungsi.php          ← Fungsi helper procedural (cegah(), nosql(), xloc(), dll)
│   └── class/              ← DomPDF, PhpSpreadsheet (bundled vendor)
├── adm/                    ← Dashboard Administrator (135+ file PHP)
├── admgr/                  ← Dashboard Guru Mapel
├── admsw/                  ← Dashboard Siswa
├── admwk/                  ← Dashboard Wali Kelas
├── admks/                  ← Dashboard Kepala Sekolah
├── admpiket/               ← Dashboard Piket
├── admbk/                  ← Dashboard Guru BK
├── admbdh/                 ← Dashboard Bendahara
├── adminv/                 ← Dashboard Sarpras/Inventaris
├── login.php               ← Single login point dengan 12 tipe role
├── index.php               ← Landing page
├── db/sisfokol_v7.sql      ← Full SQL dump (75 tabel, MyISAM)
├── template/               ← HTML template engine sederhana (parse {variable})
└── filebox/                ← Upload folder (tanpa isolasi tenant)
```

**Pola autentikasi:** Session-based PHP native, password disimpan dalam **MD5** (KRITIS), login dilakukan di satu file `login.php` dengan percabangan `if ($tipe == "tp01") ... if ($tipe == "tp02") ...` hingga 12 percabangan.

**Pola keamanan SQL Injection:** Menggunakan fungsi custom `cegah()` dan `nosql()` yang melakukan **string replacement** (mengganti `'` dengan `xpsijix`, `%` dengan `xpersenx`, dll.) — **BUKAN prepared statements**. Ini adalah anti-pattern yang tidak aman.

### 2.2. Arsitektur Laravel Multi-Tenant SaaS Starter — Modern Framework

```
lararavel-multi-tenant-saas-starter/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Central/        ← Admin panel controllers
│   │   │   └── Tenant/         ← Tenant app controllers (Auth, CRUD)
│   │   └── Middleware/         ← EnsureUserIsAdmin, EnsureTenantAdmin
│   ├── Models/
│   │   ├── Tenant.php          ← Extends Stancl\Tenancy\Models\Tenant
│   │   └── User.php            ← Auth model dengan roles
│   └── Services/               ← Business logic layer
├── database/
│   ├── migrations/             ← Central DB migrations
│   └── migrations/tenant/      ← Tenant-specific migrations
├── config/tenancy.php          ← Stancl/Tenancy configuration
├── routes/
│   ├── web.php                 ← Central domain routes
│   ├── tenant.php              ← Tenant domain routes (with InitializeTenancyByDomain middleware)
│   └── admin.php               ← Admin panel routes
└── resources/
    └── js/                     ← React 18 + Inertia.js + Tailwind CSS
```

**Pola autentikasi:** Laravel Breeze/Sanctum, password **hashed** (bcrypt), middleware-based authorization, separate session per tenant.

**Pola multi-tenant:** Stancl/Tenancy dengan **separate database per tenant**, event-driven tenant lifecycle (create → migrate → seed → provision), domain-based tenant identification.

---

## 3. PENILAIAN KESIAPAN PER DIMENSI

### 3.1. 📊 Database Readiness for Multi-Tenancy: **SANGAT RENDAH (1/10)**

| Aspek | Status | Detail |
|-------|--------|--------|
| **Engine Tabel** | ❌ KRITIS | 75 dari 75 tabel menggunakan **MyISAM**. MyISAM tidak mendukung transactions, foreign keys, row-level locking. Tidak kompatibel dengan Laravel Eloquent ORM yang mengasumsikan InnoDB |
| **Charset/Kolasi** | ❌ MASALAH | Campuran `latin1_swedish_ci`, `utf8mb4_general_ci`, `utf8_general_ci`. Tidak konsisten, akan menyebabkan masalah encoding untuk nama siswa/guru Indonesia (ejaan khusus) |
| **Primary Keys** | ⚠️ INKONSISTEN | Beberapa tabel menggunakan `VARCHAR(50)` (MD5 hex), beberapa tidak memiliki PK yang jelas. Laravel mengasumsikan auto-increment `BIGINT` |
| **Foreign Keys** | ❌ TIDAK ADA | Tidak ada deklarasi FK sama sekali. Relasi dilakukan di level application code dengan manual JOIN |
| **Tenant Isolation** | ❌ TIDAK ADA | Tidak ada kolom `tenant_id` di satu pun tabel. Semua data tersatu database tunggal |
| **Schema Migrations** | ❌ TIDAK ADA | Hanya file `.sql` mentah. Tidak ada sistem migration terstruktur |
| **Index/Constraint** | ⚠️ MINIMAL | Tidak ada unique constraints kecuali pada `adminx.usernamex` |

**KESIMPULAN:** Seluruh 75 tabel harus **di-rewrite** ke Laravel Migrations dengan:
- Konversi MyISAM → InnoDB
- Normalisasi charset ke utf8mb4
- Penambahan kolom `tenant_id` pada setiap tabel (atau gunakan separate database)
- Definisi foreign keys dan indexes yang proper
- Primary keys menjadi auto-increment BIGINT

### 3.2. 🔐 Security Assessment: **SANGAT RENDAH (1/10)**

| Aspek | Status | Detail |
|-------|--------|--------|
| **Password Hashing** | ❌ KRITIS | Menggunakan **MD5** — sudah tidak aman sejak 2012. Tidak ada salt, tidak ada pepper |
| **SQL Injection Prevention** | ❌ KRITIS | Fungsi `cegah()` hanya melakukan string replacement. `mysqli_query()` langsung tanpa prepared statements |
| **XSS Prevention** | ⚠️ PARTIAL | Ada `htmlspecialchars()` di fungsi `cegah()`, tapi output rendering menggunakan template parse tanpa escaping otomatis |
| **CSRF Protection** | ❌ TIDAK ADA | Tidak ada CSRF token di form-form |
| **Session Security** | ⚠️ LEMAH | Session hanya `session_start()`, tidak ada regeneration, tidak ada HTTP-only flag |
| **File Upload Security** | ❌ KRITIS | Folder `filebox/` dengan subfolder per tipe file, tidak ada validasi MIME type, tidak ada isolasi tenant |
| **Authorization** | ⚠️ BASIC | Hanya session-based role checking di setiap file PHP, tidak ada centralized authorization |

**KESIMPULAN:** Security posture saat ini tidak memenuhi standar untuk production SaaS. Diperlukan **security overhaul total**.

### 3.3. 🏗️ Architecture Readiness: **RENDAH (2/10)**

| Aspek | Status | Detail |
|-------|--------|--------|
| **Code Organization** | ❌ FLAT | File PHP tersebar di direktori per role (adm/, admgr/, admsw/) tanpa MVC pattern |
| **Reusability** | ❌ RENDAH | Kode terduplikasi di setiap direktori role (masing-masing punya index.php, s/, h/ yang serupa) |
| **Business Logic** | ❠️ SCATTERED | Logika bisnis bercampur dengan presentation logic di setiap file PHP |
| **Dependency Management** | ❌ TIDAK ADA | Tidak ada Composer, tidak ada autoloader, semua `require()` manual |
| **Testing** | ❌ TIDAK ADA | Tidak ada unit test, integration test, atau E2E test |
| **CI/CD** | ❌ TIDAK ADA | Tidak ada pipeline otomatis |
| **API** | ❌ TIDAK ADA | Tidak ada REST API atau GraphQL, semuanya direct PHP rendering |

### 3.4. 📦 Technology Stack Compatibility: **SANGAT RENDAH (1/10)**

| Aspek | SISFOKOL | SaaS Starter | Gap |
|-------|----------|--------------|-----|
| PHP Version | PHP 8.2 (runtime) | PHP 8.4 (requirement) | Minor |
| Framework | None (Procedural) | Laravel 12 | MAJOR |
| Database | MySQL/MariaDB (MyISAM) | MySQL (InnoDB implied) | MAJOR |
| Frontend | jQuery, Vanilla JS, HTML Templates | React 18 + Inertia.js + Tailwind | MAJOR |
| ORM | Raw mysqli | Eloquent ORM | MAJOR |
| Auth | Custom session | Laravel Sanctum + Breeze | MAJOR |
| Package Manager | None (bundled vendors) | Composer + NPM | MAJOR |

### 3.5. 📋 Feature Mapping: MODUL SISFOKOL → SaaS

| No | Modul SISFOKOL | Direktori | Status untuk SaaS | Kompleksitas Migrasi |
|----|----------------|-----------|-------------------|----------------------|
| 1 | **Master Data** (Siswa, Pegawai, Kelas, Mapel) | adm/m/ | ✅ Dapat dipetakan ke Eloquent Models | SEDANG |
| 2 | **Absensi** (Siswa & Pegawai) | adm/ab/, adm/im/ | ✅ CRUD standar | RENDAH |
| 3 | **Akademik** (Mapel, RPP, Silabus) | adm/akad/ | ✅ CRUD standar | RENDAH |
| 4 | **Penilaian** (Nilai, Raport, Kenaikan) | adm/nil/ | ⚠️ Logika kompleks (perhitungan nilai) | TINGGI |
| 5 | **Jadwal** | adm/jw/ | ⚠️ Conflict detection algorithm | TINGGI |
| 6 | **Keuangan** (SPP, Bayar, Tunggakan, Nota) | adm/keu/, admbdh/keu/ | ⚠️ Membutuhkan transaction support, laporan keuangan | TINGGI |
| 7 | **Inventaris/Sarpras** | adm/inv/, adminv/ | ✅ CRUD dengan KIB A-F | SEDANG |
| 8 | **Kurikulum Merdeka** | admgr/kurmer/, admwk/kurmer/ | ⚠️ Asesmen formatif & sumatif, TP, CP | TINGGI |
| 9 | **Ekstrakurikuler** | adm/ek/ | ✅ CRUD standar | RENDAH |
| 10 | **Pelanggaran & Pembinaan (BK)** | adm/pb/, adm/pl/, admbk/ | ✅ CRUD dengan sistem point | SEDANG |
| 11 | **Perpustakaan** | adm/ps/, adm/pt/ | ✅ CRUD standar | RENDAH |
| 12 | **Nabung Siswa** | adm/nabung/ | ⚠️ Membutuhkan transaction, saldo | SEDANG |
| 13 | **Piket** | adm/piket/, admpiket/ | ✅ CRUD standar | RENDAH |
| 14 | **Filebox/Dokumen** | filebox/ | ⚠️ Perlu isolasi tenant + cloud storage | TINGGI |
| 15 | **WhatsApp Integration** | adm/keu/i_proses_wa.php | ⚠️ API integration | SEDANG |
| 16 | **PDF Generation** | Berbagai *_pdf.php | ✅ Bisa pakai Laravel DomPDF | RENDAH |
| 17 | **Excel Export** | Berbagai export | ✅ Bisa pakai PhpSpreadsheet | RENDAH |

### 3.6. 👥 Multi-Role Mapping: SISFOKOL → SaaS Tenant

| SISFOKOL Role | SaaS Equivalent | Catatan |
|---------------|-----------------|---------|
| Administrator | Tenant Super Admin | Full access ke semua modul tenant |
| Kepala Sekolah (tp04) | Tenant Admin | Bisa impersonate, approve, laporan |
| Wali Kelas (tp03) | Tenant Staff + Role "WaliKelas" | Akses kelas tertentu saja |
| Guru Mapel (tp01) | Tenant Staff + Role "Guru" | Akses mapel & kelas yang diampu |
| Guru BK (tp011) | Tenant Staff + Role "BK" | Akses modul pelanggaran & pembinaan |
| Bendahara (tp042) | Tenant Staff + Role "Bendahara" | Akses modul keuangan saja |
| Sarpras (tp041) | Tenant Staff + Role "Sarpras" | Akses modul inventaris saja |
| Piket (tp033) | Tenant Staff + Role "Piket" | Akses absensi & agenda harian |
| Siswa (tp02) | Tenant User (End-user) | Read-only untuk nilai, jadwal, keuangan |
| Orang Tua (jika ada) | Tenant User (Parent) | Monitoring anak |

---

## 4. GAP ANALYSIS: YANG HARUS DIUBAH

### 4.1. High-Priority Gaps (Showstoppers)

| # | Gap | Dampak | Estimasi Usaha |
|---|-----|--------|----------------|
| 1 | **MyISAM → InnoDB** | Tanpa transaction, tanpa FK, tanpa row-locking. SaaS keuangan tidak bisa tanpa ini | 2-3 minggu |
| 2 | **MD5 → bcrypt/password_hash** | Password rentan dicrack dalam hitungan menit | 1 minggu |
| 3 | **String replacement SQL safety → Prepared Statements** | Rentan SQL injection yang sophisticated | 2-3 minggu |
| 4 | **Procedural PHP → Laravel MVC** | Tidak bisa menggunakan fitur multi-tenant Stancl/Tenancy | 3-4 bulan |
| 5 | **Single Database → Multi-Database per Tenant** | Tidak ada isolasi data antar sekolah | 1-2 minggu (setelah #4 selesai) |
| 6 | **No CSRF, no session security** | Vulnerable terhadap session hijacking & CSRF | 1 minggu |
| 7 | **Flat file structure → Service/Repository pattern** | Tidak maintainable untuk tim | 1-2 bulan |

### 4.2. Medium-Priority Gaps

| # | Gap | Dampak | Estimasi Usaha |
|---|-----|--------|----------------|
| 8 | **jQuery → React/Inertia** | UI modern, SPA experience | 2-3 bulan |
| 9 | **HTML Template engine → Blade/React** | Component reusability | 1-2 bulan |
| 10 | **Manual file upload → Laravel Storage + tenant isolation** | File security & scalability | 2 minggu |
| 11 | **No API layer → REST/GraphQL API** | Mobile app integration, webhook | 1 bulan |
| 12 | **No testing → PHPUnit + Pest** | Reliability, CI/CD | 1-2 bulan |
| 13 | **No migration system → Laravel Migrations** | Schema versioning, deployment | 2 minggu |

### 4.3. Low-Priority Gaps (Nice to Have)

| # | Gap | Dampak | Estimasi Usaha |
|---|-----|--------|----------------|
| 14 | **Tailwind CSS adoption** | Modern UI | 1 bulan |
| 15 | **Queue workers & Horizon** | Background job processing | 1 minggu |
| 16 | **Telescope & Debugging** | Dev productivity | 3 hari |
| 17 | **API documentation (Swagger/Scribe)** | Developer experience | 1 minggu |

---

## 5. REKOMENDASI STRATEGI MIGRASI

### 5.1. Opsi A: REWRITE TOTAL (DIREKOMENDASIKAN)

**Pendekatan:** Bangun ulang SISFOKOL dari nol menggunakan Laravel 12 + Stancl/Tenancy sebagai fondasi, dengan React/Inertia untuk frontend.

**Keuntungan:**
- ✅ Clean architecture dari awal
- ✅ Multi-tenant native (isolasi penuh per sekolah)
- ✅ Security modern (bcrypt, CSRF, prepared statements)
- ✅ Testable, maintainable, scalable
- ✅ Bisa menggunakan seluruh ekosistem Laravel (queues, events, broadcasting, etc.)

**Kerugian:**
- ❌ Estimasi waktu: 6-12 bulan untuk tim 3-5 developer
- ❌ Seluruh 75 tabel harus di-migrate ke Laravel Migrations
- ❌ Seluruh 1,675 file PHP harus ditulis ulang
- ❌ Perlu UX/UI redesign total

### 5.2. Opsi B: ADAPTIVE MIGRATION (Hybrid)

**Pendekatan:** Bungkus SISFOKOL sebagai "legacy module" dalam Laravel, secara bertahap migrate fitur per fitur.

**Keuntungan:**
- ✅ Bisa mulai digunakan lebih cepat (3-4 bulan untuk versi MVP)
- ✅ Multi-tenant wrapper di level routing & database
- ✅ Fitur-fitur yang sudah stabil di SISFOKOL tetap bisa digunakan

**Kerugian:**
- ❌ Technical debt terbawa
- ❌ Security issues dari kode lama tetap ada
- ❌ Arsitektur menjadi hybrid yang sulit di-maintain
- ❌ Sulit untuk true multi-tenant isolation

### 5.3. Opsi C: WRAPPER APPROACH (TIDAK DIREKOMENDASIKAN)

**Pendekatan:** Bungkus SISFOKOL dalam Docker container, routing per tenant ke container yang berbeda.

**Keuntungan:**
- ✅ Paling cepat (1-2 bulan)
- ✅ Minimal perubahan kode

**Kerugian:**
- ❌ Sangat boros resource (satu container per sekolah)
- ❌ Tidak scalable
- ❌ Tidak bisa menggunakan fitur SaaS boilerplate
- ❌ Security issues tetap ada

---

## 6. ROADMAP REKOMENDASI (OPSI A - REWRITE TOTAL)

### Phase 1: Foundation (Bulan 1-2)
- [ ] Setup Laravel 12 + Stancl/Tenancy + React/Inertia
- [ ] Desain ulang database schema (75 tabel → InnoDB, FK, indexes)
- [ ] Buat Laravel Migrations untuk semua tabel dengan tenant_id
- [ ] Setup tenant lifecycle (create → provision → migrate → seed)
- [ ] Implementasi authentication & authorization (Laravel Breeze/Sanctum)
- [ ] Setup multi-role system (RBAC) untuk 12 role SISFOKOL
- [ ] Setup file storage dengan tenant isolation
- [ ] CI/CD pipeline dasar

### Phase 2: Core Modules (Bulan 3-5)
- [ ] Modul Master Data (Siswa, Pegawai, Kelas, Mapel, Tahun Ajaran)
- [ ] Modul Absensi (Siswa & Pegawai) dengan QR Code support
- [ ] Modul Penilaian (Nilai, Raport, Kenaikan Kelas)
- [ ] Modul Jadwal (dengan conflict detection)
- [ ] Modul Keuangan (SPP, Pembayaran, Tunggakan, Nota)
- [ ] Modul Kurikulum Merdeka (TP, CP, Asesmen Formatif/Sumatif)

### Phase 3: Extended Modules (Bulan 6-8)
- [ ] Modul Inventaris/Sarpras (KIB A-F)
- [ ] Modul Ekstrakurikuler
- [ ] Modul Pelanggaran & Pembinaan (BK)
- [ ] Modul Perpustakaan
- [ ] Modul Nabung Siswa
- [ ] Modul Piket
- [ ] WhatsApp Integration
- [ ] PDF & Excel Export (migrasi dari existing code)

### Phase 4: Polish & Production (Bulan 9-10)
- [ ] Frontend UI/UX dengan Tailwind CSS + React
- [ ] Dashboard & analytics
- [ ] Reporting & export
- [ ] Testing suite (unit, integration, E2E)
- [ ] Performance optimization
- [ ] Security audit & penetration testing
- [ ] Documentation
- [ ] Deployment ke production

### Phase 5: SaaS Features (Bulan 11-12)
- [ ] Multi-tenant billing & subscription
- [ ] Tenant self-registration & onboarding
- [ ] Admin dashboard (super admin)
- [ ] Tenant impersonation
- [ ] Tenant analytics
- [ ] Feature toggling per tenant tier
- [ ] API documentation
- [ ] Mobile app (opsional)

---

## 7. ESTIMASI SUMBER DAYA

### Tim Minimum:
| Role | Jumlah | Catatan |
|------|--------|---------|
| Backend Developer (Laravel) | 2-3 | Senior level |
| Frontend Developer (React/Inertia) | 1-2 | |
| UI/UX Designer | 1 | |
| QA/Tester | 1 | |
| DevOps | 0.5 (part-time) | |
| Project Manager | 1 | |
| **Total** | **6-9 orang** | |

### Estimasi Biaya (asumsi):
| Item | Estimasi |
|------|----------|
| Development (12 bulan) | Rp 2.5 - 5 Miliar |
| Infrastructure (cloud) | Rp 5-15 juta/bulan |
| Testing & QA | Rp 200-500 juta |
| Documentation & Training | Rp 100-200 juta |
| **Total** | **Rp 3 - 6 Miliar** |

---

## 8. RISIKO DAN MITIGASI

| Risiko | Dampak | Probabilitas | Mitigasi |
|--------|--------|-------------|----------|
| Data loss saat migrasi | KRITIS | Sedang | Backup lengkap sebelum migrasi, dry-run testing |
| Security vulnerability dari kode lama | TINGGI | Tinggi | Rewrite total, security audit, penetration testing |
| Performance degradation | SEDANG | Sedang | Profiling, caching strategy, query optimization |
| User adoption rendah | TINGGI | Sedang | UI/UX yang familiar, training, documentation |
| Vendor lock-in ke Stancl/Tenancy | RENDAH | Rendah | Abstraction layer, multi-database strategy |
| Scope creep | TINGGI | Tinggi | Strict requirement management, phased delivery |
| Budget overrun | TINGGI | Sedang | Fixed-price contract dengan milestones |

---

## 9. KESIMPULAN DAN REKOMENDASI AKHIR

### Readiness Score: **2/10** (Sangat Rendah untuk direct SaaS adaptation)

SISFOKOL v7.00 adalah aplikasi yang **sangat matang secara fungsional** — memiliki fitur-fitur yang sesuai dengan kebutuhan sekolah di Indonesia. Namun, dari perspektif arsitektur dan kesiapan untuk diadaptasi menjadi SaaS multi-tenant, aplikasi ini memiliki **technical debt yang sangat besar**.

**Rekomendasi Utama:**
1. **JANGAN** langsung adaptasi kode SISFOKOL ke dalam SaaS boilerplate tanpa rewrite
2. **LAKUKAN** rewrite total menggunakan Laravel 12 + Stancl/Tenancy + React/Inertia
3. **GUNAKAN** SISFOKOL sebagai **referensi fungsional** (business logic, domain knowledge) bukan sebagai basis kode
4. **PRIORITASKAN** security overhaul (MD5 → bcrypt, string replacement → prepared statements)
5. **DESAIN ULANG** database schema dari MyISAM ke InnoDB dengan proper FK dan indexing

**Catatan Penting untuk Konteks Indonesia:**
- Sistem penilaian dan rapor harus mengikuti standar Kemendikbud/Kemenag terbaru
- Kurikulum Merdeka memerlukan support untuk TP (Tujuan Pembelajaran) dan CP (Capaian Pembelajaran)
- Integrasi dengan Dapodik (Data Pokok Pendidikan) perlu dipertimbangkan
- Support untuk berbagai jenjang (SD, SMP, SMA, SMK) memerlukan fleksibilitas schema
- Pembayaran SPP perlu support multi-metode (QRIS, VA, e-wallet)

---

*Dokumen ini disusun berdasarkan analisis mendalam terhadap kedua repository. Estimasi waktu dan biaya bersifat indikatif dan dapat berubah sesuai kebutuhan spesifik.*

*Disusun oleh: AI Professor/NLP/AI/Software Engineering Consultant*
*Tanggal: 17 Juni 2026*
