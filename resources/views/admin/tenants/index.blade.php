<x-layouts.admin title="Manajemen Tenant">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Manajemen Tenant</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Kelola dan pantau toko yang terdaftar di PetMarket.
                </p>
            </div>
            <a href="{{ route('admin.tenants.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                + Tambah Tenant
            </a>
        </div>

        <!-- Alert Notifikasi Berhasil (Poin 1) -->
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

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
            <form method="GET" action="{{ route('admin.tenants.index') }}" class="mb-5 flex flex-wrap gap-3">
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
                            <th class="px-3 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-800">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="px-3 py-4">{{ $tenant->code }}</td>
                                <td class="px-3 py-4 font-medium">{{ $tenant->name }}</td>
                                <td class="px-3 py-4">{{ $tenant->slug }}</td>
                                <td class="px-3 py-4">
                                    @if ($tenant->status === 'pending')
                                        <div class="flex items-center gap-1.5">
                                            <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700">
                                                    Setujui
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="suspended">
                                                <button type="submit" class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700">
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        @php
                                            $statusClass = match ($tenant->status) {
                                                'active' => 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',
                                                'suspended' => 'bg-red-100 text-red-700 hover:bg-red-200',
                                                default => 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200',
                                            };
                                            $nextStatus = $tenant->status === 'active' ? 'suspended' : 'active';
                                        @endphp
                                        <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                                            <button type="submit" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $statusClass }}">
                                                {{ ucfirst($tenant->status) }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tenant {{ $tenant->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 dark:bg-red-950 dark:text-red-400">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-zinc-500">
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
</x-layouts.admin>