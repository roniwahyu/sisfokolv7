@extends('layouts.app')

@section('title', 'Rekap Kehadiran — SISFOKOL')
@section('page-title', '📊 Rekap Kehadiran')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- ─── Header & Quick Actions ─── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Rekap Kehadiran</h1>
            <p class="text-sm text-slate-500 mt-0.5">Data presensi harian seluruh siswa</p>
        </div>
        <a href="{{ route('presence.scan') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold shadow-lg shadow-indigo-500/20 transition">
            <i class="fas fa-qrcode"></i> Buka Scanner
        </a>
    </div>

    {{-- ─── Filter Bar ─── --}}
    <form method="GET" action="{{ route('presence.rekap') }}"
        class="rounded-2xl bg-slate-900/80 border border-slate-800 p-4 backdrop-blur-sm flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Tanggal</label>
            <input type="date" name="date" value="{{ request('date') }}"
                class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Status</label>
            <select name="status"
                class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <option value="">Semua Status</option>
                <option value="present" @selected(request('status') === 'present')>Hadir</option>
                <option value="late"    @selected(request('status') === 'late')>Terlambat</option>
                <option value="early"   @selected(request('status') === 'early')>Pulang Awal</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['date', 'status']))
            <a href="{{ route('presence.rekap') }}"
                class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm transition flex items-center gap-2">
                <i class="fas fa-times"></i> Reset
            </a>
            @endif
        </div>
    </form>

    {{-- ─── Table ─── --}}
    <div class="rounded-3xl bg-slate-900/80 border border-slate-800 overflow-hidden backdrop-blur-sm shadow-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Jam</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipe</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($attendances as $att)
                    <tr class="hover:bg-slate-800/30 transition group">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                    {{ substr($att->attendable?->nama ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-100">{{ $att->attendable?->nama ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $att->attendable?->nis ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-300">{{ $att->date?->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-slate-300 font-mono">{{ $att->time?->format('H:i') }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold
                                {{ $att->type === 'in' ? 'bg-emerald-950/50 text-emerald-400 border border-emerald-800/60' : 'bg-blue-950/50 text-blue-400 border border-blue-800/60' }}">
                                <i class="fas fa-{{ $att->type === 'in' ? 'sign-in-alt' : 'sign-out-alt' }}"></i>
                                {{ $att->type === 'in' ? 'Masuk' : 'Pulang' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @php
                                $statusMap = [
                                    'present' => ['label' => 'Hadir', 'class' => 'bg-emerald-950/50 text-emerald-400 border-emerald-800/60'],
                                    'late'    => ['label' => 'Terlambat', 'class' => 'bg-amber-950/50 text-amber-400 border-amber-800/60'],
                                    'early'   => ['label' => 'Pulang Awal', 'class' => 'bg-blue-950/50 text-blue-400 border-blue-800/60'],
                                ];
                                $s = $statusMap[$att->status] ?? ['label' => $att->status, 'class' => 'bg-slate-800 text-slate-400 border-slate-700'];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $s['class'] }}">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-xs text-slate-500 capitalize">{{ $att->source ?? '—' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-600">
                                <i class="fas fa-calendar-times text-4xl"></i>
                                <p class="text-sm">Tidak ada data kehadiran untuk filter ini</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($attendances->hasPages())
        <div class="px-5 py-4 border-t border-slate-800">
            {{ $attendances->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
