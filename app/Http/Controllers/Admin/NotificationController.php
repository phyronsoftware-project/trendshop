<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        // Convert stored UTC timestamps before selecting the three local Cambodia date groups.
        $localDateExpression = "DATE(CONVERT_TZ(created_at, '+00:00', '".config('app.display_timezone_offset')."'))";

        // Paginate distinct dates so each page contains three complete day groups.
        $distinctDates = AdminNotification::query()
            ->selectRaw($localDateExpression.' AS notification_date')
            ->distinct()
            ->toBase();
        $datePages = DB::query()
            ->fromSub($distinctDates, 'notification_dates')
            ->orderByDesc('notification_date')
            ->paginate(3)
            ->withQueryString();
        $visibleDates = $datePages->getCollection()->pluck('notification_date')->all();

        $notifications = AdminNotification::query()
            ->with('order:id,order_number')
            ->whereIn(DB::raw($localDateExpression), $visibleDates)
            ->latest('created_at')
            ->latest('id')
            ->get();
        $notificationGroups = $notifications->groupBy(function (AdminNotification $notification): string {
            $createdAt = $notification->created_at->timezone(config('app.display_timezone'));

            return match (true) {
                $createdAt->isToday() => 'Today',
                $createdAt->isYesterday() => 'Yesterday',
                default => $createdAt->format('l, d M Y'),
            };
        });

        return view('admin.notifications.index', compact('datePages', 'notificationGroups'));
    }

    public function open(AdminNotification $notification): RedirectResponse
    {
        $notification->update(['read_at' => $notification->read_at ?? now()]);
        $notification->loadMissing('order');

        if ($notification->order === null) {
            return redirect()->route('admin.notifications.index');
        }

        return redirect()->route('admin.orders.show', $notification->order);
    }

    public function read(AdminNotification $notification): RedirectResponse
    {
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }
}
