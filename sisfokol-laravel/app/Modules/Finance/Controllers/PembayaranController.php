<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Finance\Requests\BayarTagihanRequest;
use App\Modules\Finance\Services\PembayaranService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('create', Pembayaran::class);

        $search = $request->input('search');
        $selectedSiswa = null;
        $tagihan = collect();

        if ($search) {
            $selectedSiswa = Siswa::where('nis', $search)
                ->orWhere('nama', 'like', "%{$search}%")
                ->first();

            if ($selectedSiswa) {
                $tagihan = TagihanSiswa::with('itemPembayaran')
                    ->where('siswa_id', $selectedSiswa->id)
                    ->where('lunas', false)
                    ->get();
            }
        }

        return view('finance.pembayaran.index', compact('search', 'selectedSiswa', 'tagihan'));
    }

    public function store(BayarTagihanRequest $request, Siswa $siswa, PembayaranService $service)
    {
        Gate::authorize('create', Pembayaran::class);

        try {
            $pembayaran = $service->bayar(
                $siswa,
                $request->validated()['pembayaran'],
                auth()->user()
            );

            return redirect()
                ->route('finance.pembayaran.riwayat')
                ->with('success', "Pembayaran berhasil diproses dengan No. Nota: {$pembayaran->no_nota}.")
                ->with('latest_pembayaran_id', $pembayaran->id);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function riwayat(Request $request)
    {
        Gate::authorize('viewAny', Pembayaran::class);

        $search = $request->input('search');
        $query = Pembayaran::with('siswa');

        if ($search) {
            $query->where('no_nota', 'like', "%{$search}%")
                  ->orWhereHas('siswa', function ($q) use ($search) {
                      $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                  });
        }

        $riwayat = $query->latest()->paginate(15)->withQueryString();

        return view('finance.pembayaran.riwayat', compact('riwayat', 'search'));
    }

    public function cetakKwitansi(Pembayaran $pembayaran)
    {
        Gate::authorize('viewPembayaran', [Pembayaran::class, $pembayaran]);

        $pembayaran->load(['siswa', 'rincian.tagihanSiswa.itemPembayaran', 'bendahara']);

        $pdf = Pdf::loadView('finance.pembayaran.kwitansi', compact('pembayaran'));
        return $pdf->stream("kwitansi-{$pembayaran->no_nota}.pdf");
    }
}
