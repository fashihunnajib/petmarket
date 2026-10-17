<x-layouts.admin title="Manajemen Tenant">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Manajemen Tenant</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Kelola dan pantau toko yang terdaftar di PetMarket.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Total Tenant</p>
                <p class="mt-2 text-3xl font-bold">{{ $totalTenants }}</p>
            </div>

            <div class="rounded-xl border bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Tenant Aktif</p>
                <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $activeTenants }}</p>
            </div>

            <div class="rounded-xl border bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Menunggu</p>
                <p class="mt-2 text-3xl font-bold text-amber-600">{{ $pendingTenants }}</p>
            </div>
        </div>

        <div class="rounded-xl border bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <form method="GET" action="{{ route('admin.tenants.index') }}"
                  class="mb-5 flex flex-wrap gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama atau kode tenant..."
                    class="min-w-0 flex-1 rounded-lg border border-zinc-300 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                >
                <button class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white">
                    Cari
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-zinc-500 dark:border-zinc-700">
                        <tr>
                            <th class="px-3 py-3">Kode</th>
                            <th class="px-3 py-3">Nama Tenant</th>
                            <th class="px-3 py-3">Slug</th>
                            <th class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-800">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="px-3 py-4">{{ $tenant->code }}</td>
                                <td class="px-3 py-4 font-medium">{{ $tenant->name }}</td>
                                <td class="px-3 py-4">{{ $tenant->slug }}</td>
                                <td class="px-3 py-4">
                                    @php
                                        $statusClass = match ($tenant->status) {
                                            'active' => 'bg-emerald-100 text-emerald-700',
                                            'pending' => 'bg-amber-100 text-amber-700',
                                            'suspended' => 'bg-red-100 text-red-700',
                                            default => 'bg-zinc-100 text-zinc-700',
                                        };
                                    @endphp
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-zinc-500">
                                    Belum ada tenant yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $tenants->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>php artisan optimize:clear