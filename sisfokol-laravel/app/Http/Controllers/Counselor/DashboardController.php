<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        $stats = $this->dashboardService->getCounselorStats();

        return view('counselor.dashboard', compact('stats'));
    }
}
