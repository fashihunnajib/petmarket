<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan katalog produk dengan tampilan Grid Card sesuai Figma
     */
    public function index(Tenant $tenant, Request $request): View
    {
        $search = $request->query('search');
        $type = $request->query('type'); // 'animal', 'goods', atau null (semua)
        $categoryId = $request->query('category_id');
        $stockFilter = $request->query('stock');
        $priceFilter = $request->query('price');

        // Total count per jenis produk
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
     * Form Tambah Produk (Multi-step Wizard)
     */
    public function create(Tenant $tenant): View
    {
        $categories = DB::table('product_categories')->get();

        return view('tenant.products.create', compact('tenant', 'categories'));
    }

    /**
     * Simpan data produk & detail spesifiknya
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
{
    // Sanitasi input harga: bersihkan titik/koma (misal '15.000' -> '15000')
    if ($request->has('base_price')) {
        $cleanPrice = preg_replace('/[^0-9]/', '', (string) $request->input('base_price'));
        $request->merge(['base_price' => $cleanPrice]);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:animal,goods',
        'category_id' => 'nullable|exists:product_categories,id',
        'base_price' => 'required|numeric|min:0',
        'stock_qty' => 'required|integer|min:0',
        'description' => 'nullable|string',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'breed' => 'nullable|string|max:100',
        'age_months' => 'nullable|integer|min:0',
        'gender' => 'nullable|in:male,female',
        'vaccination_status' => 'nullable|string',
        'weight_kg' => 'nullable|numeric',
        'health_history' => 'nullable|string',
    ]);

    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('products', 'public');
    }

    // Membungkus dengan DB Transaction agar rollback otomatis jika terjadi error
    DB::transaction(function () use ($validated, $tenant, $photoPath, $request) {
        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'base_price' => $validated['base_price'],
            'stock_qty' => $validated['stock_qty'],
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($validated['type'] === 'animal' && DB::getSchemaBuilder()->hasTable('product_animals')) {
            DB::table('product_animals')->insert([
                'product_id' => $productId,
                'tenant_id' => $tenant->id,
                'breed' => $request->input('breed'),
                'age_months' => $request->input('age_months'),
                'gender' => $request->input('gender'),
                'vaccination_status' => $request->input('vaccination_status'),
                'weight_kg' => $request->input('weight_kg'),
                'health_history' => $request->input('health_history'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    return redirect()
        ->route('tenant.products.create', ['tenant' => $tenant->slug ?? $tenant->id, 'step' => 'success'])
        ->with('success', 'Produk berhasil ditambahkan!');
}

   /**
     * Form Edit Produk
     */
    public function edit(Tenant $tenant, int $product): View
    {
        $productData = DB::table('products')
            ->leftJoin('product_animals', 'products.id', '=', 'product_animals.product_id')
            ->select('products.*', 'product_animals.breed', 'product_animals.age_months', 'product_animals.gender', 'product_animals.vaccination_status', 'product_animals.weight_kg', 'product_animals.health_history')
            ->where('products.tenant_id', $tenant->id)
            ->where('products.id', $product)
            ->first();

        if (! $productData) {
            abort(404);
        }

        $categories = DB::table('product_categories')->get();

        return view('tenant.products.edit', [
            'tenant' => $tenant,
            'product' => $productData,
            'categories' => $categories,
        ]);
    }

    /**
     * Update Produk
     */
    public function update(Request $request, Tenant $tenant, int $product): RedirectResponse
    {
        $existingProduct = DB::table('products')->where('tenant_id', $tenant->id)->where('id', $product)->first();
        if (! $existingProduct) {
            abort(404);
        }

        if ($request->has('base_price')) {
            $cleanPrice = preg_replace('/[^0-9]/', '', (string) $request->input('base_price'));
            $request->merge(['base_price' => $cleanPrice]);
        }

        $validated = $request->validate([
    'name' => 'required|string|max:255',
    'type' => 'required|in:animal,goods',
    'category_id' => 'nullable|exists:product_categories,id',
    'base_price' => 'required|numeric|min:0',
    'stock_qty' => 'required|integer|min:0',
    'description' => 'nullable|string',
    'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    'breed' => 'nullable|string|max:100',
    'age_months' => 'nullable|integer|min:0',
    'gender' => 'nullable|in:male,female',
    'vaccination_status' => 'nullable|string',
    'weight_kg' => 'nullable|numeric',
    'health_history' => 'nullable|string',
    'is_available' => 'boolean',
]);

        $photoPath = $existingProduct->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('products', 'public');
        }

        DB::table('products')->where('id', $product)->update([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'base_price' => $validated['base_price'],
            'stock_qty' => $validated['stock_qty'],
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
            'is_available' => $request->has('is_available'),
            'updated_at' => now(),
        ]);

        if ($validated['type'] === 'animal' && DB::getSchemaBuilder()->hasTable('product_animals')) {
            DB::table('product_animals')->updateOrInsert(
                ['product_id' => $product],
                [
                    'tenant_id' => $tenant->id,
                    'breed' => $request->input('breed'),
                    'age_months' => $request->input('age_months'),
                    'gender' => $request->input('gender'),
                    'vaccination_status' => $request->input('vaccination_status'),
                    'weight_kg' => $request->input('weight_kg'),
                    'health_history' => $request->input('health_history'),
                    'updated_at' => now(),
                ]
            );
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Toggle status keaktifan produk
     */
    public function toggleStatus(Tenant $tenant, int $product): RedirectResponse
    {
        $existingProduct = DB::table('products')->where('tenant_id', $tenant->id)->where('id', $product)->first();
        if ($existingProduct) {
            DB::table('products')->where('id', $product)->update([
                'is_available' => ! $existingProduct->is_available,
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Status produk diubah.');
    }

    /**
     * Hapus Produk
     */
    public function destroy(Tenant $tenant, int $product): RedirectResponse
    {
        $existingProduct = DB::table('products')->where('tenant_id', $tenant->id)->where('id', $product)->first();
        if ($existingProduct) {
            if ($existingProduct->photo_path && Storage::disk('public')->exists($existingProduct->photo_path)) {
                Storage::disk('public')->delete($existingProduct->photo_path);
            }
            DB::table('products')->where('id', $product)->delete();
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug ?? $tenant->id])
            ->with('success', 'Produk berhasil dihapus.');
    }
}