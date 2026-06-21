@extends('layouts.app')

@section('title', 'Aksi Diblokir')
@section('page-title', 'Aksi Diblokir')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="rounded-3xl bg-slate-900 border border-slate-800/80 shadow-2xl p-8 text-center relative overflow-hidden">
        <div class="absolute top-0 inset-x-0 h-1.5 bg-rose-600"></div>
        
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-500/10 text-rose-500 mx-auto text-2xl mb-6">
            <i class="fas fa-ban"></i>
        </div>

        <h3 class="text-xl font-bold text-slate-100">Tindakan Keamanan Aktif</h3>
        <p class="text-slate-400 text-sm mt-3 leading-relaxed">
            Anda sedang berada dalam mode <strong>impersonation</strong> (login sebagai pengguna lain). 
            Demi alasan keamanan, tindakan penulisan data sensitif seperti membuat/mengubah pengguna, 
            mengatur peran/izin, mengganti kata sandi, atau mengubah pengaturan plugin dinonaktifkan.
        </p>

        <div class="mt-8 flex flex-col gap-3">
            <a href="{{ route('dashboard') }}" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium rounded-xl py-2.5 px-4 text-sm transition border border-slate-750">
                <i class="fas fa-home mr-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
