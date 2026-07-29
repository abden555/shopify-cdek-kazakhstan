<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Contracts\View\View;

final class ShipmentController extends Controller
{
    public function index(): View
    {
        $shipments = Shipment::query()->with(['order', 'shop'])->latest()->paginate(20);

        return view('admin.shipments.index', compact('shipments'));
    }
}
