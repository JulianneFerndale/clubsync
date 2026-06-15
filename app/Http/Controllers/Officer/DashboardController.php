<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubOfficer;
use App\Models\Event;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Officers may belong to multiple clubs; show the first one by default.
        $officerRecord = ClubOfficer::where('user_id', auth_user_id())
            ->with('club')
            ->first();

        $club          = $officerRecord?->club;
        $position      = $officerRecord?->position;
        $nextEvent     = null;
        $memberCount   = 0;
        $recentPosts   = collect();

        if ($club) {
            $nextEvent   = Event::where('club_id', $club->id)->upcoming()->first();
            $memberCount = ClubMember::where('club_id', $club->id)
                ->where('status', 'active')->count();
            $recentPosts = Announcement::where('club_id', $club->id)
                ->published()->take(5)->get();
        }

        return view('officer.dashboard', compact(
            'club',
            'position',
            'nextEvent',
            'memberCount',
            'recentPosts',
        ));
    }
}
