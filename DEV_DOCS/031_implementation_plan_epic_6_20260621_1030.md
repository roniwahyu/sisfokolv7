# DEV_DOCS-031: Rencana Implementasi — Epic 6: Evaluation Module (Penilaian & Rapor)

- **Tanggal:** 2026-06-21 10:30
- **Status:** ⏳ PENDING IMPLEMENTASI
- **Penulis:** Antigravity (Google DeepMind)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith

---

## 🛡️ KONTEKS & KEPUTUSAN ARSITEKTUR

### Struktur Data yang Sudah Ada
Tabel-tabel evaluasi **sudah ada** di database (dibuat dari migrasi legacy), namun belum memiliki:
- Kolom `tenant_id`, `created_by`, `updated_by` (multi-tenancy + audit)
- Trait `BelongsToTenant` dan `TracksAuditColumns` pada model
- Service layer untuk kalkulasi nilai otomatis
- Controllers, Policies, dan Blade Views

Tabel yang relevan:
| Tabel | Fungsi |
|-------|--------|
| `formative_assessments` | Header penilaian harian / tugas / kuis |
| `formative_assessment_scores` | Nilai per siswa per penilaian formatif |
| `summative_assessments` | Header penilaian UTS/UAS |
| `summative_assessment_scores` | Nilai per siswa per penilaian sumatif |
| `student_monthly_scores` | Nilai bulanan per siswa per mapel |
| `student_semester_scores` | Nilai akhir semester per siswa per mapel |
| `student_yearly_scores` | Nilai akhir tahun per siswa per mapel |
| `curriculum_competencies` | Capaian Pembelajaran (CP) per mapel |
| `curriculum_learning_materials` | Materi ajar dari CP |
| `subject_descriptions` | Deskripsi naratif rapor per mapel |
| `report_notes` | Catatan wali kelas di rapor |

### Keputusan Desain
1. **Multi-Tenant via Migration Alter**: Tambahkan `tenant_id` ke semua tabel evaluasi melalui migrasi alter (tidak drop-recreate).
2. **GradeCalculatorService**: Service terpusat untuk menghitung nilai akhir semester dari gabungan formatif + sumatif berdasarkan bobot yang dikonfigurasi.
3. **GradeEntryController**: Guru memasukkan nilai per mapel, per kelas, per siswa menggunakan UI grid nilai (tabel interaktif Alpine.js).
4. **RaporGeneratorService**: Generate rapor PDF (DomPDF) per siswa yang mengambil nilai semester, deskripsi, dan catatan wali kelas.
5. **TeacherAgenda Integration**: GradeEntry terhubung ke `TeacherAgenda` sehingga guru hanya bisa entri nilai untuk kelas yang diampu.

---

## 📁 STRUKTUR FILE YANG AKAN DIBUAT/DIUBAH

```
app/Modules/Evaluation/
├── Database/Migrations/
│   ├── 2026_06_21_000200_add_tenant_to_formative_assessments.php
│   ├── 2026_06_21_000201_add_tenant_to_summative_assessments.php
│   ├── 2026_06_21_000202_add_tenant_to_student_scores_tables.php
│   └── 2026_06_21_000203_add_tenant_to_curriculum_tables.php
│
├── Services/
│   ├── GradeCalculatorService.php     (hitung nilai akhir dari formatif + sumatif)
│   └── RaporGeneratorService.php      (generate PDF rapor per siswa)
│
├── Controllers/
│   ├── GradeEntryController.php       (entri nilai per kelas + mapel oleh guru)
│   ├── RaporController.php            (preview & download rapor PDF)
│   └── CurriculumController.php       (CRUD Capaian Pembelajaran & Materi Ajar)
│
├── Policies/
│   ├── GradePolicy.php
│   └── RaporPolicy.php
│
├── Requests/
│   ├── StoreGradeRequest.php
│   └── BatchGradeRequest.php
│
└── routes.php

resources/views/evaluation/
├── grade-entry/
│   ├── index.blade.php   (pilih kelas & mapel)
│   └── form.blade.php    (grid nilai siswa interaktif)
├── rapor/
│   ├── index.blade.php   (daftar siswa + tombol preview/download)
│   ├── show.blade.php    (preview rapor)
│   └── pdf.blade.php     (template rapor untuk DomPDF)
└── curriculum/
    ├── index.blade.php
    └── form.blade.php

tests/Feature/Evaluation/
├── GradeCalculatorTest.php
└── RaporGeneratorTest.php
```

---

## 📝 TAHAPAN IMPLEMENTASI

### Task 1: Migrasi Alter — Tambahkan Kolom Tenant & Audit
- Alter 8 tabel evaluasi: tambah `tenant_id`, `created_by`, `updated_by`
- Update model `FormativeAssessment`, `SummativeAssessment`, `StudentMonthlyScore`, `StudentSemesterScore`, `StudentYearlyScore`, `CurriculumCompetency`, `SubjectDescription`, `ReportNote` → tambah trait `BelongsToTenant` + `TracksAuditColumns`
- Jalankan `php83 artisan migrate`

### Task 2: GradeCalculatorService + Tests
- Logika kalkulasi: `Nilai Akhir = (rata_formatif × 0.40) + (rata_sumatif × 0.60)`
- Support konfigurasi bobot per sekolah melalui `tenant_settings`
- Auto-generate predikat: A (90-100), B (80-89), C (70-79), D (<70)
- Unit test `tests/Feature/Evaluation/GradeCalculatorTest.php` (6 test cases)

### Task 3: GradeEntryController + Grid UI
- Controller dengan endpoint: `index`, `form` (GET per kelas+mapel), `store` (batch POST)
- Blade view `grade-entry/form.blade.php`: tabel siswa × kolom nilai, editable inline dengan Alpine.js, auto-save via AJAX (debounce 500ms)
- Validasi: skor 0–100, wajib integer

### Task 4: RaporGeneratorService + PDF Export
- Service mengagregasi: nilai per mapel, predikat, deskripsi naratif, catatan wali kelas, data kehadiran dari modul Presence
- Template rapor PDF menggunakan `barryvdh/laravel-dompdf` dengan layout A4 resmi
- Controller `RaporController`: preview HTML + download PDF
- Feature test: generate rapor untuk 1 siswa

### Task 5: CurriculumController (CP & Materi Ajar)
- CRUD `CurriculumCompetency` (Capaian Pembelajaran per fase/mapel)
- CRUD `CurriculumLearningMaterial` (sub-materi dari CP)
- Views dengan tampilan hierarkis (collapse/expand per CP)

---

## 📦 DEPENDENSI BARU

```bash
composer require barryvdh/laravel-dompdf
```

---

## 📈 RENCANA VERIFIKASI

### Pengujian Otomatis
```powershell
php83 artisan test tests/Feature/Evaluation/GradeCalculatorTest.php
php83 artisan test tests/Feature/Evaluation/RaporGeneratorTest.php
php83 artisan test   # full suite — target 90+ tests green
```

### Verifikasi Manual
1. Login sebagai `guru.demo` → buka `/evaluation/grade-entry` → pilih kelas & mapel → entri nilai 5 siswa → submit
2. Verifikasi nilai tersimpan di `formative_assessment_scores` + auto-hitung nilai semester
3. Login sebagai `walikelas.demo` → buka `/evaluation/rapor/{siswa}` → preview rapor HTML → download PDF
4. Verifikasi PDF berisi: nama siswa, kelas, semua nilai mapel, predikat, deskripsi, tanda tangan kepala sekolah
