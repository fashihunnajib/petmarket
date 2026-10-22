<x-layouts.tenant title="Manajemen Pesanan">
    <div class="space-y-6">
        <!-- Header & Tombol Logout -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Daftar Pesanan Masuk</h1>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                    Logout
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Tabel Pesanan -->
            <table class="w-full text-left text-sm">
                <thead class="border-b text-zinc-500 dark:border-zinc-800">
                    <tr>
                        <th class="pb-3">ID Pesanan</th>
                        <th class="pb-3">Pelanggan</th>
                        <th class="pb-3">Total</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="py-3 font-medium">#{{ $order->public_id ?? $order->id }}</td>
                            <td class="py-3">{{ $order->order->user->name ?? 'Guest' }}</td>
                            <td class="py-3">Rp {{ number_format($order->grand_total_amount ?? $order->grand_total, 0, ',', '.') }}</td>
                            <td class="py-3">
                                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <a href="{{ route('tenant.orders.show', $order) }}" class="text-emerald-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-zinc-500">Belum ada pesanan masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-layouts.tenant>