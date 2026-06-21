@extends('layouts.app')

@section('title', 'Edit Komponen Kompetensi')
@section('page-title', 'Edit Komponen Kompetensi')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <a href="{{ route('kurikulum.komponen.index') }}"
       class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 text-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Kembali ke Daftar Komponen
    </a>

    <div class="bg-slate-900/70 backdrop-blur-sm border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-amber-900/20 to-orange-900/10">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
                Edit Komponen: <code class="text-amber-300 font-mono ml-1">{{ $komponen->kode_kompetensi }}</code>
            </h3>
        </div>

        <form action="{{ route('kurikulum.komponen.update', $komponen) }}" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="struktur_id" class="block text-sm font-medium text-slate-300">
                    Struktur Kurikulum <span class="text-red-400">*</span>
                </label>
                <select id="struktur_id" name="struktur_id"
                        class="w-full px-4 py-2.5 bg-slate-800/70 border border-slate-600/50 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all text-sm">
                    @foreach ($strukturOptions as $id => $label)
                    <option value="{{ $id }}" {{ old('struktur_id', $komponen->struktur_id) == $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('struktur_id') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="kode_kompetensi" class="block text-sm font-medium text-slate-300">
                        Kode Kompetensi <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="kode_kompetensi" name="kode_kompetensi"
                           value="{{ old('kode_kompetensi', $komponen->kode_kompetensi) }}"
                           class="w-full px-4 py-2.5 bg-slate-800/70 border border-slate-600/50 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all font-mono text-sm">
                    @error('kode_kompetensi') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="pendekatan_pedagogis" class="block text-sm font-medium text-slate-300">Pendekatan Pedagogis</label>
                    <input type="text" id="pendekatan_pedagogis" name="pendekatan_pedagogis"
                           value="{{ old('pendekatan_pedagogis', $komponen->pendekatan_pedagogis) }}"
                           class="w-full px-4 py-2.5 bg-slate-800/70 border border-slate-600/50 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all text-sm">
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="teks_kompetensi" class="block text-sm font-medium text-slate-300">
                    Teks / Deskripsi Kompetensi <span class="text-red-400">*</span>
                </label>
                <textarea id="teks_kompetensi" name="teks_kompetensi" rows="5"
                          class="w-full px-4 py-2.5 bg-slate-800/70 border {{ $errors->has('teks_kompetensi') ? 'border-red-500/70' : 'border-slate-600/50' }} rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all text-sm resize-none leading-relaxed">{{ old('teks_kompetensi', $komponen->teks_kompetensi) }}</textarea>
                @error('teks_kompetensi') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('kurikulum.komponen.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-600/60 text-slate-300 text-sm font-medium hover:bg-slate-800/60 transition-colors">
                    Batal
                </a>
                <button type="submit" id="btn-update-komponen"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white text-sm font-semibold shadow-lg shadow-amber-900/40 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                    Perbarui Komponen
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
