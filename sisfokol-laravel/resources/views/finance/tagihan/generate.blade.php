@extends('layouts.app')

@section('title', 'Keuangan — Pembangkit Tagihan SPP')
@section('page-title', 'Pembangkit Tagihan SPP Bulanan')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Generate Tagihan SPP</h1>
            <p class="text-sm text-slate-400 mt-1">Generate tagihan SPP bulanan otomatis untuk seluruh siswa di suatu kelas.</p>
        </div>
        <a href="{{ route('finance.tagihan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 rounded-xl text-sm font-medium transition border border-slate-700">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Alert Warning/Info -->
    <div class="p-4 bg-amber-950/20 border border-amber-900/40 rounded-2xl text-amber-350 text-sm flex gap-3">
        <i class="fas fa-exclamation-triangle text-amber-500 text-lg mt-0.5"></i>
        <div>
            <span class="font-semibold block mb-0.5">Catatan Penting:</span>
            Proses ini dirancang secara idempotent. Siswa yang sudah memiliki tagihan untuk item, periode/bulan, dan tahun ajaran yang dipilih **tidak akan digenerate ulang**, mencegah terjadinya tagihan ganda.
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
        <form method="POST" action="{{ route('finance.tagihan.generate') }}" class="space-y-6">
            @csrf

            <!-- Item Pembayaran -->
            <div>
                <label for="item_pembayaran_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pilih Pos Pembayaran (SPP) <span class="text-rose-500">*</span></label>
                <select name="item_pembayaran_id" id="item_pembayaran_id" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('item_pembayaran_id') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    <option value="">-- Pilih Pos Pembayaran --</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ old('item_pembayaran_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->nama }} (Rp {{ number_format($item->nominal, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                @error('item_pembayaran_id')
                    <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kelas -->
                <div>
                    <label for="kelas_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pilih Kelas Target <span class="text-rose-500">*</span></label>
                    <select name="kelas_id" id="kelas_id" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('kelas_id') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" {{ old('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('kelas_id')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bulan -->
                <div>
                    <label for="bulan" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Bulan Tagihan <span class="text-rose-500">*</span></label>
                    <select name="bulan" id="bulan" class="w-full px-4 py-2.5 bg-slate-950/50 border @error('bulan') border-rose-500 @else border-slate-800 @enderror rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                        <option value="">-- Pilih Bulan --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('bulan', now()->format('n')) == $m ? 'selected' : '' }}>
                                {{ carbon_month_name($m) }}
                            </option>
                        @endfor
                    </select>
                    @error('bulan')
                        <p class="text-xs text-rose-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-800">
                <a href="{{ route('finance.tagihan.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition text-sm font-medium">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                    <i class="fas fa-cog"></i> Proses Generate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
