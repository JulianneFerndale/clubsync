import '@hotwired/turbo'; // SPA-style navigation (no full page reloads); auto-starts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ── PWA service worker ──────────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((reg) => reg.update())
            .catch(() => {});
    });

    // When a new worker takes control (e.g. after an asset rebuild), reload once
    // so the page uses the fresh worker + assets. This self-heals the "unstyled
    // page from a stale cache" problem instead of requiring a manual cache clear.
    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) return;
        reloading = true;
        window.location.reload();
    });
}

// ── Turbo: let the browser handle file downloads natively ───────────────────
// Turbo would otherwise try to "render" an attachment response. Any link to a
// download route (…/download, …/letter, …/pdf, …/xlsx) is taken over the wire.
document.addEventListener('turbo:click', (event) => {
    const url = event.detail?.url ?? event.target?.href ?? '';
    let path;
    try { path = new URL(url, window.location.href).pathname; } catch { return; }
    if (/\/(download|letter|pdf|xlsx)(?:[/?]|$)/i.test(path)) {
        event.preventDefault();
        window.location.href = url; // native request → browser downloads the file
    }
});

// ── Real-time notifications via Laravel Reverb ──────────────────────────────
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    // Connect to Reverb relative to however this page was loaded, so the same
    // build works locally (http://…:8080) and behind an HTTPS tunnel/proxy
    // (wss on 443, where a front proxy routes /app to Reverb) with no rebuild.
    const isHttps = window.location.protocol === 'https:';
    const localPort = Number(import.meta.env.VITE_REVERB_PORT) || 8080;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: window.location.hostname,
        wsPort: localPort,
        wssPort: 443,
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
    });

    document.addEventListener('DOMContentLoaded', subscribeToNotifications);
}

let notificationsSubscribed = false;

function subscribeToNotifications() {
    // Echo + the WebSocket persist across Turbo navigations, so subscribe once.
    if (notificationsSubscribed) return;

    const meta = document.querySelector('meta[name="cs-user-id"]');
    const userId = meta && meta.content ? parseInt(meta.content, 10) : 0;
    if (!userId || !window.Echo) return;

    notificationsSubscribed = true;

    window.Echo.private(`notifications.${userId}`)
        .listen('.notification.created', (e) => {
            updateBadge(e.unread);
            showToast(e);
        });
}

// Update every notification badge in the chrome (member layout has two).
function updateBadge(count) {
    const n = Number(count) || 0;
    document.querySelectorAll('.js-notif-badge').forEach((el) => {
        el.textContent = n > 9 ? '9+' : String(n);
        el.style.display = n > 0 ? '' : 'none';
    });
}

// Lightweight, self-contained toast (no markup required in Blade).
function showToast(payload) {
    let host = document.getElementById('cs-toast-host');
    if (!host) {
        host = document.createElement('div');
        host.id = 'cs-toast-host';
        host.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:120;display:flex;flex-direction:column;gap:.5rem;max-width:22rem;';
        document.body.appendChild(host);
    }

    const toast = document.createElement('a');
    toast.href = payload.action_url || '#';
    toast.style.cssText = 'display:block;background:#fff;border-left:4px solid #1B5E20;border-radius:.75rem;box-shadow:0 10px 25px rgba(0,0,0,.15);padding:.75rem 1rem;text-decoration:none;color:#111827;transform:translateX(120%);transition:transform .25s ease;';

    const title = document.createElement('p');
    title.style.cssText = 'font-weight:700;font-size:.875rem;margin:0 0 .15rem;';
    title.textContent = payload.title || 'New notification';

    const body = document.createElement('p');
    body.style.cssText = 'font-size:.8rem;color:#4b5563;margin:0;line-height:1.3;';
    body.textContent = payload.body || '';

    toast.appendChild(title);
    toast.appendChild(body);
    host.appendChild(toast);

    requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });

    const dismiss = () => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 250);
    };
    setTimeout(dismiss, 6000);
}
