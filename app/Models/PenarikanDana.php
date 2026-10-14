<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenarikanDana extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'penarikan_danas';

    protected $fillable = [
        'tenant_id',
        'rekening_bank_id',
        'jumlah',
        'status',
        'catatan_admin',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rekeningBank(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class);
    }
}
