<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    private const MIN_WITHDRAWAL_AMOUNT = 50000;

    public function balance(Tenant $tenant): View
    {
        $balance = DB::table('tenant_balances')
            ->where('tenant_id', $tenant->id)
            ->first();

        $availableAmount = is_numeric($balance->available_amount ?? null) ? (int) $balance->available_amount : 0;
        $heldAmount = is_numeric($balance->held_amount ?? null) ? (int) $balance->held_amount : 0;

        $totalWithdrawn = (int) DB::table('withdrawals')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $totalNetIncome = (int) DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->sum('net_amount');

        $ledgerEntries = collect();
        if (Schema::hasTable('ledger_entries')) {
            $ledgerEntries = DB::table('ledger_entries')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        $completedOrders = DB::table('tenant_orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        return view('tenant.balance.index', compact(
            'tenant',
            'availableAmount',
            'heldAmount',
            'totalWithdrawn',
            'totalNetIncome',
            'ledgerEntries',
            'completedOrders'
        ));
    }

    public function index(Tenant $tenant): View
    {
        $balance = DB::table('tenant_balances')
            ->where('tenant_id', $tenant->id)
            ->first();

        $availableAmount = is_numeric($balance->available_amount ?? null) ? (int) $balance->available_amount : 0;
        $heldAmount = is_numeric($balance->held_amount ?? null) ? (int) $balance->held_amount : 0;

        $totalApprovedAmount = (int) DB::table('withdrawals')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $bankAccounts = DB::table('tenant_bank_accounts')
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'inactive')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->get();

        $withdrawals = DB::table('withdrawals')
            ->leftJoin('tenant_bank_accounts', 'tenant_bank_accounts.id', '=', 'withdrawals.bank_account_id')
            ->where('withdrawals.tenant_id', $tenant->id)
            ->select([
                'withdrawals.*',
                'tenant_bank_accounts.bank_code',
                'tenant_bank_accounts.account_last4',
                'tenant_bank_accounts.account_holder',
            ])
            ->orderByDesc('withdrawals.id')
            ->paginate(10);

        $hasActiveWithdrawal = DB::table('withdrawals')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'requested', 'processing'])
            ->exists();

        $minWithdrawal = self::MIN_WITHDRAWAL_AMOUNT;

        return view('tenant.withdrawals.index', compact(
            'tenant',
            'availableAmount',
            'heldAmount',
            'totalApprovedAmount',
            'bankAccounts',
            'withdrawals',
            'hasActiveWithdrawal',
            'minWithdrawal'
        ));
    }

    public function storeBankAccount(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'bank_code' => ['required', 'string', 'max:20', Rule::in(['BCA', 'BRI', 'BNI', 'MANDIRI', 'BSI', 'CIMB', 'PERMATA', 'JAGO', 'SEABANK'])],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{6,25}$/'],
            'account_holder' => ['required', 'string', 'max:120'],
            'is_primary' => ['nullable', 'boolean'],
        ], [
            'bank_code.required' => 'Pilih bank tujuan terlebih dahulu.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.regex' => 'Nomor rekening harus berupa 6–25 digit angka tanpa spasi.',
            'account_holder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        $accountNumber = is_string($validated['account_number']) ? $validated['account_number'] : '';
        $bankCode = is_string($validated['bank_code']) ? strtoupper($validated['bank_code']) : '';
        $accountHolder = is_string($validated['account_holder']) ? trim($validated['account_holder']) : '';

        $alreadyExists = DB::table('tenant_bank_accounts')
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'inactive')
            ->where('bank_code', $bankCode)
            ->where('account_last4', substr($accountNumber, -4))
            ->get()
            ->contains(function (object $row) use ($accountNumber): bool {
                try {
                    $cipher = is_string($row->account_number_cipher ?? null) ? $row->account_number_cipher : '';

                    return $cipher !== '' && Crypt::decryptString($cipher) === $accountNumber;
                } catch (\Throwable) {
                    return false;
                }
            });

        if ($alreadyExists) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Rekening {$bankCode} dengan nomor tersebut sudah terdaftar.");
        }

        $hasExisting = DB::table('tenant_bank_accounts')
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'inactive')
            ->exists();
        $isPrimary = ! $hasExisting || (bool) ($validated['is_primary'] ?? false);

        DB::transaction(function () use ($tenant, $bankCode, $accountNumber, $accountHolder, $isPrimary): void {
            if ($isPrimary) {
                DB::table('tenant_bank_accounts')
                    ->where('tenant_id', $tenant->id)
                    ->update([
                        'is_primary' => false,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('tenant_bank_accounts')->insert([
                'tenant_id' => $tenant->id,
                'verified_by' => auth()->id(),
                'account_number_cipher' => Crypt::encryptString($accountNumber),
                'account_last4' => substr($accountNumber, -4),
                'bank_code' => $bankCode,
                'account_holder' => $accountHolder,
                'status' => 'verified',
                'is_primary' => $isPrimary,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('tenant.withdrawals.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', "Rekening {$bankCode} (•••• ".substr($accountNumber, -4).') berhasil ditambahkan.');
    }

    public function setPrimaryBankAccount(Tenant $tenant, int $bankAccount): RedirectResponse
    {
        $exists = DB::table('tenant_bank_accounts')
            ->where('id', $bankAccount)
            ->where('tenant_id', $tenant->id)
            ->exists();

        if (! $exists) {
            return redirect()
                ->back()
                ->with('error', 'Rekening bank tidak ditemukan.');
        }

        DB::transaction(function () use ($tenant, $bankAccount): void {
            DB::table('tenant_bank_accounts')
                ->where('tenant_id', $tenant->id)
                ->update([
                    'is_primary' => false,
                    'updated_at' => now(),
                ]);

            DB::table('tenant_bank_accounts')
                ->where('id', $bankAccount)
                ->where('tenant_id', $tenant->id)
                ->update([
                    'is_primary' => true,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('tenant.withdrawals.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Rekening utama berhasil diperbarui.');
    }

    public function destroyBankAccount(Tenant $tenant, int $bankAccount): RedirectResponse
    {
        $account = DB::table('tenant_bank_accounts')
            ->where('id', $bankAccount)
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'inactive')
            ->first();

        if ($account === null) {
            return redirect()
                ->back()
                ->with('error', 'Rekening bank tidak ditemukan.');
        }

        $usedInWithdrawal = DB::table('withdrawals')
            ->where('bank_account_id', $bankAccount)
            ->exists();

        DB::transaction(function () use ($tenant, $bankAccount, $account, $usedInWithdrawal): void {
            if ($usedInWithdrawal) {
                DB::table('tenant_bank_accounts')
                    ->where('id', $bankAccount)
                    ->where('tenant_id', $tenant->id)
                    ->update([
                        'status' => 'inactive',
                        'is_primary' => false,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('tenant_bank_accounts')
                    ->where('id', $bankAccount)
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            }

            if ((bool) $account->is_primary) {
                $nextPrimary = DB::table('tenant_bank_accounts')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', '!=', 'inactive')
                    ->orderByDesc('id')
                    ->first();

                if ($nextPrimary !== null) {
                    DB::table('tenant_bank_accounts')
                        ->where('id', $nextPrimary->id)
                        ->update([
                            'is_primary' => true,
                            'updated_at' => now(),
                        ]);
                }
            }
        });

        $message = $usedInWithdrawal
            ? 'Rekening bank dihapus dari daftar aktif (tetap disimpan di sistem untuk riwayat transaksi).'
            : 'Rekening bank berhasil dihapus.';

        return redirect()
            ->route('tenant.withdrawals.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', $message);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => [
                'required',
                'integer',
                Rule::exists('tenant_bank_accounts', 'id')
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'verified'),
            ],
            'amount' => ['required', 'integer', 'min:'.self::MIN_WITHDRAWAL_AMOUNT],
        ], [
            'bank_account_id.required' => 'Pilih rekening bank tujuan pencairan.',
            'bank_account_id.exists' => 'Rekening bank tidak valid atau sudah dinonaktifkan.',
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.min' => 'Minimal penarikan dana adalah Rp '.number_format(self::MIN_WITHDRAWAL_AMOUNT, 0, ',', '.').'.',
        ]);

        $bankAccountId = is_numeric($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : 0;
        $amount = is_numeric($validated['amount']) ? (int) $validated['amount'] : 0;
        $userId = auth()->id();

        if (! is_numeric($userId)) {
            return redirect()->back()->with('error', 'Sesi pengguna tidak valid. Silakan login ulang.');
        }

        $errorMessage = null;

        DB::transaction(function () use ($tenant, $bankAccountId, $amount, $userId, &$errorMessage): void {
            $activeExists = DB::table('withdrawals')
                ->where('tenant_id', $tenant->id)
                ->where(function ($query) use ($tenant): void {
                    $query->where('active_tenant_lock', $tenant->id)
                        ->orWhereIn('status', ['pending', 'requested', 'processing']);
                })
                ->lockForUpdate()
                ->exists();

            if ($activeExists) {
                $errorMessage = 'Anda masih memiliki pengajuan penarikan dana yang sedang diproses Admin.';

                return;
            }

            $balance = DB::table('tenant_balances')
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->first();
            $available = is_numeric($balance->available_amount ?? null) ? (int) $balance->available_amount : 0;
            $held = is_numeric($balance->held_amount ?? null) ? (int) $balance->held_amount : 0;

            if ($amount > $available) {
                $errorMessage = 'Saldo tersedia tidak mencukupi (maksimal Rp '.number_format($available, 0, ',', '.').').';

                return;
            }

            $bank = DB::table('tenant_bank_accounts')
                ->where('id', $bankAccountId)
                ->where('tenant_id', $tenant->id)
                ->where('status', 'verified')
                ->first();

            if ($bank === null) {
                $errorMessage = 'Rekening bank tujuan tidak ditemukan.';

                return;
            }

            DB::table('tenant_balances')
                ->where('tenant_id', $tenant->id)
                ->update([
                    'available_amount' => $available - $amount,
                    'held_amount' => $held + $amount,
                    'updated_at' => now(),
                ]);

            $idempotencyKey = 'wd-'.$tenant->id.'-'.Str::uuid()->toString();

            $withdrawalId = DB::table('withdrawals')->insertGetId([
                'tenant_id' => $tenant->id,
                'bank_account_id' => $bankAccountId,
                'requested_by' => (int) $userId,
                'reviewed_by' => null,
                'idempotency_key' => $idempotencyKey,
                'amount' => $amount,
                'status' => 'pending',
                'transfer_snapshot' => json_encode([
                    'bank_code' => $bank->bank_code,
                    'account_last4' => $bank->account_last4,
                    'account_holder' => $bank->account_holder,
                ]),
                'active_tenant_lock' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('ledger_entries')) {
                DB::table('ledger_entries')->insert([
                    'tenant_id' => $tenant->id,
                    'tenant_order_id' => null,
                    'payment_id' => null,
                    'withdrawal_id' => $withdrawalId,
                    'idempotency_key' => 'ledger-hold-'.$withdrawalId,
                    'type' => 'hold',
                    'available_delta' => -$amount,
                    'held_delta' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        if ($errorMessage !== null) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $errorMessage);
        }

        return redirect()
            ->route('tenant.withdrawals.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Pengajuan penarikan dana sebesar Rp '.number_format($amount, 0, ',', '.').' berhasil dikirim dan menunggu persetujuan Admin.');
    }
}
