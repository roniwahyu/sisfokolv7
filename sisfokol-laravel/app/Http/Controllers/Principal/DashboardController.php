<?php

namespace App\Http\Controllers\Principal;

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
        $stats = $this->dashboardService->getAdminStats(); // Principal sees all stats

        return view('principal.dashboard', compact('stats'));
    }
}
