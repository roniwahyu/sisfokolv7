<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::latest()->paginate(20);

        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function store(StoreAcademicYearRequest $request)
    {
        AcademicYear::create($request->validated());

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil ditambahkan.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(StoreAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->validated());

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil dihapus.');
    }
}
