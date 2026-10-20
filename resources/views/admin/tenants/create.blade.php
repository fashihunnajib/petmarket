<x-layouts.admin title="Tambah Tenant Baru">
    <div class="space-y-6 max-w-2xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Tambah Tenant Baru</h1>
                <p class="mt-1 text-sm text-zinc-500">Daftarkan toko/tenant baru ke sistem PetMarket.</p>
            </div>
            <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-zinc-100">
                Kembali
            </a>
        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium mb-1">Nama Tenant / Toko</label>
                    <input type="text" id="tenant-name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800" placeholder="Contoh: Petshop Sejahtera">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Kode Tenant</label>
                        <input type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800" placeholder="PST01">
                        @error('code')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Slug URL</label>
                        <input type="text" id="tenant-slug" name="slug" value="{{ old('slug') }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800" placeholder="petshop-sejahtera">
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Latitude (Opsional)</label>
                        <input type="text" name="latitude" value="{{ old('latitude', '-8.2192') }}" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Longitude (Opsional)</label>
                        <input type="text" name="longitude" value="{{ old('longitude', '114.3692') }}" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Status Awal</label>
                    <select name="status" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                        <option value="active">Active (Langsung Aktif)</option>
                        <option value="pending">Pending (Menunggu Verifikasi)</option>
                        <option value="suspended">Suspended (Ditangguhkan)</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 text-base font-semibold text-white hover:bg-emerald-700 transition-colors shadow-sm">
                        Simpan Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Auto-Slug Otomatis -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('tenant-name');
            const slugInput = document.getElementById('tenant-slug');

            if (nameInput && slugInput) {
                nameInput.addEventListener('keyup', function() {
                    slugInput.value = nameInput.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w ]+/g, '')
                        .replace(/ +/g, '-');
                });
            }
        });
    </script>
</x-layouts.admin>