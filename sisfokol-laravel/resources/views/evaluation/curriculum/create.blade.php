@extends('layouts.adminlte')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Tambah Kompetensi Kurikulum</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Form Baru</h3>
                    </div>
                    <form method="POST" action="{{ route('evaluation.curriculum.store') }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label>Mata Pelajaran</label>
                                <select name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
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

                            <div class="form-group mb-3">
                                <label>Fase (e.g. A, B, C, D, E, F)</label>
                                <input type="text" name="phase" class="form-control @error('phase') is-invalid @enderror" value="{{ old('phase') }}" placeholder="Fase E" required>
                                @error('phase')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label>Kode Kompetensi (e.g. CP-01)</label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="CP-01" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label>Deskripsi Kompetensi</label>
                                <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Menjelaskan konsep matematika..." required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route('evaluation.curriculum.index') }}" class="btn btn-default">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
