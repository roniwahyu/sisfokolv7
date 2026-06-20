<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherAgendaRequest;
use App\Models\Schedule;
use App\Models\TeacherAgenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherAgendaController extends Controller
{
    public function index()
    {
        $employee = Auth::user()->userable;

        $agendas = TeacherAgenda::with('schedule')
            ->where('employee_id', $employee?->id)
            ->latest()
            ->paginate(20);

        return view('teacher.agendas.index', compact('agendas'));
    }

    public function create()
    {
        $employee = Auth::user()->userable;
        $schedules = Schedule::where('employee_id', $employee?->id)->get();

        return view('teacher.agendas.create', compact('schedules'));
    }

    public function store(StoreTeacherAgendaRequest $request)
    {
        $data = $request->validated();
        $data['employee_id'] = Auth::user()->userable?->id;

        TeacherAgenda::create($data);

        return redirect()->route('teacher.agendas.index')->with('success', 'Agenda mengajar berhasil ditambahkan.');
    }

    public function edit(TeacherAgenda $agenda)
    {
        $employee = Auth::user()->userable;
        $schedules = Schedule::where('employee_id', $employee?->id)->get();

        return view('teacher.agendas.edit', compact('agenda', 'schedules'));
    }

    public function update(StoreTeacherAgendaRequest $request, TeacherAgenda $agenda)
    {
        $agenda->update($request->validated());

        return redirect()->route('teacher.agendas.index')->with('success', 'Agenda mengajar berhasil diperbarui.');
    }

    public function destroy(TeacherAgenda $agenda)
    {
        $agenda->delete();

        return redirect()->route('teacher.agendas.index')->with('success', 'Agenda mengajar berhasil dihapus.');
    }
}
