<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Club;
use App\Models\ClubActivity;
use App\Models\ClubMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $type   = $request->input('type'); // 'academic', 'non_academic', or null

        $query = Club::active()->with('college');

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower((string) $search).'%'])
                  ->orWhereRaw('LOWER(acronym) LIKE ?', ['%'.strtolower((string) $search).'%']);
            });
        }

        if ($type === 'academic') {
            $query->academic();
        } elseif ($type === 'non_academic') {
            $query->nonAcademic();
        }

        $clubs = $query->orderBy('name')->paginate(18)->withQueryString();

        $joinedIds = ClubMember::where('user_id', auth_user_id())
            ->pluck('status', 'club_id');

        return view('member.clubs.index', compact('clubs', 'joinedIds', 'search', 'type'));
    }

    public function show(Club $club): View
    {
        $club->load('college', 'adviserUser');

        $upcomingEvents = ClubActivity::where('club_id', $club->id)
            ->upcoming()
            ->take(3)
            ->get();

        $announcements = Announcement::where('club_id', $club->id)
            ->published()
            ->take(5)
            ->get();

        $memberCount = $club->members()->where('status', 'active')->count();

        $membership = ClubMember::where('club_id', $club->id)
            ->where('user_id', auth_user_id())
            ->first();

        return view('member.clubs.show', compact(
            'club',
            'upcomingEvents',
            'announcements',
            'memberCount',
            'membership',
        ));
    }

    public function join(Club $club): RedirectResponse
    {
        $existing = ClubMember::where('club_id', $club->id)
            ->where('user_id', auth_user_id())
            ->first();

        if ($existing) {
            return back()->with('info', 'You have already submitted a membership request for this club.');
        }

        ClubMember::create([
            'club_id'    => $club->id,
            'user_id'    => auth_user_id(),
            'status'     => 'pending',
            'joined_at'  => now(),
            'date_joined' => now(),
        ]);

        return back()->with('success', 'Your membership request has been submitted. Please wait for officer approval.');
    }

    public function leave(Club $club): RedirectResponse
    {
        ClubMember::where('club_id', $club->id)
            ->where('user_id', auth_user_id())
            ->delete();

        return back()->with('success', 'You have left ' . $club->name . '.');
    }
}
