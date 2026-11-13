@props([
    'title' => 'Dashboard Tenant',
    'tenant' => $tenant ?? request()->route('tenant')
])

@php
    if (is_string($tenant) || is_numeric($tenant)) {
        $tenant = \App\Models\Tenant::where('slug', $tenant)
            ->orWhere('id', $tenant)
            ->first();
    }

    if (! is_object($tenant) && auth()->check()) {
        $tenantId = \Illuminate\Support\Facades\DB::table('user_tenant_roles')
            ->where('user_id', auth()->id())
            ->value('tenant_id');

        if ($tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);
        }
    }

    $tenantSlug = is_object($tenant) ? ($tenant->slug ?? $tenant->id) : $tenant;
    $tenantDisplayName = is_object($tenant) ? $tenant->name : (auth()->user()?->name ?? 'PetMarket');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 font-sans">
    <div x-data="{ open: false }" class="flex min-h-screen">
        
        <!-- SIDEBAR (Sesuai Desain Figma - Dark Theme) -->
        <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 transform bg-[#121212] text-zinc-300 p-5 transition-transform duration-200 lg:static lg:translate-x-0 flex flex-col justify-between shrink-0 shadow-xl">
            
            <div>
                <!-- Brand / Logo PetMarket -->
                <div class="flex items-center gap-3 mb-8 px-2">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 font-bold text-xl">
                        🐾
                    </div>
                    <div>
                        <h1 class="font-bold text-white text-lg leading-none">PetMarket</h1>
                        <span class="text-xs text-zinc-400 font-medium">Tenant Panel</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="flex flex-col gap-1.5 text-sm font-medium">
                    <!-- Dashboard -->
                    <a href="{{ route('tenant.dashboard', ['tenant' => $tenantSlug]) }}" 
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('tenant.dashboard') ? 'bg-emerald-500/10 text-emerald-400 font-semibold border-l-4 border-emerald-500' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Product -->
                    <a href="{{ route('tenant.products.index', ['tenant' => $tenantSlug]) }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('tenant.products*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold border-l-4 border-emerald-500' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Product</span>
                    </a>

                    <!-- Pesanan -->
                    <a href="#" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('tenant.orders*') ? 'bg-emerald-500/10 text-emerald-400 font-semibold border-l-4 border-emerald-500' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Pesanan</span>
                    </a>

                    <!-- Toko -->
                    <a href="#" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all text-zinc-400 hover:text-white hover:bg-zinc-800/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-1-4h.01M9 16h.01M9 12h.01M13 16h.01M13 12h.01"/></svg>
                        <span>Toko</span>
                    </a>

                    <!-- Menu Lokasi & Radius -->
                    <a href="{{ route('tenant.location.index', ['tenant' => $tenantSlug]) }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('tenant.location.*') ? 'bg-[#062c1e] text-emerald-400 border-l-4 border-emerald-500 font-semibold' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/60' }}">
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Lokasi & Radius</span>
                    </a>

                    <!-- Saldo -->
                    <a href="#" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all text-zinc-400 hover:text-white hover:bg-zinc-800/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Saldo</span>
                    </a>

                    <!-- Penarikan Dana -->
                    <a href="#" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all text-zinc-400 hover:text-white hover:bg-zinc-800/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Penarikan Dana</span>
                    </a>

                    <!-- Laporan -->
                    <a href="#" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all text-zinc-400 hover:text-white hover:bg-zinc-800/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan</span>
                    </a>
                </nav>
            </div>

            <!-- Profile Footer di Bagian Bawah Sidebar (Figma) -->
            <div class="pt-4 border-t border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold shrink-0">
                        🐝
                    </div>
                    <div class="text-sm font-semibold text-white truncate">
                        {{ $tenantDisplayName }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') ?? '#' }}">
                    @csrf
                    <button type="submit" class="text-zinc-400 hover:text-red-400 p-1.5 transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT CONTAINER -->
        <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">
            
            <!-- TOPBAR / HEADER (Sesuai Desain Figma) -->
            <header class="h-16 flex items-center justify-between border-b border-slate-200 bg-white px-6 sticky top-0 z-10 shadow-sm">
                <!-- Toggle Sidebar Mobile -->
                <button type="button" @click="open = !open" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <!-- Search Input Bar (Figma) -->
                <div class="relative w-full max-w-md hidden sm:block">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" placeholder="Cari Produk..." class="w-full pl-9 pr-4 py-2 bg-slate-100 border-none rounded-full text-sm text-slate-700 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition-all outline-none">
                </div>

                <!-- Right Header Actions (Lonceng Notifikasi & Profil) -->
                <div class="flex items-center gap-4 ml-auto">
                    <!-- Notification Bell -->
                    <button class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-500 rounded-full"></span>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="flex items-center gap-2 border-l border-slate-200 pl-4">
                        <div class="w-8 h-8 rounded-full bg-amber-400/20 text-amber-600 flex items-center justify-center font-bold text-sm">
                            🐝
                        </div>
                        <span class="text-sm font-semibold text-slate-700">{{ $tenantDisplayName }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT AREA ($slot) -->
            <main class="flex-1 p-6 bg-slate-50">
                {{ $slot }}
            </main>
        </div>
    </div>
    @fluxScripts
</body>
</html>