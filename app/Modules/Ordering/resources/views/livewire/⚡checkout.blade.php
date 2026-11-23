<?php

use App\Models\CustomerSession;
use App\Models\Tenant;
use App\Modules\Ordering\Data\CartLine;
use App\Modules\Ordering\Data\CartView;
use App\Modules\Ordering\Data\CheckoutDetails;
use App\Modules\Ordering\Exceptions\CheckoutException;
use App\Modules\Ordering\Exceptions\PreOrderException;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\CheckoutService;
use App\Modules\Ordering\Services\PreOrderScheduler;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $shopSlug = '';
    public string $serviceMode = CheckoutDetails::DELIVERY;
    public string $customerName = '';
    public string $whatsapp = '';
    public string $deliveryAddress = '';
    public string $pickupDate = '';
    public string $pickupSlot = '';
    public bool $showAllSlots = false;

    /** @var list<string> */
    public array $alternatives = [];

    /** @var array<int, string>|null */
    public ?array $selectedLineKeys = null;

    public string $idempotencyKey = '';

    public function mount(string $shopSlug = ''): void
    {
        $this->shopSlug = $shopSlug;
        $this->idempotencyKey = (string) Str::uuid();

        /** @var array<int, string>|null $keys */
        $keys = session('checkout_line_keys');
        $this->selectedLineKeys = is_array($keys) && $keys !== [] ?$keys : null;

        if ($this->activeLines === []) {$this->redirect($this->shopSlug !== '' ? route('customer.home', ['shop' => $this->shopSlug]) : url('/'));
        }
    }

    private function session(): ?CustomerSession
    {
        return app(ResolveCustomerSession::class)->current(request());
    }

    #[Computed]
    public function cart(): ?CartView
    {
        $session =$this->session();

        return $session ? app(CartService::class)->view($session) : null;
    }

    /** @return list<CartLine> */
    #[Computed]
    public function activeLines(): array
    {
        $lines =$this->cart?->lines ?? [];
        if ($this->selectedLineKeys === null) {
            return $lines;
        }

        return array_values(array_filter(
            $lines,
            fn (CartLine $line): bool => in_array($line->lineKey,$this->selectedLineKeys, true)
        ));
    }

    /** @return array<int, array{tenant_id: int, tenant_name: string, subtotal: int, lines: list<CartLine>}> */
    #[Computed]
    public function groupedLines(): array
    {
        $groups = [];
        foreach ($this->activeLines as$line) {
            if (! isset($groups[$line->tenantId])) {
                $groups[$line->tenantId] = [
                    'tenant_id' => $line->tenantId,
                    'tenant_name' => $line->tenantName ?? 'Toko #'.$line->tenantId,
                    'subtotal' => 0,
                    'lines' => [],
                ];
            }
            $groups[$line->tenantId]['subtotal'] +=$line->lineTotal;
            $groups[$line->tenantId]['lines'][] =$line;
        }

        return array_values($groups);
    }

    #[Computed]
    public function subtotalAmount(): int
    {
        return array_sum(array_map(static fn (CartLine $l): int => $l->lineTotal,$this->activeLines));
    }

    #[Computed]
    public function taxAmount(): int
    {
        return (int) round($this->subtotalAmount * 0.10);
    }

    #[Computed]
    public function serviceFeeAmount(): int
    {
        return (int) round($this->subtotalAmount * 0.02);
    }

    #[Computed]
    public function grandTotalAmount(): int
    {
        return $this->subtotalAmount + $this->taxAmount +$this->serviceFeeAmount;
    }

    /** @return Collection<int, Tenant> */
    #[Computed]
    public function tenants(): Collection
    {
        $ids = collect($this->activeLines)->pluck('tenantId')->unique()->values()->all();

        return Tenant::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function preOrderAvailable(): bool
    {
        return $this->tenants->isNotEmpty()
            && $this->tenants->every(static fn (Tenant $tenant): bool => (bool)$tenant->pre_order_enabled);
    }

    /** @return list<array{date: string, label: string}> */
    #[Computed]
    public function days(): array
    {
        return $this->preOrderAvailable ? app(PreOrderScheduler::class)->days($this->tenants) : [];
    }

    /** @return list<array{time: CarbonImmutable, label: string, state: string}> */
    #[Computed]
    public function pickupSlots(): array
    {
        if ($this->pickupDate === '' || !$this->preOrderAvailable) {
            return [];
        }

        $slots = app(PreOrderScheduler::class)->slots($this->tenants,
            CarbonImmutable::parse($this->pickupDate, (string) config('app.display_timezone', 'Asia/Jakarta'))
        );

        $past = array_keys(array_filter($slots, static fn (array $slot): bool =>$slot['state'] === 'past'));
        $slots = array_values(array_filter($slots,
            static fn (array $slot, int $index): bool =>$slot['state'] !== 'past' || $index >= ((end($past) ?: 0) - 1),
            ARRAY_FILTER_USE_BOTH
        ));

        return $this->showAllSlots ? $slots : array_slice($slots, 0, 12);
    }

    #[Computed]
    public function releaseLabel(): ?string
    {
        $slot =$this->selectedSlot();
        if ($slot === null) {
            return null;
        }

        $prep = (int) collect($this->activeLines)->max('prepMinutes');

        return app(PreOrderScheduler::class)
            ->releaseAt($slot,$prep)
            ->setTimezone((string) config('app.display_timezone', 'Asia/Jakarta'))
            ->format('H.i');
    }

    public function chooseMode(string $mode): void
    {
        if ($mode === CheckoutDetails::PICKUP && !$this->preOrderAvailable) {
            return;
        }

        $this->serviceMode =$mode === CheckoutDetails::PICKUP
            ? CheckoutDetails::PICKUP
            : CheckoutDetails::DELIVERY;

        if ($this->serviceMode === CheckoutDetails::PICKUP &&$this->pickupDate === '') {
            $this->pickupDate =$this->days[0]['date'] ?? '';
        }
    }

    public function chooseDay(string $date): void
    {
        $this->pickupDate =$date;
        $this->pickupSlot = '';
        $this->alternatives = [];$this->showAllSlots = false;
    }

    public function showAll(): void
    {
        $this->showAllSlots = true;
    }

    public function chooseSlot(string $iso): void
    {
        $this->pickupSlot =$iso;
        $this->alternatives = [];$this->resetErrorBag('pickupSlot');
    }

    private function selectedSlot(): ?CarbonImmutable
    {
        if ($this->pickupSlot === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($this->pickupSlot);
        } catch (\Throwable) {
            return null;
        }
    }

    public function confirm()
    {
        $session =$this->session();
        if ($session === null) {
            return $this->redirect(url('/'));
        }

        $this->validate([
            'serviceMode' => ['required', 'in:delivery,pickup'],
            'customerName' => ['required', 'string', 'max:60'],
            'whatsapp' => ['required', 'string', 'regex:/^(\+?62|0)8[\d\s\-]{7,14}$/'],
            'deliveryAddress' => ['required_if:serviceMode,delivery', 'nullable', 'string', 'max:255'],
            'pickupSlot' => ['required_if:serviceMode,pickup', 'nullable', 'date'],
        ], [
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid (contoh: 0812-3456-7890).',
            'deliveryAddress.required_if' => 'Alamat pengiriman wajib diisi.',
            'pickupSlot.required_if' => 'Pilih slot waktu ambil.',
        ]);

        $details = new CheckoutDetails($this->serviceMode,
            trim(strip_tags($this->customerName)),
            CheckoutDetails::normalizeWhatsapp($this->whatsapp),
            $this->serviceMode === CheckoutDetails::DELIVERY ? trim(strip_tags($this->deliveryAddress)) : null,
        );

        $scheduledAt = $details->isPickup() ?$this->selectedSlot() : null;

        try {
            $result = app(CheckoutService::class)->checkout($session,
                $this->idempotencyKey,$scheduledAt,
                $details,$this->selectedLineKeys
            );
        } catch (PreOrderException $e) {$this->alternatives = array_map(
                static fn (CarbonImmutable $slot): string => $slot->toIso8601String(),$e->alternatives
            );
            $this->addError('pickupSlot',$e->getMessage());
            unset($this->pickupSlots);

            return null;
        } catch (CheckoutException $e) {
            session()->flash('checkout_error', $e->getMessage());

            return $this->redirect($this->shopSlug !== '' ? route('customer.home', ['shop' => $this->shopSlug]) : url('/'));
        }

        session()->forget('checkout_line_keys');

        if ($result->trackingToken !== null) {
            Cookie::queue(cookie(
                name: 'order_tracking',
                value: $result->trackingToken,
                minutes: 240,
                path: '/',
                domain: null,
                secure: request()->isSecure() || app()->isProduction(),
                httpOnly: true,
                raw: false,
                sameSite: 'lax',
            ));
        }

        return $this->redirectRoute('ordering.status');
    }
};
?>

@php($rupiah = fn (int $n) => 'Rp'.number_format($n, 0, ',', '.'))
@php($tz = (string) config('app.display_timezone', 'Asia/Jakarta'))

<div aria-label="Checkout" class="mx-auto max-w-xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-5 dark:border-zinc-800">
        <div>
            <h2 class="text-xl font-extrabold text-zinc-900 dark:text-zinc-100">
                {{ $serviceMode === 'pickup' ? 'Pesan Dulu / Pick-up' : 'Checkout' }}
            </h2>
            <p class="mt-0.5 text-xs text-zinc-500">Lengkapi detail pesanan Anda di bawah ini</p>
        </div>
        @if ($serviceMode === 'pickup')
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                Pre-order
            </span>
        @else
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                {{ collect($this->activeLines)->sum('quantity') }} item · {{ count($this->groupedLines) }} toko
            </span>
        @endif
    </div>

    @if ($this->activeLines !== [])
        <div class="space-y-6 p-6">
            {{-- Mode Penyajian --}}
            <section>
                <h3 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-zinc-500">Mode Layanan</h3>
                <div class="grid grid-cols-2 gap-3" role="radiogroup">
                    <button type="button" wire:click="chooseMode('delivery')" role="radio"
                        aria-checked="{{ $serviceMode === 'delivery' ? 'true' : 'false' }}"
                        @class([
                            'rounded-xl border p-3.5 text-left transition',
                            'border-emerald-600 bg-emerald-50/70 text-emerald-950 ring-1 ring-emerald-600 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-100' => $serviceMode === 'delivery',
                            'border-zinc-200 bg-white text-zinc-800 hover:border-emerald-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $serviceMode !== 'delivery',
                        ])>
                        <span class="block text-sm font-bold">Kirim ke Alamat</span>
                        <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">Antar via kurir</span>
                    </button>

                    <button type="button" wire:click="chooseMode('pickup')" role="radio"
                        aria-checked="{{ $serviceMode === 'pickup' ? 'true' : 'false' }}"
                        @disabled(! $this->preOrderAvailable)
                        @class([
                            'rounded-xl border p-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50',
                            'border-emerald-600 bg-emerald-50/70 text-emerald-950 ring-1 ring-emerald-600 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-100' => $serviceMode === 'pickup',
                            'border-zinc-200 bg-white text-zinc-800 hover:border-emerald-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $serviceMode !== 'pickup',
                        ])>
                        <span class="block text-sm font-bold">Ambil di Toko / Pre-Order</span>
                        <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $this->preOrderAvailable ? 'Jadwalkan waktu ambil' : 'Belum didukung semua toko' }}
                        </span>
                    </button>
                </div>
            </section>

            {{-- Jadwal Pre-Order --}}
            @if ($serviceMode === 'pickup')
                <section data-test="pre-order">
                    <h3 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-zinc-500">Hari Pengambilan</h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($this->days as $day)
                            <button type="button" wire:key="day-{{ $day['date'] }}" wire:click="chooseDay('{{ $day['date'] }}')"
                                @class([
                                    'rounded-xl border px-3.5 py-2 text-xs font-bold transition',
                                    'border-emerald-600 bg-emerald-600 text-white shadow-sm' => $pickupDate === $day['date'],
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $pickupDate !== $day['date'],
                                ])>
                                {{ $day['label'] }}
                            </button>
                        @empty
                            <p class="text-sm text-zinc-500">Belum ada slot pengambilan yang tersedia.</p>
                        @endforelse
                    </div>

                    <div class="mb-2.5 mt-5 flex items-baseline justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Slot Waktu Ambil</h3>
                        <span class="text-xs text-zinc-400">min. 15 mnt dari sekarang</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2" data-test="slots">
                        @foreach ($this->pickupSlots as $slot)
                            @php($iso = $slot['time']->toIso8601String())
                            <button type="button" wire:key="slot-{{ $iso }}" wire:click="chooseSlot('{{ $iso }}')"
                                @disabled($slot['state'] !== 'available')
                                @class([
                                    'min-h-11 rounded-xl border text-xs font-bold transition',
                                    'border-zinc-100 bg-zinc-50 text-zinc-400 line-through dark:border-zinc-800 dark:bg-zinc-950' => $slot['state'] === 'past',
                                    'border-zinc-100 bg-zinc-50 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-950' => $slot['state'] === 'full',
                                    'border-emerald-600 bg-emerald-600 text-white shadow-sm' => $slot['state'] === 'available' && $pickupSlot === $iso,
                                    'border-zinc-200 bg-white text-zinc-800 hover:border-emerald-500 hover:bg-emerald-50/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $slot['state'] === 'available' && $pickupSlot !== $iso,
                                ])>
                                {{ $slot['label'] }}
                                @if ($slot['state'] === 'full')
                                    <span class="block text-[10px] tracking-wider no-underline">PENUH</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @if (! $showAllSlots && count($this->pickupSlots) === 12)
                        <button type="button" wire:click="showAll" class="mt-2.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">
                            Tampilkan semua slot
                        </button>
                    @endif

                    @if ($this->releaseLabel)
                        <div class="mt-4 space-y-1.5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 text-xs text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100" data-test="release-info">
                            <div class="flex justify-between">
                                <span class="text-emerald-800 dark:text-emerald-300">Waktu ambil dipilih</span>
                                <span class="font-bold">{{ \Carbon\CarbonImmutable::parse($pickupSlot)->setTimezone($tz)->format('H.i') }} WIB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-emerald-800 dark:text-emerald-300">Mulai disiapkan otomatis</span>
                                <span class="font-bold">± {{ $this->releaseLabel }} WIB</span>
                            </div>
                        </div>
                    @endif

                    @error('pickupSlot')
                        <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300" role="alert">
                            <span class="font-bold">{{ $message }}</span>
                            @if ($alternatives !== [])
                                <span class="mt-1 block">Slot terdekat tersedia:
                                    @foreach ($alternatives as $alternative)
                                        <button type="button" wire:click="chooseSlot('{{ $alternative }}')" class="font-bold text-emerald-700 underline">
                                            {{ \Carbon\CarbonImmutable::parse($alternative)->setTimezone($tz)->format('H.i') }}
                                        </button>@if (! $loop->last) / @endif
                                    @endforeach
                                </span>
                            @endif
                        </div>
                    @enderror
                </section>
            @endif

            {{-- Identitas Ringkas --}}
            <section>
                <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-zinc-500">Identitas Pemesan</h3>

                <div>
                    <label for="customerName" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nama Lengkap</label>
                    <input id="customerName" type="text" wire:model="customerName" maxlength="60" autocomplete="name" placeholder="Masukkan nama lengkap"
                        class="mt-1.5 block w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm font-medium text-zinc-900 placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                    @error('customerName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3.5">
                    <label for="whatsapp" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Nomor WhatsApp <span class="font-normal text-zinc-400">(untuk notifikasi pesanan)</span>
                    </label>
                    <input id="whatsapp" type="tel" wire:model="whatsapp" inputmode="tel" autocomplete="tel" placeholder="0812-3456-7890"
                        class="mt-1.5 block w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm font-medium text-zinc-900 placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                    @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if ($serviceMode === 'delivery')
                    <div class="mt-3.5">
                        <label for="deliveryAddress" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Alamat Pengiriman</label>
                        <textarea id="deliveryAddress" wire:model="deliveryAddress" rows="2" placeholder="Jalan, nomor rumah, patokan..."
                            class="mt-1.5 block w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm font-medium text-zinc-900 placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
                        @error('deliveryAddress')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </section>

            {{-- Ringkasan Pembayaran --}}
            <section>
                <h3 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-zinc-500">Ringkasan Pembayaran</h3>
                <dl class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/70 text-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-950/60">
                    @foreach ($this->groupedLines as $group)
                        <div class="flex justify-between px-4 py-2.5">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ $group['tenant_name'] }} · {{ collect($group['lines'])->sum('quantity') }} item</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $rupiah($group['subtotal']) }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-4 py-2.5">
                        <dt class="text-zinc-600 dark:text-zinc-400">Pajak + biaya layanan</dt>
                        <dd class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $rupiah($this->taxAmount + $this->serviceFeeAmount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between bg-white px-4 py-3.5 dark:bg-zinc-900">
                        <dt class="text-base font-extrabold text-zinc-900 dark:text-zinc-100">Total</dt>
                        <dd class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ $rupiah($this->grandTotalAmount) }}</dd>
                    </div>
                </dl>
                <p class="mt-2.5 text-xs text-zinc-500">
                    Sistem memvalidasi ulang stok &amp; harga saat konfirmasi. Pesanan dibuat atomik — tidak ada pesanan parsial.
                </p>
            </section>
        </div>

        <div class="border-t border-zinc-200 bg-zinc-50/60 p-6 dark:border-zinc-800 dark:bg-zinc-900/60">
            <button type="button" wire:click="confirm" wire:loading.attr="disabled" data-test="confirm-checkout"
                class="min-h-12 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-600">
                {{ $serviceMode === 'pickup' ? 'Konfirmasi Jadwal & Lanjut Bayar' : 'Buat Pesanan & Bayar via QRIS' }}
            </button>
            <a href="{{ $shopSlug !== '' ? route('customer.home', ['shop' => $shopSlug]) : url('/') }}"
                class="mt-2.5 flex w-full items-center justify-center rounded-xl border border-emerald-600 bg-white px-4 py-2.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:bg-zinc-900 dark:text-emerald-400 dark:hover:bg-zinc-800">
                ← Kembali ke Toko
            </a>
        </div>
    @endif
</div>