<x-admin.layout title="Orders">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1">Shopify orders</h2>
            <p class="text-body-secondary mb-0">Orders synced from connected Shopify stores.</p>
        </div>
        <form method="POST" action="{{ route('admin.orders.sync') }}">
            @csrf
            <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Sync Shopify orders</button>
        </form>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Store</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Total</th>
                        <th scope="col">Fulfillment</th>
                        <th scope="col">Shipment</th>
                        <th scope="col">Ordered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php($address = $order->shipping_address ?? [])
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $order->order_number ?: '—' }}</div>
                                <div class="small text-body-secondary">{{ $order->external_id }}</div>
                            </td>
                            <td>{{ $order->shop->name }}</td>
                            <td>
                                <div>{{ trim(($address['firstName'] ?? '').' '.($address['lastName'] ?? '')) ?: '—' }}</div>
                                <div class="small text-body-secondary">{{ $address['city'] ?? $order->email ?? '—' }}</div>
                            </td>
                            <td>{{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}</td>
                            <td><span class="badge text-bg-light border">{{ str($order->fulfillment_status ?? 'unfulfilled')->lower()->replace('_', ' ') }}</span></td>
                            <td>
                                @if ($order->shipments_count)
                                    <span class="badge text-bg-success">{{ $order->shipments_count }} created</span>
                                @else
                                    <a class="btn btn-sm {{ empty($address['city']) || empty($address['phone']) ? 'btn-outline-warning' : 'btn-primary' }}" href="{{ route('admin.orders.shipments.prepare', $order) }}">Prepare shipment</a>
                                @endif
                            </td>
                            <td>{{ $order->ordered_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-5">No Shopify orders have been synchronized yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer bg-white">{{ $orders->links() }}</div>
        @endif
    </section>
</x-admin.layout>
