@extends('layouts.app')

@section('title', 'Keuangan — Daftar Tagihan Siswa')
@section('page-title', 'Daftar Tagihan Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Daftar Tagihan Siswa</h1>
            <p class="text-sm text-slate-400 mt-1">Daftar kewajiban pembayaran siswa aktif per periode.</p>
        </div>
        <a href="{{ route('finance.tagihan.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-medium transition shadow-md shadow-indigo-600/20">
            <i class="fas fa-magic"></i> Generate Tagihan Bulanan
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
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
        <form method="GET" action="{{ route('finance.tagihan.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Cari Siswa</label>
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Nama / NIS..." class="w-full px-4 py-2 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
            </div>

            <!-- Kelas -->
            <div>
                <label for="kelas_id" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Kelas</label>
                <select name="kelas_id" id="kelas_id" class="w-full px-4 py-2 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ $kelasId == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Lunas -->
            <div>
                <label for="lunas" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Status Pembayaran</label>
                <select name="lunas" id="lunas" class="w-full px-4 py-2 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="0" {{ $lunas === '0' ? 'selected' : '' }}>Belum Lunas / Kurang</option>
                    <option value="1" {{ $lunas === '1' ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('finance.tagihan.index') }}" class="py-2 px-3.5 bg-slate-950/30 hover:bg-slate-800 border border-slate-850 text-slate-400 rounded-xl text-sm font-medium transition flex items-center justify-center" title="Reset Filter">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/20">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Tagihan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Bulan/Periode</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Nominal Tagihan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Dibayar</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Sisa / Kurang</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($tagihan as $t)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-200">{{ $t->siswa->nama }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">NIS: {{ $t->siswa->nis }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-sm">
                                {{ $t->itemPembayaran->nama }}
                            </td>
                            <td class="px-6 py-4 text-slate-350 text-sm">
                                @if($t->itemPembayaran->periode === 'bulanan')
                                    {{ carbon_month_name($t->bulan) }}
                                @else
                                    Sekali Bayar
                                @endif
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $t->tahunAjaran->nama }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-200 text-sm">
                                Rp {{ number_format($t->nominal_tagihan, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-emerald-450 text-sm font-medium">
                                Rp {{ number_format($t->nominal_bayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold {{ $t->lunas ? 'text-slate-500 line-through' : 'text-rose-400' }}">
                                Rp {{ number_format($t->nominal_kurang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($t->lunas)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-950/40 text-emerald-400 border border-emerald-900/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Lunas
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-950/30 text-rose-450 border border-rose-900/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Belum Lunas
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-info-circle text-2xl mb-3"></i>
                                <p class="text-sm">Tidak ada data tagihan yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tagihan->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/10">
                {{ $tagihan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
