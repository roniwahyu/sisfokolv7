@extends('layouts.app')

@section('title', 'Tambah Kurikulum')
@section('page-title', 'Tambah Kurikulum Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Back --}}
    <a href="{{ route('kurikulum.index') }}"
       class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 text-sm transition-colors duration-150">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Kembali ke Daftar Kurikulum
    </a>

    {{-- Form Card --}}
    <div class="bg-slate-900/70 backdrop-blur-sm border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-violet-900/20 to-indigo-900/10">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Form Tambah Kurikulum
            </h3>
            <p class="text-slate-400 text-sm mt-0.5">Isi semua kolom yang diperlukan</p>
        </div>

        <form action="{{ route('kurikulum.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            {{-- Kode Kurikulum --}}
            <div class="space-y-1.5">
                <label for="kurikulum_id" class="block text-sm font-medium text-slate-300">
                    Kode Kurikulum <span class="text-red-400">*</span>
                </label>
                <input type="text" id="kurikulum_id" name="kurikulum_id"
                       value="{{ old('kurikulum_id') }}"
                       placeholder="Contoh: K13, KURMER, KTSP"
                       class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('kurikulum_id') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition-all duration-150 font-mono text-sm uppercase">
                @error('kurikulum_id')
                    <p class="text-red-400 text-xs flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Nama Kurikulum --}}
            <div class="space-y-1.5">
                <label for="nama_kurikulum" class="block text-sm font-medium text-slate-300">
                    Nama Kurikulum <span class="text-red-400">*</span>
                </label>
                <input type="text" id="nama_kurikulum" name="nama_kurikulum"
                       value="{{ old('nama_kurikulum') }}"
                       placeholder="Contoh: Kurikulum Merdeka"
                       class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('nama_kurikulum') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition-all duration-150 text-sm">
                @error('nama_kurikulum')
                    <p class="text-red-400 text-xs flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div class="space-y-1.5">
                <label for="deskripsi" class="block text-sm font-medium text-slate-300">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          placeholder="Deskripsi singkat mengenai kurikulum ini..."
                          class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('deskripsi') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition-all duration-150 text-sm resize-none">{{ old('deskripsi') }}</textarea>
            </div>

            {{-- Status Aktif --}}
            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-800/40 border border-slate-700/40">
                <div x-data="{ checked: {{ old('status_aktif', '1') ? 'true' : 'false' }} }">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="status_aktif" value="0">
                        <input type="checkbox" id="status_aktif" name="status_aktif" value="1"
                               {{ old('status_aktif', '1') ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-500/50 rounded-full peer peer-checked:after:translate-x-4 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                    </label>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-200">Status Aktif</p>
                    <p class="text-xs text-slate-400">Kurikulum akan ditandai sebagai aktif dan dapat dipilih oleh mata pelajaran</p>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('kurikulum.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-600/60 text-slate-300 text-sm font-medium hover:bg-slate-800/60 transition-colors duration-150">
                    Batal
                </a>
                <button type="submit" id="btn-simpan-kurikulum"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white text-sm font-semibold shadow-lg shadow-violet-900/40 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                    Simpan Kurikulum
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
