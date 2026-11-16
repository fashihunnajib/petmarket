<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
     * Menyetujui pengajuan penarikan dana.
     */
    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan pencairan dana ini sudah pernah diproses.');
        }

        DB::transaction(function () use ($withdrawal): void {
            $amount = (int) $withdrawal->amount;

            DB::table('withdrawals')
                ->where('id', $withdrawal->id)
                ->update([
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'processed_at' => now(),
                    'active_tenant_lock' => null,
                    'updated_at' => now(),
                ]);

            $balance = DB::table('tenant_balances')
                ->where('tenant_id', $withdrawal->tenant_id)
                ->lockForUpdate()
                ->first();

            if ($balance !== null) {
                $held = is_numeric($balance->held_amount ?? null) ? (int) $balance->held_amount : 0;
                $available = is_numeric($balance->available_amount ?? null) ? (int) $balance->available_amount : 0;

                if ($held >= $amount) {
                    DB::table('tenant_balances')
                        ->where('tenant_id', $withdrawal->tenant_id)
                        ->update([
                            'held_amount' => $held - $amount,
                            'updated_at' => now(),
                        ]);
                } elseif ($available >= $amount) {
                    DB::table('tenant_balances')
                        ->where('tenant_id', $withdrawal->tenant_id)
                        ->update([
                            'available_amount' => $available - $amount,
                            'updated_at' => now(),
                        ]);
                }
            }

            if (Schema::hasTable('ledger_entries')) {
                DB::table('ledger_entries')->updateOrInsert(
                    ['idempotency_key' => 'ledger-debit-'.$withdrawal->id],
                    [
                        'tenant_id' => $withdrawal->tenant_id,
                        'withdrawal_id' => $withdrawal->id,
                        'type' => 'withdrawal_debit',
                        'available_delta' => 0,
                        'held_delta' => -$amount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
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

        DB::transaction(function () use ($withdrawal): void {
            $amount = (int) $withdrawal->amount;

            DB::table('withdrawals')
                ->where('id', $withdrawal->id)
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => auth()->id(),
                    'processed_at' => now(),
                    'active_tenant_lock' => null,
                    'updated_at' => now(),
                ]);

            $balance = DB::table('tenant_balances')
                ->where('tenant_id', $withdrawal->tenant_id)
                ->lockForUpdate()
                ->first();

            if ($balance !== null) {
                $held = is_numeric($balance->held_amount ?? null) ? (int) $balance->held_amount : 0;
                $available = is_numeric($balance->available_amount ?? null) ? (int) $balance->available_amount : 0;
                $releaseAmount = min($held, $amount);

                if ($releaseAmount > 0) {
                    DB::table('tenant_balances')
                        ->where('tenant_id', $withdrawal->tenant_id)
                        ->update([
                            'available_amount' => $available + $releaseAmount,
                            'held_amount' => $held - $releaseAmount,
                            'updated_at' => now(),
                        ]);
                }
            }

            if (Schema::hasTable('ledger_entries')) {
                DB::table('ledger_entries')->updateOrInsert(
                    ['idempotency_key' => 'ledger-release-'.$withdrawal->id],
                    [
                        'tenant_id' => $withdrawal->tenant_id,
                        'withdrawal_id' => $withdrawal->id,
                        'type' => 'release',
                        'available_delta' => $amount,
                        'held_delta' => -$amount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return redirect()
            ->back()
            ->with('status', 'Pengajuan penarikan dana ditolak dan saldo dikembalikan.');
    }
}
