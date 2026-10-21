<x-layouts.admin title="Verifikasi Dana">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Verifikasi Pencairan Dana
                </h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Kelola dan verifikasi permintaan penarikan saldo toko/tenant.
                </p>
            </div>
        </div>

        <!-- Alert Flash Message -->
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <!-- Kartu Summary Financial Status -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Permintaan Pending</p>
                <p class="mt-2 text-3xl font-bold text-amber-600">
                    {{ $totalPending }} <span class="text-xs font-normal text-zinc-400">pengajuan</span>
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Nominal Pending</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">
                    Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Pencairan Disetujui</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    Rp {{ number_format($totalApproved, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <!-- Tabel & Filter -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <!-- Tab Filter Status -->
            <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <a href="{{ route('admin.withdrawals.index', ['status' => 'all']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $currentStatus === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' }}">
                    Semua
                </a>
                <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $currentStatus === 'pending' ? 'bg-amber-500 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' }}"
                   @if($currentStatus === 'pending') style="background-color: #f59e0b; color: #ffffff;" @endif>
                    Pending
                </a>
                <a href="{{ route('admin.withdrawals.index', ['status' => 'approved']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' }}"
                   @if($currentStatus === 'approved') style="background-color: #059669; color: #ffffff;" @endif>
                    Disetujui
                </a>
                <a href="{{ route('admin.withdrawals.index', ['status' => 'rejected']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $currentStatus === 'rejected' ? 'bg-red-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' }}"
                   @if($currentStatus === 'rejected') style="background-color: #dc2626; color: #ffffff;" @endif>
                    Ditolak
                </a>
            </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <th class="px-3 py-3">Tenant / Toko</th>
                            <th class="px-3 py-3">Rekening Bank Tujuan</th>
                            <th class="px-3 py-3">Nominal Penarikan</th>
                            <th class="px-3 py-3">Tanggal Pengajuan</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-800">
                        @forelse ($withdrawals as $item)
                            <tr>
                                <td class="px-3 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $item->tenant?->name ?? 'Toko Tidak Ditemukan' }}
                                    </div>
                                    <div class="text-xs text-zinc-400">Kode: {{ $item->tenant?->code }}</div>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $item->bankAccount?->bank_name ?? 'Bank BCA' }}
                                    </div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $item->bankAccount?->account_number_cipher ? '•••• '.$item->bankAccount?->account_last4 : 'N/A' }} 
                                        a.n {{ $item->bankAccount?->account_holder_name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-3 py-4 font-bold text-zinc-900 dark:text-white">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 text-xs text-zinc-500">
                                    {{ $item->created_at ? $item->created_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-3 py-4">
                                    @if ($item->status === 'pending')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                            Pending
                                        </span>
                                    @elseif ($item->status === 'approved')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                                            Disetujui
                                        </span>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-400">
                                            Ditolak
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-right">
                                    @if ($item->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Tombol Setujui -->
                                            <form action="{{ route('admin.withdrawals.approve', $item) }}" method="POST" onsubmit="return confirm('Setujui pencairan dana sebesar Rp {{ number_format($item->amount, 0, ',', '.') }}?');">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                                                    Setujui
                                                </button>
                                            </form>

                                            <!-- Tombol Tolak -->
                                            <form action="{{ route('admin.withdrawals.reject', $item) }}" method="POST" onsubmit="return confirm('Tolak pengajuan pencairan dana ini? Saldo akan dikembalikan ke tenant.');">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-950 dark:text-red-400">
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-400">Selesai Diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-zinc-500">
                                    Belum ada data pengajuan penarikan dana.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>