<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-950 relative overflow-hidden py-12 px-4 sm:px-6 lg:px-8">
        <!-- Background Gradients -->
        <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/40 via-gray-950 to-gray-950 pointer-events-none"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-900/20 via-transparent to-transparent pointer-events-none"></div>

        <div class="relative z-10 w-full sm:max-w-md flex flex-col items-center">
            <!-- Logo Header -->
            <div class="mb-8">
                <Link href="/" class="focus:outline-none">
                    <ApplicationLogo />
                </Link>
            </div>

            <!-- Card Box -->
            <div class="w-full bg-gray-900/90 backdrop-blur-xl border border-white/10 rounded-2xl p-6 sm:p-8 shadow-2xl shadow-black/50">
                <slot />
            </div>

            <!-- Back to Home link -->
            <div class="mt-6 text-center">
                <Link href="/" class="text-xs text-gray-400 hover:text-amber-400 transition-colors flex items-center gap-1">
                    ← Kembali ke Pohon Silsilah
                </Link>
            </div>
        </div>
    </div>
</template>
