<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use stdClass;

class ProductController extends Controller
{
    /**
     * Tampilkan katalog produk milik tenant.
     */
    public function index(Tenant $tenant, Request $request): View|JsonResponse
    {
        $this->ensureDefaultCategories((int) $tenant->id);

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

        if (is_string($search) && $search !== '') {
            $query->where('products.name', 'like', "%{$search}%");
        }

        if (is_numeric($categoryId)) {
            $query->where('products.category_id', (int) $categoryId);
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

        if ($request->wantsJson()) {
            return response()->json([
                'products' => $products->items(),
            ]);
        }

        $categories = $this->getTenantCategories((int) $tenant->id);

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
     * Tampilkan form tambah produk baru.
     */
    public function create(Tenant $tenant): View
    {
        $this->ensureDefaultCategories((int) $tenant->id);
        $categories = $this->getTenantCategories((int) $tenant->id);

        return view('tenant.products.create', compact('tenant', 'categories'));
    }

    /**
     * Simpan produk baru ke database.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse|JsonResponse
    {
        $this->ensureDefaultCategories((int) $tenant->id);

        if ($request->has('base_price')) {
            $rawPrice = $request->input('base_price');
            $cleanPrice = preg_replace('/[^0-9]/', '', is_scalar($rawPrice) ? (string) $rawPrice : '');
            $request->merge(['base_price' => $cleanPrice]);
        }

        // Jika category_id kosong, otomatis gunakan kategori pertama milik tenant agar tidak gagal NOT NULL
        if (! $request->filled('category_id')) {
            $firstCategory = $this->getTenantCategories((int) $tenant->id)->first();
            if ($firstCategory !== null) {
                $request->merge(['category_id' => $firstCategory->id]);
            }
        }

        $categoryRule = Schema::hasColumn('product_categories', 'tenant_id')
            ? Rule::exists('product_categories', 'id')->where('tenant_id', $tenant->id)
            : Rule::exists('product_categories', 'id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['animal', 'goods'])],
            'category_id' => ['required', $categoryRule],
            'base_price' => ['required', 'numeric', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'breed' => ['nullable', 'string', 'max:100'],
            'age_months' => ['nullable', 'integer', 'min:0'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'vaccination_status' => ['nullable', 'string'],
            'weight_kg' => ['nullable', 'numeric'],
            'health_history' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
        ], [
            'category_id.required' => 'Kategori produk wajib dipilih.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $storedPath = $request->file('photo')->store('products', 'public');
            $photoPath = is_string($storedPath) ? $storedPath : null;
        }

        $isAvailable = $request->has('is_available')
            ? (bool) $request->input('is_available')
            : true;

        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'base_price' => $validated['base_price'],
            'stock_qty' => $validated['stock_qty'],
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
            'is_available' => $isAvailable,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($validated['type'] === 'animal' && Schema::hasTable('product_animals')) {
            DB::table('product_animals')->insert([
                'tenant_id' => $tenant->id,
                'product_id' => $productId,
                'breed' => $validated['breed'] ?? null,
                'age_months' => $validated['age_months'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'vaccination_status' => $validated['vaccination_status'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'health_history' => $validated['health_history'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->wantsJson()) {
            $createdProduct = DB::table('products')->where('id', $productId)->first();

            return response()->json([
                'ok' => true,
                'product' => $createdProduct,
            ], 201);
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit produk.
     */
    public function edit(Tenant $tenant, int $product): View
    {
        $this->ensureDefaultCategories((int) $tenant->id);

        $productData = DB::table('products')
            ->leftJoin('product_animals', 'products.id', '=', 'product_animals.product_id')
            ->select(
                'products.*',
                'product_animals.breed',
                'product_animals.age_months',
                'product_animals.gender',
                'product_animals.vaccination_status',
                'product_animals.weight_kg',
                'product_animals.health_history'
            )
            ->where('products.tenant_id', $tenant->id)
            ->where('products.id', $product)
            ->first();

        if (! $productData) {
            abort(404);
        }

        $categories = $this->getTenantCategories((int) $tenant->id);

        return view('tenant.products.edit', [
            'tenant' => $tenant,
            'product' => $productData,
            'categories' => $categories,
        ]);
    }

    /**
     * Update produk (mendukung form web & partial PATCH JSON).
     */
    public function update(Request $request, Tenant $tenant, int $product): RedirectResponse|JsonResponse
    {
        $existingProduct = DB::table('products')
            ->where('tenant_id', $tenant->id)
            ->where('id', $product)
            ->first();

        if (! $existingProduct) {
            abort(404);
        }

        if ($request->has('base_price')) {
            $rawPrice = $request->input('base_price');
            $cleanPrice = preg_replace('/[^0-9]/', '', is_scalar($rawPrice) ? (string) $rawPrice : '');
            $request->merge(['base_price' => $cleanPrice]);
        }

        $categoryRule = Schema::hasColumn('product_categories', 'tenant_id')
            ? Rule::exists('product_categories', 'id')->where('tenant_id', $tenant->id)
            : Rule::exists('product_categories', 'id');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['animal', 'goods'])],
            'category_id' => ['sometimes', 'required', $categoryRule],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_qty' => ['sometimes', 'required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'breed' => ['nullable', 'string', 'max:100'],
            'age_months' => ['nullable', 'integer', 'min:0'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'vaccination_status' => ['nullable', 'string'],
            'weight_kg' => ['nullable', 'numeric'],
            'health_history' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $photoPath = is_string($existingProduct->photo_path) ? $existingProduct->photo_path : null;
        if ($request->hasFile('photo')) {
            if ($photoPath !== null && $photoPath !== '' && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $storedPath = $request->file('photo')->store('products', 'public');
            $photoPath = is_string($storedPath) ? $storedPath : null;
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
        if ($effectiveType === 'animal' && Schema::hasTable('product_animals')) {
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

            if ($animalData !== []) {
                $animalData['tenant_id'] = $tenant->id;
                $animalData['updated_at'] = now();

                DB::table('product_animals')->updateOrInsert(
                    ['product_id' => $product],
                    $animalData
                );
            }
        }

        $updatedProduct = DB::table('products')->where('id', $product)->first();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'is_available' => $updatedProduct !== null && (bool) $updatedProduct->is_available,
                'product' => $updatedProduct,
            ]);
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Hapus produk milik tenant.
     */
    public function destroy(Tenant $tenant, int $product): RedirectResponse|JsonResponse
    {
        $existingProduct = DB::table('products')
            ->where('tenant_id', $tenant->id)
            ->where('id', $product)
            ->first();

        if (! $existingProduct) {
            abort(404);
        }

        $photoPath = is_string($existingProduct->photo_path) ? $existingProduct->photo_path : null;
        if ($photoPath !== null && $photoPath !== '' && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }

        if (Schema::hasTable('product_animals')) {
            DB::table('product_animals')->where('product_id', $product)->delete();
        }

        DB::table('products')->where('id', $product)->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Buat kategori default otomatis jika tenant belum memiliki kategori sama sekali.
     */
    private function ensureDefaultCategories(int $tenantId): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        $query = DB::table('product_categories');
        if (Schema::hasColumn('product_categories', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($query->exists()) {
            return;
        }

        $defaults = ['Hewan Peliharaan', 'Makanan & Pakan', 'Aksesoris & Perlengkapan'];

        foreach ($defaults as $name) {
            $row = ['name' => $name];

            if (Schema::hasColumn('product_categories', 'tenant_id')) {
                $row['tenant_id'] = $tenantId;
            }
            if (Schema::hasColumn('product_categories', 'slug')) {
                $row['slug'] = Str::slug($name).'-'.$tenantId;
            }
            if (Schema::hasColumn('product_categories', 'is_active')) {
                $row['is_active'] = true;
            }
            if (Schema::hasColumn('product_categories', 'created_at')) {
                $row['created_at'] = now();
            }
            if (Schema::hasColumn('product_categories', 'updated_at')) {
                $row['updated_at'] = now();
            }

            DB::table('product_categories')->insert($row);
        }
    }

    /**
     * Ambil daftar kategori milik tenant.
     *
     * @return Collection<int, stdClass>
     */
    private function getTenantCategories(int $tenantId): Collection
    {
        $query = DB::table('product_categories');

        if (Schema::hasColumn('product_categories', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->orderBy('name')->get();
    }
}
