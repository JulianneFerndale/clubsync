@extends('layouts.app-dsa')
@section('title', 'Clubs — DSA')

@section('content')

{{-- Page title bar --}}
<div class="bg-[#1B5E20] px-5 py-4">
    <h1 class="text-white font-bold text-xl">Clubs</h1>
    <p class="text-white/60 text-xs mt-0.5">Manage all registered clubs</p>
</div>

<div class="px-4 py-4 space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ route('dsa.clubs.index') }}" class="flex gap-2">
        @if($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Search clubs..."
                   class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30">
        </div>
        <button type="submit"
                class="px-4 py-2.5 bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl">
            Search
        </button>
    </form>

    {{-- Filter tabs --}}
    <div class="flex bg-gray-100 rounded-xl p-1 text-xs font-semibold">
        <a href="{{ route('dsa.clubs.index', array_filter(['search' => $search])) }}"
           class="flex-1 text-center py-2 rounded-lg transition-colors {{ ! $type ? 'bg-white shadow-sm text-[#1B5E20]' : 'text-gray-500' }}">
            All
        </a>
        <a href="{{ route('dsa.clubs.index', array_filter(['type' => 'academic', 'search' => $search])) }}"
           class="flex-1 text-center py-2 rounded-lg transition-colors {{ $type === 'academic' ? 'bg-white shadow-sm text-[#1B5E20]' : 'text-gray-500' }}">
            Academic
        </a>
        <a href="{{ route('dsa.clubs.index', array_filter(['type' => 'non_academic', 'search' => $search])) }}"
           class="flex-1 text-center py-2 rounded-lg transition-colors {{ $type === 'non_academic' ? 'bg-white shadow-sm text-[#1B5E20]' : 'text-gray-500' }}">
            Non-Academic
        </a>
    </div>

    {{-- Club list --}}
    @forelse($clubs as $club)
        <a href="{{ route('dsa.clubs.show', $club) }}"
           class="flex items-center gap-3 bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">

            {{-- Logo --}}
            @if($club->profile_photo_url)
                <img src="{{ $club->profile_photo_url }}" alt="{{ $club->name }}"
                     class="w-12 h-12 rounded-full object-cover flex-shrink-0">
            @else
                <div class="w-12 h-12 rounded-full bg-[#1B5E20] flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($club->acronym ?? $club->name, 0, 2)) }}</span>
                </div>
            @endif

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $club->name }}</p>
                    <x-status-badge :status="$club->type ?? ($club->club_type === 'Academic' ? 'academic' : 'non_academic')" />
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $club->acronym }}
                    @if($club->college)· {{ $club->college->name }}@endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $club->active_member_count }} active member{{ $club->active_member_count !== 1 ? 's' : '' }}
                    @if($club->adviserUser)
                        · Adviser: {{ $club->adviserUser->name }}
                    @endif
                </p>
            </div>

            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    @empty
        <div class="text-center py-12">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-500 font-medium">No clubs found</p>
            <p class="text-xs text-gray-400 mt-1">Try adjusting your search or filter</p>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if($clubs->hasPages())
        <div class="pt-2">{{ $clubs->links() }}</div>
    @endif

</div>

{{-- FAB: Create club --}}
<a href="{{ route('dsa.clubs.create') }}"
   class="fixed bottom-20 right-4 w-14 h-14 bg-[#1B5E20] rounded-full shadow-lg flex items-center justify-center hover:opacity-90 transition-opacity z-30">
    <svg class="w-7 h-7 text-[#F9A825]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/>
    </svg>
</a>

@endsection
