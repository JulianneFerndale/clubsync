@extends('layouts.app-member')
@section('title', 'Browse Clubs — ClubSync')
@section('page-title', 'Browse Clubs')

@section('content')

{{-- Header --}}
<div class="bg-[#1B5E20] px-5 pt-12 pb-5">
    <h1 class="text-white font-bold text-xl">Browse Clubs</h1>
    <p class="text-white/60 text-xs mt-1">Discover and join clubs at Saint Columban College</p>

    {{-- Search --}}
    <form method="GET" action="{{ route('member.clubs.index') }}" class="mt-4">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Search by name or acronym…"
                   class="w-full bg-white/15 text-white placeholder-white/50 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white/25 transition">
        </div>
    </form>
</div>

{{-- Type filter tabs --}}
<div class="flex bg-white border-b border-gray-100 sticky top-0 z-10">
    <a href="{{ route('member.clubs.index', ['q' => $search, 'type' => '']) }}"
       class="flex-1 py-3 text-sm font-semibold text-center transition-colors
              {{ !$type ? 'text-[#1B5E20] border-b-2 border-[#F9A825]' : 'text-gray-400' }}">
        All
    </a>
    <a href="{{ route('member.clubs.index', ['q' => $search, 'type' => 'academic']) }}"
       class="flex-1 py-3 text-sm font-semibold text-center transition-colors
              {{ $type === 'academic' ? 'text-[#1B5E20] border-b-2 border-[#F9A825]' : 'text-gray-400' }}">
        Academic
    </a>
    <a href="{{ route('member.clubs.index', ['q' => $search, 'type' => 'non_academic']) }}"
       class="flex-1 py-3 text-sm font-semibold text-center transition-colors
              {{ $type === 'non_academic' ? 'text-[#1B5E20] border-b-2 border-[#F9A825]' : 'text-gray-400' }}">
        Non-Academic
    </a>
</div>

<div class="px-4 py-5">

    @if($clubs->isEmpty())
        <div class="text-center py-14">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-400 font-medium">No clubs found</p>
            @if($search->isNotEmpty())
                <p class="text-xs text-gray-300 mt-1">Try a different search term</p>
                <a href="{{ route('member.clubs.index') }}" class="mt-3 inline-block text-sm text-[#1B5E20] font-semibold">Clear search</a>
            @endif
        </div>
    @else
        <div class="grid grid-cols-3 gap-2.5 md:grid-cols-4 lg:grid-cols-6">
            @foreach($clubs as $club)
                @php $status = $joinedIds->get($club->id); @endphp
                <a href="{{ route('member.clubs.show', $club) }}"
                   class="flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative">

                    {{-- Membership badge --}}
                    @if($status === 'active')
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-green-500"></span>
                    @elseif($status === 'pending')
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#F9A825]"></span>
                    @endif

                    @if($club->profile_photo_url)
                        <img src="{{ $club->profile_photo_url }}"
                             alt="{{ $club->acronym ?? $club->name }}"
                             class="w-14 h-14 rounded-full object-cover border-2 border-[#1B5E20]/20">
                    @else
                        <div class="w-14 h-14 rounded-full bg-[#1B5E20] flex items-center justify-center">
                            <span class="text-white font-bold text-sm">
                                {{ strtoupper(substr($club->acronym ?? $club->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <span class="text-[10px] font-semibold text-gray-700 text-center leading-tight line-clamp-2">
                        {{ $club->acronym ?? $club->name }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-4 mt-4 px-1">
            <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> Member
            </div>
            <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                <span class="w-2 h-2 rounded-full bg-[#F9A825] inline-block"></span> Pending
            </div>
        </div>

        {{-- Pagination --}}
        @if($clubs->hasPages())
            <div class="flex items-center justify-center gap-3 pt-5">
                @if($clubs->onFirstPage())
                    <span class="text-gray-300 text-sm">←</span>
                @else
                    <a href="{{ $clubs->previousPageUrl() }}" class="text-[#1B5E20] text-sm font-semibold">← Prev</a>
                @endif

                <span class="text-xs text-gray-400">Page {{ $clubs->currentPage() }} of {{ $clubs->lastPage() }}</span>

                @if($clubs->hasMorePages())
                    <a href="{{ $clubs->nextPageUrl() }}" class="text-[#1B5E20] text-sm font-semibold">Next →</a>
                @else
                    <span class="text-gray-300 text-sm">→</span>
                @endif
            </div>
        @endif
    @endif

</div>
@endsection
