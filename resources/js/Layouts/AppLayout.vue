<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const dropdownOpen = ref(false);
const scrolled = ref(false);

const handleScroll = () => {
    scrolled.value = window.scrollY > 10;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const handleGlobalClick = (e) => {
    if (!e.target.closest('#nav-dropdown-container')) {
        dropdownOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    window.addEventListener('click', handleGlobalClick);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('click', handleGlobalClick);
});
</script>

<template>
    <div class="min-h-screen bg-gray-950">
        <!-- Navbar -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-gray-900/90 backdrop-blur-md border-b border-white/10 shadow-lg shadow-black/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-3 sm:gap-6">
                    <!-- Left: Logo & Brand + Search Bar Slot -->
                    <div class="flex items-center gap-3 sm:gap-5 flex-1 min-w-0">
                        <!-- Logo -->
                        <Link :href="route('tree.index')" class="flex items-center gap-2.5 group flex-shrink-0">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                                <span class="text-gray-950 font-black text-lg">T</span>
                            </div>
                            <div class="hidden sm:block">
                                <div class="text-white font-bold text-base leading-tight">Tarombo</div>
                                <div class="text-amber-400 text-[11px] leading-tight font-medium">Silsilah Batak</div>
                            </div>
                        </Link>

                        <!-- Search Bar Slot (Beside Logo - Nomor 1) -->
                        <div class="flex-1 max-w-xs sm:max-w-sm md:max-w-md">
                            <slot name="search" />
                        </div>
                    </div>

                    <!-- Right: Consolidated Dropdown Menu (Stats & Navigation - Nomor 3) -->
                    <div class="flex items-center gap-2">
                        <div id="nav-dropdown-container" class="relative">
                            <button
                                @click="dropdownOpen = !dropdownOpen"
                                type="button"
                                class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white text-xs sm:text-sm font-medium transition-all shadow-md hover:border-amber-500/30"
                            >
                                <div v-if="stats" class="flex items-center gap-1.5 text-xs text-amber-400 font-semibold">
                                    <span>👥 {{ stats.total_nodes }} Node</span>
                                    <span class="text-gray-600">•</span>
                                    <span>🏷️ {{ stats.total_marga }} Marga</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5 text-xs text-gray-200">
                                    <span>🌳 Menu</span>
                                </div>

                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Panel -->
                            <transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-if="dropdownOpen"
                                    class="absolute right-0 mt-2 w-64 rounded-2xl bg-gray-900 border border-white/10 shadow-2xl overflow-hidden z-50 divide-y divide-white/10"
                                >
                                    <!-- Stats Summary -->
                                    <div v-if="stats" class="p-3.5 bg-gradient-to-br from-amber-500/10 via-white/5 to-transparent">
                                        <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Statistik Silsilah</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="bg-gray-800/80 border border-white/5 rounded-xl p-2 text-center">
                                                <div class="text-amber-400 font-bold text-lg leading-tight">{{ stats.total_nodes }}</div>
                                                <div class="text-gray-400 text-[10px]">Total Node</div>
                                            </div>
                                            <div class="bg-gray-800/80 border border-white/5 rounded-xl p-2 text-center">
                                                <div class="text-amber-400 font-bold text-lg leading-tight">{{ stats.total_marga }}</div>
                                                <div class="text-gray-400 text-[10px]">Total Marga</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Navigation Links -->
                                    <div class="p-2 space-y-1">
                                        <Link
                                            :href="route('tree.index')"
                                            @click="closeDropdown"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-200 hover:text-white hover:bg-white/5 transition-colors"
                                        >
                                            <span class="text-base">🌳</span>
                                            <span class="font-medium">Pohon Silsilah</span>
                                        </Link>

                                        <Link
                                            :href="route('request.create')"
                                            @click="closeDropdown"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-amber-300 hover:text-amber-200 hover:bg-amber-500/10 transition-colors"
                                        >
                                            <span class="text-base">📝</span>
                                            <span class="font-medium">Daftar Silsilah</span>
                                        </Link>
                                    </div>

                                    <!-- Admin / Auth -->
                                    <div class="p-2">
                                        <template v-if="$page.props.auth?.user">
                                            <Link
                                                :href="route('admin.dashboard')"
                                                @click="closeDropdown"
                                                class="flex items-center justify-between px-3 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-sm font-semibold transition-colors"
                                            >
                                                <span class="flex items-center gap-2">
                                                    <span>⚙️</span>
                                                    <span>Admin Panel</span>
                                                </span>
                                                <span class="text-xs">→</span>
                                            </Link>
                                        </template>
                                        <template v-else>
                                            <Link
                                                :href="route('login')"
                                                @click="closeDropdown"
                                                class="flex items-center justify-between px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-200 hover:text-white text-sm font-medium transition-colors"
                                            >
                                                <span class="flex items-center gap-2">
                                                    <span>🔐</span>
                                                    <span>Login Admin</span>
                                                </span>
                                                <span class="text-xs text-gray-400">→</span>
                                            </Link>
                                        </template>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="fixed top-20 right-4 z-50 max-w-sm">
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 backdrop-blur-sm flex items-start gap-3 shadow-xl">
                <span class="text-emerald-400 text-xl flex-shrink-0">✅</span>
                <p class="text-emerald-300 text-sm">{{ $page.props.flash.success }}</p>
            </div>
        </div>
        <div v-if="$page.props.flash?.error" class="fixed top-20 right-4 z-50 max-w-sm">
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 backdrop-blur-sm flex items-start gap-3 shadow-xl">
                <span class="text-red-400 text-xl flex-shrink-0">❌</span>
                <p class="text-red-300 text-sm">{{ $page.props.flash.error }}</p>
            </div>
        </div>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
