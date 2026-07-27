<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('shop:id,name,domain')
            ->withCount('shipments')
            ->latest('ordered_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }
}
