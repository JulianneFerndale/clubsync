@extends('layouts.app-member')
@section('title', $club->name . ' — ClubSync')

@section('content')

{{-- Club header --}}
<div class="bg-[#1B5E20] px-5 pt-5 pb-8">
    <a href="{{ route('member.dashboard') }}" class="text-white/70 hover:text-white mb-4 inline-block">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>

    <div class="flex flex-col items-center text-center mt-2">
        @if($club->profile_photo_url)
            <img src="{{ $club->profile_photo_url }}" alt="{{ $club->name }}"
                 class="w-20 h-20 rounded-full object-cover border-4 border-white/20 shadow-lg">
        @else
            <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center shadow-lg">
                <span class="text-white font-bold text-2xl">{{ strtoupper(substr($club->acronym ?? $club->name, 0, 2)) }}</span>
            </div>
        @endif
        <h1 class="text-white font-bold text-xl mt-3">{{ $club->name }}</h1>
        <p class="text-white/60 text-xs mt-0.5">{{ $club->acronym }}</p>
        <div class="mt-2">
            <x-status-badge :status="$club->type ?? ($club->club_type === 'Academic' ? 'academic' : 'non_academic')" />
        </div>
    </div>
</div>

<div class="px-4 py-5 space-y-5">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-blue-700 text-sm">{{ session('info') }}</div>
    @endif

    {{-- Membership action --}}
    @if(! $membership)
        <form method="POST" action="{{ route('member.clubs.join', $club) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-[#1B5E20] text-[#F9A825] font-bold text-sm py-4 rounded-xl hover:opacity-90 transition-opacity shadow-sm">
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

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#1B5E20] rounded-xl p-4 text-center shadow-sm">
            <p class="text-white font-bold text-2xl">{{ $memberCount }}</p>
            <p class="text-[#F9A825] text-xs font-semibold mt-0.5">Members</p>
        </div>
        <div class="bg-[#1B5E20] rounded-xl p-4 text-center shadow-sm">
            <p class="text-white font-bold text-2xl">{{ $upcomingEvents->count() }}</p>
            <p class="text-[#F9A825] text-xs font-semibold mt-0.5">Upcoming Activities</p>
        </div>
    </div>

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

    {{-- Upcoming activities --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold text-gray-700">Upcoming Activities</h2>
        </div>
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

</div>
@endsection
