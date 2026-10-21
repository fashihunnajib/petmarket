<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_bank_account_id',
        'amount',
        'status', // 'pending', 'approved', 'rejected'
        'note',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(TenantBankAccount::class, 'tenant_bank_account_id');
    }
}
