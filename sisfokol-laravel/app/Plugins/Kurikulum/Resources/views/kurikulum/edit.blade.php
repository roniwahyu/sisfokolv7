@extends('layouts.app')

@section('title', 'Edit Kurikulum')
@section('page-title', 'Edit Kurikulum')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <a href="{{ route('kurikulum.index') }}"
       class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 text-sm transition-colors duration-150">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Kembali ke Daftar Kurikulum
    </a>

    <div class="bg-slate-900/70 backdrop-blur-sm border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-amber-900/20 to-orange-900/10">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
                Edit: <span class="text-amber-300">{{ $kurikulum->nama_kurikulum }}</span>
            </h3>
            <p class="text-slate-400 text-sm mt-0.5">Kode: <code class="text-violet-300 font-mono">{{ $kurikulum->kurikulum_id }}</code></p>
        </div>

        <form action="{{ route('kurikulum.update', $kurikulum) }}" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="kurikulum_id" class="block text-sm font-medium text-slate-300">
                    Kode Kurikulum <span class="text-red-400">*</span>
                </label>
                <input type="text" id="kurikulum_id" name="kurikulum_id"
                       value="{{ old('kurikulum_id', $kurikulum->kurikulum_id) }}"
                       class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('kurikulum_id') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all duration-150 font-mono text-sm uppercase">
                @error('kurikulum_id')
                    <p class="text-red-400 text-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="nama_kurikulum" class="block text-sm font-medium text-slate-300">
                    Nama Kurikulum <span class="text-red-400">*</span>
                </label>
                <input type="text" id="nama_kurikulum" name="nama_kurikulum"
                       value="{{ old('nama_kurikulum', $kurikulum->nama_kurikulum) }}"
                       class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('nama_kurikulum') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all duration-150 text-sm">
                @error('nama_kurikulum')
                    <p class="text-red-400 text-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="deskripsi" class="block text-sm font-medium text-slate-300">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          class="w-full px-4 py-2.5 bg-slate-800/70 border border-slate-600/50 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all duration-150 text-sm resize-none">{{ old('deskripsi', $kurikulum->deskripsi) }}</textarea>
            </div>

            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-800/40 border border-slate-700/40">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="status_aktif" value="0">
                    <input type="checkbox" id="status_aktif" name="status_aktif" value="1"
                           {{ old('status_aktif', $kurikulum->status_aktif) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-10 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-500/50 rounded-full peer peer-checked:after:translate-x-4 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
                <div>
                    <p class="text-sm font-medium text-slate-200">Status Aktif</p>
                    <p class="text-xs text-slate-400">Kurikulum dapat dipilih sebagai referensi mata pelajaran</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('kurikulum.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-600/60 text-slate-300 text-sm font-medium hover:bg-slate-800/60 transition-colors">
                    Batal
                </a>
                <button type="submit" id="btn-update-kurikulum"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white text-sm font-semibold shadow-lg shadow-amber-900/40 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                    Perbarui Kurikulum
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
