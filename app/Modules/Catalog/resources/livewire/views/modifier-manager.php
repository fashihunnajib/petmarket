<?php

use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\UserTenantRole;
use App\Modules\Admin\Services\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $tenantId = 0;

    public string $groupName = '';

    public int $minSelect = 0;

    public int $maxSelect = 1;

    public array $optionName = [];

    public array $optionPrice = [];

    public function mount(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function booted(): void
    {
        $user = Auth::user();
        $isMember = $user !== null && UserTenantRole::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $this->tenantId)
            ->exists();
        abort_unless($isMember, 403);
        app(TenantContext::class)->set(Tenant::query()->findOrFail($this->tenantId));
    }

    #[Computed]
    public function groups(): Collection
    {
        return ModifierGroup::query()
            ->with(['options' => fn ($query) => $query->orderBy('price_delta')->orderBy('id')])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->with('modifierGroups:id')
            ->orderBy('name')
            ->get();
    }

    public function createGroup(): void
    {
        $data = $this->validate([
            'groupName' => ['required', 'string', 'max:120'],
            'minSelect' => ['required', 'integer', 'min:0', 'max:10'],
            'maxSelect' => ['required', 'integer', 'min:1', 'max:10', 'gte:minSelect'],
        ], [], ['groupName' => 'nama grup', 'minSelect' => 'minimal pilihan', 'maxSelect' => 'maksimal pilihan']);

        ModifierGroup::create([
            'name' => $data['groupName'],
            'min_select' => $data['minSelect'],
            'max_select' => $data['maxSelect'],
            'is_active' => true,
        ]);

        $this->reset(['groupName', 'minSelect', 'maxSelect']);
        unset($this->groups);
    }

    public function addOption(int $groupId): void
    {
        $group = ModifierGroup::query()->findOrFail($groupId);
        $this->validate([
            "optionName.{$groupId}" => ['required', 'string', 'max:120'],
            "optionPrice.{$groupId}" => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [], ["optionName.{$groupId}" => 'nama opsi', "optionPrice.{$groupId}" => 'selisih harga']);

        $option = new ModifierOption;
        $option->forceFill([
            'group_id' => $group->id,
            'name' => $this->optionName[$groupId],
            'price_delta' => (int) $this->optionPrice[$groupId],
            'stock_qty' => 0,
            'is_available' => true,
        ])->save();

        unset($this->optionName[$groupId], $this->optionPrice[$groupId], $this->groups);
    }

    public function toggleOption(int $optionId): void
    {
        $option = ModifierOption::query()->findOrFail($optionId);
        $option->forceFill(['is_available' => ! $option->is_available])->save();
        app(AuditLogger::class)->record('modifier_option', $option->id, 'availability_changed', null, ['is_available' => $option->is_available], $this->tenantId);
        unset($this->groups);
    }

    public function toggleGroup(int $groupId): void
    {
        $group = ModifierGroup::query()->findOrFail($groupId);
        $group->forceFill(['is_active' => ! $group->is_active])->save();
        unset($this->groups);
    }

    public function toggleAttachment(int $groupId, int $productId): void
    {
        $group = ModifierGroup::query()->findOrFail($groupId);
        $product = Product::query()->findOrFail($productId);
        if ($product->modifierGroups()->whereKey($group->id)->exists()) {
            $product->modifierGroups()->detach($group->id);
        } else {
            $product->modifierGroups()->attach($group->id, ['tenant_id' => $this->tenantId, 'sort_order' => 1]);
        }
        unset($this->products);
    }
};
?>

<div class="space-y-6">
    {{-- Form Tambah Grup Varian --}}
    <form wire:submit="createGroup" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="mb-4 text-base font-bold text-zinc-900 dark:text-zinc-100">Buat Grup Varian / Opsi Baru</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
                    Nama Grup
                    <input type="text" wire:model="groupName" placeholder="Mis. Ukuran, Rasa, Kemasan"
                        class="mt-1.5 min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm font-normal text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </label>
                @error('groupName') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
                    Min. Pilihan
                    <input type="number" min="0" wire:model="minSelect"
                        class="mt-1.5 min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm font-normal text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </label>
                @error('minSelect') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
                    Maks. Pilihan
                    <input type="number" min="1" wire:model="maxSelect"
                        class="mt-1.5 min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm font-normal text-zinc-900 focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                </label>
                @error('maxSelect') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit"
                class="min-h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                + Tambah Grup
            </button>
        </div>
    </form>

    {{-- Daftar Grup Varian --}}
    @forelse ($this->groups as $group)
        <section wire:key="group-{{ $group->id }}" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" data-test="modifier-group">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $group->name }}</h3>
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $group->min_select > 0,
                            'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' => $group->min_select === 0,
                        ])>
                            {{ $group->min_select > 0 ? 'Wajib' : 'Opsional' }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-500">Pilih {{ $group->min_select }} sampai {{ $group->max_select }} opsi</p>
                </div>

                <button type="button" wire:click="toggleGroup({{ $group->id }})" role="switch" aria-checked="{{ $group->is_active ? 'true' : 'false' }}"
                    @class([
                        'min-h-9 rounded-full px-4 text-xs font-bold transition',
                        'bg-emerald-600 text-white hover:bg-emerald-700' => $group->is_active,
                        'bg-zinc-200 text-zinc-600 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300' => ! $group->is_active,
                    ])>
                    {{ $group->is_active ? 'AKTIF' : 'NONAKTIF' }}
                </button>
            </div>

            {{-- Daftar Opsi di Dalam Grup --}}
            <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                @forelse ($group->options as $option)
                    <li wire:key="option-{{ $option->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span @class(['font-semibold', 'text-zinc-400 line-through' => ! $option->is_available, 'text-zinc-800 dark:text-zinc-200' => $option->is_available])>
                                {{ $option->name }}
                            </span>
                            <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-zinc-800 dark:text-emerald-400">
                                +Rp{{ number_format($option->price_delta, 0, ',', '.') }}
                            </span>
                        </div>

                        <button type="button" wire:click="toggleOption({{ $option->id }})" role="switch" aria-checked="{{ $option->is_available ? 'true' : 'false' }}"
                            @class([
                                'min-h-8 rounded-full px-3.5 text-xs font-bold transition',
                                'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300' => $option->is_available,
                                'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300' => ! $option->is_available,
                            ])>
                            {{ $option->is_available ? 'TERSEDIA' : 'HABIS' }}
                        </button>
                    </li>
                @empty
                    <li class="px-5 py-4 text-xs text-zinc-400">Belum ada opsi pada grup ini. Tambahkan opsi di bawah.</li>
                @endforelse
            </ul>

            {{-- Form Tambah Opsi --}}
            <form wire:submit="addOption({{ $group->id }})" class="flex flex-wrap items-center gap-2.5 border-t border-zinc-200 bg-zinc-50/40 px-5 py-3.5 dark:border-zinc-800 dark:bg-zinc-800/20">
                <input type="text" wire:model="optionName.{{ $group->id }}" placeholder="Nama opsi (mis. Ukuran Besar)" aria-label="Nama opsi"
                    class="min-h-10 min-w-48 flex-1 rounded-xl border border-zinc-300 bg-white px-3.5 text-sm focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800">
                <input type="number" min="0" wire:model="optionPrice.{{ $group->id }}" placeholder="Selisih Rp" aria-label="Selisih harga"
                    class="min-h-10 w-32 rounded-xl border border-zinc-300 bg-white px-3.5 text-sm focus:border-emerald-600 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800">
                <button type="submit"
                    class="min-h-10 rounded-xl bg-zinc-900 px-4 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    + Opsi
                </button>
                @error("optionName.{$group->id}") <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
                @error("optionPrice.{$group->id}") <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
            </form>

            {{-- Kaitkan ke Produk --}}
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <p class="mb-2.5 text-xs font-bold uppercase tracking-wider text-zinc-500">Dipasang pada produk</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->products as $product)
                        @php($attached = $product->modifierGroups->contains('id', $group->id))
                        <button type="button" wire:key="attach-{{ $group->id }}-{{ $product->id }}" wire:click="toggleAttachment({{ $group->id }}, {{ $product->id }})" aria-pressed="{{ $attached ? 'true' : 'false' }}"
                            @class([
                                'min-h-9 rounded-full border px-3.5 text-xs font-semibold transition',
                                'border-emerald-600 bg-emerald-600 text-white shadow-xs' => $attached,
                                'border-zinc-300 bg-white text-zinc-700 hover:border-emerald-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! $attached,
                            ])>
                            {{ $attached ? '✓ ' : '' }}{{ $product->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    @empty
        <x-empty-state title="Belum ada grup varian" description="Buat grup seperti Ukuran atau Kemasan, lalu pasang pada produk." />
    @endforelse
</div>