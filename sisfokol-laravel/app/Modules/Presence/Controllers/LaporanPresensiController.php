<?php

namespace App\Modules\Presence\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Attendance;
use App\Models\Permit;
use App\Modules\Academic\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LaporanPresensiController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Attendance::class);

        $month = $request->input('month', now()->format('Y-m'));

        try {
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate   = $startDate->copy()->endOfMonth();
        } catch (\Throwable $e) {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        }

        // Summary stats for the selected month
        $totalPresensi = Attendance::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'in')
            ->count();

        $totalTerlambat = Attendance::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'in')
            ->where('status', 'late')
            ->count();

        $totalAbsen = Absence::whereBetween('date', [$startDate, $endDate])->count();

        $totalIzin = Permit::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->count();

        // Daily trend data for chart
        $dailyTrend = Attendance::selectRaw('DATE(date) as day, COUNT(*) as total, SUM(status = \'late\') as terlambat')
            ->where('type', 'in')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Top 10 most absent students
        $topAbsen = Siswa::withCount([
            'kelasSiswa' => function ($q) {},
        ])
        ->select('siswa.*')
        ->selectRaw('COUNT(attendances.id) as hadir_count')
        ->leftJoin('attendances', function ($join) use ($startDate, $endDate) {
            $join->on('attendances.attendable_id', '=', 'siswa.id')
                ->where('attendances.attendable_type', Siswa::class)
                ->where('attendances.type', 'in')
                ->whereBetween('attendances.date', [$startDate, $endDate]);
        })
        ->groupBy('siswa.id')
        ->orderBy('hadir_count', 'asc')
        ->limit(10)
        ->get();

        return view('presence.laporan', compact(
            'month', 'startDate', 'endDate',
            'totalPresensi', 'totalTerlambat', 'totalAbsen', 'totalIzin',
            'dailyTrend', 'topAbsen'
        ));
    }
}
