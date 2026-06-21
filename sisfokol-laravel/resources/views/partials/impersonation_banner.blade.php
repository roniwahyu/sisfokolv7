@php
    $imp = app(\App\Modules\Auth\Services\ImpersonationService::class);
@endphp
@if($imp->isImpersonating())
    @php
        $original = \App\Models\User::find(session('impersonated_by'));
    @endphp
    <div class="alert alert-danger flex justify-between items-center mb-0 rounded-none" style="z-index: 9999; position: relative; background: #991b1b; color: white; border: none; padding: 12px 24px;">
        <div class="text-sm font-medium">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Anda sedang login sebagai <strong>{{ auth()->user()->nama }}</strong>
            (impersonated oleh {{ $original?->nama ?? 'SuperAdmin' }}).
            <span class="ml-2 bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full">Aksi Sensitif Diblokir</span>
        </div>
        <form method="POST" action="{{ route('impersonate.stop') }}">
            @csrf
            <button class="bg-white hover:bg-slate-100 text-slate-900 font-semibold rounded-lg px-3 py-1.5 text-xs transition flex items-center gap-1.5" type="submit">
                <i class="fas fa-undo"></i> Kembali ke akun saya
            </button>
        </form>
    </div>
@endif
