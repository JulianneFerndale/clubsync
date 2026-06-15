@extends('layouts.app-adviser')
@section('title', $announcement->title ?? 'Review Announcement')
@section('page-title', 'Review')

@section('content')

<div class="bg-[#1B5E20] px-5 pt-5 pb-6">
    <a href="{{ route('adviser.announcements.index') }}" class="text-white/70 hover:text-white mb-4 inline-block">
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
    <p class="text-white/50 text-xs mt-2">
        By {{ $announcement->author?->first_name }} {{ $announcement->author?->last_name }}
        · {{ $announcement->created_at->format('F j, Y') }}
    </p>
</div>

<div class="px-4 py-5 space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

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

    {{-- Actions (only for pending_review) --}}
    @if($announcement->status === 'pending_review')

        {{-- Approve --}}
        <form method="POST" action="{{ route('adviser.announcements.approve', $announcement) }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-between bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl px-5 py-4 hover:opacity-90 transition-opacity"
                    onclick="return confirm('Approve and publish this announcement?')">
                Approve & Publish
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </button>
        </form>

        {{-- Request revision --}}
        <div x-data="{ open: false }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between border-2 border-[#F9A825] text-[#F9A825] font-semibold text-sm rounded-xl px-5 py-4 hover:bg-[#F9A825]/5 transition-colors">
                Request Revision
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-2">
                <form method="POST" action="{{ route('adviser.announcements.request-revision', $announcement) }}" class="space-y-2">
                    @csrf
                    @error('adviser_notes')
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                    <textarea name="adviser_notes" rows="3" required
                              placeholder="Explain what needs to be revised..."
                              class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#F9A825]/30 resize-none">{{ old('adviser_notes') }}</textarea>
                    <button type="submit"
                            class="w-full bg-[#F9A825] text-white font-semibold text-sm rounded-xl py-3 hover:opacity-90 transition-opacity">
                        Send Revision Request
                    </button>
                </form>
            </div>
        </div>

        {{-- Reject --}}
        <div x-data="{ open: false }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between border-2 border-red-300 text-red-500 font-semibold text-sm rounded-xl px-5 py-4 hover:bg-red-50 transition-colors">
                Reject
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-2">
                <form method="POST" action="{{ route('adviser.announcements.reject', $announcement) }}" class="space-y-2">
                    @csrf
                    <textarea name="adviser_notes" rows="3" required
                              placeholder="State the reason for rejection..."
                              class="w-full border border-red-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200 resize-none">{{ old('adviser_notes') }}</textarea>
                    <button type="submit"
                            class="w-full bg-red-500 text-white font-semibold text-sm rounded-xl py-3 hover:opacity-90 transition-opacity"
                            onclick="return confirm('Reject this announcement?')">
                        Confirm Rejection
                    </button>
                </form>
            </div>
        </div>

    @else
        {{-- Already reviewed state --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-500 text-center">
            Reviewed {{ $announcement->reviewed_at?->format('F j, Y') }}
        </div>
    @endif

</div>
@endsection
