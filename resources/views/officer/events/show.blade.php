@extends('layouts.app-officer')
@section('title', $event->title)
@section('club-name', $club?->name ?? 'ClubSync')

@section('content')

{{-- Header --}}
<div class="bg-[#1B5E20] px-5 pt-5 pb-6">
    <a href="{{ route('officer.events.index') }}" class="text-white/70 hover:text-white mb-4 inline-block">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1">
            <h1 class="text-white font-bold text-xl leading-snug">{{ $event->title }}</h1>
            <p class="text-white/60 text-xs mt-1">
                {{ $event->date->format('l, F j, Y') }}
            </p>
        </div>
        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
            <x-status-badge :status="$event->status" />
            <x-status-badge :status="$event->event_type" />
        </div>
    </div>
</div>

<div class="px-4 py-5 space-y-5">

    {{-- Key details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Date</span>
            <span class="text-xs font-semibold text-gray-800">{{ $event->date->format('F j, Y') }}</span>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Time</span>
            <span class="text-xs font-semibold text-gray-800">
                {{ \Carbon\Carbon::parse($event->time_start)->format('g:i A') }}
                – {{ \Carbon\Carbon::parse($event->time_end)->format('g:i A') }}
            </span>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Venue</span>
            <span class="text-xs font-semibold text-gray-800">{{ $event->venue }}</span>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Expected</span>
            <span class="text-xs font-semibold text-gray-800">{{ number_format($event->expected_participants) }} participants</span>
        </div>
        @if($attendanceCount > 0)
        <div class="flex items-center justify-between px-4 py-3">
            <span class="text-xs text-gray-500">Attendance Recorded</span>
            <span class="text-xs font-semibold text-green-600">{{ $attendanceCount }} checked in</span>
        </div>
        @endif
    </div>

    {{-- Description --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1.5 font-medium">Description</p>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $event->description }}</p>
    </div>

    {{-- Purpose --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1.5 font-medium">Purpose / Objectives</p>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $event->purpose }}</p>
    </div>

    {{-- Post-event report (if available) --}}
    @if($event->post_report_status)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700">Post-Event Report</p>
            <x-status-badge :status="$event->post_report_status" />
        </div>
    </div>
    @endif

    {{-- Action buttons --}}
    @if($event->status === 'scheduled')
        <div class="space-y-3 pt-2">
            {{-- Record Attendance --}}
            <a href="{{ route('officer.events.attendance', $event) }}"
               class="flex items-center justify-between w-full bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl px-5 py-4 hover:opacity-90 transition-opacity">
                Record Attendance
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </a>

            {{-- Mark as Completed (Step 7) --}}
            <form method="POST" action="{{ route('officer.events.complete', $event) }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-between border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl px-5 py-4 hover:bg-[#1B5E20]/5 transition-colors"
                        onclick="return confirm('Mark this event as completed? This will generate a post-event report.')">
                    Mark as Completed
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </button>
            </form>
        </div>
    @elseif($event->status === 'completed')
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm text-center font-medium">
            This event has been completed.
        </div>
    @endif

</div>
@endsection
