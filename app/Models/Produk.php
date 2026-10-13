<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    protected $table = 'produks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'tenant_id', 'nama_produk', 'slug', 'tipe', 'harga', 'stok'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function detailBarang(): HasOne
    {
        return $this->hasOne(DetailBarang::class, 'produk_id');
    }

    public function detailHewan(): HasOne
    {
        return $this->hasOne(DetailHewan::class, 'produk_id');
    }

    public function itemKeranjangs(): HasMany
    {
        return $this->hasMany(ItemKeranjang::class, 'produk_id');
    }

    public function itemPesanans(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'produk_id');
    }
}