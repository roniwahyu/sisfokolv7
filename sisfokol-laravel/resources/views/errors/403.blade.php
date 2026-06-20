@extends('layouts.adminlte')

@section('title', 'Akses Ditolak')
@section('page-title', '403 - Akses Ditolak')

@section('content')
    <div class="error-page">
        <h2 class="headline text-warning">403</h2>
        <div class="error-content">
            <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Akses ditolak.</h3>
            <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    </div>
@endsection
