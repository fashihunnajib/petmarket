<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengguna extends Model
{
    protected $table = 'penggunas';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'nama', 'email', 'password', 'role'
    ];

    public function superAdmin(): HasOne
    {
        return $this->hasOne(SuperAdmin::class, 'pengguna_id');
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'pengguna_id');
    }

    public function pelanggan(): HasOne
    {
        return $this->hasOne(Pelanggan::class, 'pengguna_id');
    }
}