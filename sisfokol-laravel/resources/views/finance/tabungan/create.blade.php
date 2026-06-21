@extends('layouts.app')

@section('title', 'Keuangan — Buka Rekening Tabungan')
@section('page-title', 'Buka Rekening Tabungan Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Buka Rekening Tabungan Baru</h1>
            <p class="text-sm text-slate-400 mt-1">Pilih siswa aktif untuk membukakan nomor rekening simpanan tabungan sekolah baru.</p>
        </div>
        <a href="{{ route('finance.tabungan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 rounded-xl text-sm font-medium transition border border-slate-700">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
        <form method="POST" action="{{ route('finance.tabungan.store') }}" class="space-y-6">
            @csrf

            <!-- Siswa -->
            <div>
                <label for="siswa_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pilih Siswa Target <span class="text-rose-500">*</span></label>
                <select name="siswa_id" id="siswa_id" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('siswa_id') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    <option value="">-- Pilih Siswa --</option>
                    @foreach($siswaWithoutTabungan as $s)
                        <option value="{{ $s->id }}" {{ old('siswa_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->nama }} (NIS: {{ $s->nis }})
                        </option>
                    @endforeach
                </select>
                @error('siswa_id')
                    <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-800">
                <a href="{{ route('finance.tabungan.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition text-sm font-medium">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                    <i class="fas fa-key"></i> Buka Rekening
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
