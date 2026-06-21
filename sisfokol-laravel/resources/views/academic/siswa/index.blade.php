@extends('layouts.app')

@section('title', 'Akademik — Siswa')
@section('page-title', 'Manajemen Siswa')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Daftar Siswa</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola data siswa, pencarian, dan profil siswa secara terpusat.</p>
        </div>
        @can('create', App\Modules\Academic\Models\Siswa::class)
            <div>
                <a href="{{ route('academic.siswa.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm shadow-md shadow-indigo-600/20 transition">
                    <i class="fas fa-plus"></i> Tambah Siswa
                </a>
            </div>
        @endcan
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-slate-900/40 backdrop-blur-md border border-slate-800/60 rounded-2xl p-5 shadow-lg">
        <form method="GET" action="{{ route('academic.siswa.index') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN..." class="w-full pl-10 pr-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-sm font-medium transition flex items-center gap-2 border border-slate-700">
                    Filter
                </button>
                @if($search)
                    <a href="{{ route('academic.siswa.index') }}" class="px-5 py-2.5 bg-slate-950/20 hover:bg-slate-950/40 text-slate-400 hover:text-slate-300 rounded-xl text-sm font-medium transition flex items-center gap-2 border border-slate-800/80">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800/60">
                        <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider">NIS / NISN</th>
                        <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider">L/P</th>
                        @field('siswa.telepon')
                            <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Telepon</th>
                        @endfield
                        <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($siswa as $s)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="p-4">
                                <span class="text-sm font-semibold text-slate-200 block">{{ $s->nis }}</span>
                                <span class="text-xs text-slate-500">{{ $s->nisn ?? '-' }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-200 font-medium block">{{ $s->nama }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-300">{{ $s->jenis_kelamin }}</span>
                            </td>
                            @field('siswa.telepon')
                                <td class="p-4">
                                    <span class="text-sm text-slate-300">{{ $s->telepon ?? '-' }}</span>
                                </td>
                            @endfield
                            <td class="p-4">
                                @if($s->status === 'aktif')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-950/40 text-emerald-400 border border-emerald-900/50">Aktif</span>
                                @elseif($s->status === 'nonaktif')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-rose-950/40 text-rose-400 border border-rose-900/50">Nonaktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700/60">{{ ucfirst($s->status) }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @can('view', $s)
                                        <a href="{{ route('academic.siswa.show', $s) }}" class="p-2 bg-slate-850 hover:bg-slate-800 text-slate-300 rounded-lg border border-slate-800 transition" title="Detail">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                    @endcan
                                    @can('update', $s)
                                        <a href="{{ route('academic.siswa.edit', $s) }}" class="p-2 bg-indigo-950/40 hover:bg-indigo-900/40 text-indigo-400 rounded-lg border border-indigo-900/50 transition" title="Edit">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $s)
                                        <form method="POST" action="{{ route('academic.siswa.destroy', $s) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-950/20 hover:bg-rose-900/20 text-rose-400 rounded-lg border border-rose-900/50 transition" title="Hapus">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-sm text-slate-500">
                                Tidak ada data siswa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($siswa->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/20">
                {{ $siswa->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
