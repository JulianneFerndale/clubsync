@extends('layouts.app-dsa')
@section('title', 'Dashboard — DSA')

@section('content')
{{-- Page title bar --}}
<div class="bg-[#1B5E20] px-5 py-4">
    <h1 class="text-white font-bold text-xl">Dashboard</h1>
    <p class="text-white/60 text-xs mt-0.5">Dean of Student Affairs</p>
</div>

<div class="px-4 py-5 space-y-5">

    {{-- Session error --}}
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Overview cards --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#1B5E20] rounded-xl p-4 shadow-sm">
            <p class="text-[#F9A825] text-xs font-semibold uppercase tracking-wide">Total Clubs</p>
            <p class="text-white font-bold text-3xl mt-1">{{ $totalClubs }}</p>
        </div>
        <div class="bg-[#1B5E20] rounded-xl p-4 shadow-sm">
            <p class="text-[#F9A825] text-xs font-semibold uppercase tracking-wide">Total Events</p>
            <p class="text-white font-bold text-3xl mt-1">{{ $totalEvents }}</p>
        </div>
    </div>

    {{-- Club type overview --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Club Overview</h2>
        <div class="space-y-2">
            <a href="{{ route('dsa.clubs.index', ['type' => 'academic']) }}" class="flex items-center justify-between bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#1B5E20]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B5E20]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Academic Clubs</p>
                        <p class="text-xs text-gray-400">View all academic clubs</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-[#1B5E20]">{{ $academicClubs }}</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </div>
            </a>

            <a href="{{ route('dsa.clubs.index', ['type' => 'non_academic']) }}" class="flex items-center justify-between bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#F9A825]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#F9A825]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Non-Academic Clubs</p>
                        <p class="text-xs text-gray-400">View all non-academic clubs</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold text-[#1B5E20]">{{ $nonAcademicClubs }}</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </div>
            </a>
        </div>
    </div>

    {{-- Create new club button --}}
    <a href="{{ route('dsa.clubs.create') }}"
       class="flex items-center justify-between w-full border-2 border-[#1B5E20] rounded-xl px-4 py-3.5 text-[#1B5E20] font-semibold text-sm hover:bg-[#1B5E20]/5 transition-colors">
        Create a new club
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
    </a>

</div>
@endsection
