<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubOfficer;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth_user_id();

        // The club this user holds an officer position in (drives the quick actions).
        $officerRecord = ClubOfficer::where('user_id', $userId)->with('club')->first();
        $club     = $officerRecord?->club;
        $position = $officerRecord?->position;

        // Like the member home: every academic / non-academic club the user belongs
        // to — whether as a member or as an officer.
        $belongsTo = function ($query) use ($userId) {
            $query->whereHas('members', fn ($m) => $m->where('user_id', $userId))
                  ->orWhereHas('officers', fn ($o) => $o->where('user_id', $userId));
        };

        $academicClubs = Club::active()->academic()->where($belongsTo)->orderBy('name')->get();
        $nonAcademicClubs = Club::active()->nonAcademic()->where($belongsTo)->orderBy('name')->get();

        return view('officer.dashboard', compact(
            'club',
            'position',
            'academicClubs',
            'nonAcademicClubs',
        ));
    }
}
