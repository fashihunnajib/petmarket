<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $table = 'tenants';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'pengguna_id', 'nama_toko', 'latitude', 'longitude', 'max_radius_km', 'saldo', 'is_active',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class, 'tenant_id');
    }

    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'tenant_id');
    }

    public function penarikanDanas(): HasMany
    {
        return $this->hasMany(PenarikanDana::class, 'tenant_id');
    }

    public function rekeningBanks(): HasMany
    {
        return $this->hasMany(RekeningBank::class);
    }
}