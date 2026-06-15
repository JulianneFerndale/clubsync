<?php

namespace App\Http\Controllers\DSA;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Event;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalClubs        = Club::count();
        $academicClubs     = Club::academic()->count();
        $nonAcademicClubs  = Club::nonAcademic()->count();
        $totalEvents       = Event::count();

        return view('dsa.dashboard', compact(
            'totalClubs',
            'academicClubs',
            'nonAcademicClubs',
            'totalEvents',
        ));
    }
}
