@extends('layouts.adminlte')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="m-0 text-dark">Kompetensi Kurikulum</h1>
            <a href="{{ route('evaluation.curriculum.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Tambah Kompetensi
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="icon fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card card-dark card-outline">
            <div class="card-body">
                <form method="GET" action="{{ route('evaluation.curriculum.index') }}" class="form-row align-items-center">
                    <div class="col-md-9 my-1">
                        <select name="mapel_id" class="form-control bg-dark text-white border-secondary">
                            <option value="">-- Pilih Mata Pelajaran (Semua) --</option>
                            @foreach($mapels as $mapel)
                                <option value="{{ $mapel->id }}" {{ $mapelId == $mapel->id ? 'selected' : '' }}>
                                    {{ $mapel->nama }} ({{ $mapel->kode }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 my-1">
                        <button type="submit" class="btn btn-secondary btn-block">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">Daftar Capaian / Kompetensi</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="pl-4" style="width: 15%">Kode</th>
                                <th style="width: 25%">Mata Pelajaran</th>
                                <th style="width: 15%">Fase</th>
                                <th style="width: 35%">Deskripsi</th>
                                <th class="pr-4" style="width: 10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($competencies as $c)
                                <tr>
                                    <td class="pl-4 font-weight-bold text-info">{{ $c->code }}</td>
                                    <td>{{ $c->subject?->name ?? $c->subject_id }}</td>
                                    <td><span class="badge badge-secondary">Fase {{ $c->phase }}</span></td>
                                    <td>{{ $c->description }}</td>
                                    <td class="pr-4">-</td>
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
    </div>
</section>
@endsection
