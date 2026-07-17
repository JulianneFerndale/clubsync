import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ── PWA service worker ──────────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

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

function subscribeToNotifications() {
    const meta = document.querySelector('meta[name="cs-user-id"]');
    const userId = meta && meta.content ? parseInt(meta.content, 10) : 0;
    if (!userId || !window.Echo) return;

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
