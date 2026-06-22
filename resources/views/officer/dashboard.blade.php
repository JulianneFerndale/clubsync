@extends('layouts.app-officer')
@section('title', 'Dashboard — Officer')
@section('club-name', $club?->name ?? 'ClubSync')

@section('content')

{{-- Club header --}}
<div class="bg-[#1B5E20] px-5 pt-5 pb-6">
    <a href="#" class="flex items-center gap-3">
        @if($club?->profile_photo_url)
            <img src="{{ $club->profile_photo_url }}" alt="{{ $club->name }}"
                 class="w-12 h-12 rounded-full object-cover border-2 border-white/30">
        @else
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <span class="text-white font-bold text-lg">
                    {{ strtoupper(substr($club?->acronym ?? $club?->name ?? 'C', 0, 2)) }}
                </span>
            </div>
        @endif
        <div class="flex-1">
            <div class="flex items-center gap-1">
                <p class="text-white font-bold text-base">{{ $club?->name ?? 'No Club Assigned' }}</p>
                <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </div>
            <p class="text-white/60 text-xs capitalize">{{ $position ?? 'Officer' }}</p>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
            <span class="text-white/80 text-sm font-semibold">{{ $memberCount }}</span>
            <span class="text-white/50 text-xs">members</span>
        </div>
    </a>
</div>

<div class="px-4 py-5 space-y-5">

    @if(! $club)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-4 text-yellow-800 text-sm">
            You have not been assigned to any club as an officer yet.
        </div>
    @else

    {{-- Quick actions --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('officer.activities.create') }}"
           class="flex items-center justify-center gap-2 bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3 hover:opacity-90 transition-opacity">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Activity
        </a>
        <a href="{{ route('officer.announcements.create') }}"
           class="flex items-center justify-center gap-2 bg-white border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl py-3 hover:bg-[#1B5E20]/5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
            </svg>
            Draft Post
        </a>
    </div>

    {{-- Next activity --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Next Activity</h2>
        @if($nextEvent)
            <div class="bg-[#1B5E20] rounded-xl p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="bg-[#F9A825] rounded-xl px-2.5 py-2 flex flex-col items-center">
                        <span class="text-white font-bold text-lg leading-none">{{ $nextEvent->date->format('d') }}</span>
                        <span class="text-white text-[9px] font-semibold uppercase">{{ $nextEvent->date->format('D') }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">{{ $nextEvent->title }}</p>
                        <p class="text-white/60 text-xs mt-0.5">
                            {{ \Carbon\Carbon::parse($nextEvent->time_start)->format('g:i A') }}
                            · {{ $nextEvent->venue }}
                        </p>
                        <x-status-badge :status="$nextEvent->status" class="mt-2" />
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <p class="text-sm text-gray-400">No upcoming activities. Add one above!</p>
            </div>
        @endif
    </div>

    {{-- Post feed --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">Post Feed</h2>
            <div class="flex bg-gray-100 rounded-lg p-0.5 text-xs font-medium">
                <button class="px-3 py-1 bg-white rounded-md shadow-sm text-gray-700">Newest</button>
                <button class="px-3 py-1 text-gray-400">Oldest</button>
            </div>
        </div>

        @forelse($recentPosts as $post)
            <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm mb-3">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-[#1B5E20]/10 flex items-center justify-center flex-shrink-0">
                        <span class="text-[#1B5E20] text-xs font-bold">
                            {{ strtoupper(substr($post->author->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800">{{ $post->author->name ?? 'Unknown' }}</p>
                            <x-status-badge :status="$post->type" />
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $post->published_at?->diffForHumans() ?? $post->created_at->diffForHumans() }}</p>
                        <p class="text-sm text-gray-700 mt-2 line-clamp-3">{{ $post->content }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-6">No published posts yet.</p>
        @endforelse
    </div>

    @endif
</div>
@endsection
