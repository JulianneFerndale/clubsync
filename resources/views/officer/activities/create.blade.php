@extends('layouts.app-officer')
@section('title', 'Add Activity')
@section('club-name', $club?->name ?? 'ClubSync')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 bg-[#1B5E20] px-5 py-4">
    <a href="{{ route('officer.activities.index') }}" class="text-white/70 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>
    <h1 class="text-white font-bold text-xl">Add Activity</h1>
</div>

<div class="px-4 py-5">

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Duplicate warning (non-blocking) --}}
    @if(session('duplicate_warning'))
        <div class="mb-4 bg-[#F9A825]/10 border border-[#F9A825]/40 rounded-xl px-4 py-3">
            <p class="text-sm text-gray-700 font-medium">
                A club activity with this title already exists on the selected date. You may still proceed.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('officer.activities.store') }}" enctype="multipart/form-data" class="space-y-4" id="event-form"
          data-loading="dialog" data-loading-message="Adding event"
          x-data="{ activityType: '{{ old('activity_type', 'internal_meeting') }}' }">
        @csrf

        {{-- Pass confirmed = true if the user is re-submitting after duplicate warning --}}
        @if(session('duplicate_warning'))
            <input type="hidden" name="confirmed" value="1">
        @endif

        {{-- Activity title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Activity Title <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   placeholder="Upcoming Activity..."
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                          {{ $errors->has('title') ? 'border-red-400' : 'border-gray-300' }}">
            @error('title')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Description <span class="text-red-500">*</span>
            </label>
            <textarea name="description" rows="3" required
                      placeholder="Activity description..."
                      class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30 resize-none
                             {{ $errors->has('description') ? 'border-red-400' : 'border-gray-300' }}">{{ old('description') }}</textarea>
            @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Date --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Date <span class="text-red-500">*</span>
            </label>
            <input type="date" name="date" value="{{ old('date') }}" required
                   min="{{ today()->toDateString() }}"
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                          {{ $errors->has('date') ? 'border-red-400' : 'border-gray-300' }}">
            @error('date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Time (start + end) --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Start Time <span class="text-red-500">*</span>
                </label>
                <input type="time" name="time_start" value="{{ old('time_start') }}" required
                       class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                              {{ $errors->has('time_start') ? 'border-red-400' : 'border-gray-300' }}">
                @error('time_start')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    End Time <span class="text-red-500">*</span>
                </label>
                <input type="time" name="time_end" value="{{ old('time_end') }}" required
                       class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                              {{ $errors->has('time_end') ? 'border-red-400' : 'border-gray-300' }}">
                @error('time_end')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Location --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Location <span class="text-red-500">*</span>
            </label>
            <select name="venue" required
                    class="w-full border rounded-xl px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                           {{ $errors->has('venue') ? 'border-red-400' : 'border-gray-300' }}">
                <option value="">Select a venue...</option>
                @foreach($venues as $venue)
                    <option value="{{ $venue }}" {{ old('venue') === $venue ? 'selected' : '' }}>
                        {{ $venue }}
                    </option>
                @endforeach
            </select>
            @error('venue')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Purpose / Objectives --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Purpose / Objectives <span class="text-red-500">*</span>
            </label>
            <textarea name="purpose" rows="3" required
                      placeholder="State the purpose and objectives of this activity..."
                      class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30 resize-none
                             {{ $errors->has('purpose') ? 'border-red-400' : 'border-gray-300' }}">{{ old('purpose') }}</textarea>
            @error('purpose')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Expected Participants --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Expected Participants <span class="text-red-500">*</span>
            </label>
            <input type="number" name="expected_participants" value="{{ old('expected_participants') }}"
                   min="1" required placeholder="0"
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                          {{ $errors->has('expected_participants') ? 'border-red-400' : 'border-gray-300' }}">
            @error('expected_participants')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Activity Type --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Activity Type <span class="text-red-500">*</span>
            </label>
            <select name="activity_type" required x-model="activityType"
                    class="w-full border rounded-xl px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                           {{ $errors->has('activity_type') ? 'border-red-400' : 'border-gray-300' }}">
                <option value="internal_meeting">Internal Meeting</option>
                <option value="acle">ACLE</option>
                <option value="community_involvement">Community Involvement</option>
                <option value="campus_resource_use">Campus Resource Use</option>
                <option value="other_external">Other External Activity</option>
            </select>
            @error('activity_type')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-1.5" x-show="activityType === 'internal_meeting'">
                Internal meetings are scheduled immediately — no DSA approval required.
            </p>
            <p class="text-xs text-[#F9A825] mt-1.5 font-medium" x-show="activityType !== 'internal_meeting'">
                This activity type requires DSA approval before it is confirmed.
            </p>
        </div>

        {{-- Approval letter (external / ACLE / community / campus resource activities only) --}}
        <div x-show="activityType !== 'internal_meeting'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Approval Letter
                <span class="text-xs text-gray-400 font-normal ml-1">(Optional, PDF/JPG/PNG, max 5MB)</span>
            </label>
            <input type="file" name="approval_letter" accept=".pdf,.jpg,.jpeg,.png"
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30
                          {{ $errors->has('approval_letter') ? 'border-red-400' : 'border-gray-300' }}">
            @error('approval_letter')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Reminder (optional, UI only — dispatch implemented in notification step) --}}
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-sm font-medium text-gray-700 mb-3">
                Set Reminder Notification
                <span class="text-xs text-gray-400 font-normal ml-1">(Optional)</span>
            </p>
            <div class="space-y-2">
                @foreach([
                    'none'    => 'None',
                    '1440'    => '1 day before',
                    '60'      => '1 hour before',
                    '30'      => '30 minutes before',
                    'custom'  => 'Custom',
                ] as $value => $label)
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="reminders[]" value="{{ $value }}"
                               class="w-4 h-4 rounded border-gray-300 text-[#1B5E20] focus:ring-[#1B5E20]"
                               {{ in_array($value, old('reminders', ['none'])) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-600">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Buttons --}}
        <div class="grid grid-cols-2 gap-3 pt-2">
            <a href="{{ route('officer.activities.index') }}"
               class="flex items-center justify-center border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl py-3.5 hover:bg-[#1B5E20]/5 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity">
                Post Activity
            </button>
        </div>

    </form>
</div>

@endsection
