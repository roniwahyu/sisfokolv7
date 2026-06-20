<?php

namespace App\Http\Controllers\Homeroom;

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
        $stats = $this->dashboardService->getHomeroomStats($request->user());

        return view('homeroom.dashboard', compact('stats'));
    }
}
