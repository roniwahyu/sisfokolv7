<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Models\TabunganSiswa;
use App\Modules\Finance\Services\TabunganMutasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TabunganSiswaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TabunganSiswa::class);

        $search = $request->input('search');
        $query = TabunganSiswa::with('siswa');

        if ($search) {
            $query->where('no_rekening', 'like', "%{$search}%")
                  ->orWhereHas('siswa', function ($q) use ($search) {
                      $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                  });
        }

        $tabungan = $query->paginate(15)->withQueryString();

        return view('finance.tabungan.index', compact('tabungan', 'search'));
    }

    public function create()
    {
        Gate::authorize('create', TabunganSiswa::class);
        $siswaWithoutTabungan = Siswa::whereDoesntHave('tabunganSiswa')->get();
        return view('finance.tabungan.create', compact('siswaWithoutTabungan'));
    }

    public function store(Request $request, TabunganMutasiService $service)
    {
        Gate::authorize('create', TabunganSiswa::class);

        $request->validate([
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
        ]);

        $siswa = Siswa::findOrFail($request->input('siswa_id'));
        $tabungan = $service->getOrCreateAccount($siswa);

        return redirect()
            ->route('finance.tabungan.index')
            ->with('success', "Rekening tabungan untuk {$siswa->nama} berhasil dibuat dengan nomor: {$tabungan->no_rekening}.");
    }

    public function show(TabunganSiswa $tabungan)
    {
        Gate::authorize('view', $tabungan);
        $tabungan->load('siswa');
        return view('finance.tabungan.show', compact('tabungan'));
    }

    public function setor(Request $request, TabunganSiswa $tabungan, TabunganMutasiService $service)
    {
        Gate::authorize('update', $tabungan);

        $request->validate([
            'nominal' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $service->setor($tabungan, (float) $request->input('nominal'));

            return redirect()
                ->route('finance.tabungan.show', $tabungan->id)
                ->with('success', "Setoran tabungan senilai Rp " . number_format($request->input('nominal'), 0, ',', '.') . " berhasil diproses.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tarik(Request $request, TabunganSiswa $tabungan, TabunganMutasiService $service)
    {
        Gate::authorize('update', $tabungan);

        $request->validate([
            'nominal' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $service->tarik($tabungan, (float) $request->input('nominal'));

            return redirect()
                ->route('finance.tabungan.show', $tabungan->id)
                ->with('success', "Penarikan tabungan senilai Rp " . number_format($request->input('nominal'), 0, ',', '.') . " berhasil diproses.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
