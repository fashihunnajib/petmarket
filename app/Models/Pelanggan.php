<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggans';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'pengguna_id', 'alamat', 'latitude', 'longitude'
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function keranjang(): HasOne
    {
        return $this->hasOne(Keranjang::class, 'pelanggan_id');
    }

    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'pelanggan_id');
    }
}