@extends($layout ?? 'layouts.app-member')
@section('title', $club->name . ' — ClubSync')

@php
    $typeStatus = $club->type ?? ($club->club_type === 'Academic' ? 'academic' : 'non_academic');
    $initials   = strtoupper(substr($club->acronym ?? $club->name, 0, 2));
@endphp

@section('content')

{{-- Banner / cover --}}
<div class="relative">
    <a href="{{ route($dashboardRoute ?? 'member.dashboard') }}"
       class="absolute top-4 left-4 z-10 w-9 h-9 rounded-full bg-black/30 text-white flex items-center justify-center hover:bg-black/50 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>
    @if($club->banner_image)
        <img src="{{ $club->banner_image }}" alt="" class="w-full h-40 object-cover"
             onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{className:'w-full h-40 bg-gradient-to-br from-[#1B5E20] to-[#0d3311]'}))">
    @else
        <div class="w-full h-40 bg-gradient-to-br from-[#1B5E20] to-[#0d3311]"></div>
    @endif
</div>

{{-- Identity --}}
<div class="px-4 -mt-10 relative">
    @if($club->profile_photo_url)
        <img src="{{ $club->profile_photo_url }}" alt="{{ $club->acronym }}"
             class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md bg-white"
             onerror="this.onerror=null;this.outerHTML='<div class=\'w-20 h-20 rounded-full border-4 border-white shadow-md bg-[#1B5E20] flex items-center justify-center\'><span class=\'text-white font-bold text-xl\'>{{ $initials }}</span></div>'">
    @else
        <div class="w-20 h-20 rounded-full border-4 border-white shadow-md bg-[#1B5E20] flex items-center justify-center">
            <span class="text-white font-bold text-xl">{{ $initials }}</span>
        </div>
    @endif

    <h1 class="text-xl font-bold text-gray-900 mt-2">{{ $club->name }}</h1>
    <div class="flex items-center gap-2 mt-0.5">
        <x-status-badge :status="$typeStatus" />
        <span class="text-xs text-gray-400">{{ $club->acronym }}</span>
    </div>
    <p class="text-sm text-gray-500 mt-1.5">
        <span class="font-semibold text-gray-700">{{ $memberCount }}</span> {{ $memberCount === 1 ? 'member' : 'members' }}
        · <span class="font-semibold text-gray-700">{{ $upcomingEvents->count() }}</span> upcoming
    </p>
</div>

<div class="px-4 py-4 space-y-4">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-blue-700 text-sm">{{ session('info') }}</div>
    @endif

    {{-- Membership action (members only; officers view enrolled clubs read-only) --}}
    @unless($readOnly ?? false)
        @if(! $membership)
            <form method="POST" action="{{ route('member.clubs.join', $club) }}"
                  data-loading="dialog" data-loading-message="Submitting membership request">
                @csrf
                <button type="submit"
                        class="w-full bg-[#1B5E20] text-[#F9A825] font-bold text-sm py-3.5 rounded-xl hover:opacity-90 transition-opacity shadow-sm">
                    Join This Club
                </button>
            </form>
        @elseif($membership->status === 'pending')
            <div class="bg-[#F9A825]/10 border border-[#F9A825]/30 rounded-xl px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-[#F9A825]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700">Membership request pending</span>
                </div>
                <form method="POST" action="{{ route('member.clubs.leave', $club) }}"
                      onsubmit="return confirm('Cancel your membership request for {{ addslashes($club->name) }}?')">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 font-medium hover:text-red-700">Cancel</button>
                </form>
            </div>
        @elseif($membership->status === 'active')
            <div class="flex items-center gap-2.5 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span class="text-sm font-semibold text-green-700 flex-1">You are a member of this club</span>
                <form method="POST" action="{{ route('member.clubs.leave', $club) }}"
                      onsubmit="return confirm('Leave {{ addslashes($club->name) }}? You will need to re-apply to rejoin.')">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 font-medium hover:text-red-700">Leave</button>
                </form>
            </div>
        @endif
    @endunless

    {{-- Officer tools — only for officers of THIS club --}}
    @if($canManage ?? false)
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('officer.activities.create') }}"
               class="flex items-center justify-center gap-2 bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Activity
            </a>
            <a href="{{ route('officer.announcements.create') }}"
               class="flex items-center justify-center gap-2 bg-white border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl py-3.5 hover:bg-[#1B5E20]/5 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                </svg>
                Draft Post
            </a>
        </div>
    @endif

    {{-- About --}}
    @if($club->description)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">About</h2>
            <p class="text-sm text-gray-600 leading-relaxed">{{ $club->description }}</p>
            @if($club->adviserUser)
                <p class="text-xs text-gray-400 mt-3">Adviser: <span class="font-semibold text-gray-600">{{ $club->adviserUser->name }}</span></p>
            @endif
        </div>
    @endif

    {{-- Club content — visible only to approved members / club officers --}}
    @if($canViewContent ?? false)
        {{-- Upcoming activities --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Upcoming Activities</h2>
            @forelse($upcomingEvents as $event)
                <x-activity-row :event="$event" />
                @if(! $loop->last)<div class="border-b border-gray-100"></div>@endif
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No upcoming activities.</p>
            @endforelse
        </div>

        {{-- Announcements --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Announcements</h2>
            @forelse($announcements as $ann)
                <div class="bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm mb-2">
                    <p class="text-sm font-semibold text-gray-800">{{ $ann->title ?? 'Announcement' }}</p>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-3">{{ $ann->content }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ $ann->published_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No announcements yet.</p>
            @endforelse
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-8 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-600">Members only</p>
            <p class="text-xs text-gray-400 mt-1">Join this club and get approved to see its activities and announcements.</p>
        </div>
    @endif

</div>
@endsection
