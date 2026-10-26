@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="id" class="antialiased">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white text-gray-900">

    <!-- Header Topbar -->
    <header class="flex items-center justify-between border-b border-gray-300 bg-white px-8 py-3">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 border border-emerald-300">
                    <span class="text-xs font-bold text-emerald-800">Pets</span>
                </div>
                <span class="text-xl font-bold text-gray-900">PetMarket</span>
            </div>
            <h1 class="border-l border-gray-400 pl-4 text-xl font-bold text-gray-900">Super Admin Panel</h1>
        </div>

        <div class="flex items-center gap-6">
            <span class="text-base font-semibold text-gray-900">
                Admin : <strong class="font-bold">{{ auth()->user()->name ?? 'Super Admin' }}</strong>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md bg-emerald-200 px-5 py-1.5 text-sm font-medium text-gray-900 hover:bg-emerald-300 transition">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <!-- Content Area -->
    <div class="flex min-h-[calc(100vh-65px)] p-6 gap-8">

        {{-- Sidebar --}}
        <aside class="w-72 shrink-0 rounded-sm border border-emerald-400 bg-white p-5 min-h-[500px]">
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-300 font-bold text-lg text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Menu SuperAdmin
            </div>

            <nav class="space-y-4 font-semibold text-base">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2 text-gray-900 transition-colors hover:text-emerald-700
                   {{ request()->routeIs('admin.dashboard') ? 'font-bold underline underline-offset-4 decoration-2 decoration-emerald-500' : '' }}">
                    <span>&rarr;|</span>
                    <span>Manajemen Dashboard</span>
                </a>

                <a href="{{ route('admin.tenants.index') }}"
                   class="flex items-center gap-2 text-gray-900 transition-colors hover:text-emerald-700
                   {{ request()->routeIs('admin.tenants.*') ? 'font-bold underline underline-offset-4 decoration-2 decoration-emerald-500' : '' }}">
                    <span>▣</span>
                    <span>Manajemen Tenant</span>
                </a>

                <a href="{{ route('admin.withdrawals.index') }}"
                   class="flex items-center gap-2 text-gray-900 transition-colors hover:text-emerald-700
                   {{ request()->routeIs('admin.withdrawals.*') ? 'font-bold underline underline-offset-4 decoration-2 decoration-emerald-500' : '' }}">
                    <span>◈</span>
                    <span>Verifikasi Dana</span>
                </a>

                <a href="{{ url('/admin/settings') }}"
                   class="flex items-center gap-2 text-gray-900 transition-colors hover:text-emerald-700
                   {{ request()->is('admin/settings*') ? 'font-bold underline underline-offset-4 decoration-2 decoration-emerald-500' : '' }}">
                    <span>⚙</span>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <div class="mt-12 border-t border-gray-200 pt-4 text-xs text-gray-500">
                Panel Pengelola Platform
            </div>
        </aside>

        {{-- Konten utama --}}
        <main class="min-w-0 flex-1">
            {{ $slot }}
        </main>

    </div>

    @stack('scripts')
    @fluxScripts
</body>
</html>