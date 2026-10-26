<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint contoh untuk membuktikan isolasi (global scope + scoped binding + policy).
 * UI Livewire penuh menyusul Modul 7; di sini fokus pada pembuktian keamanan.
 */
final class TenantProductController extends Controller
{
    use AuthorizesRequests;

    public function index(Tenant $tenant): JsonResponse
    {
        // Global scope memfilter ke tenant aktif (TenantContext dari middleware).
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'base_price', 'is_available']);

        return response()->json(['tenant' => $tenant->slug, 'products' => $products]);
    }

    public function show(Tenant $tenant, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return response()->json($product->only(['id', 'name', 'base_price', 'is_available']));
    }

    public function update(Request $request, Tenant $tenant, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate(['is_available' => ['required', 'boolean']]);
        $product->update(['is_available' => $data['is_available']]);

        return response()->json(['ok' => true, 'is_available' => $product->is_available]);
    }
}
