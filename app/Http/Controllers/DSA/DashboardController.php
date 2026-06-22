<?php

namespace App\Http\Controllers\DSA;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubActivity;
use App\Services\AcleMonitorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private AcleMonitorService $acleMonitor) {}

    public function index(Request $request): View
    {
        $totalClubs        = Club::count();
        $academicClubs     = Club::academic()->count();
        $nonAcademicClubs  = Club::nonAcademic()->count();
        $totalActivities   = ClubActivity::count();

        return view('dsa.dashboard', array_merge(
            compact('totalClubs', 'academicClubs', 'nonAcademicClubs', 'totalActivities'),
            $this->acleMonitor->build($request),
        ));
    }
}
