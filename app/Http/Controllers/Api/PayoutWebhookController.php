<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\PayoutGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PayoutWebhookController extends Controller
{
    public function handle(Request $request, PayoutGatewayService $payoutService): JsonResponse
    {
        $callbackToken = (string) $request->header('X-Callback-Token', '');

        try {
            /** @var array<string, mixed> $payload */
            $payload = $request->all();
            $result = $payoutService->handleWebhook($callbackToken, $payload);

            return response()->json([
                'ok' => true,
                'result' => $result,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}
