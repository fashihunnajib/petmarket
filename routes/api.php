<?php

use App\Http\Controllers\Api\PayoutWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payout', [PayoutWebhookController::class, 'handle'])
    ->name('api.webhooks.payout');
