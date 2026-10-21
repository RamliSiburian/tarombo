<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    marga: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Keanggotaan — Tarombo Batak" />

        <!-- Heading -->
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-white tracking-tight">Daftar Keanggotaan</h1>
            <p class="text-xs text-gray-400 mt-1">Bergabunglah dengan komunitas Tarombo Batak</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nama Lengkap -->
            <div>
                <InputLabel for="name" value="Nama Lengkap *" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="contoh: Budi sihotang"
                />
                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
                <InputLabel for="email" value="Alamat Email *" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
                <p class="text-xs text-gray-400 mt-1">📧 Verifikasi email wajib dilakukan setelah pendaftaran.</p>
            </div>

            <!-- Row: Marga + No HP -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="marga" value="Marga" />
                    <TextInput
                        id="marga"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.marga"
                        autocomplete="off"
                        placeholder="contoh: sihotang"
                    />
                    <InputError class="mt-1.5" :message="form.errors.marga" />
                </div>
                <div>
                    <InputLabel for="phone" value="No. HP / WhatsApp" />
                    <TextInput
                        id="phone"
                        type="tel"
                        class="mt-1 block w-full"
                        v-model="form.phone"
                        autocomplete="tel"
                        placeholder="08xxxxxxxxxx"
                    />
                    <InputError class="mt-1.5" :message="form.errors.phone" />
                </div>
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Password *" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Min. 8 karakter"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi Password *" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                />
                <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-2">
                <Link
                    :href="route('login')"
                    class="text-xs text-gray-400 hover:text-amber-400 transition-colors"
                >
                    Sudah punya akun? Masuk
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-gray-950 font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                    {{ form.processing ? 'Mendaftar...' : 'Daftar Sekarang' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
