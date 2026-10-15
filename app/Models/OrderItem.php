<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['name_snapshot', 'type_snapshot', 'unit_price_snapshot', 'quantity', 'line_total'];

    protected function casts(): array
    {
        return [
            'type_snapshot' => 'enum:animal,good',
            'unit_price_snapshot' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /** @return BelongsTo<TenantOrder, $this> */
    public function tenantOrder(): BelongsTo
    {
        return $this->belongsTo(TenantOrder::class);
    }
}
