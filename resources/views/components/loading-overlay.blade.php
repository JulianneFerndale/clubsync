{{--
    Global loading UI for the whole app. Three modes, driven by vanilla JS
    (no Alpine/Inertia dependency):
      • nav    — a bare green spinner shown while navigating between pages/layouts
      • splash — full-screen branded screen with a message (login, club enrollment)
      • dialog — a card with a message and a Stop button to cancel an operation

    Opt a form in with:  data-loading="dialog|splash" data-loading-message="Adding event"
    Cancelling (Stop) aborts the in-flight request and restores the form.
--}}
<div id="cs-loading" class="fixed inset-0 z-[100] hidden" role="alert" aria-live="assertive" aria-busy="true">

    {{-- Navigation: just the green circle on a soft backdrop --}}
    <div data-cs-layer="nav" class="hidden absolute inset-0 bg-white/55 backdrop-blur-[1px] items-center justify-center">
        <span class="cs-spinner"></span>
    </div>

    {{-- Splash: full-screen brand screen with a message --}}
    <div data-cs-layer="splash" class="hidden absolute inset-0 bg-[#1B5E20] flex-col items-center justify-center gap-6 px-8 text-center">
        <img src="/images/scc-crest.png" alt="" class="w-20 h-20 object-contain" onerror="this.style.display='none'">
        <span class="cs-spinner cs-spinner--light"></span>
        <p class="text-white font-semibold text-lg" data-cs-message>Loading…</p>
    </div>

    {{-- Dialog: card + message + Stop --}}
    <div data-cs-layer="dialog" class="hidden absolute inset-0 bg-black/40 items-center justify-center p-6">
        <div class="bg-white rounded-3xl shadow-2xl px-10 py-8 w-full max-w-xs flex flex-col items-center gap-4">
            <span class="cs-spinner"></span>
            <p class="text-gray-900 font-semibold text-lg text-center" data-cs-message>Processing…</p>
            <button type="button" id="cs-loading-stop"
                    class="bg-[#1B5E20] text-[#F9A825] font-bold text-base rounded-full px-12 py-2.5 hover:opacity-90 transition-opacity">
                Stop
            </button>
        </div>
    </div>
</div>

<style>
    .cs-spinner{width:44px;height:44px;border-radius:50%;border:4px solid rgba(27,94,32,.18);border-top-color:#1B5E20;animation:cs-spin .8s linear infinite}
    .cs-spinner--light{border-color:rgba(255,255,255,.25);border-top-color:#F9A825}
    @keyframes cs-spin{to{transform:rotate(360deg)}}
    /* Show the active layer as a flex container; layers default to display:none via .hidden */
    #cs-loading:not(.hidden) [data-cs-layer]:not(.hidden){display:flex}
</style>

<script>
(function () {
    const root = document.getElementById('cs-loading');
    if (!root || root.dataset.csReady) return;
    root.dataset.csReady = '1';

    const layers = {
        nav:    root.querySelector('[data-cs-layer="nav"]'),
        splash: root.querySelector('[data-cs-layer="splash"]'),
        dialog: root.querySelector('[data-cs-layer="dialog"]'),
    };

    let active = null;        // current mode, or null when hidden
    let pendingForm = null;   // the form whose submit triggered the overlay
    let navTimer = null;

    function show(mode, message) {
        if (!layers[mode]) return;
        active = mode;
        Object.keys(layers).forEach((k) => layers[k].classList.toggle('hidden', k !== mode));
        if (message) {
            root.querySelectorAll('[data-cs-message]').forEach((el) => { el.textContent = message; });
        }
        root.classList.remove('hidden');
    }

    function hide() {
        active = null;
        clearTimeout(navTimer);
        root.classList.add('hidden');
        // Re-enable any submit buttons we disabled, and clear the submitting guard.
        if (pendingForm) {
            delete pendingForm.dataset.csSubmitting;
            pendingForm = null;
        }
        document.querySelectorAll('button[data-cs-disabled]').forEach((b) => {
            b.disabled = false;
            b.removeAttribute('data-cs-disabled');
        });
    }

    // Public API in case a page needs to drive it manually.
    window.ClubSyncLoading = { show, hide };

    // ── Stop: cancel the in-flight request and restore the UI ──────────────
    const stopBtn = document.getElementById('cs-loading-stop');
    if (stopBtn) {
        stopBtn.addEventListener('click', function () {
            try { window.stop(); } catch (e) {}
            hide();
        });
    }

    // ── Forms that opt in via data-loading ─────────────────────────────────
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;
        if (form.dataset.csSubmitting) return;                 // guard double-submit
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) return; // let native validation show first

        const mode = form.getAttribute('data-loading') === 'splash' ? 'splash' : 'dialog';
        const msg  = form.getAttribute('data-loading-message') || (mode === 'splash' ? 'Please wait…' : 'Processing…');

        form.dataset.csSubmitting = '1';
        pendingForm = form;
        form.querySelectorAll('button[type="submit"]').forEach((b) => {
            b.disabled = true;
            b.setAttribute('data-cs-disabled', '');
        });
        show(mode, msg);
    }, true);

    // ── Lightweight spinner while navigating between pages/layouts ─────────
    document.addEventListener('click', function (e) {
        if (active) return;
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-loading')) return;

        let url;
        try { url = new URL(a.href, window.location.href); } catch (_) { return; }
        if (url.origin !== window.location.origin) return;                 // external link
        if (url.href === window.location.href || url.hash && url.pathname === window.location.pathname) return; // same page / anchor

        // Debounce: only reveal if the next page takes a moment to load, avoiding a flash on fast navigations.
        navTimer = setTimeout(function () { show('nav'); }, 180);
    });

    // ── Cleanup ────────────────────────────────────────────────────────────
    // When restored from the back/forward (bfcache) the overlay must not linger.
    window.addEventListener('pageshow', function (ev) { if (ev.persisted) hide(); });
    window.addEventListener('pagehide', function () { clearTimeout(navTimer); });
})();
</script>
