@extends('layouts.app-dsa')
@section('title', $club->name . ' — DSA')

@section('content')

{{-- Club header --}}
<div class="bg-[#1B5E20] px-5 pt-5 pb-6">
    <div class="flex items-start justify-between mb-1">
        <a href="{{ route('dsa.clubs.index') }}" class="text-white/70 hover:text-white mt-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <a href="{{ route('dsa.clubs.edit', $club) }}"
           class="flex items-center gap-1.5 bg-white/10 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-white/20 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
            </svg>
            Edit
        </a>
    </div>

    <div class="flex items-center gap-4 mt-3">
        @if($club->profile_photo_url)
            <img src="{{ $club->profile_photo_url }}" alt="{{ $club->name }}"
                 class="w-16 h-16 rounded-full object-cover border-2 border-white/30 flex-shrink-0">
        @else
            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-xl">{{ strtoupper(substr($club->acronym ?? $club->name, 0, 2)) }}</span>
            </div>
        @endif
        <div>
            <h1 class="text-white font-bold text-lg leading-tight">{{ $club->name }}</h1>
            <p class="text-white/60 text-xs mt-0.5">{{ $club->acronym }}</p>
            <div class="flex items-center gap-2 mt-1.5">
                <x-status-badge :status="$club->type ?? ($club->club_type === 'Academic' ? 'academic' : 'non_academic')" />
                @if(! $club->is_active)
                    <x-status-badge status="inactive" />
                @endif
            </div>
        </div>
    </div>
</div>

<div class="px-4 py-5 space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats grid --}}
    <div class="grid grid-cols-3 gap-2.5">
        <div class="bg-[#1B5E20] rounded-xl p-3 text-center">
            <p class="text-white font-bold text-2xl">{{ $stats['members'] }}</p>
            <p class="text-[#F9A825] text-[10px] font-semibold uppercase mt-0.5">Members</p>
        </div>
        <div class="bg-[#1B5E20] rounded-xl p-3 text-center">
            <p class="text-white font-bold text-2xl">{{ $stats['events'] }}</p>
            <p class="text-[#F9A825] text-[10px] font-semibold uppercase mt-0.5">Events</p>
        </div>
        <div class="bg-{{ $stats['pending'] > 0 ? '[#F9A825]' : '[#1B5E20]' }} rounded-xl p-3 text-center">
            <p class="{{ $stats['pending'] > 0 ? 'text-[#1B5E20]' : 'text-white' }} font-bold text-2xl">{{ $stats['pending'] }}</p>
            <p class="{{ $stats['pending'] > 0 ? 'text-[#1B5E20]' : 'text-[#F9A825]' }} text-[10px] font-semibold uppercase mt-0.5">Pending</p>
        </div>
    </div>

    {{-- Club meta --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        @if($club->college)
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">College</span>
            <span class="text-xs font-semibold text-gray-800">{{ $club->college->name }}</span>
        </div>
        @endif
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Adviser</span>
            <span class="text-xs font-semibold text-gray-800">
                {{ $club->adviserUser?->name ?? 'Not assigned' }}
            </span>
        </div>
        @if($club->description)
        <div class="px-4 py-3">
            <span class="text-xs text-gray-500 block mb-1">About</span>
            <p class="text-xs text-gray-700 leading-relaxed">{{ $club->description }}</p>
        </div>
        @endif
    </div>

    {{-- Officers --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Officers</h2>
        @forelse($club->officers as $officer)
            <div class="flex items-center gap-3 bg-white rounded-xl px-4 py-3 border border-gray-100 shadow-sm mb-2">
                <div class="w-9 h-9 rounded-full bg-[#1B5E20]/10 flex items-center justify-center flex-shrink-0">
                    <span class="text-[#1B5E20] text-xs font-bold">{{ strtoupper(substr($officer->user->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">{{ $officer->user->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-500">{{ $officer->user->email ?? '' }}</p>
                </div>
                <span class="bg-[#1B5E20]/10 text-[#1B5E20] text-xs font-semibold px-2.5 py-1 rounded-full capitalize">
                    {{ $officer->position }}
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No officers assigned yet.</p>
        @endforelse
    </div>

    {{-- Members list --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold text-gray-700">Active Members</h2>
            <span class="text-xs text-gray-400">{{ $stats['members'] }} total</span>
        </div>
        @forelse($activeMembers as $member)
            <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-gray-500 text-xs font-bold">{{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $member->user->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $member->user->email ?? '' }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No active members yet.</p>
        @endforelse

        @if($activeMembers->hasPages())
            <div class="pt-3">{{ $activeMembers->links() }}</div>
        @endif
    </div>

    {{-- Recent events --}}
    @if($recentEvents->count())
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Recent Events</h2>
        @foreach($recentEvents as $event)
            <x-event-row :event="$event" />
            @if(! $loop->last)<div class="border-b border-gray-100"></div>@endif
        @endforeach
    </div>
    @endif

</div>
@endsection
