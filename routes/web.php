<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Rute Beli Langsung (Checkout Satuan tanpa lewat keranjang)
Route::post('/checkout/direct', function (Request $request) {
    $data = $request->validate([
        'product_id' => ['required', 'integer', 'exists:products,id'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
    ]);

    /** @var Product $product */
    $product = Product::query()
        ->with('tenant')
        ->whereKey((int) $data['product_id'])
        ->firstOrFail();

    $qty = isset($data['quantity']) ? (int) $data['quantity'] : 1;

    abort_if(! $product->is_available || $product->stock_qty < $qty, 422, 'Stok produk tidak mencukupi.');

    $subtotal = (int) $product->base_price * $qty;
    $tax = (int) round($subtotal * 0.10);
    $serviceFee = (int) round($subtotal * 0.02);

    session([
        'checkout_mode' => 'direct',
        'checkout_items' => [
            [
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'tenant_name' => $product->tenant->name ?? 'Toko',
                'name' => $product->name,
                'quantity' => $qty,
                'unit_price' => (int) $product->base_price,
                'price_at_add' => (int) $product->base_price,
                'line_total' => $subtotal,
            ],
        ],
        'checkout_summary' => [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_fee' => $serviceFee,
            'grand_total' => $subtotal + $tax + $serviceFee,
        ],
    ]);

    return redirect()->route('customer.checkout');
})->name('customer.checkout.direct');

// Halaman Ringkasan Checkout
Route::get('/checkout', function () {
    /** @var array<int, array<string, mixed>> $items */
    $items = session('checkout_items', []);
    /** @var array<string, int> $summary */
    $summary = session('checkout_summary', []);

    abort_if($items === [], 404, 'Tidak ada item untuk di-checkout.');

    /** @var view-string $viewName */
    $viewName = 'customer.checkout';

    return view($viewName, compact('items', 'summary'));
})->name('customer.checkout');

// Konfirmasi Pesanan & Hapus Item yang Dipilih dari Keranjang
Route::post('/checkout/confirm', function (Request $request) {
    /** @var array<int, array<string, mixed>> $items */
    $items = session('checkout_items', []);
    abort_if($items === [], 404);

    if (session('checkout_mode') === 'cart_selection') {
        $customerSession = app(ResolveCustomerSession::class)->current($request);
        if ($customerSession !== null) {
            $cartService = app(CartService::class);
            /** @var array<int, string> $lineKeys */
            $lineKeys = session('checkout_line_keys', []);
            foreach ($lineKeys as $lineKey) {
                $cartService->remove($customerSession, $lineKey);
            }
        }
    }

    session()->forget(['checkout_mode', 'checkout_line_keys', 'checkout_items', 'checkout_summary']);

    return redirect()->back()->with('success', 'Pesanan berhasil dibuat!');
})->name('customer.checkout.confirm');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if (! $user) {
            return view('dashboard');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('tenant')) {
            $tenantId = DB::table('user_tenant_roles')
                ->where('user_id', $user->id)
                ->value('tenant_id');
        }

        // Cari tenant_id milik user dari tabel user_tenant_roles
        $tenantId = DB::table('user_tenant_roles')
            ->where('user_id', $user->id)
            ->value('tenant_id');

        if ($user->hasRole('tenant') || $tenantId) {
            /** @var Tenant|null $tenant */
            $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

            if ($tenant) {
                return redirect()->route('tenant.dashboard', [
                    'tenant' => $tenant->slug ?? $tenant->id,
                ]);
            }
        }

        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
