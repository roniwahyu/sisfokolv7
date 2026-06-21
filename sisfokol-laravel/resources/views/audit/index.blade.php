@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-slate-900 border border-slate-800/80 shadow-2xl p-6 sm:p-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-800/60">
            <div>
                <h3 class="text-xl font-bold text-slate-100 flex items-center gap-2">
                    <i class="fas fa-history text-indigo-400"></i> Log Aktivitas Sistem
                </h3>
                <p class="text-slate-400 text-sm mt-1">Daftar rekaman jejak audit sistem multi-tenant.</p>
            </div>
        </div>

        <!-- Filter Form -->
        <form class="flex flex-wrap gap-4 py-6" method="GET">
            <div class="w-full sm:w-80">
                <label for="event" class="sr-only">Event</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="event" id="event" class="w-full bg-slate-950 border border-slate-800 rounded-xl py-2.5 pl-10 pr-4 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="Cari nama event..." value="{{ request('event') }}">
                </div>
            </div>
            
            <div class="w-full sm:w-48">
                <label for="user_id" class="sr-only">User ID</label>
                <input type="text" name="user_id" id="user_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" placeholder="ID User..." value="{{ request('user_id') }}">
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl py-2.5 px-5 text-sm transition shadow-lg shadow-indigo-600/10 flex items-center gap-2">
                <i class="fas fa-filter"></i> Filter
            </button>

            @if(request()->anyFilled(['event', 'user_id']))
                <a href="{{ route('audit.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium rounded-xl py-2.5 px-5 text-sm transition flex items-center gap-2">
                    <i class="fas fa-undo"></i> Reset
                </a>
            @endif
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800/80 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-4 px-4">Waktu</th>
                        <th class="py-4 px-4">Pengguna</th>
                        <th class="py-4 px-4">Event</th>
                        <th class="py-4 px-4">IP Address</th>
                        <th class="py-4 px-4 text-right">Detail Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-950/30 transition">
                            <td class="py-4 px-4 whitespace-nowrap text-slate-300">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-4 px-4">
                                @if($log->user)
                                    <div class="font-medium text-slate-200">{{ $log->user->nama }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->user->username }}</div>
                                @else
                                    <span class="text-slate-600 font-medium italic">— System —</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @php
                                    $badgeStyle = match (true) {
                                        str_contains($log->event, 'failed') => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        str_contains($log->event, 'success') => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        str_contains($log->event, 'created') => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                        str_contains($log->event, 'updated') => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        str_contains($log->event, 'impersonate.start') => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        str_contains($log->event, 'impersonate.stop') => 'bg-teal-500/10 text-teal-400 border-teal-500/20',
                                        default => 'bg-slate-800 text-slate-300 border-slate-700'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeStyle }}">
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <code class="text-xs text-slate-400 bg-slate-950 px-2 py-1 rounded-md border border-slate-850">{{ $log->ip_address ?? '—' }}</code>
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap" x-data="{ open: false }">
                                @if($log->new_values || $log->old_values)
                                    <button @click="open = !open" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 focus:outline-none transition inline-flex items-center gap-1">
                                        <i class="fas" :class="open ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        <span x-text="open ? 'Tutup Detail' : 'Lihat Detail'"></span>
                                    </button>

                                    <!-- Expandable panel -->
                                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.away="open = false">
                                        <div class="w-full max-w-2xl bg-slate-900 border border-slate-850 rounded-2xl p-6 text-left shadow-2xl">
                                            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                                                <h4 class="text-base font-bold text-slate-200">Detail Log #{{ $log->id }} - {{ $log->event }}</h4>
                                                <button @click="open = false" class="text-slate-500 hover:text-slate-300"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                                                @if($log->old_values)
                                                    <div>
                                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1">Nilai Lama (Sebelum)</span>
                                                        <pre class="bg-slate-950 border border-slate-850 rounded-xl p-4 text-xs overflow-x-auto text-rose-300"><code>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                    </div>
                                                @endif
                                                @if($log->new_values)
                                                    <div>
                                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1">Nilai Baru (Sesudah)</span>
                                                        <pre class="bg-slate-950 border border-slate-850 rounded-xl p-4 text-xs overflow-x-auto text-emerald-300"><code>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex justify-end pt-4 border-t border-slate-800">
                                                <button @click="open = false" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm transition">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-600 italic">Tidak ada payload</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-600 font-medium">
                                Tidak ada log aktivitas ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
