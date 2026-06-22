<?php

namespace App\Http\Controllers\DSA;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $type   = $request->query('type'); // 'academic' | 'non_academic' | null

        $clubs = Club::query()
            ->when($type === 'academic',     fn ($q) => $q->academic())
            ->when($type === 'non_academic', fn ($q) => $q->nonAcademic())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                ->orWhereRaw('LOWER(acronym) LIKE ?', ['%'.strtolower($search).'%'])
            ))
            ->withCount(['members as active_member_count' => fn ($q) => $q->where('status', 'active')])
            ->with('adviserUser', 'college')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dsa.clubs.index', compact('clubs', 'search', 'type'));
    }

    public function create(): View
    {
        $colleges  = College::orderBy('name')->get();
        $advisers  = User::where('role', 'adviser')->orderBy('first_name')->get();

        return view('dsa.clubs.create', compact('colleges', 'advisers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'acronym'     => ['required', 'string', 'max:50'],
            'type'        => ['required', 'in:academic,non_academic'],
            'college_id'  => ['nullable', 'exists:colleges,id'],
            'adviser_id'  => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'logo'        => ['nullable', 'image', 'max:2048'],
        ]);

        $logoUrl = null;
        if ($request->hasFile('logo')) {
            $path    = $request->file('logo')->store('clubs/logos', 'public');
            $logoUrl = Storage::url($path);
        }

        Club::create([
            'name'             => $validated['name'],
            'acronym'          => $validated['acronym'],
            'slug'             => Str::slug($validated['name']) . '-' . Str::random(4),
            'type'             => $validated['type'],
            'club_type'        => $validated['type'] === 'academic' ? 'Academic' : 'Non-Academic',
            'college_id'       => $validated['college_id'] ?? null,
            'adviser_id'       => $validated['adviser_id'] ?? null,
            'description'      => $validated['description'] ?? null,
            'profile_photo_url' => $logoUrl,
            'is_active'        => true,
        ]);

        return redirect()->route('dsa.clubs.index')
            ->with('success', 'Club created successfully.');
    }

    public function show(Club $club): View
    {
        $club->load('officers.user', 'adviserUser', 'college');

        $activeMembers = $club->members()
            ->where('status', 'active')
            ->with('user')
            ->paginate(10);

        $recentEvents = $club->activities()
            ->orderByDesc('date')
            ->take(5)
            ->get();

        $stats = [
            'members'    => $club->members()->where('status', 'active')->count(),
            'activities' => $club->activities()->count(),
            'officers' => $club->officers()->count(),
            'pending'  => $club->members()->where('status', 'pending')->count(),
        ];

        return view('dsa.clubs.show', compact('club', 'activeMembers', 'recentEvents', 'stats'));
    }

    public function edit(Club $club): View
    {
        $colleges = College::orderBy('name')->get();
        $advisers = User::where('role', 'adviser')->orderBy('first_name')->get();

        return view('dsa.clubs.edit', compact('club', 'colleges', 'advisers'));
    }

    public function update(Request $request, Club $club): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'acronym'     => ['required', 'string', 'max:50'],
            'type'        => ['required', 'in:academic,non_academic'],
            'college_id'  => ['nullable', 'exists:colleges,id'],
            'adviser_id'  => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'logo'        => ['nullable', 'image', 'max:2048'],
            'is_active'   => ['boolean'],
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if it was stored locally
            if ($club->profile_photo_url && Str::startsWith($club->profile_photo_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($club->profile_photo_url, '/storage/'));
            }
            $path = $request->file('logo')->store('clubs/logos', 'public');
            $validated['profile_photo_url'] = Storage::url($path);
        }

        $club->update([
            'name'             => $validated['name'],
            'acronym'          => $validated['acronym'],
            'type'             => $validated['type'],
            'club_type'        => $validated['type'] === 'academic' ? 'Academic' : 'Non-Academic',
            'college_id'       => $validated['college_id'] ?? null,
            'adviser_id'       => $validated['adviser_id'] ?? null,
            'description'      => $validated['description'] ?? null,
            'is_active'        => $request->boolean('is_active', true),
            'profile_photo_url' => $validated['profile_photo_url'] ?? $club->profile_photo_url,
        ]);

        return redirect()->route('dsa.clubs.show', $club)
            ->with('success', 'Club updated successfully.');
    }
}
