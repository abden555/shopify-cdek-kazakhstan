<x-admin.layout title="Shipments">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h3 mb-1">CDEK shipments</h2><p class="text-body-secondary mb-0">Created shipments and their current delivery status.</p></div><a class="btn btn-outline-primary" href="{{ route('admin.orders.index') }}"><i class="bi bi-bag me-1"></i>Orders</a></div>
    <section class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Order</th><th>Tracking number</th><th>Recipient</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>
    @forelse($shipments as $shipment)
        <tr><td class="fw-semibold">{{ $shipment->order?->order_number ?? '—' }}</td><td>{{ $shipment->tracking_number ?? 'Awaiting CDEK' }}</td><td>{{ $shipment->recipient['name'] ?? '—' }}</td><td><span class="badge text-bg-{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'cancelled' ? 'secondary' : 'primary') }}">{{ str($shipment->status)->replace('_', ' ')->headline() }}</span></td><td>{{ $shipment->created_at->format('d M Y H:i') }}</td><td>@if($shipment->order)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.orders.shipments.prepare', $shipment->order) }}">Open</a>@endif</td></tr>
    @empty <tr><td colspan="6" class="text-center text-body-secondary py-5">No CDEK shipments have been created yet.</td></tr>@endforelse
    </tbody></table></div>@if($shipments->hasPages())<div class="card-footer bg-white">{{ $shipments->links() }}</div>@endif</section>
</x-admin.layout>
