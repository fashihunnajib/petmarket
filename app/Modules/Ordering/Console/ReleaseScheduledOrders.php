<?php

namespace App\Modules\Ordering\Console;

use App\Modules\Ordering\Services\PreOrderScheduler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ordering:release-scheduled')]
#[Description('Lepaskan pesanan pre-order terjadwal ke antrean toko')]
class ReleaseScheduledOrders extends Command
{
    public function handle(PreOrderScheduler $scheduler): int
    {
        $released = $scheduler->releaseDue();
        $this->components->info("{$released} pesanan terjadwal dilepas ke antrean toko.");

        return self::SUCCESS;
    }
}
