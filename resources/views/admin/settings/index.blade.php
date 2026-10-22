<x-layouts.admin title="Pengaturan Platform">
    <div class="max-w-4xl space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                Pengaturan Platform
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Kelola skema komisi, pajak, biaya layanan, dan parameter operasional global.
            </p>
        </div>

        <!-- Alert Flash Message -->
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <!-- Form Pengaturan -->
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Kartu Keuangan & Komisi -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="border-b border-zinc-200 pb-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-white">
                    Skema Komisi & Keuangan Platform
                </h3>

                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Komisi Platform (%)
                        </label>
                        <p class="text-xs text-zinc-500 mb-2">Potongan standar dari setiap transaksi tenant.</p>
                        <input
                            type="number"
                            step="0.01"
                            name="platform_commission_rate"
                            value="{{ old('platform_commission_rate', $settings['platform_commission_rate']) }}"
                            class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            required
                        >
                        @error('platform_commission_rate')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tarif Pajak Standard / PPN (%)
                        </label>
                        <p class="text-xs text-zinc-500 mb-2">Pajak yang dikenakan pada pesanan.</p>
                        <input
                            type="number"
                            step="0.01"
                            name="tax_rate"
                            value="{{ old('tax_rate', $settings['tax_rate']) }}"
                            class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            required
                        >
                        @error('tax_rate')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Biaya Layanan Aplikasi (%)
                        </label>
                        <p class="text-xs text-zinc-500 mb-2">Biaya penanganan platform per transaksi.</p>
                        <input
                            type="number"
                            step="0.01"
                            name="service_fee_rate"
                            value="{{ old('service_fee_rate', $settings['service_fee_rate']) }}"
                            class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            required
                        >
                        @error('service_fee_rate')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Kartu Parameter Operasional -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="border-b border-zinc-200 pb-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-white">
                    Parameter Operasional & Pengiriman
                </h3>

                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Batas Maksimal Radius Pengiriman (KM)
                        </label>
                        <p class="text-xs text-zinc-500 mb-2">Jarak maksimum pengiriman bawaan untuk toko baru.</p>
                        <input
                            type="number"
                            step="0.1"
                            name="max_delivery_radius_km"
                            value="{{ old('max_delivery_radius_km', $settings['max_delivery_radius_km']) }}"
                            class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            required
                        >
                        @error('max_delivery_radius_km')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>