<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ClubNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $unreadCount = ClubNotification::where('recipient_id', auth_user_id())
            ->where('is_read', false)
            ->count();

        $notifications = ClubNotification::where('recipient_id', auth_user_id())
            ->with('club')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Mark all as read after counting and fetching so the page reflects unread state
        ClubNotification::where('recipient_id', auth_user_id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('member.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(ClubNotification $notification): RedirectResponse
    {
        if ($notification->recipient_id !== auth_user_id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return redirect($notification->action_url ?? route('member.notifications.index'));
    }
}
