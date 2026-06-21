@extends('layouts.app')

@section('title', 'Keuangan — Laporan Keuangan')
@section('page-title', 'Laporan Keuangan & Pemasukan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-5 border-b border-slate-800 gap-4">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Laporan Rekap Keuangan</h1>
            <p class="text-sm text-slate-400 mt-1">Ringkasan pemasukan kasir dan rincian transaksi per periode.</p>
        </div>
        
        <!-- Period Filter Form -->
        <form method="GET" action="{{ route('finance.laporan.index') }}" class="flex flex-wrap items-center gap-3 bg-slate-900 border border-slate-800/80 p-2.5 rounded-2xl shadow-lg">
            <div class="flex items-center gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 bg-slate-950/60 border border-slate-800 rounded-xl text-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <span class="text-xs text-slate-500">s/d</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 bg-slate-950/60 border border-slate-800 rounded-xl text-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition">
                Terapkan
            </button>
        </form>
    </div>

    <!-- Stats Grid Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Widget 1: Total Pemasukan Period -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 text-7xl text-indigo-550/10 -mr-4 -mt-2">
                <i class="fas fa-coins"></i>
            </div>
            <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block mb-1">Pemasukan Periode Ini</span>
            <span class="text-2xl font-bold text-slate-200">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
            <p class="text-xs text-slate-500 mt-2">Pemasukan kasir dari {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        </div>

        <!-- Widget 2: Nominal Hari Ini -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 text-7xl text-emerald-550/10 -mr-4 -mt-2">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block mb-1">Nominal Hari Ini</span>
            <span class="text-2xl font-bold text-slate-200">Rp {{ number_format($nominalHariIni, 0, ',', '.') }}</span>
            <p class="text-xs text-slate-500 mt-2">Total perolehan tunai kasir khusus hari ini.</p>
        </div>

        <!-- Widget 3: Transaksi Hari Ini -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 text-7xl text-purple-550/10 -mr-4 -mt-2">
                <i class="fas fa-receipt"></i>
            </div>
            <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider block mb-1">Transaksi Hari Ini</span>
            <span class="text-2xl font-bold text-slate-200" x-text="'{{ $transaksiHariIni }} Kwitansi'">0 Kwitansi</span>
            <p class="text-xs text-slate-500 mt-2">Jumlah nota kwitansi pembayaran terbit hari ini.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Right: Pemasukan per Item breakdown (Col span 1) -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl h-fit">
            <h3 class="font-bold text-slate-250 text-sm border-b border-slate-800 pb-3 mb-4">Breakdown per Pos Pembayaran</h3>
            
            <div class="space-y-4">
                @forelse($pemasukanPerItem as $item)
                    <div class="flex items-center justify-between border-b border-slate-850/80 pb-3 last:border-b-0 last:pb-0">
                        <div>
                            <span class="font-medium text-slate-200 text-sm block">{{ $item->nama_item }}</span>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider block mt-0.5">{{ $item->jenis_item }}</span>
                        </div>
                        <span class="font-bold text-slate-300 text-sm">Rp {{ number_format($item->total_jumlah, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-500 text-sm">
                        <i class="fas fa-info-circle mb-2"></i>
                        <p>Tidak ada rincian pos pembayaran.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Left: Recent Transaksi (Col span 2) -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/20">
                <h3 class="font-bold text-slate-200 text-sm">10 Transaksi Pembayaran Terbaru</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-850 bg-slate-950/10">
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">No. Nota</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Waktu Nota</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/50">
                        @forelse($transaksiTerbaru as $tr)
                            <tr class="hover:bg-slate-850/30 transition text-sm">
                                <td class="px-6 py-3.5">
                                    <span class="font-semibold text-indigo-400">{{ $tr->no_nota }}</span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="font-medium text-slate-200">{{ $tr->siswa->nama }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">NIS: {{ $tr->siswa->nis }}</div>
                                </td>
                                <td class="px-6 py-3.5 text-slate-400">
                                    {{ $tr->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                </td>
                                <td class="px-6 py-3.5 font-bold text-slate-200">
                                    Rp {{ number_format($tr->total, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                    <p class="text-sm">Tidak ada riwayat transaksi dalam range tanggal ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
