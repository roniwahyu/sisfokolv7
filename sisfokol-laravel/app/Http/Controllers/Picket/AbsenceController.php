<?php

namespace App\Http\Controllers\Picket;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsenceRequest;
use App\Models\Absence;
use App\Models\Employee;
use App\Models\Student;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index()
    {
        $absences = Absence::with('user')->latest()->paginate(20);

        return view('picket.absences.index', compact('absences'));
    }

    public function create()
    {
        return view('picket.absences.create');
    }

    public function store(StoreAbsenceRequest $request)
    {
        $data = $request->validated();

        $person = Student::where('nis', $data['code'])->first()
            ?? Employee::where('code', $data['code'])->first();

        if (! $person) {
            return back()->with('error', 'Kode tidak ditemukan.');
        }

        $data['user_id'] = $person->user?->id;
        $data['absentable_type'] = get_class($person);
        $data['absentable_id'] = $person->id;
        unset($data['code']);

        Absence::create($data);

        return redirect()->route('picket.absences.index')->with('success', 'Absensi berhasil dicatat.');
    }

    public function destroy(Absence $absence)
    {
        $absence->delete();

        return redirect()->route('picket.absences.index')->with('success', 'Absensi berhasil dihapus.');
    }
}
