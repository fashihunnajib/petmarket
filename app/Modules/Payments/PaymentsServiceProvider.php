<?php

namespace App\Modules\Payments;

use App\Modules\ModuleServiceProvider;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Gateways\FakeQrisGateway;

final class PaymentsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn (): FakeQrisGateway => new FakeQrisGateway(
            unavailable: (bool) config('services.qris.fake_unavailable'),
        ));
    }

    protected function moduleAlias(): string
    {
        return 'payments';
    }
}
