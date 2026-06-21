@extends('layouts.app')

@section('title', 'Scanner Presensi QR — SISFOKOL')
@section('page-title', '📷 Scanner Presensi QR')

@push('styles')
<style>
    #qr-reader {
        width: 100%;
        border-radius: 1rem;
        overflow: hidden;
    }
    #qr-reader video {
        border-radius: 1rem;
    }
    .scan-ring {
        animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(99, 102, 241, 0); }
        100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }
    .status-badge-present  { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
    .status-badge-late     { background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; }
    .status-badge-early    { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); color: #60a5fa; }
    .status-badge-error    { background: rgba(239, 68, 68, 0.15);  border: 1px solid rgba(239, 68, 68, 0.4);  color: #f87171; }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto" x-data="qrScanner()">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- ─── Scanner Camera Panel ─── --}}
        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 backdrop-blur-sm shadow-2xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-500/20">
                    <i class="fas fa-qrcode text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-100">Kamera QR</h2>
                    <p class="text-xs text-slate-500">Arahkan kamera ke QR Code siswa</p>
                </div>
                <div class="ml-auto">
                    <span class="text-xs px-3 py-1 rounded-full bg-emerald-950/50 border border-emerald-800/60 text-emerald-400 font-medium"
                          x-text="cameraActive ? '● Live' : '○ Idle'"></span>
                </div>
            </div>

            {{-- Camera viewport --}}
            <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-square scan-ring" id="qr-reader-wrapper">
                <div id="qr-reader" class="w-full h-full"></div>
                {{-- Overlay crosshair --}}
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-48 h-48 border-2 border-indigo-400/50 rounded-xl relative">
                        <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-indigo-400 rounded-tl-md"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-indigo-400 rounded-tr-md"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-indigo-400 rounded-bl-md"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-indigo-400 rounded-br-md"></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button type="button" id="btn-start-camera"
                    class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition flex items-center justify-center gap-2"
                    @click="startCamera()">
                    <i class="fas fa-camera"></i> Aktifkan Kamera
                </button>
                <button type="button" id="btn-stop-camera"
                    class="flex-1 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold transition flex items-center justify-center gap-2 hidden"
                    @click="stopCamera()">
                    <i class="fas fa-stop-circle"></i> Stop
                </button>
            </div>
        </div>

        {{-- ─── Result & Manual Input Panel ─── --}}
        <div class="flex flex-col gap-6">

            {{-- Result card --}}
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 backdrop-blur-sm shadow-2xl min-h-44">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Hasil Scan</h3>

                <div x-show="!lastResult" class="flex flex-col items-center justify-center py-8 text-slate-600">
                    <i class="fas fa-barcode text-4xl mb-3"></i>
                    <p class="text-sm">Belum ada scan hari ini</p>
                </div>

                <div x-show="lastResult" x-cloak class="space-y-3">
                    <div :class="'flex items-center gap-3 p-4 rounded-2xl ' + statusClass()">
                        <i :class="'fas fa-' + statusIcon() + ' text-2xl'"></i>
                        <div>
                            <p class="font-bold text-lg" x-text="lastResult?.nama ?? lastResult?.nis"></p>
                            <p class="text-xs opacity-75" x-text="lastResult?.message"></p>
                        </div>
                        <span class="ml-auto text-sm font-semibold" x-text="lastResult?.time"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700">
                            <p class="text-slate-500 text-xs mb-1">Status</p>
                            <p class="font-semibold capitalize" x-text="lastResult?.status"></p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700">
                            <p class="text-slate-500 text-xs mb-1">Tipe</p>
                            <p class="font-semibold capitalize" x-text="lastResult?.type === 'in' ? 'Masuk' : 'Pulang'"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual input fallback --}}
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 backdrop-blur-sm shadow-2xl">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Input Manual NIS / QR</h3>
                <form @submit.prevent="manualScan()" class="flex gap-3">
                    <input type="text" id="manual-nis" x-model="manualInput"
                        placeholder="NIS atau kode QR..."
                        class="flex-1 px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm transition">
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition flex items-center gap-2"
                        :disabled="loading">
                        <i class="fas fa-search" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading" x-cloak></i>
                        Cari
                    </button>
                </form>
            </div>

            {{-- Scan history --}}
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 backdrop-blur-sm shadow-2xl flex-1">
                <h3 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">Riwayat Hari Ini</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1" x-show="history.length > 0">
                    <template x-for="item in history" :key="item.time + item.nis">
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/60 border border-slate-700/50 text-sm">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-xs font-bold"
                                :class="item.status === 'present' ? 'bg-emerald-950 text-emerald-400' : item.status === 'late' ? 'bg-amber-950 text-amber-400' : 'bg-slate-700 text-slate-300'">
                                <i :class="'fas fa-' + (item.type === 'in' ? 'sign-in-alt' : 'sign-out-alt')"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate" x-text="item.nama"></p>
                                <p class="text-xs text-slate-500" x-text="item.status + ' — ' + item.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-slate-600 text-sm text-center py-4" x-show="history.length === 0">Belum ada scan</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
function qrScanner() {
    return {
        cameraActive: false,
        loading: false,
        manualInput: '',
        lastResult: null,
        history: [],
        html5QrCode: null,

        init() {
            this.html5QrCode = new Html5Qrcode("qr-reader");
        },

        startCamera() {
            document.getElementById('btn-start-camera').classList.add('hidden');
            document.getElementById('btn-stop-camera').classList.remove('hidden');
            this.cameraActive = true;

            this.html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 200, height: 200 } },
                (decodedText) => { this.handleScan(decodedText); }
            ).catch(err => console.error("Camera error:", err));
        },

        stopCamera() {
            document.getElementById('btn-start-camera').classList.remove('hidden');
            document.getElementById('btn-stop-camera').classList.add('hidden');
            this.cameraActive = false;
            this.html5QrCode.stop().catch(() => {});
        },

        manualScan() {
            if (!this.manualInput.trim()) return;
            this.handleScan(this.manualInput.trim());
            this.manualInput = '';
        },

        handleScan(payload) {
            if (this.loading) return;
            this.loading = true;

            fetch("{{ route('presence.scan.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ qr_payload: payload }),
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.lastResult = {
                        ...data,
                        nama: data.message.split(' berhasil')[0].replace('Presensi ', ''),
                        nis: payload,
                    };
                    this.history.unshift(this.lastResult);
                    if (this.history.length > 20) this.history.pop();
                } else {
                    this.lastResult = { error: true, message: data.message, time: '', status: 'error' };
                }
            })
            .catch(() => {
                this.loading = false;
                this.lastResult = { error: true, message: 'Koneksi gagal, coba lagi.', time: '', status: 'error' };
            });
        },

        statusClass() {
            if (!this.lastResult) return '';
            if (this.lastResult.error) return 'status-badge-error';
            const s = this.lastResult.status;
            if (s === 'present') return 'status-badge-present';
            if (s === 'late')    return 'status-badge-late';
            if (s === 'early')   return 'status-badge-early';
            return 'status-badge-error';
        },

        statusIcon() {
            if (!this.lastResult || this.lastResult.error) return 'times-circle';
            const s = this.lastResult.status;
            if (s === 'present') return 'check-circle';
            if (s === 'late')    return 'clock';
            if (s === 'early')   return 'history';
            return 'times-circle';
        },
    };
}
</script>
@endpush
