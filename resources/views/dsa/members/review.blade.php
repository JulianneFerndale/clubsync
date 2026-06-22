@extends('layouts.app-dsa')
@section('title', 'Members — ' . $club->name)

@section('content')

{{-- Page title bar --}}
<div class="bg-[#1B5E20] px-5 py-4">
    <h1 class="text-white font-bold text-xl">{{ $club->name }}</h1>
    <p class="text-white/60 text-xs mt-0.5">Member Registration Review</p>
</div>

<div class="px-4 py-5 space-y-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($members->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">No members submitted for this club yet.</p>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
            @foreach($members as $member)
                <div class="px-4 py-3.5">
                    <div class="flex items-center gap-3">
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
                        </div>
                        <x-status-badge :status="$member->registration_status" />
                    </div>

                    @if($member->registration_status === 'pending' && $member->submitted_by)
                        <div class="flex items-center gap-2 mt-3">
                            <form method="POST" action="{{ route('dsa.clubs.members.approve', [$club, $member]) }}">
                                @csrf
                                <button type="submit"
                                        class="bg-[#1B5E20] text-white text-xs font-semibold rounded-lg px-3 py-1.5 hover:opacity-90 transition-opacity">
                                    Approve
                                </button>
                            </form>
                            <button type="button" onclick="document.getElementById('reject-form-{{ $member->id }}').classList.toggle('hidden')"
                                    class="border border-red-300 text-red-500 text-xs font-semibold rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">
                                Reject
                            </button>
                        </div>

                        <form id="reject-form-{{ $member->id }}" method="POST" action="{{ route('dsa.clubs.members.reject', [$club, $member]) }}" class="hidden mt-3 space-y-2">
                            @csrf
                            <textarea name="dsa_remarks" rows="2" required placeholder="Reason for rejection..."
                                      class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30"></textarea>
                            <button type="submit"
                                    class="bg-red-500 text-white text-xs font-semibold rounded-lg px-3 py-1.5 hover:opacity-90 transition-opacity">
                                Confirm Rejection
                            </button>
                        </form>
                    @elseif($member->registration_status === 'rejected' && $member->dsa_remarks)
                        <p class="text-xs text-red-500 mt-2">Remarks: {{ $member->dsa_remarks }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
