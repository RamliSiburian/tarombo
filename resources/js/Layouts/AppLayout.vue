<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const mobileMenuOpen = ref(false);
const scrolled = ref(false);

const handleScroll = () => {
    scrolled.value = window.scrollY > 10;
};

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <div class="min-h-screen bg-gray-950">
        <!-- Navbar -->
        <nav :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', scrolled ? 'bg-gray-900/95 backdrop-blur-md shadow-lg shadow-black/20' : 'bg-transparent']">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link :href="route('tree.index')" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <span class="text-gray-900 font-bold text-lg">T</span>
                        </div>
                        <div>
                            <div class="text-white font-bold text-lg leading-tight">Tarombo</div>
                            <div class="text-amber-400 text-xs leading-tight">Silsilah Batak</div>
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center gap-6">
                        <Link :href="route('tree.index')" class="text-gray-300 hover:text-amber-400 transition-colors text-sm font-medium">
                            🌳 Pohon Silsilah
                        </Link>
                        <Link :href="route('request.create')" class="text-gray-300 hover:text-amber-400 transition-colors text-sm font-medium">
                            📝 Daftar Silsilah
                        </Link>
                        <template v-if="$page.props.auth.user">
                            <Link :href="route('admin.dashboard')" class="bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold text-sm px-4 py-2 rounded-lg transition-colors">
                                Admin Panel
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="border border-amber-500/50 hover:border-amber-400 text-amber-400 text-sm px-4 py-2 rounded-lg transition-colors">
                                Login Admin
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile menu button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-400 hover:text-white p-2">
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="mobileMenuOpen" class="md:hidden pb-4 border-t border-white/10 mt-2 pt-4 space-y-2">
                    <Link :href="route('tree.index')" class="block text-gray-300 hover:text-amber-400 py-2 text-sm">🌳 Pohon Silsilah</Link>
                    <Link :href="route('request.create')" class="block text-gray-300 hover:text-amber-400 py-2 text-sm">📝 Daftar Silsilah</Link>
                    <Link v-if="$page.props.auth.user" :href="route('admin.dashboard')" class="block text-amber-400 py-2 text-sm font-semibold">Admin Panel</Link>
                    <Link v-else :href="route('login')" class="block text-amber-400 py-2 text-sm">Login Admin</Link>
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
