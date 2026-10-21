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
     * Tampilkan Daftar Pengajuan Penarikan Dana
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $withdrawals = Withdrawal::with(['tenant', 'bankAccount'])
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'currentStatus' => $status,
            'totalPending' => Withdrawal::where('status', 'pending')->count(),
            'totalPendingAmount' => Withdrawal::where('status', 'pending')->sum('amount'),
            'totalApproved' => Withdrawal::where('status', 'approved')->sum('amount'),
        ]);
    }

    /**
     * Setujui Penarikan Dana
     */
    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan penarikan ini sudah diproses.');
        }

        DB::transaction(function () use ($withdrawal) {
            // Update status pengajuan
            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // [Sesuai Ledger System ERD] Potong saldo tertahan (held_amount) jika ada
            $balance = DB::table('tenant_balances')
                ->where('tenant_id', $withdrawal->tenant_id)
                ->first();

            if ($balance) {
                DB::table('tenant_balances')
                    ->where('tenant_id', $withdrawal->tenant_id)
                    ->decrement('held_amount', $withdrawal->amount);
            }
        });

        return redirect()->back()->with('status', 'Pencairan dana berhasil disetujui!');
    }

    /**
     * Tolak Penarikan Dana (Kembalikan Saldo)
     */
    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengajuan penarikan ini sudah diproses.');
        }

        $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($withdrawal, $request) {
            // Update status pengajuan jadi rejected
            $withdrawal->update([
                'status' => 'rejected',
                'note' => $request->note ?? 'Pengajuan ditolak oleh admin.',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Kembalikan dana ke available_amount saldo toko
            $balance = DB::table('tenant_balances')
                ->where('tenant_id', $withdrawal->tenant_id)
                ->first();

            if ($balance) {
                DB::table('tenant_balances')
                    ->where('tenant_id', $withdrawal->tenant_id)
                    ->increment('available_amount', $withdrawal->amount);

                DB::table('tenant_balances')
                    ->where('tenant_id', $withdrawal->tenant_id)
                    ->decrement('held_amount', $withdrawal->amount);
            }
        });

        return redirect()->back()->with('status', 'Pengajuan penarikan berhasil ditolak dan saldo dikembalikan ke tenant.');
    }
}
