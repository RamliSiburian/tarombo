<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-white tracking-tight">Masuk Admin Panel</h2>
            <p class="text-xs text-gray-400 mt-1">Silakan masuk menggunakan akun pengelola Tarombo</p>
        </div>

        <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-medium text-emerald-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-medium text-gray-300 mb-1.5">Alamat Email</label>
                <input
                    id="email"
                    type="email"
                    class="w-full px-4 py-2.5 bg-gray-950/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                    v-model="form.email"
                    placeholder="nama@email.com"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-gray-300 mb-1.5">Kata Sandi</label>
                <input
                    id="password"
                    type="password"
                    class="w-full px-4 py-2.5 bg-gray-950/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="rounded border-white/20 bg-gray-950 text-amber-500 focus:ring-amber-500 focus:ring-offset-gray-900"
                    />
                    <span class="ms-2 text-xs text-gray-400">Ingat saya</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs text-gray-400 hover:text-amber-400 transition-colors"
                >
                    Lupa kata sandi?
                </Link>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-gray-950 font-bold text-sm rounded-xl shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-2"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="animate-spin text-base">⏳</span>
                    <span>Masuk ke Admin Panel</span>
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
