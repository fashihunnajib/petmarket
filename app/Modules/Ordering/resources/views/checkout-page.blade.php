<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — PetMarket</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5">
            <a href="{{ $shopSlug !== '' ? route('customer.home', ['shop' => $shopSlug]) : url('/') }}"
                class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                PetMarket
            </a>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                Checkout Pesanan
            </span>
        </div>
    </header>

    <main class="px-4 py-8">
        <livewire:ordering::checkout :shop-slug="$shopSlug" />
    </main>
</body>
</html>