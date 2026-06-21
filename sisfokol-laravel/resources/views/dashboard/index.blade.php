@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome card -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800/80 p-8 shadow-2xl">
        <div class="relative z-10">
            <h3 class="text-2xl font-bold text-slate-100">Selamat datang kembali, {{ $user->nama }}!</h3>
            <p class="text-slate-400 mt-2 max-w-xl">
                Anda masuk sebagai role <span class="text-indigo-400 font-semibold">{{ $user->getRoleNames()->implode(', ') ?: 'User' }}</span>.
                SISFOKOL v7 modular monolith siap membantu mempermudah manajemen sekolah Anda.
            </p>

            @if($isSuperAdmin)
                <div class="mt-6 flex items-start gap-3 p-4 rounded-2xl bg-indigo-950/40 border border-indigo-900/60 text-indigo-300 text-sm max-w-2xl">
                    <i class="fas fa-info-circle text-lg mt-0.5"></i>
                    <div>
                        <span class="font-semibold block mb-0.5">Hak Akses SuperAdmin Aktif</span>
                        Anda memiliki hak akses global penuh atas seluruh platform multi-tenant. Seluruh konfigurasi sistem dan audit log global dapat diakses langsung.
                    </div>
                </div>
            @endif
        </div>
        <!-- Decorative subtle background circle -->
        <div class="absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-indigo-600/10 blur-2xl pointer-events-none"></div>
    </div>

    <!-- Quick info / placeholders -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-2xl bg-slate-900 border border-slate-800/60 p-6 shadow-xl flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl">
                <i class="fas fa-school"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Tenant Aktif</p>
                <h4 class="text-lg font-bold text-slate-200 mt-0.5">{{ $user->tenant?->nama ?? 'SuperAdmin (Global)' }}</h4>
            </div>
        </div>

        <div class="rounded-2xl bg-slate-900 border border-slate-800/60 p-6 shadow-xl flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Login Terakhir</p>
                <h4 class="text-sm font-bold text-slate-200 mt-1">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Baru saja' }}</h4>
            </div>
        </div>

        <div class="rounded-2xl bg-slate-900 border border-slate-800/60 p-6 shadow-xl flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Status Keamanan</p>
                <h4 class="text-sm font-bold text-slate-200 mt-1">Terlindungi (Bcrypt)</h4>
            </div>
        </div>
    </div>
</div>
@endsection
