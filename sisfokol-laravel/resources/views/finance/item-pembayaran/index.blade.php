@extends('layouts.app')

@section('title', 'Keuangan — Master Pembayaran')
@section('page-title', 'Master Pos Pembayaran')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Master Pos Pembayaran</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola master jenis pembayaran sekolah per tahun ajaran.</p>
        </div>
        <a href="{{ route('finance.item-pembayaran.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-medium transition shadow-md shadow-indigo-600/20">
            <i class="fas fa-plus"></i> Tambah Pos Pembayaran
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
        <form method="GET" action="{{ route('finance.item-pembayaran.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama pos pembayaran..." class="w-full px-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
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
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Pos</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Tahun Ajaran</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Jenis</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Nominal</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Periode</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-200">{{ $item->nama }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-350 text-sm">
                                {{ $item->tahunAjaran->nama ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-950/40 text-indigo-400 border border-indigo-900/50 uppercase">
                                    {{ $item->jenis }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-200 text-sm">
                                Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-slate-350 text-sm capitalize">
                                {{ $item->periode }}
                            </td>
                            <td class="px-6 py-4">
                                @if($item->aktif)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-950/40 text-emerald-400 border border-emerald-900/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('finance.item-pembayaran.edit', $item->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-slate-100 rounded-lg text-xs transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('finance.item-pembayaran.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pos pembayaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-rose-950/30 hover:bg-rose-900/50 border border-rose-900/40 text-rose-400 hover:text-rose-200 rounded-lg text-xs transition" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-info-circle text-2xl mb-3"></i>
                                <p class="text-sm">Tidak ada pos pembayaran yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/10">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
