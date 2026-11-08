<?php

namespace App\Modules\Catalog\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PublicCatalogQuery
{
    /**
     * Katalog lintas-tenant untuk halaman publik PetMarket.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function browse(
        ?string $search = null,
        ?string $type = null,
        ?int $categoryId = null,
        ?int $minPrice = null,
        ?int $maxPrice = null,
        int $perPage = 16
    ): LengthAwarePaginator {
        return Product::query()
            ->withoutGlobalScope('tenant')
            ->whereHas('tenant', function ($query): void {
                $query->where('status', 'active');
            })
            ->whereHas('category', function ($query): void {
                $query->where('is_active', true);
            })
            ->sellable()
            ->when($search !== null && trim($search) !== '', function ($query) use ($search): void {
                $keyword = trim($search);

                $query->where(function ($q) use ($keyword): void {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('animalDetail', function ($animal) use ($keyword): void {
                            $animal->where('breed', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when(
                in_array($type, ['animal', 'good'], true),
                fn ($query) => $query->where('type', $type)
            )
            ->when(
                $categoryId !== null,
                fn ($query) => $query->where('category_id', $categoryId)
            )
            ->when(
                $minPrice !== null,
                fn ($query) => $query->where('base_price', '>=', $minPrice)
            )
            ->when(
                $maxPrice !== null,
                fn ($query) => $query->where('base_price', '<=', $maxPrice)
            )
            ->with([
                'tenant:id,name,slug,status',
                'category:id,tenant_id,name,is_active',
                'animalDetail',
                'goodDetail',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}