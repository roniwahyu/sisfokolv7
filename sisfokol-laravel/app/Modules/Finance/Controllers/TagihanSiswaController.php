<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Kelas;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Finance\Requests\GenerateTagihanRequest;
use App\Modules\Finance\Services\TagihanGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagihanSiswaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TagihanSiswa::class);

        $search = $request->input('search');
        $lunas = $request->input('lunas');
        $kelasId = $request->input('kelas_id');

        $query = TagihanSiswa::with(['siswa', 'itemPembayaran', 'tahunAjaran']);

        if ($search) {
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($lunas !== null && $lunas !== '') {
            $query->where('lunas', (bool) $lunas);
        }

        if ($kelasId) {
            $query->whereHas('siswa.kelasSiswa', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        $tagihan = $query->latest()->paginate(15)->withQueryString();
        $kelasList = Kelas::all();

        return view('finance.tagihan.index', compact('tagihan', 'search', 'lunas', 'kelasId', 'kelasList'));
    }

    public function create()
    {
        Gate::authorize('create', TagihanSiswa::class);

        $kelasList = Kelas::all();
        $items = ItemPembayaran::where('aktif', true)->where('jenis', 'spp')->get();

        return view('finance.tagihan.generate', compact('kelasList', 'items'));
    }

    public function generate(GenerateTagihanRequest $request, TagihanGeneratorService $service)
    {
        Gate::authorize('create', TagihanSiswa::class);

        $kelas = Kelas::findOrFail($request->input('kelas_id'));
        $item = ItemPembayaran::findOrFail($request->input('item_pembayaran_id'));
        $tapel = TahunAjaran::where('aktif', true)->firstOrFail();

        $bulan = (int) $request->input('bulan');

        $count = $service->generateSpp($tapel, $kelas, $item, $bulan);

        return redirect()
            ->route('finance.tagihan.index')
            ->with('success', "Pembangkitan tagihan selesai. {$count} tagihan baru berhasil dibuat.");
    }
}
