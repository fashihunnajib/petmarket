<x-layouts.admin title="Edit Data Tenant">
    <div class="space-y-6 max-w-2xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Edit Tenant</h1>
                <p class="mt-1 text-sm text-zinc-500">Ubah data toko/tenant: {{ $tenant->name }}</p>
            </div>
            <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-zinc-100">
                Batal
            </a>
        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium mb-1">Nama Tenant / Toko</label>
                    <input type="text" id="tenant-name" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Kode Tenant</label>
                        <input type="text" name="code" value="{{ old('code', $tenant->code) }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Slug URL</label>
                        <input type="text" id="tenant-slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                        @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $tenant->latitude) }}" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $tenant->longitude) }}" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border p-2 text-sm dark:bg-zinc-800">
                        <option value="active" {{ old('status', $tenant->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ old('status', $tenant->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="suspended" {{ old('status', $tenant->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 text-base font-semibold text-white hover:bg-emerald-700 transition-colors shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('tenant-name');
            const slugInput = document.getElementById('tenant-slug');

            if (nameInput && slugInput) {
                nameInput.addEventListener('keyup', function() {
                    slugInput.value = nameInput.value.toLowerCase().trim().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
                });
            }
        });
    </script>
</x-layouts.admin>