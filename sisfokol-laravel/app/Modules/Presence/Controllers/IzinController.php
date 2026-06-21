<?php

namespace App\Modules\Presence\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permit;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Presence\Services\IzinApprovalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class IzinController extends Controller
{
    public function __construct(private IzinApprovalService $approvalService) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Permit::class);

        $query = Permit::with(['permitable', 'approver'])
            ->latest('date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $permits = $query->paginate(25)->withQueryString();

        return view('presence.izin.index', compact('permits'));
    }

    public function create()
    {
        Gate::authorize('create', Permit::class);

        $siswaList = Siswa::where('status', 'aktif')->orderBy('nama')->get();

        return view('presence.izin.create', compact('siswaList'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Permit::class);

        $data = $request->validate([
            'siswa_id'        => 'required|exists:siswa,id',
            'date'            => 'required|date',
            'type'            => 'required|in:sick,permission,other',
            'reason'          => 'required|string|max:1000',
            'attachment'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('permits', 'public');
        }

        $permit = $this->approvalService->submit([
            'permitable_type' => Siswa::class,
            'permitable_id'   => $data['siswa_id'],
            'date'            => $data['date'],
            'type'            => $data['type'],
            'reason'          => $data['reason'],
            'attachment_path' => $attachmentPath,
        ], Auth::user());

        return redirect()->route('presence.izin.show', $permit)
            ->with('success', 'Pengajuan izin berhasil disimpan.');
    }

    public function show(Permit $permit)
    {
        Gate::authorize('view', $permit);

        $permit->load(['permitable', 'approver', 'user']);

        return view('presence.izin.show', compact('permit'));
    }

    public function approve(Request $request, Permit $permit)
    {
        Gate::authorize('approve', $permit);

        try {
            $this->approvalService->approve($permit, Auth::user());

            return redirect()->route('presence.izin.show', $permit)
                ->with('success', 'Izin berhasil disetujui.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Permit $permit)
    {
        Gate::authorize('approve', $permit);

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        try {
            $this->approvalService->reject($permit, Auth::user(), $request->rejection_reason);

            return redirect()->route('presence.izin.show', $permit)
                ->with('success', 'Izin berhasil ditolak.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Permit $permit)
    {
        Gate::authorize('delete', $permit);

        $permit->delete();

        return redirect()->route('presence.izin.index')
            ->with('success', 'Izin berhasil dihapus.');
    }
}
