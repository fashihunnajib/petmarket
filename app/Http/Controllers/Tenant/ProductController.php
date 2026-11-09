<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan katalog produk
     */
    public function index(Tenant $tenant, Request $request): View|JsonResponse
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $categoryId = $request->query('category_id');
        $stockFilter = $request->query('stock');
        $priceFilter = $request->query('price');

        $countSemua = DB::table('products')->where('tenant_id', $tenant->id)->count();
        $countHewan = DB::table('products')->where('tenant_id', $tenant->id)->where('type', 'animal')->count();
        $countBarang = DB::table('products')->where('tenant_id', $tenant->id)->where('type', 'goods')->count();

        $query = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_animals', 'products.id', '=', 'product_animals.product_id')
            ->select(
                'products.*',
                'product_categories.name as category_name',
                'product_animals.breed',
                'product_animals.age_months',
                'product_animals.gender',
                'product_animals.vaccination_status'
            )
            ->where('products.tenant_id', $tenant->id);

        if ($type === 'animal') {
            $query->where('products.type', 'animal');
        } elseif ($type === 'goods') {
            $query->where('products.type', 'goods');
        }

        if ($search) {
            $query->where('products.name', 'like', "%{$search}%");
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($stockFilter === 'low') {
            $query->where('products.stock_qty', '<=', 3);
        } elseif ($stockFilter === 'available') {
            $query->where('products.stock_qty', '>', 0);
        }

        if ($priceFilter === 'asc') {
            $query->orderBy('products.base_price', 'asc');
        } elseif ($priceFilter === 'desc') {
            $query->orderBy('products.base_price', 'desc');
        } else {
            $query->latest('products.created_at');
        }

        $products = $query->paginate(12)->withQueryString();

        // Dukungan respons JSON jika dipanggil via API / getJson
        if ($request->wantsJson()) {
            return response()->json([
                'products' => $products->items(),
            ]);
        }

        $categories = DB::table('product_categories')->get();

        return view('tenant.products.index', compact(
            'tenant',
            'products',
            'categories',
            'countSemua',
            'countHewan',
            'countBarang',
            'search',
            'type',
            'categoryId',
            'stockFilter',
            'priceFilter'
        ));
    }

    /**
     * Update Produk (Mendukung Web Form & Partial PATCH JSON)
     */
    public function update(Request $request, Tenant $tenant, int $product): RedirectResponse|JsonResponse
    {
        $existingProduct = DB::table('products')->where('tenant_id', $tenant->id)->where('id', $product)->first();
        if (! $existingProduct) {
            abort(404);
        }

        if ($request->has('base_price')) {
            $cleanPrice = preg_replace('/[^0-9]/', '', (string) $request->input('base_price'));
            $request->merge(['base_price' => $cleanPrice]);
        }

        // Menggunakan 'sometimes|required' agar aman untuk parsial update (PATCH)
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:animal,goods',
            'category_id' => 'nullable|exists:product_categories,id',
            'base_price' => 'sometimes|required|numeric|min:0',
            'stock_qty' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'breed' => 'nullable|string|max:100',
            'age_months' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:male,female',
            'vaccination_status' => 'nullable|string',
            'weight_kg' => 'nullable|numeric',
            'health_history' => 'nullable|string',
            'is_available' => 'nullable|boolean',
        ]);

        $photoPath = $existingProduct->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('products', 'public');
        }

        $updateData = ['updated_at' => now()];

        if ($request->has('name')) {
            $updateData['name'] = $validated['name'];
        }
        if ($request->has('type')) {
            $updateData['type'] = $validated['type'];
        }
        if ($request->has('category_id')) {
            $updateData['category_id'] = $validated['category_id'];
        }
        if ($request->has('base_price')) {
            $updateData['base_price'] = $validated['base_price'];
        }
        if ($request->has('stock_qty')) {
            $updateData['stock_qty'] = $validated['stock_qty'];
        }
        if ($request->has('description')) {
            $updateData['description'] = $validated['description'];
        }
        if ($request->hasFile('photo')) {
            $updateData['photo_path'] = $photoPath;
        }
        if ($request->has('is_available')) {
            $updateData['is_available'] = (bool) $request->input('is_available');
        } elseif (! $request->wantsJson() && $request->has('name')) {
            $updateData['is_available'] = false;
        }

        DB::table('products')->where('id', $product)->update($updateData);

        $effectiveType = $validated['type'] ?? $existingProduct->type;
        if ($effectiveType === 'animal' && DB::getSchemaBuilder()->hasTable('product_animals')) {
            $animalData = [];
            if ($request->has('breed')) {
                $animalData['breed'] = $request->input('breed');
            }
            if ($request->has('age_months')) {
                $animalData['age_months'] = $request->input('age_months');
            }
            if ($request->has('gender')) {
                $animalData['gender'] = $request->input('gender');
            }
            if ($request->has('vaccination_status')) {
                $animalData['vaccination_status'] = $request->input('vaccination_status');
            }
            if ($request->has('weight_kg')) {
                $animalData['weight_kg'] = $request->input('weight_kg');
            }
            if ($request->has('health_history')) {
                $animalData['health_history'] = $request->input('health_history');
            }

            if (! empty($animalData)) {
                $animalData['tenant_id'] = $tenant->id;
                $animalData['updated_at'] = now();

                DB::table('product_animals')->updateOrInsert(
                    ['product_id' => $product],
                    $animalData
                );
            }
        }

        $updatedProduct = DB::table('products')->where('id', $product)->first();

        // Dukungan respons JSON untuk testing & API
        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'is_available' => (bool) $updatedProduct->is_available,
                'product' => $updatedProduct,
            ]);
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil diperbarui.');
    }
}
