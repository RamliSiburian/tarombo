<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const sidebarOpen = ref(true);

const navItems = [
    { label: 'Dashboard', icon: '📊', route: 'admin.dashboard' },
    { label: 'Kelola Node', icon: '🌳', route: 'admin.nodes.index' },
    { label: 'Request Masuk', icon: '📬', route: 'admin.requests.index', badge: true },
    { label: 'Riwayat Request', icon: '📜', route: 'admin.requests.history' },
];

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-gray-950 flex">
        <!-- Sidebar -->
        <aside :class="['flex-shrink-0 bg-gray-900 border-r border-white/5 transition-all duration-300', sidebarOpen ? 'w-64' : 'w-16']">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="p-4 border-b border-white/5">
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                        <div class="w-9 h-9 flex-shrink-0 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                            <span class="text-gray-900 font-bold text-lg">T</span>
                        </div>
                        <div v-if="sidebarOpen" class="overflow-hidden">
                            <div class="text-white font-bold text-base leading-tight whitespace-nowrap">Tarombo</div>
                            <div class="text-amber-400 text-xs leading-tight whitespace-nowrap">Admin Panel</div>
                        </div>
                    </Link>
                </div>

                <!-- Nav Items -->
                <nav class="flex-1 p-3 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-200',
                            route().current(item.route)
                                ? 'bg-amber-500/15 text-amber-400 font-medium'
                                : 'text-gray-400 hover:text-white hover:bg-white/5'
                        ]"
                    >
                        <span class="text-lg flex-shrink-0">{{ item.icon }}</span>
                        <span v-if="sidebarOpen" class="whitespace-nowrap overflow-hidden flex-1">{{ item.label }}</span>
                        <span v-if="sidebarOpen && item.badge && $page.props.pendingRequestCount > 0"
                              class="bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                            {{ $page.props.pendingRequestCount }}
                        </span>
                    </Link>
                </nav>

                <!-- Footer -->
                <div class="p-3 border-t border-white/5 space-y-2">
                    <Link :href="route('tree.index')" :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-white/5 transition-all']">
                        <span class="text-lg flex-shrink-0">🌐</span>
                        <span v-if="sidebarOpen" class="whitespace-nowrap">Lihat Website</span>
                    </Link>
                    <button @click="logout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-red-400 hover:bg-red-500/5 transition-all">
                        <span class="text-lg flex-shrink-0">🚪</span>
                        <span v-if="sidebarOpen" class="whitespace-nowrap">Keluar</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Bar -->
            <header class="bg-gray-900/50 border-b border-white/5 px-6 py-4 flex items-center gap-4 sticky top-0 z-10 backdrop-blur-md">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-semibold text-lg flex-1">
                    <slot name="title">Admin Panel</slot>
                </h1>
                <div class="flex items-center gap-3 text-sm text-gray-400">
                    <span>{{ $page.props.auth.user?.name }}</span>
                    <div class="w-8 h-8 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold">
                        {{ $page.props.auth.user?.name?.charAt(0) }}
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4">
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-emerald-400 text-xl">✅</span>
                    <p class="text-emerald-300 text-sm">{{ $page.props.flash.success }}</p>
                </div>
            </div>
            <div v-if="$page.props.flash?.error" class="mx-6 mt-4">
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-red-400 text-xl">❌</span>
                    <p class="text-red-300 text-sm">{{ $page.props.flash.error }}</p>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
