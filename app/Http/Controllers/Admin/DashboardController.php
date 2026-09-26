<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $stats = [
            'products' => Product::query()->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'orders' => Order::query()->count(),
            'revenue' => Order::query()->where('payment_status', 'paid')->sum('grand_total'),
        ];
        $recentOrders = Order::query()->with('user:id,name,email')->latest()->limit(8)->get();
        $lowStockProducts = Product::query()->with('translations')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(6)->get();
        $unreadNotifications = AdminNotification::query()->whereNull('read_at')->count();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts', 'unreadNotifications'));
    }
}
