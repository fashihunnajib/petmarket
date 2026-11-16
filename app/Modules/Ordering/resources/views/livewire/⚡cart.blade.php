<?php

use App\Models\CustomerSession;
use App\Modules\Ordering\Data\CartView;
use App\Modules\Ordering\Exceptions\CartException;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\ResolveCustomerSession;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $marketSlug = '';

    public function mount(string $marketSlug = ''): void
    {
        $this->marketSlug = $marketSlug;
    }

    private function session(bool $autoCreate = false): ?CustomerSession
    {
        $resolver = app(ResolveCustomerSession::class);
        $current = $resolver->current(request());

        if ($current !== null) {
            return $current;
        }

        if ($autoCreate && ! app()->runningUnitTests()) {
            return $resolver->resolveOrCreate(request());
        }

        return null;
    }

    #[Computed]
    public function cart(): ?CartView
    {
        $session = $this->session(false);

        return $session ? app(CartService::class)->view($session) : null;
    }

    #[On('cart-add')]
    public function add(int $productId): void
    {
        $session = $this->session(true);
        if ($session === null) {
            return;
        }

        if (app(CartService::class)->modifierGroupsFor($session, $productId)->isNotEmpty()) {
            $this->dispatch('customize-item', productId: $productId);
            return;
        }

        try {
            app(CartService::class)->add($session, $productId, 1);
            $this->resetErrorBag('cart');
            unset($this->cart);
        } catch (CartException $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->resetErrorBag('cart');
        unset($this->cart);
    }

    #[Computed]
    public function tableLabel(): ?string
    {
        return $this->session(false)?->diningTable?->label;
    }

    public function increment(string $lineKey): void
    {
        $this->changeBy($lineKey, 1);
    }

    public function decrement(string $lineKey): void
    {
        $this->changeBy($lineKey, -1);
    }

    private function changeBy(string $lineKey, int $delta): void
    {
        $session = $this->session(false);
        if ($session === null) {
            return;
        }

        $view = app(CartService::class)->view($session);
        foreach ($view->lines as $line) {
            if ($line->lineKey === $lineKey) {
                app(CartService::class)->setQuantity($session, $lineKey, $line->quantity + $delta);
                break;
            }
        }
        unset($this->cart);
    }

    public function remove(string $lineKey): void
    {
        $session = $this->session(false);
        if ($session === null) {
            return;
        }

        app(CartService::class)->remove($session, $lineKey);
        unset($this->cart);
    }
};
?>

<div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-label="Keranjang">
    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b-2 border-zinc-900 p-4 dark:border-zinc-100">
        <h2 class="text-lg font-extrabold">Keranjang Belanja</h2>
        <span class="whitespace-nowrap text-xs font-semibold text-zinc-500">
            @if ($this->tableLabel) Meja: {{ $this->tableLabel }} @endif
            @if ($this->cart && $this->cart->totalQuantity > 0) · {{ $this->cart->totalQuantity }} item @endif
        </span>
    </div>

    @error('cart')
        <div class="m-4 rounded-lg bg-red-100 px-3 py-2 text-sm font-medium text-red-800 dark:bg-red-900/40 dark:text-red-300" role="alert">{{ $message }}</div>
    @enderror

    @if (! $this->cart)
        <div class="p-4"><x-empty-state title="Belum ada sesi" description="Tambahkan produk dari katalog untuk mulai berbelanja." /></div>
    @elseif ($this->cart->isEmpty())
        <div class="p-4"><x-empty-state title="Keranjang kosong" description="Tambahkan produk dari katalog toko." /></div>
    @else
        @php
            $groups = method_exists($this->cart, 'tenantGroups')
                ? $this->cart->tenantGroups()
                : collect($this->cart->lines)->groupBy('tenantId')->map(fn ($lines, $tenantId) => [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $lines->first()->tenantName ?? 'Toko',
                    'subtotal' => $lines->sum('lineTotal'),
                    'lines' => $lines->all(),
                ])->all();
        @endphp

        @foreach ($groups as $group)
            <section wire:key="tenant-{{ $group['tenant_id'] ?? $group['vendor_id'] ?? 0 }}" data-test="cart-vendor">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-zinc-100 px-4 py-2 dark:bg-zinc-800">
                    <h3 class="text-xs font-extrabold uppercase tracking-wide">{{ $group['tenant_name'] ?? $group['vendor_name'] ?? 'Toko' }}</h3>
                    <span class="text-xs font-semibold text-zinc-500">Subtotal Rp{{ number_format($group['subtotal'], 0, ',', '.') }}</span>
                </div>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($group['lines'] as $line)
                        <li wire:key="line-{{ $line->lineKey }}" @class(['flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between', 'opacity-70' => ! $line->available])>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold leading-snug text-zinc-900 dark:text-zinc-100">{{ $line->name }}</p>
                                @if ($line->modifiers !== [] || $line->note)
                                    <p class="mt-0.5 text-xs text-zinc-500">
                                        {{ collect($line->modifiers)->map(fn ($o) => '+ ' . (is_array($o) ? ($o['name'] ?? '') : $o))->implode(', ') }}
                                        @if ($line->note) {{ $line->modifiers !== [] ? '·' : '' }} "{{ $line->note }}" @endif
                                    </p>
                                @endif
                                <p class="mt-1 text-sm font-semibold text-emerald-600">
                                    Rp{{ number_format($line->unitPrice + $line->modifierTotal, 0, ',', '.') }}{{ $line->quantity > 1 ? ' × ' . $line->quantity : '' }}
                                </p>

                                @if (! $line->available)
                                    <p class="mt-1 text-xs font-medium text-red-600">
                                        @if (in_array('product_unavailable', $line->issues, true)) Produk telah habis. @endif
                                        @if (in_array('insufficient_stock', $line->issues, true)) Stok tidak mencukupi. @endif
                                        @if (in_array('option_unavailable', $line->issues, true)) Varian pilihan tidak tersedia. @endif
                                    </p>
                                @elseif ($line->priceChanged())
                                    <p class="mt-1 text-xs font-medium text-amber-600">Harga diperbarui menjadi Rp{{ number_format($line->unitPrice + $line->modifierTotal, 0, ',', '.') }}.</p>
                                @endif
                                <button type="button" wire:click="remove('{{ $line->lineKey }}')" class="mt-1 text-xs font-medium text-red-600 underline">Hapus</button>
                            </div>

                            <div class="flex shrink-0 self-start overflow-hidden rounded-lg border border-zinc-300 sm:self-center dark:border-zinc-700">
                                <button type="button" wire:click="decrement('{{ $line->lineKey }}')" class="h-9 w-9 font-bold hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Kurangi">-</button>
                                <span class="flex h-9 min-w-9 items-center justify-center border-x border-zinc-300 px-2 text-sm font-bold dark:border-zinc-700">{{ $line->quantity }}</span>
                                <button type="button" wire:click="increment('{{ $line->lineKey }}')" class="h-9 w-9 font-bold hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Tambah">+</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <dl class="m-4 divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700" data-test="cart-summary">
            <div class="flex items-center justify-between gap-2 px-3 py-2">
                <dt class="text-zinc-600 dark:text-zinc-400">Subtotal ({{ count($groups) }} toko)</dt>
                <dd class="font-semibold whitespace-nowrap">Rp{{ number_format($this->cart->subtotal, 0, ',', '.') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2 px-3 py-2">
                <dt class="text-zinc-600 dark:text-zinc-400">Pajak</dt>
                <dd class="font-semibold whitespace-nowrap">Rp{{ number_format($this->cart->taxAmount, 0, ',', '.') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2 px-3 py-2">
                <dt class="text-zinc-600 dark:text-zinc-400">Biaya layanan</dt>
                <dd class="font-semibold whitespace-nowrap">Rp{{ number_format($this->cart->serviceFeeAmount, 0, ',', '.') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2 border-t border-zinc-300 bg-zinc-50 px-3 py-2.5 text-base font-extrabold dark:border-zinc-700 dark:bg-zinc-800/60">
                <dt>Total</dt>
                <dd class="whitespace-nowrap text-emerald-600">Rp{{ number_format($this->cart->grandTotal(), 0, ',', '.') }}</dd>
            </div>
        </dl>

        @if ($this->cart->hasBlockingIssues)
            <p class="mx-4 text-xs text-red-600">Perbaiki produk bermasalah sebelum melanjutkan.</p>
        @endif
    @endif

    <div class="p-4">
        <button type="button" @disabled(! $this->cart?->isOrderable()) data-test="checkout-button"
            class="flex min-h-12 w-full items-center justify-between gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-600">
            <span>Checkout</span>
            <span class="whitespace-nowrap">Rp{{ number_format($this->cart?->grandTotal() ?? 0, 0, ',', '.') }}</span>
        </button>
    </div>
</div>