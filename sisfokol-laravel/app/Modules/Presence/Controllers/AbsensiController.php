<?php

namespace App\Modules\Presence\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Modules\Academic\Models\Siswa;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Absence::class);

        $query = Absence::with('attendable')
            ->latest('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $absences = $query->paginate(25)->withQueryString();

        return view('presence.absensi.index', compact('absences'));
    }

    public function create()
    {
        Gate::authorize('create', Absence::class);

        $siswaList = Siswa::where('status', 'aktif')->orderBy('nama')->get();

        return view('presence.absensi.create', compact('siswaList'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Absence::class);

        $data = $request->validate([
            'permitable_id'   => 'required|exists:siswa,id',
            'date'            => 'required|date',
            'reason'          => 'required|string|max:500',
        ]);

        $siswa = Siswa::findOrFail($data['permitable_id']);

        Absence::create([
            'user_id'         => Auth::id(),
            'absentable_type' => Siswa::class,
            'absentable_id'   => $siswa->id,
            'date'            => $data['date'],
            'reason'          => $data['reason'],
            'status'          => 'absent',
        ]);

        return redirect()->route('presence.absensi.index')
            ->with('success', "Absensi {$siswa->nama} berhasil dicatat.");
    }

    public function destroy(Absence $absence)
    {
        Gate::authorize('delete', $absence);

        $absence->delete();

        return redirect()->route('presence.absensi.index')
            ->with('success', 'Absensi berhasil dihapus.');
    }
}
