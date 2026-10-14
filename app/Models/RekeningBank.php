<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekeningBank extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'rekening_banks';

    protected $fillable = [
        'tenant_id',
        'nama_bank',
        'nomor_rekening',
        'nama_pemilik',
        'is_primary',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function penarikanDanas(): HasMany
    {
        return $this->hasMany(PenarikanDana::class);
    }
}
