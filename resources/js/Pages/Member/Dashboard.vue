<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

defineProps({
    user: Object,
});

const page = usePage();
</script>

<template>
    <Head title="Dashboard Anggota — Tarombo Batak" />

    <div class="min-h-screen bg-gray-950 text-white" style="font-family: 'Inter', sans-serif;">
        <!-- Navbar -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-gray-900/90 backdrop-blur-md border-b border-white/10 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-red-700 flex items-center justify-center">🌳</div>
                        <span class="text-sm font-bold text-white" style="font-family: 'Cinzel', serif;">TAROMBO BATAK</span>
                    </Link>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-400">{{ user?.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="px-3 py-1.5 text-xs border border-white/20 text-gray-300 hover:border-red-500/50 hover:text-red-400 rounded-lg transition-all"
                        >
                            Keluar
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="pt-24 pb-16 max-w-5xl mx-auto px-6">
            <!-- Welcome Banner -->
            <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-amber-900/30 to-red-900/20 border border-amber-500/20">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">👋</div>
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-1">Horas, {{ user?.name }}!</h1>
                        <p class="text-gray-400 text-sm">
                            Selamat datang di dashboard keanggotaan Tarombo Batak.
                            <span v-if="user?.marga" class="text-amber-400 font-medium">Marga {{ user.marga }}.</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Approval Status Banner -->
            <div v-if="user?.membership_status === 'pending'" class="mb-8 p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-start gap-3.5">
                <span class="text-2xl flex-shrink-0">⏳</span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-amber-400">Pendaftaran Menunggu Persetujuan Admin</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-400/20 text-amber-300">Dalam Peninjauan</span>
                    </div>
                    <p class="text-xs text-amber-200/80 mt-1 leading-relaxed">
                        Email Anda telah terverifikasi. Saat ini pendaftaran keanggotaan Anda sedang ditinjau oleh pengelola (Admin).
                        Akses fitur penuh komunitas akan terbuka otomatis setelah disetujui.
                    </p>
                </div>
            </div>

            <div v-else-if="user?.membership_status === 'rejected'" class="mb-8 p-5 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-start gap-3.5">
                <span class="text-2xl flex-shrink-0">❌</span>
                <div>
                    <h3 class="text-sm font-bold text-red-400">Pendaftaran Belum Disetujui</h3>
                    <p class="text-xs text-red-300/80 mt-1 leading-relaxed">
                        Mohon maaf, permohonan keanggotaan Anda belum disetujui oleh pengelola. Silakan hubungi pengelola komunitas jika ada kekeliruan data.
                    </p>
                </div>
            </div>

            <div v-else class="mb-8 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Email Terverifikasi · Keanggotaan Disetujui</span>
            </div>

            <!-- Coming Soon Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="p-6 rounded-2xl bg-gray-900/60 border border-white/5 opacity-50">
                    <div class="text-3xl mb-3">🌳</div>
                    <h3 class="text-base font-semibold text-white mb-1">Silsilah Saya</h3>
                    <p class="text-sm text-gray-500">Tandai posisi Anda dalam pohon silsilah.</p>
                    <span class="inline-block mt-3 px-2 py-0.5 text-xs bg-gray-800 text-gray-500 rounded-full border border-white/5">Segera Hadir</span>
                </div>
                <div class="p-6 rounded-2xl bg-gray-900/60 border border-white/5 opacity-50">
                    <div class="text-3xl mb-3">✏️</div>
                    <h3 class="text-base font-semibold text-white mb-1">Ajukan Data</h3>
                    <p class="text-sm text-gray-500">Tambahkan anggota keluarga ke pohon.</p>
                    <Link :href="route('request.create')" class="inline-block mt-3 px-3 py-1 text-xs bg-amber-500/20 text-amber-400 rounded-full border border-amber-500/30 hover:bg-amber-500/30 transition-colors">
                        Buka Form →
                    </Link>
                </div>
                <div class="p-6 rounded-2xl bg-gray-900/60 border border-white/5 opacity-50">
                    <div class="text-3xl mb-3">👥</div>
                    <h3 class="text-base font-semibold text-white mb-1">Direktori Anggota</h3>
                    <p class="text-sm text-gray-500">Terhubung dengan sesama anggota marga.</p>
                    <span class="inline-block mt-3 px-2 py-0.5 text-xs bg-gray-800 text-gray-500 rounded-full border border-white/5">Segera Hadir</span>
                </div>
            </div>

            <!-- Quick link to tree -->
            <div class="mt-8 p-5 rounded-2xl bg-gray-900/60 border border-amber-500/20 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Jelajahi Pohon Silsilah</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Lihat seluruh pohon keluarga Batak secara interaktif.</p>
                </div>
                <Link
                    :href="route('tree.index')"
                    class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-gray-900 font-bold rounded-xl hover:from-amber-400 hover:to-amber-500 transition-all text-sm"
                >
                    Buka Pohon →
                </Link>
            </div>
        </div>
    </div>
</template>
