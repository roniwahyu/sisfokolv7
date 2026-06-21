<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Academic\Requests\StoreSiswaRequest;
use App\Modules\Academic\Requests\UpdateSiswaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Siswa::class);

        $search = $request->input('search');
        $query = Siswa::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $siswa = $query->latest()->paginate(15)->withQueryString();

        return view('academic.siswa.index', compact('siswa', 'search'));
    }

    public function create()
    {
        Gate::authorize('create', Siswa::class);
        return view('academic.siswa.create');
    }

    public function store(StoreSiswaRequest $request)
    {
        Gate::authorize('create', Siswa::class);

        $siswa = Siswa::create($request->validated());

        return redirect()
            ->route('academic.siswa.index')
            ->with('success', "Data siswa {$siswa->nama} berhasil ditambahkan.");
    }

    public function show(Siswa $siswa)
    {
        Gate::authorize('view', $siswa);
        return view('academic.siswa.show', compact('siswa'));
    }

    public function edit(Siswa $siswa)
    {
        Gate::authorize('update', $siswa);
        return view('academic.siswa.edit', compact('siswa'));
    }

    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        Gate::authorize('update', $siswa);

        $siswa->update($request->validated());

        return redirect()
            ->route('academic.siswa.index')
            ->with('success', "Data siswa {$siswa->nama} berhasil diperbarui.");
    }

    public function destroy(Siswa $siswa)
    {
        Gate::authorize('delete', $siswa);

        $siswa->delete();

        return redirect()
            ->route('academic.siswa.index')
            ->with('success', "Data siswa {$siswa->nama} berhasil dihapus.");
    }
}
