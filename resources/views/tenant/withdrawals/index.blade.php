<x-layouts.tenant :tenant="$tenant" title="Penarikan Dana">
    @php
        $tenantSlug = $tenant->slug ?? $tenant->id;
    @endphp

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Penarikan Dana</h2>
        <p class="text-xs text-slate-500 mt-1">Kelola rekening bank tujuan dan ajukan pencairan saldo toko Anda.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-medium rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Ringkasan Saldo 3 Kolom -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-6 grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Saldo Siap Tarik</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-lg font-bold shrink-0">
                    👛
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($availableAmount, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-slate-400">Minimal penarikan Rp {{ number_format($minWithdrawal, 0, ',', '.') }}</p>
        </div>

        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Sedang Diproses</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-lg font-bold shrink-0">
                    ⏳
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($heldAmount, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-slate-400">Maksimal 1 pengajuan aktif dalam satu waktu</p>
        </div>

        <div class="p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold text-slate-400">Total Sudah Dicairkan</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg font-bold shrink-0">
                    ✅
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-bold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($totalApprovedAmount, 0, ',', '.') }}
                </h3>
            </div>
            <p class="text-xs text-slate-400">Total pencairan berstatus disetujui</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Form Ajukan Penarikan Dana (2 Kolom) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4 h-fit">
            <div>
                <h3 class="text-base font-bold text-slate-900">Ajukan Penarikan Dana</h3>
                <p class="text-xs text-slate-400 mt-0.5">Dana akan ditahan sementara hingga diverifikasi dan ditransfer oleh Admin platform.</p>
            </div>

            @if($hasActiveWithdrawal)
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                    Anda masih memiliki pengajuan penarikan dana yang berstatus <strong>Menunggu Persetujuan</strong>. Pengajuan baru dapat dibuat setelah pengajuan sebelumnya selesai diproses.
                </div>
            @elseif($bankAccounts->isEmpty())
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs">
                    Belum ada rekening bank terdaftar. Silakan tambahkan rekening bank terlebih dahulu pada panel di sebelah kanan.
                </div>
            @else
                <form method="POST" action="{{ route('tenant.withdrawals.store', ['tenant' => $tenantSlug]) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="bank_account_id" class="block text-xs font-bold text-slate-700 mb-1">Rekening Bank Tujuan *</label>
                        <select id="bank_account_id"
                                name="bank_account_id"
                                required
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" @selected((string) old('bank_account_id') === (string) $bank->id)>
                                    {{ $bank->bank_code }} • •••• {{ $bank->account_last4 }} — {{ $bank->account_holder }} {{ $bank->is_primary ? '(Utama)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_account_id')
                            <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="amount" class="block text-xs font-bold text-slate-700">Nominal Penarikan (Rp) *</label>
                            @if($availableAmount >= $minWithdrawal)
                                <button type="button"
                                        onclick="document.getElementById('amount').value = '{{ $availableAmount }}'"
                                        class="text-[11px] font-semibold text-emerald-600 hover:underline">
                                    Tarik Semua (Rp {{ number_format($availableAmount, 0, ',', '.') }})
                                </button>
                            @endif
                        </div>
                        <input id="amount"
                               type="number"
                               name="amount"
                               min="{{ $minWithdrawal }}"
                               max="{{ $availableAmount }}"
                               step="1000"
                               value="{{ old('amount') }}"
                               placeholder="Contoh: 500000"
                               required
                               class="w-full px-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        @error('amount')
                            <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                @disabled($availableAmount < $minWithdrawal)
                                class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all">
                            Kirim Pengajuan Pencairan
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Tambah & Daftar Rekening Bank (1 Kolom) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4 h-fit">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekening Bank Toko</h3>
                <p class="text-xs text-slate-400 mt-0.5">Rekening penerima transfer pencairan.</p>
            </div>

            @if($bankAccounts->isNotEmpty())
                <div class="space-y-2">
                    @foreach($bankAccounts as $bank)
                        <div class="p-3 rounded-xl border {{ $bank->is_primary ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-slate-50/50' }} text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-slate-800">{{ $bank->bank_code }} • •••• {{ $bank->account_last4 }}</span>
                                @if($bank->is_primary)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-semibold">Utama</span>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-500 block mt-0.5">{{ $bank->account_holder }}</span>

                            <div class="flex items-center gap-3 mt-2 pt-2 border-t border-slate-200/70">
                                @if(! $bank->is_primary)
                                    <form method="POST" action="{{ route('tenant.bank-accounts.primary', ['tenant' => $tenantSlug, 'bankAccount' => $bank->id]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-[11px] font-semibold text-emerald-600 hover:underline">
                                            Jadikan Utama
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('tenant.bank-accounts.destroy', ['tenant' => $tenantSlug, 'bankAccount' => $bank->id]) }}" onsubmit="return confirm('Hapus rekening {{ $bank->bank_code }} (•••• {{ $bank->account_last4 }})?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-red-600 hover:underline">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.bank-accounts.store', ['tenant' => $tenantSlug]) }}" class="space-y-3 pt-3 border-t border-slate-100">
                @csrf
                <h4 class="text-xs font-bold text-slate-700">+ Tambah Rekening Baru</h4>

                <div>
                    <select name="bank_code" required class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                        <option value="">-- Pilih Bank --</option>
                        @foreach(['BCA', 'BRI', 'BNI', 'MANDIRI', 'BSI', 'CIMB', 'PERMATA', 'JAGO', 'SEABANK'] as $code)
                            <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('bank_code')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <input type="text"
                           name="account_number"
                           value="{{ old('account_number') }}"
                           placeholder="Nomor Rekening (angka saja)"
                           required
                           class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                    @error('account_number')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <input type="text"
                           name="account_holder"
                           value="{{ old('account_holder') }}"
                           placeholder="Nama Pemilik Rekening"
                           required
                           class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                    @error('account_holder')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="is_primary" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Jadikan rekening utama</span>
                </label>

                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition">
                    Simpan Rekening
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Pengajuan Penarikan -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Riwayat Pengajuan Penarikan Dana</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 uppercase text-[11px] border-b border-slate-100">
                        <th class="py-3.5 px-6 font-semibold">Tanggal Pengajuan</th>
                        <th class="py-3.5 px-6 font-semibold">Rekening Tujuan</th>
                        <th class="py-3.5 px-6 font-semibold">Nominal</th>
                        <th class="py-3.5 px-6 font-semibold">Status</th>
                        <th class="py-3.5 px-6 font-semibold">Diproses Pada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($withdrawals as $item)
                        @php
                            $badge = match ($item->status) {
                                'pending', 'requested' => ['Menunggu Persetujuan', 'bg-amber-100 text-amber-700'],
                                'processing' => ['Diproses Bank', 'bg-blue-100 text-blue-700'],
                                'approved', 'paid' => ['Disetujui', 'bg-emerald-100 text-emerald-700'],
                                'rejected' => ['Ditolak', 'bg-red-100 text-red-700'],
                                default => [ucfirst((string) $item->status), 'bg-slate-100 text-slate-700'],
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-6 text-slate-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="font-semibold text-slate-800">{{ $item->bank_code ?? '-' }} • •••• {{ $item->account_last4 ?? '----' }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $item->account_holder ?? '' }}</span>
                            </td>
                            <td class="py-3.5 px-6 font-bold text-slate-900 whitespace-nowrap">
                                Rp {{ number_format((int) $item->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-slate-500 whitespace-nowrap">
                                {{ $item->processed_at ? \Carbon\Carbon::parse($item->processed_at)->format('d M Y, H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400">
                                Belum ada riwayat pengajuan penarikan dana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</x-layouts.tenant>