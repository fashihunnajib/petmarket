<x-layouts.tenant title="Detail Pesanan">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold">Detail Pesanan #{{ $order->id }}</h1>
        <p>Status: {{ ucfirst($order->status) }}</p>
    </div>
</x-layouts.tenant>