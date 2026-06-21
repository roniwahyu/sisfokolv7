@extends('layouts.app')

@section('title', 'Daftar Izin — SISFOKOL')
@section('page-title', '📋 Daftar Pengajuan Izin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Pengajuan Izin</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola izin sakit dan keperluan siswa</p>
        </div>
        @can('create', \App\Models\Permit::class)
        <a href="{{ route('presence.izin.create') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold shadow-lg shadow-indigo-500/20 transition">
            <i class="fas fa-plus-circle"></i> Ajukan Izin Baru
        </a>
        @endcan
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('presence.izin.index') }}"
        class="rounded-2xl bg-slate-900/80 border border-slate-800 p-4 backdrop-blur-sm flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Status</label>
            <select name="status"
                class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <option value="">Semua</option>
                <option value="pending"  @selected(request('status') === 'pending')>Menunggu</option>
                <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
            </select>
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Tanggal</label>
            <input type="date" name="date" value="{{ request('date') }}"
                class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['status', 'date']))
            <a href="{{ route('presence.izin.index') }}"
                class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm transition flex items-center gap-2">
                <i class="fas fa-times"></i> Reset
            </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="rounded-3xl bg-slate-900/80 border border-slate-800 overflow-hidden backdrop-blur-sm shadow-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Jenis</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Alasan</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($permits as $permit)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                    {{ substr($permit->permitable?->nama ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-100">{{ $permit->permitable?->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $permit->permitable?->nis ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-300">{{ $permit->date?->format('d M Y') }}</td>
                        <td class="px-5 py-4">
                            @php $typeMap = ['sick' => ['label' => 'Sakit', 'icon' => 'thermometer-half', 'class' => 'bg-rose-950/50 text-rose-400 border-rose-800/60'], 'permission' => ['label' => 'Keperluan', 'icon' => 'briefcase', 'class' => 'bg-blue-950/50 text-blue-400 border-blue-800/60'], 'other' => ['label' => 'Lainnya', 'icon' => 'ellipsis-h', 'class' => 'bg-slate-800 text-slate-400 border-slate-700']]; $t = $typeMap[$permit->type] ?? $typeMap['other']; @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $t['class'] }}">
                                <i class="fas fa-{{ $t['icon'] }}"></i> {{ $t['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-400 max-w-xs truncate">{{ $permit->reason }}</td>
                        <td class="px-5 py-4">
                            @php $statusMap = ['pending' => ['label' => 'Menunggu', 'class' => 'bg-amber-950/50 text-amber-400 border-amber-800/60'], 'approved' => ['label' => 'Disetujui', 'class' => 'bg-emerald-950/50 text-emerald-400 border-emerald-800/60'], 'rejected' => ['label' => 'Ditolak', 'class' => 'bg-rose-950/50 text-rose-400 border-rose-800/60']]; $s = $statusMap[$permit->status] ?? ['label' => $permit->status, 'class' => 'bg-slate-800 text-slate-400 border-slate-700']; @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $s['class'] }}">{{ $s['label'] }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('presence.izin.show', $permit) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-950/50 hover:bg-indigo-900/70 text-indigo-400 text-xs font-semibold border border-indigo-800/60 transition">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-600">
                                <i class="fas fa-folder-open text-4xl"></i>
                                <p class="text-sm">Belum ada pengajuan izin</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($permits->hasPages())
        <div class="px-5 py-4 border-t border-slate-800">{{ $permits->links() }}</div>
        @endif
    </div>
</div>
@endsection
