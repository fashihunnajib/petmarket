@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="w-64 shrink-0 bg-zinc-900 text-white">
            <div class="border-b border-zinc-700 p-6">
                <h1 class="text-xl font-bold">PetMarket</h1>
                <p class="mt-1 text-xs text-zinc-400">
                    SUPER ADMIN
                </p>
            </div>

            <nav class="space-y-2 p-4">
                <p class="mb-3 px-3 text-xs font-semibold uppercase text-zinc-500">
                    Menu Utama
                </p>

                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors
                   {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white' : 'text-zinc-300 hover:bg-zinc-800' }}">
                    <span>▦</span>
                    <span>Manajemen Dashboard</span>
                </a>

                <a href="{{ route('admin.tenants.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors
                   {{ request()->routeIs('admin.tenants.*') ? 'bg-emerald-600 text-white' : 'text-zinc-300 hover:bg-zinc-800' }}">
                    <span>▣</span>
                    <span>Manajemen Tenant</span>
                </a>

                <a href="{{ route('admin.withdrawals.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors
                   {{ request()->routeIs('admin.withdrawals.*') ? 'bg-emerald-600 text-white' : 'text-zinc-300 hover:bg-zinc-800' }}">
                    <span>◈</span>
                    <span>Verifikasi Dana</span>
                </a>

                <a href="{{ url('/admin/settings') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors
                   {{ request()->is('admin/settings*') ? 'bg-emerald-600 text-white' : 'text-zinc-300 hover:bg-zinc-800' }}">
                    <span>⚙</span>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <div class="mt-6 border-t border-zinc-700 p-4 text-xs text-zinc-500">
                Panel Pengelola Platform
            </div>
        </aside>

        {{-- Konten utama --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex min-h-16 items-center justify-between border-b border-zinc-200 bg-white px-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">{{ $title }}</h2>
                <span class="text-sm text-zinc-500">
                    {{ auth()->user()->name ?? 'Super Admin' }}
                </span>
            </header>

            <main class="w-full flex-1 p-6">
                {{ $slot }}
            </main>
        </div>

    </div>

    @fluxScripts
</body>
</html>