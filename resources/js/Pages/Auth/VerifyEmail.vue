<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({});
const submit = () => form.post(route('verification.send'));
</script>

<template>
    <Head title="Verifikasi Email — Punguan Sihotang" />

    <div class="min-h-screen bg-gray-950 flex items-center justify-center p-4" style="font-family: 'Inter', sans-serif;">
        <!-- Background glow -->
        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(180, 83, 9, 0.12) 0%, transparent 70%);"></div>

        <div class="relative z-10 w-full max-w-md">
            <!-- Card -->
            <div class="bg-gray-900/80 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl text-center">
                <!-- Icon -->
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-primary-500/20 to-primary-700/20 border border-primary-500/30 flex items-center justify-center text-4xl">
                    📧
                </div>

                <!-- Logo -->
                <div class="flex items-center justify-center gap-2.5 mb-6">
                    <img src="/images/logo.png" alt="Logo" class="w-8 h-8 object-contain" />
                    <span class="text-xs font-bold text-white tracking-wider" style="font-family: 'Cinzel', serif;">PUNGUAN SIHOTANG DOHOT BORUNA</span>
                </div>

                <h1 class="text-2xl font-bold text-white mb-3">Verifikasi Email Anda</h1>
                <p class="text-gray-400 text-sm leading-relaxed mb-6">
                    Terima kasih sudah mendaftar! Kami telah mengirimkan link verifikasi ke alamat email Anda.
                    Silakan periksa kotak masuk (dan folder <strong class="text-gray-300">Spam</strong>) untuk mengaktifkan akun.
                </p>

                <!-- Success alert -->
                <div
                    v-if="status === 'verification-link-sent'"
                    class="mb-6 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm"
                >
                    ✅ Link verifikasi baru telah dikirim ke email Anda.
                </div>

                <!-- Resend form -->
                <form @submit.prevent="submit">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-6 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 text-gray-900 font-bold hover:from-primary-400 hover:to-primary-500 transition-all shadow-lg shadow-primary-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'Mengirim...' : '🔁 Kirim Ulang Email Verifikasi' }}
                    </button>
                </form>

                <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-center gap-4 text-sm">
                    <Link :href="route('home')" class="text-gray-500 hover:text-gray-300 transition-colors">
                        ← Kembali ke Beranda
                    </Link>
                    <span class="text-gray-700">|</span>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-gray-500 hover:text-red-400 transition-colors"
                    >
                        Keluar
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
