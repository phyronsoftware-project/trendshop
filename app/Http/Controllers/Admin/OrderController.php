<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        // Convert stored UTC timestamps before selecting the three local Cambodia date groups.
        $localDateExpression = "DATE(CONVERT_TZ(COALESCE(placed_at, created_at), '+00:00', '".config('app.display_timezone_offset')."'))";
        $filteredOrders = Order::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')))
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner->where('order_number', 'like', '%'.$request->string('search').'%')->orWhere('recipient_name', 'like', '%'.$request->string('search').'%')));

        // Paginate distinct order dates so one date group is never split between pages.
        $distinctDates = (clone $filteredOrders)
            ->selectRaw($localDateExpression.' AS order_date')
            ->distinct()
            ->toBase();
        $datePages = DB::query()
            ->fromSub($distinctDates, 'order_dates')
            ->orderByDesc('order_date')
            ->paginate(3)
            ->withQueryString();
        $visibleDates = $datePages->getCollection()->pluck('order_date')->all();

        $orders = (clone $filteredOrders)
            ->with(['user:id,name,email', 'items'])
            ->whereIn(DB::raw($localDateExpression), $visibleDates)
            ->orderByRaw('COALESCE(placed_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();
        $orderGroups = $orders->groupBy(function (Order $order): string {
            $placedAt = ($order->placed_at ?? $order->created_at)->timezone(config('app.display_timezone'));

            return match (true) {
                $placedAt->isToday() => 'Today',
                $placedAt->isYesterday() => 'Yesterday',
                default => $placedAt->format('l, d M Y'),
            };
        });

        return view('admin.orders.index', compact('datePages', 'orderGroups'));
    }

    public function show(Order $order): View
    {
        $order->load(['handledBy:id,name', 'items', 'statusHistories.changedBy:id,name', 'user:id,name,email']);

        return view('admin.orders.show', compact('order'));
    }

    public function label(Order $order): View
    {
        $order->load(['items', 'user:id,name,email']);
        $storeSettings = Setting::query()
            ->where('setting_key', 'support_phone')
            ->pluck('value', 'setting_key');
        // Keep the printed reference short while retaining the full order number in the database.
        $orderReference = Str::afterLast($order->order_number, '-');

        return view('admin.orders.label', compact('order', 'orderReference', 'storeSettings'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])], 'payment_status' => ['required', Rule::in(['unpaid', 'pending', 'paid', 'failed', 'refunded'])]]);
        $previousStatus = $order->status;
        $updates = [...$data, 'handled_by' => $request->user()->id];
        if (in_array($data['status'], ['confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
            $updates[$data['status'].'_at'] = now();
        }
        $order->update($updates);
        $order->statusHistories()->create(['changed_by' => $request->user()->id, 'from_status' => $previousStatus, 'to_status' => $data['status'], 'note' => 'Updated from admin dashboard.']);
        AdminNotification::query()->where('order_id', $order->id)->whereNull('read_at')->update(['read_at' => now()]);

        $shouldPrintLabel = in_array($data['status'], ['shipped', 'delivered'], true)
            && $previousStatus !== $data['status'];

        if ($shouldPrintLabel) {
            return redirect()->route('admin.orders.label', ['order' => $order, 'updated' => 1]);
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }
}
