<?php

namespace App\Http\Controllers\Picket;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermitRequest;
use App\Models\Employee;
use App\Models\Permit;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermitController extends Controller
{
    public function index()
    {
        $permits = Permit::with('user')->latest()->paginate(20);

        return view('picket.permits.index', compact('permits'));
    }

    public function create()
    {
        return view('picket.permits.create');
    }

    public function store(StorePermitRequest $request)
    {
        $data = $request->validated();

        $person = Student::where('nis', $data['code'])->first()
            ?? Employee::where('code', $data['code'])->first();

        if (! $person) {
            return back()->with('error', 'Kode tidak ditemukan.');
        }

        $data['user_id'] = $person->user?->id;
        $data['permitable_type'] = get_class($person);
        $data['permitable_id'] = $person->id;
        $data['approved_by'] = Auth::id();
        $data['approved_at'] = now();
        $data['status'] = 'approved';
        unset($data['code']);

        Permit::create($data);

        return redirect()->route('picket.permits.index')->with('success', 'Izin berhasil dicatat.');
    }

    public function approve(Permit $permit)
    {
        $permit->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Izin disetujui.');
    }
}
