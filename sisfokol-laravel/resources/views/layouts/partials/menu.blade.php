@php
    $user = auth()->user();
@endphp

<!-- Base link -->
<a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-950 text-indigo-400 border border-indigo-900/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200' }}">
    <i class="fas fa-home w-5 text-center text-base"></i>
    <span>Beranda</span>
</a>

@if ($user->hasRole('admin') || $user->hasRole('super_admin'))
    <div class="pt-4 pb-2 px-4">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Administrasi Platform</p>
    </div>

    <a href="{{ route('audit.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition {{ request()->routeIs('audit.*') ? 'bg-indigo-950 text-indigo-400 border border-indigo-900/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200' }}">
        <i class="fas fa-history w-5 text-center text-base"></i>
        <span>Audit Log</span>
    </a>

    <!-- Admin Submenus -->
    <div x-data="{ open: {{ request()->routeIs('admin.*') ? 'true' : 'false' }} }">
        <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition">
            <div class="flex items-center gap-3">
                <i class="fas fa-cogs w-5 text-center text-base"></i>
                <span>Konfigurasi Sekolah</span>
            </div>
            <i class="fas text-[10px] transition-transform duration-200" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        
        <div x-show="open" class="mt-1 pl-8 space-y-1" x-cloak>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Pengguna</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Tahun Pelajaran</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Kelas</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Mapel</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Jadwal</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Waktu Presensi</a>
            <a href="#" class="block py-2 text-sm text-slate-400 hover:text-slate-200 transition">Profil Sekolah</a>
        </div>
    </div>
@endif

@if ($user->hasRole('teacher'))
    <div class="pt-4 pb-2 px-4">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Guru</p>
    </div>
    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition">
        <i class="fas fa-clipboard w-5 text-center text-base"></i>
        <span>Jurnal Mengajar</span>
    </a>
    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition">
        <i class="fas fa-bullseye w-5 text-center text-base"></i>
        <span>Tujuan Pembelajaran</span>
    </a>
    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition">
        <i class="fas fa-qrcode w-5 text-center text-base"></i>
        <span>Scan Presensi</span>
    </a>
@endif

@if ($user->hasRole('student'))
    <div class="pt-4 pb-2 px-4">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Siswa</p>
    </div>
    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition">
        <i class="fas fa-user-graduate w-5 text-center text-base"></i>
        <span>Dashboard Siswa</span>
    </a>
@endif
