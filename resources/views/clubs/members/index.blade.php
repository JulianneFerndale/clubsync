@extends(auth_role() === 'adviser' ? 'layouts.app-adviser' : 'layouts.app-officer')
@section('title', 'Members — ' . ($club?->name ?? 'ClubSync'))
@section('club-name', $club?->name ?? 'ClubSync')
@section('page-title', 'Members')

@section('content')

{{-- Page title bar --}}
<div class="bg-[#1B5E20] px-5 py-4">
    <h1 class="text-white font-bold text-xl">Members</h1>
    <p class="text-white/60 text-xs mt-0.5">{{ $club?->name ?? 'No club assigned' }}</p>
</div>

<div class="px-4 py-5 space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="bg-[#F9A825]/10 border border-[#F9A825]/40 rounded-xl px-4 py-3 text-gray-700 text-sm">
            {{ session('info') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if(! $club)
        <p class="text-sm text-gray-400 text-center py-8">You are not associated with any club.</p>
    @elseif($members->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">No members yet. Students who join this club will appear here.</p>
    @else
        <form method="POST" action="{{ route('clubs.members.store') }}">
            @csrf

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($members as $member)
                    <div class="flex items-center gap-3 px-4 py-3.5">
                        @if($member->registration_status !== 'approved')
                            <input type="checkbox" name="member_ids[]" value="{{ $member->id }}"
                                   class="w-4 h-4 rounded border-gray-300 text-[#1B5E20] focus:ring-[#1B5E20]">
                        @else
                            <span class="w-4 h-4"></span>
                        @endif

                        <div class="w-9 h-9 rounded-full bg-[#1B5E20]/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-[#1B5E20] text-xs font-bold">
                                {{ strtoupper(substr($member->user?->first_name ?? 'U', 0, 1) . substr($member->user?->last_name ?? '', 0, 1)) }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $member->user?->name ?? 'Unknown user' }}</p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $member->user?->email ?? '' }}
                                @if($member->submittedBy)
                                    · submitted by {{ $member->submittedBy->name }}
                                @endif
                            </p>
                            @if($member->registration_status === 'rejected' && $member->dsa_remarks)
                                <p class="text-xs text-red-500 mt-1">DSA remarks: {{ $member->dsa_remarks }}</p>
                            @endif
                        </div>

                        <x-status-badge :status="$member->registration_status" />
                    </div>
                @endforeach
            </div>

            <button type="submit"
                    class="mt-4 w-full bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity">
                Submit Selected to DSA
            </button>
        </form>
    @endif

</div>
@endsection
