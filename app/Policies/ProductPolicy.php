<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

/**
 * Policy = lapisan kemampuan. Binding membatasi objek; policy membatasi aksi.
 * Defense-in-depth: product harus milik tenant aktif (selain global scope + composite FK).
 */
class ProductPolicy
{
    private function ownedByActiveTenant(Product $product): bool
    {
        return $product->tenant_id === app(TenantContext::class)->idOrNull();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->ownedByActiveTenant($product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownedByActiveTenant($product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownedByActiveTenant($product);
    }
}
