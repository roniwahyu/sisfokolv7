# DEV_DOCS-049: Implementation Plan — Perbaikan Gap Epic 1 (Setup & Fondasi)

- **Tanggal:** 2026-06-22
- **Status:** ⏳ PENDING APPROVAL (Menunggu Persetujuan User)
- **Penulis:** Antigravity (Google DeepMind pair-agent)
- **Proyek:** Konversi SISFOKOL v7 (PHP native) → Laravel 11 modular monolith
- **Tujuan:** Dokumen rencana perbaikan untuk menyelesaikan seluruh temuan gap kritis pada Epic 1 (Setup & Fondasi).

---

## ⚡ 1. RINGKASAN TINDAKAN PERBAIKAN

Berdasarkan audit fungsional, kita akan menerapkan 4 perbaikan taktis berikut:

1. **Membuat `CurriculumController` & Views**: Menyediakan CRUD dasar untuk `CurriculumCompetency` agar rute `/evaluation/curriculum` tidak memicu crash runtime.
2. **Sinkronisasi Permission & Menu**: Mendaftarkan permission bahasa Indonesia di `RolePermissionSeeder.php` agar menu navigasi dapat tampil di sidebar masing-masing peran pengguna (*roles*).
3. **SuperAdmin Bypass**: Menyuntikkan `Gate::before` ke `AuthServiceProvider.php` agar SuperAdmin secara global mem-bypass semua cek permission.
4. **FQCN Namespace di FieldSeeder**: Memperbarui kolom model pada `FieldSeeder.php` menggunakan Fully Qualified Class Name yang tepat.

---

## 📁 2. DAFTAR PERUBAHAN BERKAS

### 2.1 Modul Evaluation
#### [NEW] [CurriculumController.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Modules/Evaluation/Controllers/CurriculumController.php)
Membuat controller kurikulum baru untuk melayani rute kurikulum core agar tidak crash:
```php
<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CurriculumCompetency;
use App\Modules\Academic\Models\Mapel;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function index(Request $request)
    {
        $mapelId = $request->get('mapel_id');
        $competencies = CurriculumCompetency::when($mapelId, fn($q) => $q->where('subject_id', $mapelId))
            ->with(['subject'])
            ->get();
        $mapels = Mapel::all();

        return view('evaluation.curriculum.index', compact('competencies', 'mapels', 'mapelId'));
    }

    public function create()
    {
        $mapels = Mapel::all();
        return view('evaluation.curriculum.create', compact('mapels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:mapel,id',
            'phase' => 'required|string|max:10',
            'code' => 'required|string|max:50',
            'description' => 'required|string',
        ]);

        $academicYear = \App\Models\AcademicYear::active();

        CurriculumCompetency::create(array_merge($validated, [
            'academic_year_id' => $academicYear?->id,
        ]));

        return redirect()->route('evaluation.curriculum.index')->with('success', 'Kompetensi kurikulum berhasil ditambahkan.');
    }
}
```

#### [NEW] [index.blade.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/resources/views/evaluation/curriculum/index.blade.php)
Halaman daftar kompetensi kurikulum dengan desain dark theme modern:
```html
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white">Kompetensi Kurikulum</h2>
        <a href="{{ route('evaluation.curriculum.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Tambah Kompetensi
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card bg-dark text-white border-secondary mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('evaluation.curriculum.index') }}" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <select name="mapel_id" class="form-select bg-secondary text-white border-secondary">
                        <option value="">-- Pilih Mata Pelajaran (Semua) --</option>
                        @foreach($mapels as $mapel)
                            <option value="{{ $mapel->id }}" {{ $mapelId == $mapel->id ? 'selected' : '' }}>
                                {{ $mapel->nama }} ({{ $mapel->kode }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-dark text-white border-secondary">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Kode</th>
                        <th>Mata Pelajaran</th>
                        <th>Fase</th>
                        <th>Deskripsi</th>
                        <th class="pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competencies as $c)
                        <tr>
                            <td class="ps-4 font-mono text-info">{{ $c->code }}</td>
                            <td>{{ $c->subject?->name ?? $c->subject_id }}</td>
                            <td><span class="badge bg-secondary">Fase {{ $c->phase }}</span></td>
                            <td>{{ $c->description }}</td>
                            <td class="pe-4">-</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Tidak ada kompetensi kurikulum yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

#### [NEW] [create.blade.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/resources/views/evaluation/curriculum/create.blade.php)
Form tambah kompetensi kurikulum:
```html
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Tambah Kompetensi Kurikulum</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('evaluation.curriculum.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="subject_id" class="form-select bg-secondary text-white border-secondary @error('subject_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($mapels as $mapel)
                                    <option value="{{ $mapel->id }}" {{ old('subject_id') == $mapel->id ? 'selected' : '' }}>
                                        {{ $mapel->nama }} ({{ $mapel->kode }})
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fase (e.g. A, B, C, D, E, F)</label>
                            <input type="text" name="phase" class="form-control bg-secondary text-white border-secondary @error('phase') is-invalid @enderror" value="{{ old('phase') }}" required>
                            @error('phase')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Kompetensi (e.g. CP-01)</label>
                            <input type="text" name="code" class="form-control bg-secondary text-white border-secondary @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Kompetensi</label>
                            <textarea name="description" rows="4" class="form-control bg-secondary text-white border-secondary @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('evaluation.curriculum.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

### 2.2 Seeder & Authorization

#### [MODIFY] [RolePermissionSeeder.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/database/seeders/RolePermissionSeeder.php)
Mendaftarkan permission bahasa Indonesia untuk menu sidebar navigasi, serta memetakan permission tersebut ke peran (`roles`) masing-masing pengguna agar menu muncul.

#### [MODIFY] [FieldSeeder.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/database/seeders/FieldSeeder.php)
Mengubah penulisan nama model singkat menjadi nama kelas FQCN yang valid:
- `'Siswa'` $\rightarrow$ `'App\Modules\Academic\Models\Siswa'`
- `'OrangTua'` $\rightarrow$ `'App\Modules\Academic\Models\OrangTua'`
- `'TagihanSiswa'` $\rightarrow$ `'App\Modules\Finance\Models\TagihanSiswa'`
- `'Pembayaran'` $\rightarrow$ `'App\Modules\Finance\Models\Pembayaran'`
- `'TabunganSiswa'` $\rightarrow$ `'App\Modules\Finance\Models\TabunganSiswa'`

#### [MODIFY] [AuthServiceProvider.php](file:///d:/laragon/www/sisfokolv7/sisfokol-laravel/app/Providers/AuthServiceProvider.php)
Menambahkan global bypass pada Gate agar SuperAdmin memiliki akses penuh tanpa error permission check:
```diff
     public function boot(): void
     {
-        //
+        $this->registerPolicies();
+
+        \Illuminate\Support\Facades\Gate::before(function (User $user) {
+            if ($user->isSuperAdmin()) {
+                return true;
+            }
+        });
     }
```

---

## ⚡ 3. METODE VERIFIKASI (Definition of Done)

Perbaikan ini dianggap selesai jika:
1. **Aplikasi Bisa Boot Tanpa Crash**: Menjalankan server lokal `php83 artisan serve` berjalan sukses dan dapat dirender.
2. **Menu Sidebar Tampil**: Login sebagai Admin, Guru, atau Siswa $\rightarrow$ Menu navigasi di sidebar muncul sesuai hak akses role.
3. **SuperAdmin Bebas Akses**: SuperAdmin dapat mengakses semua halaman yang memiliki pengamanan middleware `permission:xxx`.
4. **Halaman Kurikulum Fungsional**: Halaman `/evaluation/curriculum` dapat diakses dan form tambah data dapat menyimpan kompetensi baru ke database.
5. **Semua Tes Hijau**: Menjalankan `php83 artisan test` lulus 100%.
