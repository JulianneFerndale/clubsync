@extends('layouts.app-member')
@section('title', 'Home — ClubSync')

@section('content')

{{-- Green header --}}
<div class="bg-[#1B5E20] px-5 pt-12 pb-6">
    <div class="flex items-center justify-between mb-1">
        <div>
            <p class="text-white/60 text-xs font-medium">Welcome back</p>
            <h1 class="text-white font-bold text-xl">SCC ClubSync</h1>
            <p class="text-white/50 text-[11px]">Saint Columban College</p>
        </div>
        <img src="/images/scc-crest.png" alt="SCC"
             class="h-10 w-10 object-contain opacity-90"
             onerror="this.style.display='none'">
    </div>
</div>

<div class="px-4 py-5 space-y-6">

    {{-- CTA buttons --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('member.bulletin.index') }}"
           class="flex items-center justify-center bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity shadow-sm">
            See Announcement
        </a>
        <a href="{{ route('member.clubs.index') }}"
           class="flex items-center justify-center bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity shadow-sm">
            Browse Clubs
        </a>
    </div>

    {{-- My academic clubs --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-gray-800">My Academic Clubs</h2>
        </div>

        @if($academicClubs->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">You have not joined any academic clubs yet.</p>
        @else
            <div class="grid grid-cols-3 gap-2.5">
                @foreach($academicClubs as $club)
                    <x-club-card :club="$club" :href="route('member.clubs.show', $club)" />
                @endforeach
            </div>
        @endif
    </div>

    {{-- My non-academic clubs --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-gray-800">My Non-Academic Clubs</h2>
        </div>

        @if($nonAcademicClubs->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">You have not joined any non-academic clubs yet.</p>
        @else
            <div class="grid grid-cols-3 gap-2.5">
                @foreach($nonAcademicClubs as $club)
                    <x-club-card :club="$club" :href="route('member.clubs.show', $club)" />
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
