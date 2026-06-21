@extends('layouts.app')

@section('title', 'Keuangan — Kasir Pembayaran')
@section('page-title', 'Kasir Penerimaan Pembayaran')

@section('content')
<div class="space-y-6" x-data="kasirApp()">
    <div class="flex items-center justify-between pb-5 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">Kasir Penerimaan Pembayaran</h1>
            <p class="text-sm text-slate-400 mt-1">Cari siswa, pilih tagihan, dan catat pembayaran masuk secara real-time.</p>
        </div>
        <a href="{{ route('finance.pembayaran.riwayat') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 rounded-xl text-sm font-medium transition border border-slate-700">
            <i class="fas fa-history"></i> Riwayat Kwitansi
        </a>
    </div>

    <!-- Alert Status -->
    @if(session('success'))
        <div class="p-4 bg-emerald-950/30 border border-emerald-800/60 rounded-xl text-emerald-400 text-sm flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                <div>{{ session('success') }}</div>
            </div>
            @if(session('latest_pembayaran_id'))
                <a href="{{ route('finance.pembayaran.kwitansi', session('latest_pembayaran_id')) }}" target="_blank" class="px-3.5 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-emerald-100 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                    <i class="fas fa-print"></i> Cetak Kwitansi PDF
                </a>
            @endif
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-950/30 border border-rose-800/60 rounded-xl text-rose-400 text-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Search Section -->
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl">
        <form method="GET" action="{{ route('finance.pembayaran.index') }}" class="space-y-4">
            <div>
                <label for="search" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Cari Siswa Target (Ketik NIS atau Nama Lengkap)</label>
                <div class="flex gap-3">
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Contoh: siswa.2024001 atau Andi..." class="flex-1 px-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-indigo-600/20 flex items-center gap-2">
                        <i class="fas fa-search"></i> Temukan
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($selectedSiswa)
        <!-- Student Details Card -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border-b md:border-b-0 md:border-r border-slate-800/80 pb-4 md:pb-0 md:pr-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-950/50 border border-indigo-900/60 flex items-center justify-center text-indigo-400 text-lg font-bold">
                    {{ strtoupper(substr($selectedSiswa->nama, 0, 2)) }}
                </div>
                <div>
                    <h3 class="font-bold text-slate-200 text-base">{{ $selectedSiswa->nama }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">NIS: {{ $selectedSiswa->nis }} / NISN: {{ $selectedSiswa->nisn ?? '-' }}</p>
                </div>
            </div>
            <div class="border-b md:border-b-0 md:border-r border-slate-800/80 pb-4 md:pb-0 md:pr-6 flex items-center">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Kelas Aktif</span>
                    <span class="text-slate-300 font-semibold text-sm">{{ $selectedSiswa->kelasSiswa->first()->kelas->nama ?? 'Belum ada kelas' }}</span>
                </div>
            </div>
            <div class="flex items-center">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Status Keanggotaan</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/40 text-emerald-450 border border-emerald-900/50 uppercase">
                        {{ $selectedSiswa->status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Cashier Billing & Payment Form -->
        <form method="POST" action="{{ route('finance.pembayaran.store', $selectedSiswa->id) }}" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Bills List (Col span 2) -->
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/20 flex items-center justify-between">
                        <h3 class="font-bold text-slate-200 text-sm">Daftar Tagihan Belum Lunas</h3>
                        <span class="text-xs text-slate-400 font-medium">Centang pos tagihan yang ingin dibayar</span>
                    </div>
                    
                    <div class="divide-y divide-slate-800/60">
                        @forelse($tagihan as $index => $t)
                            <div class="p-6 flex items-start gap-4 hover:bg-slate-850/20 transition">
                                <div class="pt-1">
                                    <input type="checkbox" 
                                           name="pembayaran[{{ $index }}][tagihan_id]" 
                                           value="{{ $t->id }}"
                                           x-model="checkedBills"
                                           @change="toggleBill({{ $t->id }}, {{ $t->nominal_kurang }}, {{ $index }})"
                                           class="w-5 h-5 bg-slate-950 border border-slate-800 rounded text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 focus:ring-2">
                                </div>
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-semibold text-slate-200 text-sm">{{ $t->itemPembayaran->nama }}</h4>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-slate-450">
                                            <span>Periode: {{ $t->itemPembayaran->periode === 'bulanan' ? carbon_month_name($t->bulan) : 'Sekali Bayar' }}</span>
                                            <span>•</span>
                                            <span>TA: {{ $t->tahunAjaran->nama }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col sm:items-end justify-center">
                                        <span class="text-xs text-slate-500 block mb-0.5">Sisa Tagihan</span>
                                        <span class="font-bold text-slate-300 text-sm">Rp {{ number_format($t->nominal_kurang, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Payment Input Column (shown when checked) -->
                                <div class="w-36 pt-0.5" x-show="isBillChecked({{ $t->id }})">
                                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Bayar (Rp)</label>
                                    <input type="number" 
                                           name="pembayaran[{{ $index }}][jumlah]" 
                                           placeholder="Jumlah"
                                           x-model.number="amounts[{{ $t->id }}]"
                                           @input="calculateTotal()"
                                           max="{{ $t->nominal_kurang }}"
                                           min="1"
                                           required
                                           class="w-full px-3 py-1.5 bg-slate-950/60 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                                </div>
                            </div>
                        @empty
                            <div class="p-10 text-center text-slate-500">
                                <i class="fas fa-check-circle text-2xl text-emerald-500 mb-3"></i>
                                <p class="text-sm font-medium">Seluruh kewajiban tagihan siswa telah Lunas.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right: Calculator & Summary (Col span 1) -->
                <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 shadow-xl space-y-6 h-fit">
                    <h3 class="font-bold text-slate-200 text-sm border-b border-slate-800 pb-3">Ringkasan Pembayaran</h3>

                    <!-- Calculation Details -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm text-slate-400">
                            <span>Item Terpilih</span>
                            <span class="font-semibold text-slate-250" x-text="checkedBills.length + ' Tagihan'">0 Tagihan</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-slate-400">
                            <span>Total Tagihan</span>
                            <span class="font-bold text-indigo-400 text-lg" x-text="formatRupiah(totalBayar)">Rp 0</span>
                        </div>
                    </div>

                    <!-- Cash Input for Change Calculation -->
                    <div class="space-y-2 pt-4 border-t border-slate-800">
                        <label for="cash_input" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Uang Diterima / Cash (Rp)</label>
                        <input type="number" 
                               id="cash_input"
                               x-model.number="cashReceived"
                               @input="calculateChange()"
                               placeholder="Contoh: 500000"
                               class="w-full px-4 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm">
                    </div>

                    <!-- Change return display -->
                    <div class="bg-slate-950/40 border border-slate-850 p-4 rounded-xl flex justify-between items-center">
                        <span class="text-xs font-semibold text-slate-450 uppercase tracking-wider">Uang Kembali</span>
                        <span class="font-bold text-emerald-450 text-base" x-text="formatRupiah(changeReturned)">Rp 0</span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            x-bind:disabled="checkedBills.length === 0"
                            class="w-full py-3 rounded-xl font-semibold text-sm transition shadow-md flex items-center justify-center gap-2"
                            :class="checkedBills.length > 0 ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/20' : 'bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-850'">
                        <i class="fas fa-wallet"></i> Proses Pembayaran
                    </button>
                </div>
            </div>
        </form>
    @elseif($search)
        <!-- Student Not Found Alert -->
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-10 shadow-xl text-center text-slate-500">
            <i class="fas fa-user-slash text-4xl mb-4 text-slate-600"></i>
            <h3 class="font-bold text-slate-350 text-base mb-1">Siswa Tidak Ditemukan</h3>
            <p class="text-sm">Tidak dapat menemukan profil siswa dengan kata kunci "{{ $search }}".</p>
        </div>
    @endif
</div>

<script>
    function kasirApp() {
        return {
            checkedBills: [],
            amounts: {},
            totalBayar: 0,
            cashReceived: 0,
            changeReturned: 0,

            toggleBill(id, amount, index) {
                if (this.checkedBills.includes(String(id))) {
                    this.amounts[id] = amount;
                } else {
                    delete this.amounts[id];
                }
                this.calculateTotal();
            },

            isBillChecked(id) {
                return this.checkedBills.includes(String(id));
            },

            calculateTotal() {
                let sum = 0;
                for (let id in this.amounts) {
                    let val = parseFloat(this.amounts[id]);
                    if (!isNaN(val) && val > 0) {
                        sum += val;
                    }
                }
                this.totalBayar = sum;
                this.calculateChange();
            },

            calculateChange() {
                if (this.cashReceived && this.cashReceived >= this.totalBayar) {
                    this.changeReturned = this.cashReceived - this.totalBayar;
                } else {
                    this.changeReturned = 0;
                }
            },

            formatRupiah(number) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number);
            }
        }
    }
</script>
@endsection
