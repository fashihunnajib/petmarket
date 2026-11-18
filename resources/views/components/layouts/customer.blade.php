@props(['title' => 'Pelanggan'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col">
   
<header class="sticky top-0 z-10 flex min-h-14 items-center gap-2 border-b border-zinc-200 bg-white/90 px-4 sm:px-6 lg:px-8 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/90">
    <span class="text-base font-semibold">{{ $title }}</span>

    <div class="ms-auto flex items-center gap-3 text-sm font-medium">
        @guest
            <a href="{{ route('login', ['redirect' => request()->getPathInfo()]) }}"
               class="text-zinc-600 hover:text-emerald-600 dark:text-zinc-300">
                Masuk
            </a>

            <a href="{{ route('register') }}"
               class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">
                Daftar
            </a>
        @else
            <span class="text-zinc-600 dark:text-zinc-300">
                {{ auth()->user()->name }}
            </span>
        @endguest
    </div>
</header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
        <footer class="border-t border-zinc-200 p-4 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            {{ config('app.name') }}
        </footer>
    </div>
    @fluxScripts
</body>
</html>