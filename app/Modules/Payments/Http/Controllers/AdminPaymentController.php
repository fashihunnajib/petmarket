<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TenantOrder;
use App\Modules\Ordering\Exceptions\CheckoutException;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentException;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Support\QrisImage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminPaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private PaymentGateway $gateway,
    ) {}

    public function index(Request $request): View
    {
        $rawStatus = $request->query('status', 'all');
        $currentStatus = is_string($rawStatus) && in_array($rawStatus, ['all', 'pending', 'paid', 'failed', 'expired'], true)
            ? $rawStatus
            : 'all';

        $rawSearch = $request->query('search', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $totalPayments = Payment::query()->count();
        $totalPending = Payment::query()->where('status', 'pending')->count();
        $totalPaid = Payment::query()->where('status', 'paid')->count();
        $totalFailedOrExpired = Payment::query()->whereIn('status', ['failed', 'expired'])->count();
        $totalSettledAmount = (int) Payment::query()->where('status', 'paid')->sum('amount');
        $unpaidOrdersWithoutPayment = Order::query()
            ->whereIn('status', ['awaiting_payment', 'pending_payment'])
            ->doesntHave('payment')
            ->count();

        $payments = Payment::query()
            ->with([
                'order.user',
                'order.tenantOrders.tenant',
                'latestAttempt',
            ])
            ->when($currentStatus !== 'all', static function ($query) use ($currentStatus): void {
                $query->where('status', $currentStatus);
            })
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($sub) use ($search): void {
                    $sub->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('order', static function ($orderQuery) use ($search): void {
                            $orderQuery->where('order_number', 'like', "%{$search}%")
                                ->orWhereHas('user', static function ($userQuery) use ($search): void {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        /** @var view-string $viewName */
        $viewName = 'payments::admin.payments.index';

        return view($viewName, [
            'payments' => $payments,
            'currentStatus' => $currentStatus,
            'search' => $search,
            'totalPayments' => $totalPayments,
            'totalPending' => $totalPending,
            'totalPaid' => $totalPaid,
            'totalFailedOrExpired' => $totalFailedOrExpired,
            'totalSettledAmount' => $totalSettledAmount,
            'unpaidOrdersWithoutPayment' => $unpaidOrdersWithoutPayment,
            'gatewayName' => $this->gateway->name(),
            'isSandbox' => $this->gateway->isSandbox(),
        ]);
    }

    public function create(): View
    {
        $eligibleOrders = Order::query()
            ->whereIn('status', ['awaiting_payment', 'pending_payment'])
            ->doesntHave('payment')
            ->with(['user', 'tenantOrders.tenant'])
            ->latest('id')
            ->limit(50)
            ->get();

        $expirySeconds = min(
            PaymentService::MAX_EXPIRY_SECONDS,
            (int) config('services.qris.expiry_seconds', PaymentService::MAX_EXPIRY_SECONDS),
        );

        /** @var view-string $viewName */
        $viewName = 'payments::admin.payments.create';

        return view($viewName, [
            'eligibleOrders' => $eligibleOrders,
            'expirySeconds' => $expirySeconds,
            'gatewayName' => $this->gateway->name(),
            'isSandbox' => $this->gateway->isSandbox(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ], [
            'order_id.required' => 'Pilih pesanan yang akan dibuatkan tagihan QRIS.',
            'order_id.exists' => 'Pesanan tidak ditemukan.',
        ]);

        /** @var Order $order */
        $order = Order::query()->findOrFail($request->integer('order_id'));

        try {
            $payment = $this->payments->initiate($order);
        } catch (PaymentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Tagihan QRIS berhasil dibuat untuk pesanan #'.$order->order_number.'.');
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'order.user',
            'latestAttempt',
        ]);

        $attempts = $payment->attempts()->latest('id')->get();
        $events = $payment->events()->latest('id')->get();
        $tenantOrders = TenantOrder::query()
            ->withoutGlobalScope('tenant')
            ->where('order_id', $payment->order_id)
            ->with('tenant')
            ->orderBy('tenant_id')
            ->get();

        $latestAttempt = $payment->latestAttempt;
        $isExpired = $latestAttempt !== null
            && ($latestAttempt->status === 'expired' || ($latestAttempt->status === 'pending' && $latestAttempt->isExpired()));

        $qrSvg = $latestAttempt !== null
            && $latestAttempt->status === 'pending'
            && ! $latestAttempt->isExpired()
            && is_string($latestAttempt->qris_payload)
            && $latestAttempt->qris_payload !== ''
                ? QrisImage::svg($latestAttempt->qris_payload, 220)
                : null;

        $nmid = (string) config('services.qris.merchant_nmid', '');
        $maskedNmid = $nmid !== '' ? '•••'.substr($nmid, -4) : '-';

        /** @var view-string $viewName */
        $viewName = 'payments::admin.payments.show';

        return view($viewName, [
            'payment' => $payment,
            'attempts' => $attempts,
            'events' => $events,
            'tenantOrders' => $tenantOrders,
            'latestAttempt' => $latestAttempt,
            'isExpired' => $isExpired,
            'qrSvg' => $qrSvg,
            'maskedNmid' => $maskedNmid,
            'gatewayName' => $this->gateway->name(),
            'isSandbox' => $this->gateway->isSandbox(),
        ]);
    }

    public function regenerate(Payment $payment): RedirectResponse
    {
        $payment->loadMissing('order');
        $order = $payment->order;

        if ($order === null) {
            return redirect()
                ->back()
                ->with('error', 'Order induk tidak ditemukan.');
        }

        if ($payment->isPaid() || ! $order->isAwaitingPayment()) {
            return redirect()
                ->back()
                ->with('error', 'Hanya tagihan yang masih menunggu pembayaran yang dapat dibuatkan QRIS baru.');
        }

        try {
            $this->payments->initiate($order);
        } catch (PaymentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Tagihan QRIS aktif berhasil diperbarui / dibuat.');
    }

    public function simulatePay(Payment $payment): RedirectResponse
    {
        try {
            $this->payments->confirmSandbox($payment);
        } catch (PaymentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Simulasi pembayaran sandbox berhasil. Status pembayaran kini Lunas (Paid).');
    }

    public function expire(Payment $payment): RedirectResponse
    {
        try {
            $this->payments->expireActiveAttempt($payment);
        } catch (PaymentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Attempt QRIS aktif ditandai kedaluwarsa (sandbox). Anda dapat menguji tombol Buat QRIS Baru.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->loadMissing('order');

        if ($payment->isPaid()) {
            return redirect()
                ->back()
                ->with('error', 'Pembayaran yang sudah lunas tidak dapat dibatalkan atau dihapus.');
        }

        $order = $payment->order;
        if ($order === null || ! $order->isAwaitingPayment()) {
            return redirect()
                ->back()
                ->with('error', 'Pesanan terkait tidak lagi dalam status menunggu pembayaran.');
        }

        try {
            $this->payments->cancelUnpaid($order);
        } catch (CheckoutException|PaymentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Pembayaran & pesanan berhasil dibatalkan. Stok produk telah dikembalikan.');
    }
}
