<?php

use App\Models\Product;
use App\Modules\Ordering\Http\Controllers\OrderStatusController;
use App\Modules\Ordering\Http\Controllers\TenantOrderController;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use App\Support\Routing\PortalRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

PortalRoutes::tenant(function (): void {
    Route::get('/orders', [TenantOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [TenantOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [TenantOrderController::class, 'updateStatus'])->name('orders.update-status');
});

PortalRoutes::customer(function (): void {
    Route::get('/checkout', function (Request $request) {
        return view('ordering::checkout-page', [
            'shopSlug' => (string) $request->route('shop', ''),
        ]);
    })->name('checkout');
});

Route::middleware('web')->group(function (): void {
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

        $session = app(ResolveCustomerSession::class)->resolveOrCreate($request);
        app(CartService::class)->add($session, $product->id, $qty);

        $lineKeys = null;
        $cartView = app(CartService::class)->view($session);
        foreach ($cartView->lines as $line) {
            if ($line->lineKey !== '') {
                $lineKeys = [$line->lineKey];
                break;
            }
        }

        session([
            'customer_session_id' => $session->id,
            'checkout_mode' => 'direct',
            'checkout_line_keys' => $lineKeys,
        ]);

        $shop = $product->tenant->slug ?? $product->tenant_id;

        return redirect()->route('customer.checkout', ['shop' => $shop]);
    })->name('customer.checkout.direct');

    Route::get('/pesanan/{order?}', OrderStatusController::class)->name('ordering.status');
});
