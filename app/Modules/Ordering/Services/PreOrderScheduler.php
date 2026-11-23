<?php

namespace App\Modules\Ordering\Services;

use App\Models\Tenant;
use App\Models\TenantOrder;
use App\Modules\Ordering\Exceptions\PreOrderException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PreOrderScheduler
{
    public const SLOT_MINUTES = 15;

    public const MIN_LEAD_MINUTES = 15;

    public const DEFAULT_PREP_MINUTES = 15;

    public const LOOKAHEAD_DAYS = 7;

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return list<array{date: string, label: string}>
     */
    public function days(Collection $tenants, ?CarbonImmutable $now = null): array
    {
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
        $current = ($now ?? CarbonImmutable::now($tz))->setTimezone($tz);
        $days = [];

        for ($i = 0; $i < self::LOOKAHEAD_DAYS; $i++) {
            $day = $current->addDays($i);
            $hasHours = $tenants->every(
                static fn (Tenant $tenant): bool => DB::table('tenant_operating_hours')
                    ->where('tenant_id', $tenant->id)
                    ->where('day_of_week', $day->dayOfWeek)
                    ->exists()
            );

            if ($hasHours) {
                $dayNames = [
                    0 => 'Min',
                    1 => 'Sen',
                    2 => 'Sel',
                    3 => 'Rab',
                    4 => 'Kam',
                    5 => 'Jum',
                    6 => 'Sab',
                ];
                $monthNames = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'Mei',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Agu',
                    9 => 'Sep',
                    10 => 'Okt',
                    11 => 'Nov',
                    12 => 'Des',
                ];

                $dayLabel = ($dayNames[$day->dayOfWeek] ?? $day->format('D'))
                    .', '.$day->format('j').' '
                    .($monthNames[$day->month] ?? $day->format('M'));

                $days[] = [
                    'date' => $day->format('Y-m-d'),
                    'label' => $i === 0 ? 'Hari ini ('.$day->format('d M').')' : $dayLabel,
                ];
            }
        }

        return $days;
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return list<array{time: CarbonImmutable, label: string, state: string}>
     */
    public function slots(Collection $tenants, CarbonImmutable $date, ?CarbonImmutable $now = null): array
    {
        $tz = (string) config('app.display_timezone', 'Asia/Jakarta');
        $current = ($now ?? CarbonImmutable::now($tz))->setTimezone($tz);
        $minStart = $current->addMinutes(self::MIN_LEAD_MINUTES);
        $dayStart = $date->setTimezone($tz)->startOfDay();

        $hoursList = $tenants->map(
            static fn (Tenant $tenant) => DB::table('tenant_operating_hours')
                ->where('tenant_id', $tenant->id)
                ->where('day_of_week', $dayStart->dayOfWeek)
                ->first()
        );

        if ($hoursList->contains(static fn ($row): bool => $row === null)) {
            return [];
        }

        $earliestOpen = (string) ($hoursList->pluck('opens_at')->max() ?? '08:00:00');
        $latestClose = (string) ($hoursList->pluck('closes_at')->min() ?? '21:00:00');

        [$openH, $openM] = array_map('intval', explode(':', $earliestOpen));
        [$closeH, $closeM] = array_map('intval', explode(':', $latestClose));

        $cursor = $dayStart->setTime($openH, $openM, 0);
        $end = $dayStart->setTime($closeH, $closeM, 0);
        $slots = [];

        while ($cursor->lte($end)) {
            $state = 'available';

            if ($cursor->lt($minStart)) {
                $state = 'past';
            } else {
                foreach ($tenants as $tenant) {
                    $booked = TenantOrder::query()
                        ->withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenant->id)
                        ->where('scheduled_at', $cursor)
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->count();

                    if ($booked >= (int) ($tenant->pre_order_slot_capacity ?? 5)) {
                        $state = 'full';
                        break;
                    }
                }
            }

            $slots[] = [
                'time' => $cursor,
                'label' => $cursor->format('H.i'),
                'state' => $state,
            ];

            $cursor = $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return $slots;
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     */
    public function assertSchedulable(Collection $tenants, CarbonImmutable $scheduledAt, ?CarbonImmutable $now = null): void
    {
        $now ??= CarbonImmutable::now($scheduledAt->timezone);
        $slot = $this->floorToSlot($scheduledAt);

        if ($slot->lt($now->addMinutes(self::MIN_LEAD_MINUTES))) {
            throw PreOrderException::withAlternatives(
                'Waktu pre-order minimal 15 menit dari sekarang.',
                $this->nearest($tenants, $now->addMinutes(self::MIN_LEAD_MINUTES), 2, $now)
            );
        }

        foreach ($tenants as $tenant) {
            if (! $tenant->pre_order_enabled) {
                throw PreOrderException::notEnabled($tenant->name);
            }

            if (! $this->isWithinOperatingHours($tenant->id, $slot)) {
                throw PreOrderException::withAlternatives(
                    "{$tenant->name} tutup pada waktu tersebut.",
                    $this->nearest($tenants, $slot, 2, $now)
                );
            }

            $booked = TenantOrder::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('scheduled_at', $slot)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();

            if ($booked >= (int) ($tenant->pre_order_slot_capacity ?? 5)) {
                throw PreOrderException::withAlternatives(
                    "Slot pukul {$slot->format('H:i')} di {$tenant->name} sudah penuh.",
                    $this->nearest($tenants, $slot->addMinutes(self::SLOT_MINUTES), 2, $now)
                );
            }
        }
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return list<CarbonImmutable>
     */
    public function nearest(Collection $tenants, CarbonImmutable $from, int $limit = 2, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now($from->timezone);
        $cursor = $this->ceilToSlot($from);
        $minStart = $this->ceilToSlot($now->addMinutes(self::MIN_LEAD_MINUTES));

        if ($cursor->lt($minStart)) {
            $cursor = $minStart;
        }

        $alternatives = [];
        $maxSteps = (int) ((self::LOOKAHEAD_DAYS * 24 * 60) / self::SLOT_MINUTES);

        for ($i = 0; $i < $maxSteps && count($alternatives) < $limit; $i++) {
            if ($this->isSlotAvailableWithoutThrow($tenants, $cursor, $now)) {
                $alternatives[] = $cursor;
            }
            $cursor = $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return $alternatives;
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     */
    private function isSlotAvailableWithoutThrow(Collection $tenants, CarbonImmutable $slot, CarbonImmutable $now): bool
    {
        if ($slot->lt($now->addMinutes(self::MIN_LEAD_MINUTES))) {
            return false;
        }

        foreach ($tenants as $tenant) {
            if (! $tenant->pre_order_enabled) {
                return false;
            }

            if (! $this->isWithinOperatingHours($tenant->id, $slot)) {
                return false;
            }

            $booked = TenantOrder::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('scheduled_at', $slot)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();

            if ($booked >= (int) ($tenant->pre_order_slot_capacity ?? 5)) {
                return false;
            }
        }

        return true;
    }

    public function releaseAt(CarbonImmutable $scheduledAt, ?int $prepMinutes = null): CarbonImmutable
    {
        return $scheduledAt->subMinutes($prepMinutes ?? self::DEFAULT_PREP_MINUTES);
    }

    public function releaseDue(?CarbonImmutable $now = null): int
    {
        $cutoff = ($now ?? now())->format('Y-m-d H:i:s');

        $dueIds = DB::table('tenant_orders')
            ->join('orders', 'orders.id', '=', 'tenant_orders.order_id')
            ->where('tenant_orders.status', 'scheduled')
            ->where('tenant_orders.release_at', '<=', $cutoff)
            ->where('orders.status', 'paid')
            ->pluck('tenant_orders.id');

        if ($dueIds->isEmpty()) {
            return 0;
        }

        return DB::table('tenant_orders')
            ->whereIn('id', $dueIds)
            ->update([
                'status' => 'pending',
                'updated_at' => $cutoff,
            ]);
    }

    public function floorToSlot(CarbonImmutable $time): CarbonImmutable
    {
        $minute = (int) (floor($time->minute / self::SLOT_MINUTES) * self::SLOT_MINUTES);

        return $time->setTime($time->hour, $minute, 0);
    }

    public function ceilToSlot(CarbonImmutable $time): CarbonImmutable
    {
        $floored = $this->floorToSlot($time);

        return $floored->eq($time) ? $floored : $floored->addMinutes(self::SLOT_MINUTES);
    }

    private function isWithinOperatingHours(int $tenantId, CarbonImmutable $slot): bool
    {
        $row = DB::table('tenant_operating_hours')
            ->where('tenant_id', $tenantId)
            ->where('day_of_week', $slot->dayOfWeek)
            ->first();

        if (! $row) {
            return false;
        }

        if (isset($row->is_closed) && (bool) $row->is_closed) {
            return false;
        }

        $opensRaw = $row->opens_at ?? ($row->open_time ?? null);
        $closesRaw = $row->closes_at ?? ($row->close_time ?? null);

        if (! is_string($opensRaw) || $opensRaw === '' || ! is_string($closesRaw) || $closesRaw === '') {
            return false;
        }

        $slotHm = $slot->format('H:i');
        $opensHm = substr($opensRaw, 0, 5);
        $closesHm = substr($closesRaw, 0, 5);

        return $slotHm >= $opensHm && $slotHm <= $closesHm;
    }
}
