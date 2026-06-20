<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExtracurricularRequest;
use App\Models\Employee;
use App\Models\Extracurricular;
use Illuminate\Http\Request;

class ExtracurricularController extends Controller
{
    public function index()
    {
        $extracurriculars = Extracurricular::with('coach')->latest()->paginate(20);

        return view('admin.extracurriculars.index', compact('extracurriculars'));
    }

    public function create()
    {
        $coaches = Employee::where('position', 'guru')->get();

        return view('admin.extracurriculars.create', compact('coaches'));
    }

    public function store(StoreExtracurricularRequest $request)
    {
        Extracurricular::create($request->validated());

        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil ditambahkan.');
    }

    public function edit(Extracurricular $extracurricular)
    {
        $coaches = Employee::where('position', 'guru')->get();

        return view('admin.extracurriculars.edit', compact('extracurricular', 'coaches'));
    }

    public function update(StoreExtracurricularRequest $request, Extracurricular $extracurricular)
    {
        $extracurricular->update($request->validated());

        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(Extracurricular $extracurricular)
    {
        $extracurricular->delete();

        return redirect()->route('admin.extracurriculars.index')->with('success', 'Ekstrakurikuler berhasil dihapus.');
    }
}
