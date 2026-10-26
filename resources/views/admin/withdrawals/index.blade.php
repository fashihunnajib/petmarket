<x-layouts.admin title="Verifikasi Dana">
    <div class="space-y-6">
        <!-- Header Judul dengan Garis Hijau -->
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">Verifikasi Penarikan Dana</h2>
            <div class="h-[2px] w-full bg-emerald-400"></div>
        </div>

        <!-- Alert Flash Message -->
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

        <!-- Kartu Summary Financial Status -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Permintaan Pending
                </div>
                <div class="p-4">
                    <div class="text-3xl font-black text-amber-600">{{ $totalPending }}</div>
                    <div class="mt-1 text-xs font-semibold text-gray-600">Pengajuan Menunggu</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Nominal Pending
                </div>
                <div class="p-4">
                    <div class="text-2xl font-black text-amber-600">
                        Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-sm border border-emerald-400 bg-white text-center">
                <div class="border-b border-emerald-300 bg-emerald-200 py-1 text-sm font-semibold text-gray-900">
                    Total Pencairan Disetujui
                </div>
                <div class="p-4">
                    <div class="text-2xl font-black text-emerald-700">
                        Rp {{ number_format($totalApproved, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Box Utama Tabel & Filter -->
        <div class="rounded-sm border border-emerald-400 bg-white p-6 space-y-6">
            <!-- Filter Status & Subheader -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-4">
                <h3 class="text-base font-bold text-gray-900">
                    Daftar antrean pencairan <span class="text-emerald-700">(Status: {{ ucfirst($currentStatus) }})</span>
                </h3>

                <!-- Tab Filter Status -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'all']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'all' ? 'border-emerald-400 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Semua
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'pending' ? 'border-amber-400 bg-amber-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Pending
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'approved']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'approved' ? 'border-emerald-400 bg-emerald-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Disetujui
                    </a>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'rejected']) }}"
                       class="rounded-sm border px-3 py-1 text-xs font-bold transition {{ $currentStatus === 'rejected' ? 'border-red-400 bg-red-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">
                        Ditolak
                    </a>
                </div>
            </div>

            <!-- Tabel Antrean Pencairan -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-emerald-400 text-center text-sm">
                    <thead>
                        <tr class="border-b border-emerald-400 bg-white font-semibold text-gray-900">
                            <th class="border-r border-emerald-400 px-4 py-3">Tgl Pengajuan</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Nama toko</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Bank tujuan</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Nominal</th>
                            <th class="border-r border-emerald-400 px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
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
                                    <span class="font-bold">{{ $item->bankAccount?->bank_name ?? 'BCA' }}</span>
                                    <span class="text-xs text-gray-600">
                                        - {{ $item->bankAccount?->account_number_cipher ? $item->bankAccount?->account_last4 : '1234' }}
                                    </span>
                                    <div class="text-xs text-gray-500">
                                        a.n {{ $item->bankAccount?->account_holder_name ?? '-' }}
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
                                    @elseif ($item->status === 'approved')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-300">
                                            Disetujui
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
                                            <!-- Tombol Setujui / Proses -->
                                            <form action="{{ route('admin.withdrawals.approve', $item) }}" method="POST" onsubmit="return confirm('Setujui pencairan dana sebesar Rp {{ number_format($item->amount, 0, ',', '.') }}?');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-sm border border-emerald-400 bg-emerald-100 px-3 py-1 text-xs font-bold text-gray-900 transition hover:bg-emerald-200">
                                                    Proses
                                                </button>
                                            </form>

                                            <!-- Tombol Tolak -->
                                            <form action="{{ route('admin.withdrawals.reject', $item) }}" method="POST" onsubmit="return confirm('Tolak pengajuan pencairan dana ini? Saldo akan dikembalikan ke tenant.');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-sm border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600 transition hover:bg-red-100">
                                                    Tolak
                                                </button>
                                            </form>
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

            <!-- Catatan Prosedur Modal Pop-up -->
            <div class="rounded-sm border border-emerald-400 bg-white p-4 space-y-2 text-xs text-gray-800 font-medium">
                <div class="flex items-center gap-2 font-bold text-gray-900">
                    <span>Modal pop up saat tombol [PROSES] diklik:</span>
                    <div class="h-[1px] flex-1 bg-emerald-400"></div>
                </div>
                <ol class="list-decimal list-inside space-y-1 text-gray-700 pl-1">
                    <li>Admin melihat instruksi nomor rekening penjual.</li>
                    <li>Admin melakukan transfer manual via Mobile Banking.</li>
                    <li>Admin mengunggah/upload screenshot bukti transfer.</li>
                    <li>Klik tombol [Tandai Terverifikasi] &rarr; saldo tenant dipotong.</li>
                </ol>
            </div>

            <div class="mt-4">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>