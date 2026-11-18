<?php

namespace App\Modules\Payments\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PayoutGatewayService
{
    public function dispatch(int $withdrawalId, int $reviewerId): void
    {
        $withdrawal = DB::table('withdrawals')
            ->join('tenant_bank_accounts', 'tenant_bank_accounts.id', '=', 'withdrawals.bank_account_id')
            ->where('withdrawals.id', $withdrawalId)
            ->select([
                'withdrawals.*',
                'tenant_bank_accounts.bank_code',
                'tenant_bank_accounts.account_number_cipher',
                'tenant_bank_accounts.account_last4',
                'tenant_bank_accounts.account_holder',
            ])
            ->first();

        if ($withdrawal === null || $withdrawal->status !== 'pending') {
            throw new RuntimeException('Pengajuan penarikan tidak valid atau sudah pernah diproses.');
        }

        $plainAccountNumber = Crypt::decryptString((string) $withdrawal->account_number_cipher);
        $driver = (string) config('services.payout.driver', 'sandbox');
        $amount = (int) $withdrawal->amount;

        if ($driver === 'xendit') {
            $secretKey = (string) config('services.payout.secret_key', '');
            if ($secretKey === '') {
                throw new RuntimeException('PAYOUT_SECRET_KEY belum diatur pada file .env.');
            }

            $baseUrl = rtrim((string) config('services.payout.base_url', 'https://api.xendit.co'), '/');

            $response = Http::withBasicAuth($secretKey, '')
                ->withHeaders([
                    'X-IDEMPOTENCY-KEY' => (string) $withdrawal->idempotency_key,
                ])
                ->timeout(15)
                ->post($baseUrl.'/disbursements', [
                    'external_id' => (string) $withdrawal->idempotency_key,
                    'bank_code' => (string) $withdrawal->bank_code,
                    'account_holder_name' => (string) $withdrawal->account_holder,
                    'account_number' => $plainAccountNumber,
                    'amount' => $amount,
                    'description' => 'Pencairan saldo PetMarket #'.$withdrawal->id,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Payment Gateway menolak permintaan transfer: '.$response->body());
            }

            /** @var array<string, mixed> $gatewayPayload */
            $gatewayPayload = $response->json() ?? [];
        } else {
            // Mode Sandbox Simulator (menyerupai respons API Disbursement)
            $gatewayPayload = [
                'id' => 'disb_sbx_'.Str::lower(Str::random(16)),
                'external_id' => (string) $withdrawal->idempotency_key,
                'bank_code' => (string) $withdrawal->bank_code,
                'account_holder_name' => (string) $withdrawal->account_holder,
                'account_number_last4' => (string) $withdrawal->account_last4,
                'amount' => $amount,
                'status' => 'PROCESSING',
                'driver' => 'sandbox',
                'dispatched_at' => now()->toIso8601String(),
            ];
        }

        DB::table('withdrawals')
            ->where('id', $withdrawal->id)
            ->update([
                'status' => 'processing',
                'reviewed_by' => $reviewerId,
                'transfer_snapshot' => json_encode($gatewayPayload),
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string}
     */
    public function handleWebhook(string $callbackToken, array $payload): array
    {
        $expectedToken = (string) config('services.payout.webhook_token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $callbackToken)) {
            throw new RuntimeException('Token webhook tidak valid.');
        }

        $externalId = is_string($payload['external_id'] ?? null) ? trim($payload['external_id']) : '';
        $rawStatus = is_string($payload['status'] ?? null) ? strtoupper(trim($payload['status'])) : '';

        if ($externalId === '' || $rawStatus === '') {
            throw new RuntimeException('Payload webhook wajib menyertakan external_id dan status.');
        }

        $result = ['status' => 'ignored', 'message' => 'Transaksi tidak ditemukan.'];

        DB::transaction(function () use ($externalId, $rawStatus, $payload, &$result): void {
            $withdrawal = DB::table('withdrawals')
                ->where('idempotency_key', $externalId)
                ->lockForUpdate()
                ->first();

            if ($withdrawal === null) {
                return;
            }

            if (in_array($withdrawal->status, ['paid', 'approved', 'rejected'], true)) {
                $result = ['status' => 'already_processed', 'message' => 'Webhook sudah pernah diproses sebelumnya.'];

                return;
            }

            $amount = (int) $withdrawal->amount;

            if (in_array($rawStatus, ['COMPLETED', 'PAID', 'SUCCEEDED'], true)) {
                DB::table('withdrawals')
                    ->where('id', $withdrawal->id)
                    ->update([
                        'status' => 'paid',
                        'processed_at' => now(),
                        'active_tenant_lock' => null,
                        'transfer_snapshot' => json_encode($payload),
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

                $result = ['status' => 'paid', 'message' => 'Pencairan dana berhasil dikonfirmasi masuk ke rekening.'];
            } elseif (in_array($rawStatus, ['FAILED', 'REJECTED', 'CANCELLED'], true)) {
                DB::table('withdrawals')
                    ->where('id', $withdrawal->id)
                    ->update([
                        'status' => 'rejected',
                        'processed_at' => now(),
                        'active_tenant_lock' => null,
                        'transfer_snapshot' => json_encode($payload),
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

                $result = ['status' => 'rejected', 'message' => 'Transfer bank gagal; saldo dikembalikan ke tenant.'];
            } else {
                $result = ['status' => 'ignored', 'message' => 'Status webhook tidak memerlukan perubahan akhir.'];
            }
        });

        return $result;
    }
}

// Service ini menangani dua hal:
// mengirim perintah transfer ke gateway
// (mengubah status menjadi processing sementara saldo tetap tertahan)
// dan menerima laporan hasil transfer dari webhook secara idempoten.
