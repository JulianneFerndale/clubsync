@extends('layouts.app-dsa')
@section('title', 'Activity Approvals — DSA')

@section('content')

{{-- Page title bar --}}
<div class="bg-[#1B5E20] px-5 py-4">
    <h1 class="text-white font-bold text-xl">Activity Approvals</h1>
    <p class="text-white/60 text-xs mt-0.5">External, ACLE, and community activities awaiting review</p>
</div>

<div class="px-4 py-5 space-y-6">

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

    {{-- Pending --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Pending Approval</h2>

        @if($pending->isEmpty())
            <p class="text-sm text-gray-400 text-center py-8">No activities awaiting approval.</p>
        @else
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($pending as $event)
                    <div class="px-4 py-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $event->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $event->club->acronym ?? $event->club->name }} · {{ $event->date->format('M j, Y') }} · {{ $event->venue }}
                                </p>
                            </div>
                            <x-status-badge :status="$event->activity_type" />
                        </div>

                        <p class="text-xs text-gray-600 mt-2">{{ $event->purpose }}</p>

                        @if($event->approval_letter_path)
                            <a href="{{ route('dsa.activities.letter', $event) }}" class="text-xs text-[#1B5E20] font-semibold underline mt-1 inline-block">
                                View uploaded approval letter
                            </a>
                        @endif

                        <div class="flex items-center gap-2 mt-3">
                            <form method="POST" action="{{ route('dsa.activities.approve', $event) }}">
                                @csrf
                                <button type="submit"
                                        class="bg-[#1B5E20] text-white text-xs font-semibold rounded-lg px-3 py-1.5 hover:opacity-90 transition-opacity">
                                    Approve
                                </button>
                            </form>
                            <button type="button" onclick="document.getElementById('reject-form-{{ $event->id }}').classList.toggle('hidden')"
                                    class="border border-red-300 text-red-500 text-xs font-semibold rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">
                                Reject
                            </button>
                        </div>

                        <form id="reject-form-{{ $event->id }}" method="POST" action="{{ route('dsa.activities.reject', $event) }}" class="hidden mt-3 space-y-2">
                            @csrf
                            <textarea name="dsa_remarks" rows="2" required placeholder="Reason for rejection..."
                                      class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30"></textarea>
                            <button type="submit"
                                    class="bg-red-500 text-white text-xs font-semibold rounded-lg px-3 py-1.5 hover:opacity-90 transition-opacity">
                                Confirm Rejection
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recently reviewed --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Recently Reviewed</h2>

        @if($reviewed->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">No activities reviewed yet.</p>
        @else
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($reviewed as $event)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $event->title }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $event->club->acronym ?? $event->club->name }}</p>
                        </div>
                        <x-status-badge :status="$event->approval_status" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
