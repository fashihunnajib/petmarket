<?php

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Rute Beli Langsung (Checkout Satuan tanpa lewat keranjang)
Route::post('/checkout/direct', function (Request $request) {
    $data = $request->validate([
        'product_id' => ['required', 'integer', 'exists:products,id'],
        'quantity'   => ['nullable', 'integer', 'min:1', 'max:20'],
    ]);

    $product = Product::with('tenant')->findOrFail($data['product_id']);
    $qty = $data['quantity'] ?? 1;

    abort_if(! $product->is_available || $product->stock_qty < $qty, 422, 'Stok produk tidak mencukupi.');

    $subtotal = (int) $product->base_price * $qty;
    $tax = (int) round($subtotal * 0.10);
    $serviceFee = (int) round($subtotal * 0.02);

    session([
        'checkout_mode' => 'direct',
        'checkout_items' => [
            [
                'product_id'   => $product->id,
                'tenant_id'    => $product->tenant_id,
                'tenant_name'  => $product->tenant->name ?? 'Toko',
                'name'         => $product->name,
                'quantity'     => $qty,
                'unit_price'   => (int) $product->base_price,
                'price_at_add' => (int) $product->base_price,
                'line_total'   => $subtotal,
            ],
        ],
        'checkout_summary' => [
            'subtotal'    => $subtotal,
            'tax'         => $tax,
            'service_fee' => $serviceFee,
            'grand_total' => $subtotal + $tax + $serviceFee,
        ],
    ]);

    return redirect()->route('customer.checkout');
})->name('customer.checkout.direct');

// Halaman Ringkasan Checkout
Route::get('/checkout', function () {
    $items = session('checkout_items', []);
    $summary = session('checkout_summary', []);

    abort_if(empty($items), 404, 'Tidak ada item untuk di-checkout.');

    return view('customer.checkout', compact('items', 'summary'));
})->name('customer.checkout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user?->hasRole('tenant')) {
            // Cari tenant_id milik user dari tabel user_tenant_roles
            $tenantId = DB::table('user_tenant_roles')
                ->where('user_id', $user->id)
                ->value('tenant_id');

            /** @var Tenant|null $tenant */
            // Ambil data tenant, atau fallback ke tenant pertama untuk testing
            $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

            if ($tenant) {
                return redirect()->route('tenant.dashboard', [
                    'tenant' => $tenant->slug ?? $tenant->id,
                ]);
            }
        }

        if ($user?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
});
// Baris updateStatus sudah dipindahkan ke rute PortalRoutes::admin

require __DIR__.'/settings.php';