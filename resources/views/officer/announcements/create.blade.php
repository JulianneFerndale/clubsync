@extends('layouts.app-officer')
@section('title', 'Draft Announcement')
@section('club-name', $club?->name ?? 'ClubSync')

@section('content')

<div class="flex items-center gap-3 bg-[#1B5E20] px-5 py-4">
    <a href="{{ route('officer.announcements.index') }}" class="text-white/70 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
    </a>
    <h1 class="text-white font-bold text-xl">Draft Announcement</h1>
</div>

<div class="px-4 py-5">

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('officer.announcements.store') }}" class="space-y-4" id="ann-form">
        @csrf
        <input type="hidden" name="ai_assisted" id="ai_assisted_flag" value="0">

        {{-- Type toggle --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Type <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="announcement" class="sr-only peer"
                           {{ old('type', 'announcement') === 'announcement' ? 'checked' : '' }}>
                    <div class="border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-center text-gray-600
                                peer-checked:border-[#1B5E20] peer-checked:bg-[#1B5E20]/5 peer-checked:text-[#1B5E20] transition-all">
                        Announcement
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="letter" class="sr-only peer"
                           {{ old('type') === 'letter' ? 'checked' : '' }}>
                    <div class="border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-center text-gray-600
                                peer-checked:border-[#1B5E20] peer-checked:bg-[#1B5E20]/5 peer-checked:text-[#1B5E20] transition-all">
                        Letter
                    </div>
                </label>
            </div>
        </div>

        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Title <span class="text-gray-400 text-xs font-normal">(Optional)</span>
            </label>
            <input type="text" name="title" id="title-field" value="{{ old('title') }}"
                   placeholder="Announcement title..."
                   class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30">
        </div>

        {{-- AI Assist (only shown when key is configured) --}}
        @if($aiAvailable)
        <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 text-sm">✦</span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-purple-800">Generate with AI</p>
                    <p class="text-xs text-purple-500 mt-0.5">Describe what this is about and let AI draft the content.</p>
                    <textarea id="ai-context" rows="2" placeholder="e.g. Remind members about upcoming general assembly on June 5..."
                              class="w-full mt-2 border border-purple-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 bg-white resize-none"></textarea>
                    <button type="button" id="ai-btn"
                            class="mt-2 flex items-center gap-2 bg-purple-600 text-white text-xs font-semibold rounded-lg px-4 py-2 hover:bg-purple-700 transition-colors">
                        <span id="ai-btn-text">Generate Draft</span>
                        <svg id="ai-spinner" class="hidden w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </button>
                    <p id="ai-error" class="hidden text-xs text-red-500 mt-1"></p>
                </div>
            </div>
        </div>
        @endif

        {{-- Content --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Content <span class="text-red-500">*</span>
            </label>
            <textarea name="content" id="content-field" rows="8" required
                      placeholder="Write your announcement here..."
                      class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B5E20]/30 resize-none
                             {{ $errors->has('content') ? 'border-red-400' : 'border-gray-300' }}">{{ old('content') }}</textarea>
            @error('content')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2">
            <a href="{{ route('officer.announcements.index') }}"
               class="flex items-center justify-center border-2 border-[#1B5E20] text-[#1B5E20] font-semibold text-sm rounded-xl py-3.5 hover:bg-[#1B5E20]/5 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="bg-[#1B5E20] text-[#F9A825] font-semibold text-sm rounded-xl py-3.5 hover:opacity-90 transition-opacity">
                Save Draft
            </button>
        </div>
    </form>
</div>

@if($aiAvailable)
<script>
document.getElementById('ai-btn')?.addEventListener('click', async () => {
    const title   = document.getElementById('title-field').value.trim();
    const context = document.getElementById('ai-context').value.trim();
    const type    = document.querySelector('input[name="type"]:checked')?.value ?? 'announcement';

    if (! title && ! context) {
        document.getElementById('ai-error').textContent = 'Please enter a title or describe the topic above.';
        document.getElementById('ai-error').classList.remove('hidden');
        return;
    }

    const btn     = document.getElementById('ai-btn');
    const spinner = document.getElementById('ai-spinner');
    const btnText = document.getElementById('ai-btn-text');
    const errEl   = document.getElementById('ai-error');

    btn.disabled  = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Generating…';
    errEl.classList.add('hidden');

    try {
        const res = await fetch('{{ route('officer.announcements.ai-draft') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ title, type, context }),
        });

        const data = await res.json();

        if (! res.ok || data.error) {
            errEl.textContent = data.error ?? 'Generation failed. Try again.';
            errEl.classList.remove('hidden');
        } else {
            document.getElementById('content-field').value = data.content;
            document.getElementById('ai_assisted_flag').value = '1';
        }
    } catch (e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.textContent = 'Generate Draft';
    }
});
</script>
@endif

@endsection
