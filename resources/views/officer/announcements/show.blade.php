@extends('layouts.app-officer')
@section('title', $announcement->title ?? 'Announcement')
@section('club-name', $club?->name ?? 'ClubSync')

@section('content')

<div class="bg-[#1B5E20] px-5 pt-5 pb-6">
    <a href="{{ route('officer.announcements.index') }}" class="text-white/70 hover:text-white mb-4 inline-block">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>
    <div class="flex items-start justify-between gap-3">
        <h1 class="text-white font-bold text-xl leading-snug flex-1">
            {{ $announcement->title ?? 'Untitled' }}
        </h1>
        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
            <x-status-badge :status="$announcement->status" />
            <x-status-badge :status="$announcement->type" />
        </div>
    </div>
    <p class="text-white/50 text-xs mt-2">{{ $announcement->created_at->format('F j, Y') }}</p>
</div>

<div class="px-4 py-5 space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Adviser notes (revision or rejection) --}}
    @if($announcement->adviser_notes && in_array($announcement->status, ['revision_required', 'rejected']))
        <div class="bg-orange-50 border border-orange-200 rounded-xl px-4 py-3">
            <p class="text-xs font-semibold text-orange-700 mb-1">
                {{ $announcement->status === 'rejected' ? 'Rejection Reason' : 'Adviser Notes' }}
            </p>
            <p class="text-sm text-orange-800">{{ $announcement->adviser_notes }}</p>
        </div>
    @endif

    {{-- AI badge --}}
    @if($announcement->ai_assisted)
        <div class="flex items-center gap-2 px-3 py-2 bg-purple-50 rounded-xl border border-purple-100">
            <span class="text-purple-500 text-sm">✦</span>
            <span class="text-xs text-purple-600 font-medium">AI-assisted draft</span>
        </div>
    @endif

    {{-- Content --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 font-medium mb-2">Content</p>
        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $announcement->content }}</p>
    </div>

    {{-- Actions --}}
    @if(in_array($announcement->status, ['draft', 'revision_required']))
        <div class="space-y-3 pt-1">
            {{-- Submit for review --}}
            <form method="POST" action="{{ route('officer.announcements.submit', $announcement) }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-between bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl px-5 py-4 hover:opacity-90 transition-opacity"
                        onclick="return confirm('Submit this for adviser review?')">
                    Submit for Review
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
            </form>

            {{-- Edit --}}
            <a href="{{ route('officer.announcements.edit', $announcement) }}"
               class="flex items-center justify-between w-full border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl px-5 py-4 hover:bg-[#1B5E20]/5 transition-colors">
                Edit Draft
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
            </a>
        </div>
    @elseif($announcement->status === 'pending_review')
        <div class="bg-[#F9A825]/10 border border-[#F9A825]/30 rounded-xl px-4 py-3 text-sm text-gray-700 text-center font-medium">
            Awaiting adviser review.
        </div>
    @elseif($announcement->status === 'published')
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm text-center font-medium">
            Published {{ $announcement->published_at?->format('F j, Y') }}
        </div>
    @elseif($announcement->status === 'rejected')
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-600 text-sm text-center font-medium">
            This announcement was rejected by the adviser.
        </div>
    @endif

</div>
@endsection
