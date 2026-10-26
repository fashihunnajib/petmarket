<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_bank_account_id',
        'amount',
        'status', // 'pending', 'approved', 'rejected'
        'note',
        'requested_by',
        'idempotency_key',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<TenantBankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(TenantBankAccount::class, 'bank_account_id');
    }
}
