<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = session('firebase_user_id');

        $academicClubs = Club::academic()->active()
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->get();

        $nonAcademicClubs = Club::nonAcademic()->active()
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->get();

        return view('member.dashboard', compact('academicClubs', 'nonAcademicClubs'));
    }
}
