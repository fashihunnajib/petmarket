<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetMarket - Katalog Hewan dan Perlengkapan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900">
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-emerald-700">
                PetMarket<span class="text-zinc-900">.</span>
            </a>

            <nav class="flex items-center gap-5 text-sm font-medium text-zinc-600">
                <a href="{{ route('home') }}" class="text-emerald-700">Beranda</a>
                <a href="#katalog" class="hover:text-emerald-700">Katalog</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-emerald-700">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hover:text-emerald-700">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="katalog">
        <livewire:catalog.public-catalog />
    </main>

    <footer class="mt-12 border-t border-zinc-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-zinc-500 sm:px-6 lg:px-8">
           &copy; {{ date('Y') }} PetMarket &mdash; Temukan kebutuhan hewan kesayanganmu.
        </div>
    </footer>

    @livewireScripts
</body>
</html>