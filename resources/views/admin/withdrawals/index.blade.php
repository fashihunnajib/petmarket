<x-layouts.admin title="Verifikasi Dana">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Verifikasi & Payout Penarikan Dana</h2>
            <div class="h-[2px] w-full bg-emerald-400"></div>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-emerald-400 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-sm border border-red-400 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Antrean & Diproses Bank
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-amber-600">{{ $totalPending }}</div>
                    <div class="mt-1 text-xs font-semibold text-gray-600">Transaksi Aktif</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Nominal Tertahan
                </div>
                <div class="p-4">
                    <div class="text-2xl font-black text-amber-600">
                        Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Total Sudah Ditransfer
                </div>
                <div class="p-4">
                    <div class="text-2xl font-black text-emerald-700">
                        Rp {{ number_format($totalApproved, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-4">
                <h3 class="text-base font-bold text-gray-900">
                    Daftar antrean pencairan <span class="text-emerald-700">(Status: {{ ucfirst($currentStatus) }})</span>
                </h3>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'all']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'all' ? 'border-emerald-400 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Semua
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'pending' ? 'border-amber-400 bg-amber-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Pending
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'processing']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'processing' ? 'border-blue-400 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Diproses Bank
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'paid']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'paid' ? 'border-emerald-400 bg-emerald-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Sudah Ditransfer
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'rejected']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'rejected' ? 'border-red-400 bg-red-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Ditolak
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-emerald-400 text-center text-sm">
                    <thead>
                        <tr class="border-b border-emerald-400 bg-white font-semibold text-gray-900">
                            <th class="border-r border-emerald-400 px-4 py-3">Tgl Pengajuan</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Nama toko</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Bank tujuan</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Nominal</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi / Webhook</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-400 text-gray-900">
                        @forelse ($withdrawals as $item)
                            <tr>
                                <td class="border-r border-emerald-400 px-4 py-3 text-xs text-gray-700 font-medium">
                                    {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
                                    <span class="block text-[10px] text-gray-400">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</span>
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3 text-left pl-6 font-bold">
                                    {{ $item->tenant?->name ?? 'Toko Tidak Ditemukan' }}
                                    @if($item->tenant?->code)
                                        <span class="block text-xs font-normal text-gray-500">#{{ $item->tenant->code }}</span>
                                    @endif
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3 text-left pl-6">
                                    <span class="font-bold">{{ $item->bankAccount?->bank_code ?? '-' }}</span>
                                    <span class="text-xs text-gray-600">
                                        - {{ $item->bankAccount?->account_last4 ?? '----' }}
                                    </span>
                                    <div class="text-xs text-gray-500">
                                        a.n {{ $item->bankAccount?->account_holder ?? '-' }}
                                    </div>
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3 font-bold">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                                <td class="border-r border-emerald-400 px-4 py-3">
                                    @if ($item->status === 'pending')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 border border-amber-300">
                                            Pending
                                        </span>
                                    @elseif ($item->status === 'processing')
                                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 border border-blue-300">
                                            Diproses Bank
                                        </span>
                                    @elseif (in_array($item->status, ['paid', 'approved'], true))
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-300">
                                            Sudah Ditransfer
                                        </span>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800 border border-red-300">
                                            Ditolak
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->status === 'pending')
                                        <div class="flex items-center justify-center gap-2">
                                            <form action="{{ route('admin.withdrawals.approve', $item) }}" method="POST" onsubmit="return confirm('Kirim perintah transfer Rp {{ number_format($item->amount, 0, ',', '.') }} ke Payment Gateway?');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-sm border border-emerald-400 bg-emerald-100 px-3 py-1 text-xs font-bold text-gray-900 transition hover:bg-emerald-200">
                                                    Kirim ke Gateway
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.withdrawals.reject', $item) }}" method="POST" onsubmit="return confirm('Tolak pengajuan pencairan dana ini? Saldo akan dikembalikan ke tenant.');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-sm border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600 transition hover:bg-red-100">
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($item->status === 'processing')
                                        <div class="space-y-1.5">
                                            <span class="block text-[11px] font-semibold text-blue-700">Menunggu Webhook Gateway...</span>
                                            @if (config('services.payout.driver') === 'sandbox')
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <form action="{{ route('admin.withdrawals.webhook-simulate', $item) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="outcome" value="COMPLETED">
                                                        <button type="submit" class="rounded-sm border border-emerald-400 bg-emerald-600 px-2 py-0.5 text-[11px] font-bold text-white hover:bg-emerald-700">
                                                            Webhook Sukses
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.withdrawals.webhook-simulate', $item) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="outcome" value="FAILED">
                                                        <button type="submit" class="rounded-sm border border-red-400 bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white hover:bg-red-700">
                                                            Webhook Gagal
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500 font-medium">Selesai Diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">
                                    Belum ada data pengajuan penarikan dana.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-sm border border-emerald-400 bg-emerald-50/40 p-4 space-y-1.5 text-xs text-gray-800">
                <div class="font-bold text-gray-900">
                    Endpoint Webhook Payout ({{ strtoupper((string) config('services.payout.driver')) }} Mode):
                </div>
                <p class="text-gray-700">
                    URL Callback: <code class="px-1.5 py-0.5 bg-white border border-emerald-300 rounded font-mono">{{ url('/api/webhooks/payout') }}</code>
                    &bull; Header: <code class="px-1.5 py-0.5 bg-white border border-emerald-300 rounded font-mono">X-Callback-Token</code>
                </p>
                <p class="text-[11px] text-gray-600">
                    Saat status <strong>Diproses Bank</strong>, saldo tetap tertahan hingga Payment Gateway mengirim webhook <code>COMPLETED</code> (memotong saldo) atau <code>FAILED</code> (mengembalikan saldo ke tenant).
                </p>
            </div>

            <div class="mt-4">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>