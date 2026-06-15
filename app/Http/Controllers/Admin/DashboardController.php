<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalClubs'       => Club::count(),
            'totalEvents'      => 0,
            'academicClubs'    => Club::where('club_type', 'Academic')->count(),
            'nonAcademicClubs' => Club::where('club_type', 'Non-Academic')->count(),
        ]);
    }
}
