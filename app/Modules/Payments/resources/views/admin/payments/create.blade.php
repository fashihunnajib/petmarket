<x-layouts.admin title="Buat Tagihan QRIS">
    @php
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <h2 class="whitespace-nowrap text-xl font-bold text-gray-900">Buat Tagihan QRIS Dinamis</h2>
                <div class="h-[2px] w-full bg-emerald-400"></div>
            </div>

            <a href="{{ route('admin.payments.index') }}"
               class="shrink-0 rounded-sm border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100">
                ← Kembali ke Daftar
            </a>
        </div>

        @if (session('error'))
            <div class="rounded-sm border border-red-400 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-5">
            <div class="rounded-sm border border-emerald-300 bg-emerald-50/50 p-4 text-xs text-gray-700 space-y-1">
                <p class="font-bold text-gray-900">
                    Kontrak Gateway Aktif: <code>{{ $gatewayName }}</code> ({{ $isSandbox ? 'Sandbox' : 'Production' }})
                </p>
                <p>
                    Nominal tagihan QRIS <strong>selalu diambil langsung dari <code>orders.grand_total_amount</code> di database</strong>
                    beserta rincian split per tenant. Masa berlaku maksimal {{ (int) floor($expirySeconds / 60) }} menit ({{ $expirySeconds }} detik).
                </p>
            </div>

            @if ($eligibleOrders->isEmpty())
                <div class="rounded-sm border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                    Tidak ada pesanan berstatus menunggu pembayaran yang belum memiliki tagihan QRIS.
                </div>
            @else
                <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="order_id" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-900">
                            Pilih Pesanan (Belum Memiliki Tagihan QRIS)
                        </label>
                        <select id="order_id" name="order_id" required
                                class="w-full rounded-sm border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Pilih Pesanan --</option>
                            @foreach ($eligibleOrders as $order)
                                @php
                                    $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan Guest';
                                    $tenantCount = $order->tenantOrders->count();
                                @endphp
                                <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                    #{{ $order->order_number }} — {{ $customerName }} — Rp {{ number_format((int) $order->grand_total_amount, 0, ',', '.') }} ({{ $tenantCount }} toko · {{ $order->created_at?->setTimezone($tz)->format('d/m/Y H:i') }})
                                </option>
                            @endforeach
                        </select>
                        @error('order_id')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                        <a href="{{ route('admin.payments.index') }}"
                           class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100">
                            Batal
                        </a>
                        <button type="submit"
                                class="rounded-sm border border-emerald-500 bg-emerald-600 px-5 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-emerald-700">
                            Generate Tagihan QRIS
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.admin>