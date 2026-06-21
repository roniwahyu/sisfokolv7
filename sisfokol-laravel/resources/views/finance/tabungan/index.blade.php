@extends('layouts.app')

@section('title', 'Keuangan — Tabungan Siswa')
@section('page-title', 'Tabungan Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Rekening Tabungan Siswa</h1>
            <p class="text-sm text-slate-400 mt-1">Daftar saldo dan nomor rekening tabungan siswa aktif.</p>
        </div>
        <a href="{{ route('finance.tabungan.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-medium transition shadow-md shadow-indigo-600/20">
            <i class="fas fa-user-plus"></i> Buka Rekening Baru
        </a>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 bg-emerald-950/30 border border-emerald-800/60 rounded-xl text-emerald-400 text-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-4 shadow-xl">
        <form method="GET" action="{{ route('finance.tabungan.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa atau nomor rekening..." class="w-full px-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> Cari
            </button>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/20">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">No. Rekening</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Saldo Terkumpul</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($tabungan as $t)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-mono font-semibold text-indigo-405 text-sm">{{ $t->no_rekening }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-200">{{ $t->siswa->nama }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">NIS: {{ $t->siswa->nis }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-350 text-sm">
                                {{ $t->siswa->kelasSiswa->first()->kelas->nama ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-200 text-sm">
                                Rp {{ number_format($t->saldo, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('finance.tabungan.show', $t->id) }}" class="inline-flex items-center gap-1 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition">
                                    <i class="fas fa-folder-open text-indigo-400"></i> Mutasi & Setor/Tarik
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-info-circle text-2xl mb-3"></i>
                                <p class="text-sm">Tidak ada rekening tabungan siswa yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tabungan->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/10">
                {{ $tabungan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
