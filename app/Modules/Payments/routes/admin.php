<?php

use App\Modules\Payments\Http\Controllers\AdminPaymentController;
use App\Support\Routing\PortalRoutes;
use Illuminate\Support\Facades\Route;

PortalRoutes::admin(function (): void {
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [AdminPaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [AdminPaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

    Route::post('/payments/{payment}/regenerate', [AdminPaymentController::class, 'regenerate'])->name('payments.regenerate');
    Route::post('/payments/{payment}/simulate-pay', [AdminPaymentController::class, 'simulatePay'])->name('payments.simulate-pay');
    Route::patch('/payments/{payment}/expire', [AdminPaymentController::class, 'expire'])->name('payments.expire');
    Route::delete('/payments/{payment}', [AdminPaymentController::class, 'destroy'])->name('payments.destroy');
});
