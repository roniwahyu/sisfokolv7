<?php

namespace App\Http\Controllers\Student;

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
        $stats = $this->dashboardService->getStudentStats($request->user());

        return view('student.dashboard', compact('stats'));
    }
}
