<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Modules\Payments\Services\PayoutGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $currentStatus = is_string($request->input('status')) ? $request->input('status') : 'all';

        $totalPending = Withdrawal::whereIn('status', ['pending', 'processing'])->count();
        $totalPendingAmount = Withdrawal::whereIn('status', ['pending', 'processing'])->sum('amount');
        $totalApproved = Withdrawal::whereIn('status', ['approved', 'paid'])->sum('amount');

        $withdrawals = Withdrawal::with(['tenant', 'bankAccount'])
            ->when(in_array($currentStatus, ['pending', 'processing', 'paid', 'approved', 'rejected'], true), function ($query) use ($currentStatus): void {
                if ($currentStatus === 'paid') {
                    $query->whereIn('status', ['paid', 'approved']);
                } else {
                    $query->where('status', $currentStatus);
                }
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

    public function approve(Withdrawal $withdrawal, PayoutGatewayService $payoutService): RedirectResponse
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan pencairan dana ini sudah pernah diproses.');
        }

        $reviewerId = auth()->id();
        if (! is_numeric($reviewerId)) {
            return redirect()->back()->with('error', 'Sesi Admin tidak valid.');
        }

        try {
            $payoutService->dispatch((int) $withdrawal->id, (int) $reviewerId);
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('status', 'Permintaan pencairan berhasil dikirim ke Payment Gateway (Diproses Bank). Menunggu konfirmasi Webhook.');
    }

    public function simulateWebhook(Request $request, Withdrawal $withdrawal, PayoutGatewayService $payoutService): RedirectResponse
    {
        if ($withdrawal->status !== 'processing') {
            return redirect()
                ->back()
                ->with('error', 'Simulasi webhook hanya berlaku untuk transaksi berstatus Diproses Bank.');
        }

        $outcome = strtoupper((string) $request->input('outcome', 'COMPLETED'));
        $gatewayStatus = $outcome === 'FAILED' ? 'FAILED' : 'COMPLETED';

        $payload = [
            'event' => 'disbursement.updated',
            'external_id' => (string) $withdrawal->idempotency_key,
            'status' => $gatewayStatus,
            'amount' => (int) $withdrawal->amount,
            'bank_code' => $withdrawal->bankAccount?->bank_code,
            'processed_at' => now()->toIso8601String(),
        ];

        $result = $payoutService->handleWebhook(
            (string) config('services.payout.webhook_token', ''),
            $payload
        );

        return redirect()
            ->back()
            ->with('status', 'Webhook diterima ('.$gatewayStatus.'): '.$result['message']);
    }

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
