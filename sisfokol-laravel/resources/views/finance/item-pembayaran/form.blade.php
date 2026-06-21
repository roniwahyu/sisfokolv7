@extends('layouts.app')

@section('title', $isEdit ? 'Keuangan — Edit Pos Pembayaran' : 'Keuangan — Tambah Pos Pembayaran')
@section('page-title', $isEdit ? 'Edit Pos Pembayaran' : 'Tambah Pos Pembayaran')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">
                {{ $isEdit ? 'Edit Pos Pembayaran' : 'Tambah Pos Pembayaran Baru' }}
            </h1>
            <p class="text-sm text-slate-400 mt-1">Masukkan data pos pembayaran dengan lengkap dan benar.</p>
        </div>
        <a href="{{ route('finance.item-pembayaran.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 rounded-xl text-sm font-medium transition border border-slate-700">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
        <form method="POST" action="{{ $isEdit ? route('finance.item-pembayaran.update', $item->id) : route('finance.item-pembayaran.store') }}" class="space-y-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama Pos Pembayaran -->
                <div class="md:col-span-2">
                    <label for="nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Pos Pembayaran <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama', $item->nama) }}" placeholder="Contoh: SPP Juli 2026, Uang Seragam, dll" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('nama') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    @error('nama')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tahun Ajaran -->
                <div>
                    <label for="tahun_ajaran_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('tahun_ajaran_id') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        @foreach($tahunAjaran as $ta)
                            <option value="{{ $ta->id }}" {{ old('tahun_ajaran_id', $item->tahun_ajaran_id) == $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('tahun_ajaran_id')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Pembayaran -->
                <div>
                    <label for="jenis" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Jenis Pembayaran <span class="text-rose-500">*</span></label>
                    <select name="jenis" id="jenis" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('jenis') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="spp" {{ old('jenis', $item->jenis) === 'spp' ? 'selected' : '' }}>SPP (Bulanan Rutin)</option>
                        <option value="kegiatan" {{ old('jenis', $item->jenis) === 'kegiatan' ? 'selected' : '' }}>Uang Kegiatan</option>
                        <option value="infaq" {{ old('jenis', $item->jenis) === 'infaq' ? 'selected' : '' }}>Infaq / Sumbangan</option>
                        <option value="lainnya" {{ old('jenis', $item->jenis) === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('jenis')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nominal -->
                <div>
                    <label for="nominal" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nominal (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="nominal" id="nominal" value="{{ old('nominal', $item->nominal ? (int) $item->nominal : '') }}" placeholder="Contoh: 250000" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('nominal') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    @error('nominal')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Periode Pembayaran -->
                <div>
                    <label for="periode" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Periode <span class="text-rose-500">*</span></label>
                    <select name="periode" id="periode" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('periode') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        <option value="bulanan" {{ old('periode', $item->periode) === 'bulanan' ? 'selected' : '' }}>Bulanan (Spp, dll)</option>
                        <option value="sekali" {{ old('periode', $item->periode) === 'sekali' ? 'selected' : '' }}>Sekali Bayar (Seragam, dll)</option>
                    </select>
                    @error('periode')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="flex items-center gap-3 md:col-span-2 pt-2">
                    <input type="checkbox" name="aktif" id="aktif" value="1" {{ old('aktif', $item->aktif ?? true) ? 'checked' : '' }} class="w-5 h-5 bg-slate-950 border border-slate-800 rounded text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 focus:ring-2">
                    <label for="aktif" class="text-sm font-medium text-slate-300 select-none">Aktifkan pos pembayaran ini langsung</label>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-800">
                <a href="{{ route('finance.item-pembayaran.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition text-sm font-medium">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                    <i class="fas fa-save"></i> {{ $isEdit ? 'Perbarui' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
