<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    /**
     * Menampilkan daftar pengajuan penarikan dana beserta statistik dan filter status.
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->input('status', 'all');

        // 1. Kalkulasi Statistik Keuangan Ringkasan
        $totalPending = Withdrawal::where('status', 'pending')->count();
        $totalPendingAmount = Withdrawal::where('status', 'pending')->sum('amount');
        $totalApproved = Withdrawal::where('status', 'approved')->sum('amount');

        // 2. Query Data Penarikan Dana + Filter Tab Status
        $withdrawals = Withdrawal::with(['tenant', 'bankAccount'])
            ->when(in_array($currentStatus, ['pending', 'approved', 'rejected']), function ($query) use ($currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.withdrawals.index', compact(
            'withdrawals',
            'currentStatus',
            'totalPending',
            'totalPendingAmount',
            'totalApproved'
        ));
    }

    /**
     * Menyutujui pengajuan penarikan dana.
     */
    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan pencairan dana ini sudah pernah diproses.');
        }

        DB::transaction(function () use ($withdrawal) {
            // Update status pengajuan penarikan
            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
            ]);

            // Jika ada tabel saldo tenant, sesuaikan pemotongan saldo riil di sini:
            // $withdrawal->tenant->balance?->decrement('available_amount', $withdrawal->amount);
        });

        return redirect()
            ->back()
            ->with('status', 'Pengajuan penarikan dana berhasil disetujui.');
    }

    /**
     * Menolak pengajuan penarikan dana.
     */
    public function reject(Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan pencairan dana ini sudah pernah diproses.');
        }

        $withdrawal->update([
            'status' => 'rejected',
            'processed_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('status', 'Pengajuan penarikan dana ditolak dan saldo dikembalikan.');
    }
}
