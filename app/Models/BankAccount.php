<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @return BelongsTo<Tenant, $this>
 */
class BankAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'bank_name',
        'account_number',
        'account_holder_name',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
