<x-layouts.admin title="Pengaturan Platform">
    <div class="space-y-6">
        <!-- Header Judul dengan Garis Hijau -->
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Pengaturan Platform dan Komisi</h2>
            <div class="h-[2px] w-full bg-emerald-400"></div>
        </div>

        <!-- Alert Flash Message -->
        @if (session('status'))
            <div class="rounded-sm border border-emerald-400 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <!-- Form Pengaturan Platform -->
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf

            <!-- Container Card Utama -->
            <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-8">

                <!-- SEKSI 1: SKEMA POTONGAN KOMISI TRANSAKSI -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">
                        1. SKEMA POTONGAN KOMISI TRANSAKSI & KEUANGAN
                    </h3>
                    <p class="text-xs text-gray-700 font-medium">
                        Tentukan berapa nilai yang dipotong oleh platform dari setiap transaksi yang berhasil diselesaikan.
                    </p>

                    <div class="grid gap-4 pt-2 sm:grid-cols-3">
                        <!-- Nilai Potongan Komisi Platform -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">
                                Nilai Potongan Komisi Platform (%):
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                name="platform_commission_rate"
                                value="{{ old('platform_commission_rate', $settings['platform_commission_rate']) }}"
                                class="w-full rounded-sm border border-gray-300 px-3 py-1.5 text-sm focus:border-emerald-500 focus:outline-none"
                                required
                            >
                            @error('platform_commission_rate')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Tarif Pajak Standard / PPN -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">
                                Tarif Pajak Standard / PPN (%):
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                name="tax_rate"
                                value="{{ old('tax_rate', $settings['tax_rate']) }}"
                                class="w-full rounded-sm border border-gray-300 px-3 py-1.5 text-sm focus:border-emerald-500 focus:outline-none"
                                required
                            >
                            @error('tax_rate')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Biaya Layanan Aplikasi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">
                                Biaya Layanan Aplikasi (%):
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                name="service_fee_rate"
                                value="{{ old('service_fee_rate', $settings['service_fee_rate']) }}"
                                class="w-full rounded-sm border border-gray-300 px-3 py-1.5 text-sm focus:border-emerald-500 focus:outline-none"
                                required
                            >
                            @error('service_fee_rate')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- SEKSI 2: PENGATURAN OPERASIONAL & API KEY -->
                <div class="space-y-3 pt-4 border-t border-gray-200">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">
                        2. PARAMETER OPERASIONAL & API KEY PIHAK KETIGA
                    </h3>

                    <div class="grid gap-4 pt-2 sm:grid-cols-2">
                        <!-- Radius Pengiriman -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">
                                Batas Maksimal Radius Pengiriman (KM):
                            </label>
                            <input
                                type="number"
                                step="0.1"
                                name="max_delivery_radius_km"
                                value="{{ old('max_delivery_radius_km', $settings['max_delivery_radius_km']) }}"
                                class="w-full rounded-sm border border-gray-300 px-3 py-1.5 text-sm focus:border-emerald-500 focus:outline-none"
                                required
                            >
                            @error('max_delivery_radius_km')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Payment Gateway Placeholder -->
                        <div>
                            <label class="block text-xs font-bold text-gray-900 mb-1">
                                Payment Gateway (Server Key):
                            </label>
                            <input
                                type="text"
                                value="midtrans-server-key-xxxxxxxxxx"
                                class="w-full rounded-sm border border-gray-300 bg-gray-100 px-3 py-1.5 text-sm text-gray-600 focus:outline-none"
                                disabled
                            >
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="flex justify-end pt-6">
                    <button type="submit" class="rounded-sm border border-emerald-400 bg-emerald-200 px-6 py-2.5 text-xs font-bold text-gray-900 transition hover:bg-emerald-300 uppercase tracking-wider">
                        Simpan Pengaturan Platform
                    </button>
                </div>

            </div>
        </form>
    </div>
</x-layouts.admin>