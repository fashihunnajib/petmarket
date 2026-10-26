<x-layouts.admin title="Manajemen & Verifikasi Tenant">
    <div class="space-y-6">
        <!-- Header Judul & Tombol Tambah Tenant -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Manajemen & Verivikasi tenant</h2>
                <div class="h-[2px] w-full bg-emerald-400"></div>
            </div>
            <a href="{{ route('admin.tenants.create') }}" class="rounded-sm border border-emerald-400 bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-gray-900 transition hover:bg-emerald-200 whitespace-nowrap">
                + Tambah Tenant
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-emerald-400 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <!-- Box Pencarian Tenant -->
        <div class="rounded-sm border border-emerald-400 bg-white p-6">
            <form method="GET" action="{{ route('admin.tenants.index') }}" class="flex items-center gap-4">
                <label for="search" class="text-sm font-bold text-gray-900 whitespace-nowrap">
                    Cari Tenant:
                </label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ $search ?? request('search') }}"
                       placeholder=""
                       class="w-full border-b border-gray-400 px-2 py-1 text-sm focus:border-emerald-500 focus:outline-none">
                <button type="submit" class="rounded-sm border border-emerald-400 bg-emerald-100 px-6 py-1 text-sm font-semibold text-gray-900 transition hover:bg-emerald-200">
                    Cari
                </button>
            </form>
        </div>

        <!-- Box Utama Tabel Tenant -->
        <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-emerald-400 text-center text-sm">
                    <thead>
                        <tr class="border-b border-emerald-400 bg-white font-semibold text-gray-900">
                            <th class="border-r border-emerald-400 px-4 py-3">Id tenant</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Nama toko</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Pemilik</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-400 text-gray-900">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="border-r border-emerald-400 px-4 py-3 font-medium">
                                    #{{ $tenant->code }}
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3 font-medium text-left pl-6">
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="hover:underline text-gray-900 font-bold">
                                        {{ $tenant->name }}
                                    </a>
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3 text-left pl-6">
                                    {{ $tenant->owner_name ?? $tenant->users?->first()?->name ?? 'Pemilik' }}
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Indikator Bulatan Status -->
                                        <span class="h-3 w-3 rounded-full {{ $tenant->status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"></span>

                                        <!-- Sakelar Form ON / OFF -->
                                        <div class="inline-flex rounded-full border border-gray-400 p-0.5">
                                            <!-- Tombol ON -->
                                            <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" 
                                                        class="rounded-full px-3 py-0.5 text-xs font-bold transition {{ $tenant->status === 'active' ? 'bg-emerald-700 text-white' : 'text-gray-500 hover:text-gray-900' }}">
                                                    ON
                                                </button>
                                            </form>

                                            <!-- Tombol OFF -->
                                            <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="suspended">
                                                <button type="submit" 
                                                        class="rounded-full px-3 py-0.5 text-xs font-bold transition {{ $tenant->status !== 'active' ? 'bg-red-600 text-white' : 'text-gray-500 hover:text-gray-900' }}">
                                                    OFF
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Tombol Edit -->
                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" 
                                           class="rounded-sm border border-emerald-400 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-gray-900 transition hover:bg-emerald-100">
                                            Edit
                                        </a>
                                        <!-- Tombol Hapus -->
                                        <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tenant {{ $tenant->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="rounded-sm border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-500">
                                    Belum ada tenant yang cocok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Catatan Interaksi -->
            <div class="space-y-1 pt-2 text-xs text-gray-800 font-medium">
                <p class="font-bold">*Catatan Interaksi:</p>
                <p>- Klik sakelar [ON/OFF] untuk menyetujui (true) atau memblokir (false) toko secara instan. Toko Non-Aktif disembunyikan dari aplikasi.</p>
            </div>

            <div class="mt-4">
                {{ $tenants->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>