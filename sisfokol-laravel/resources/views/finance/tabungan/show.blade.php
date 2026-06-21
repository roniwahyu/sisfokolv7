@extends('layouts.app')

@section('title', 'Keuangan — Detail Rekening Tabungan')
@section('page-title', 'Detail Rekening Tabungan')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Rekening Tabungan: {{ $tabungan->no_rekening }}</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola transaksi setoran & penarikan untuk tabungan siswa.</p>
        </div>
        <a href="{{ route('finance.tabungan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 rounded-xl text-sm font-medium transition border border-slate-700">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Alert Status -->
    @if(session('success'))
        <div class="p-4 bg-emerald-950/30 border border-emerald-800/60 rounded-xl text-emerald-400 text-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-950/30 border border-rose-800/60 rounded-xl text-rose-400 text-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Account info (Col span 1) -->
        <div class="space-y-6">
            <!-- Balance Card -->
            <div class="bg-gradient-to-br from-indigo-900/60 to-purple-900/60 border border-indigo-700/30 rounded-2xl p-6 shadow-xl text-white relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 text-8xl -mr-6 -mb-6">
                    <i class="fas fa-wallet"></i>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-200/80 block mb-1">Saldo Rekening</span>
                <span class="text-3xl font-extrabold tracking-tight">Rp {{ number_format($tabungan->saldo, 0, ',', '.') }}</span>
                
                <div class="mt-8 pt-4 border-t border-indigo-700/20 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase text-indigo-300/80 block">No. Rekening</span>
                        <span class="font-mono font-bold text-sm tracking-wide mt-0.5 block">{{ $tabungan->no_rekening }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-indigo-300/80 block">Nama Siswa</span>
                        <span class="font-bold text-sm truncate mt-0.5 block" title="{{ $tabungan->siswa->nama }}">{{ $tabungan->siswa->nama }}</span>
                    </div>
                </div>
            </div>

            <!-- Student Profile Detail -->
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-slate-200 text-sm border-b border-slate-800 pb-3 flex items-center gap-2">
                    <i class="fas fa-user text-indigo-400"></i> Informasi Siswa
                </h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500 block text-xs">Nama Lengkap</span>
                        <span class="text-slate-300 font-medium">{{ $tabungan->siswa->nama }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">NIS</span>
                        <span class="text-slate-300 font-mono font-medium">{{ $tabungan->siswa->nis }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">Kelas</span>
                        <span class="text-slate-300 font-medium">{{ $tabungan->siswa->kelasSiswa->first()->kelas->nama ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">Status</span>
                        <span class="text-emerald-400 capitalize font-medium text-xs">{{ $tabungan->siswa->status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Setor & Tarik form sections (Col span 2) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Setor (Deposit) Card -->
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
                <h3 class="font-bold text-slate-200 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-arrow-alt-circle-down text-emerald-400"></i> Setor Tunai Tabungan
                </h3>
                
                <form method="POST" action="{{ route('finance.tabungan.setor', $tabungan->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="setor_nominal" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nominal Setoran (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 text-sm">Rp</span>
                            <input type="number" name="nominal" id="setor_nominal" placeholder="Masukkan jumlah setor..." required min="1" class="w-full pl-11 pr-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        </div>
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-emerald-600/20 flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Proses Setor
                    </button>
                </form>
            </div>

            <!-- Tarik (Withdrawal) Card -->
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
                <h3 class="font-bold text-slate-200 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-arrow-alt-circle-up text-rose-450"></i> Tarik Tunai Tabungan
                </h3>
                
                <form method="POST" action="{{ route('finance.tabungan.tarik', $tabungan->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="tarik_nominal" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nominal Penarikan (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 text-sm">Rp</span>
                            <input type="number" name="nominal" id="tarik_nominal" placeholder="Masukkan jumlah tarik..." required min="1" max="{{ $tabungan->saldo }}" class="w-full pl-11 pr-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">* Batas maksimum penarikan saat ini adalah Rp {{ number_format($tabungan->saldo, 0, ',', '.') }}</span>
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-rose-600/20 flex items-center gap-2">
                        <i class="fas fa-minus-circle"></i> Proses Tarik
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
