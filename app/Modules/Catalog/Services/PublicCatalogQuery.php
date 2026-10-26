<?php

namespace App\Modules\Catalog\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Kueri katalog publik lintas-tenant untuk platform PetMarket.
 * Melintasi batas tenant (withoutGlobalScope) secara eksplisit untuk menampilkan
 * etalase produk dari seluruh Pet Shop (tenant) yang berstatus aktif.
 */
final class PublicCatalogQuery
{
    /**
     * Menampilkan daftar produk katalog publik dengan pencarian, filter jenis, dan paginasi.
     *
     * @param  string|null  $search  Kata kunci nama produk atau ras hewan
     * @param  string|null  $type  Saringan entitas ('animal' atau 'good')
     * @param  int|null  $categoryId  Saringan kategori produk
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator<int, Product>
     */
    public function browse(
        ?string $search = null,
        ?string $type = null,
        ?int $categoryId = null,
        int $perPage = 16
    ): LengthAwarePaginator {
        return Product::query()
            ->withoutGlobalScope('tenant') // Bypass eksplisit isolasi satu tenant untuk etalase publik
            ->sellable() // Memastikan is_available = true dan stock_qty > 0
            ->whereHas('tenant', function ($query): void {
                $query->where('status', 'active'); // Hanya menampilkan produk dari toko yang aktif
            })
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('animalDetail', fn ($a) => $a->where('breed', 'like', "%{$keyword}%"));
                });
            })
            ->when($type, fn ($query, string $t) => $query->where('type', $t))
            ->when($categoryId, fn ($query, int $catId) => $query->where('category_id', $catId))
            ->with([
                'tenant:id,name,slug,latitude,longitude,max_delivery_radius_km',
                'category:id,name',
                'animalDetail',
                'goodDetail',
            ])
            ->latest()
            ->paginate($perPage);
    }
}
