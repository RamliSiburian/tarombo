<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    nodes: Array,
});

const form = useForm({
    parent_node_id: '',
    name: '',
    gender: 'male',
    marga: '',
    anak_ke: '',
    asal_daerah: '',
    tahun_lahir: '',
    tahun_wafat: '',
    foto: null,
    deskripsi: '',
    spouse_name: '',
    spouse_marga: '',
    spouse_deskripsi: '',
    requester_name: '',
    requester_email: '',
});

const step = ref(1);
const totalSteps = 3;
const fotoPreview = ref(null);

const selectedParent = computed(() =>
    props.nodes.find(n => n.id == form.parent_node_id)
);

const onFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

const nextStep = () => { if (step.value < totalSteps) step.value++; };
const prevStep = () => { if (step.value > 1) step.value--; };

const submit = () => {
    form.post(route('request.store'), {
        forceFormData: true,
    });
};

const levelLabel = (level) => {
    const labels = ['Generasi 1 (Root)', 'Generasi 2', 'Generasi 3', 'Generasi 4', 'Generasi 5'];
    return labels[level] || `Generasi ${level + 1}`;
};
</script>

<template>
    <Head title="Daftar Silsilah" />
    <AppLayout>
        <div class="min-h-screen pt-24 pb-16 px-4">
            <div class="max-w-2xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 rounded-full px-4 py-1.5 text-amber-400 text-sm font-medium mb-4">
                        📝 Form Pendaftaran
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-3">Masuk ke Silsilah Batak</h1>
                    <p class="text-gray-400">Isi data di bawah ini untuk mengajukan permintaan bergabung ke dalam pohon silsilah. Admin akan meninjau dan menghubungi Anda via email.</p>
                </div>

                <!-- Progress Steps -->
                <div class="flex items-center justify-center gap-0 mb-8">
                    <template v-for="s in totalSteps" :key="s">
                        <div class="flex items-center gap-0">
                            <div :class="[
                                'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                                step >= s ? 'bg-amber-500 text-gray-900' : 'bg-gray-800 text-gray-500 border border-white/10'
                            ]">{{ s }}</div>
                        </div>
                        <div v-if="s < totalSteps" :class="['w-16 h-0.5 transition-all duration-300', step > s ? 'bg-amber-500' : 'bg-gray-700']"></div>
                    </template>
                </div>

                <!-- Form Card -->
                <div class="bg-gray-900/50 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">

                    <!-- Step 1: Data Diri -->
                    <div v-if="step === 1" class="p-6 space-y-5">
                        <h2 class="text-white font-semibold text-lg mb-1">Langkah 1: Data Diri Anda</h2>

                        <!-- Parent Node -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Bergabung sebagai keturunan dari <span class="text-red-400">*</span></label>
                            <select v-model="form.parent_node_id"
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-colors">
                                <option value="" disabled>-- Pilih leluhur --</option>
                                <option v-for="node in nodes" :key="node.id" :value="node.id">
                                    {{ node.name }} {{ node.marga ? `(Marga ${node.marga})` : '' }} — {{ levelLabel(node.level) }}
                                </option>
                            </select>
                            <div v-if="form.errors.parent_node_id" class="text-red-400 text-xs mt-1">{{ form.errors.parent_node_id }}</div>

                            <!-- Selected parent info -->
                            <div v-if="selectedParent" class="mt-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 text-sm">
                                <span class="text-indigo-400">✓ Anda akan menjadi keturunan dari: </span>
                                <span class="text-white font-semibold">{{ selectedParent.name }}</span>
                            </div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Nama Lengkap <span class="text-red-400">*</span></label>
                            <input v-model="form.name" type="text" placeholder="Nama lengkap Anda"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-2">Jenis Kelamin <span class="text-red-400">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="form.gender = 'male'"
                                        :class="['border rounded-xl p-3 text-sm font-medium transition-all', form.gender === 'male' ? 'border-indigo-500 bg-indigo-500/15 text-indigo-300' : 'border-white/10 text-gray-400 hover:border-white/20']">
                                    👨 Laki-laki
                                </button>
                                <button type="button" @click="form.gender = 'female'"
                                        :class="['border rounded-xl p-3 text-sm font-medium transition-all', form.gender === 'female' ? 'border-pink-500 bg-pink-500/15 text-pink-300' : 'border-white/10 text-gray-400 hover:border-white/20']">
                                    👩 Perempuan
                                </button>
                            </div>
                            <div v-if="form.gender === 'female'" class="mt-2 bg-pink-500/5 border border-pink-500/15 rounded-xl p-3 text-xs text-pink-300">
                                ℹ️ Dalam tradisi Batak, silsilah perempuan tidak dilanjutkan di pohon utama. Data Anda tetap akan tersimpan.
                            </div>
                        </div>

                        <!-- Marga & Anak ke -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Marga</label>
                                <input v-model="form.marga" type="text" placeholder="Contoh: Simanjuntak, Siregar, dll"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Anak ke- (Urutan Kelahiran)</label>
                                <input v-model="form.anak_ke" type="number" min="1" placeholder="Contoh: 1 (Sulung/Tertua)"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                <p class="text-gray-500 text-xs mt-1">Anak ke-1 posisi paling kiri, ke-2 di sebelahnya, dst.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Data Lengkap -->
                    <div v-if="step === 2" class="p-6 space-y-5">
                        <h2 class="text-white font-semibold text-lg mb-1">Langkah 2: Data Lengkap</h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Asal Daerah</label>
                                <input v-model="form.asal_daerah" type="text" placeholder="Contoh: Medan, Tapanuli"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Tahun Lahir</label>
                                <input v-model="form.tahun_lahir" type="text" placeholder="Contoh: 1980"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Tahun Wafat (jika sudah almarhum)</label>
                            <input v-model="form.tahun_wafat" type="text" placeholder="Kosongkan jika masih hidup"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        </div>

                        <!-- Photo -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Foto (opsional)</label>
                            <div class="flex items-center gap-4">
                                <div v-if="fotoPreview" class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 border border-white/10">
                                    <img :src="fotoPreview" class="w-full h-full object-cover"/>
                                </div>
                                <div v-else class="w-16 h-16 rounded-xl bg-gray-800 border border-white/10 flex items-center justify-center text-2xl flex-shrink-0">
                                    {{ form.gender === 'female' ? '👩' : '👨' }}
                                </div>
                                <label class="flex-1 cursor-pointer border-2 border-dashed border-white/10 hover:border-amber-500/30 rounded-xl px-4 py-3 text-center transition-colors">
                                    <span class="text-gray-400 text-sm">Klik untuk upload foto</span>
                                    <input type="file" class="hidden" accept="image/*" @change="onFotoChange"/>
                                </label>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Keterangan / Deskripsi Singkat</label>
                            <textarea v-model="form.deskripsi" rows="3" placeholder="Ceritakan singkat tentang Anda atau leluhur ini..."
                                      class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors resize-none"></textarea>
                        </div>

                        <!-- Spouse Data (only for male) -->
                        <div v-if="form.gender === 'male'" class="bg-pink-500/5 border border-pink-500/15 rounded-xl p-4 space-y-3">
                            <h3 class="text-pink-300 text-sm font-semibold">💑 Data Istri (opsional)</h3>
                            <div>
                                <label class="block text-gray-400 text-xs mb-1">Nama Istri</label>
                                <input v-model="form.spouse_name" type="text" placeholder="Nama lengkap istri"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs mb-1">Marga Istri</label>
                                <input v-model="form.spouse_marga" type="text" placeholder="Marga istri"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Data Pemohon -->
                    <div v-if="step === 3" class="p-6 space-y-5">
                        <h2 class="text-white font-semibold text-lg mb-1">Langkah 3: Data Pemohon</h2>
                        <p class="text-gray-500 text-sm">Data ini digunakan untuk mengirimkan konfirmasi via email dan tidak akan ditampilkan di publik.</p>

                        <!-- Summary -->
                        <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-2">
                            <h3 class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-3">Ringkasan Data</h3>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="text-gray-500">Nama:</div><div class="text-white font-medium">{{ form.name || '—' }}</div>
                                <div class="text-gray-500">Gender:</div><div class="text-white">{{ form.gender === 'male' ? '♂ Laki-laki' : '♀ Perempuan' }}</div>
                                <div class="text-gray-500">Marga:</div><div class="text-white">{{ form.marga || '—' }}</div>
                                <div class="text-gray-500">Di bawah:</div><div class="text-white">{{ selectedParent?.name || '—' }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Nama Pemohon <span class="text-red-400">*</span></label>
                            <input v-model="form.requester_name" type="text" placeholder="Nama lengkap Anda sebagai pemohon"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            <div v-if="form.errors.requester_name" class="text-red-400 text-xs mt-1">{{ form.errors.requester_name }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Email Pemohon <span class="text-red-400">*</span></label>
                            <input v-model="form.requester_email" type="email" placeholder="email@contoh.com"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                            <div v-if="form.errors.requester_email" class="text-red-400 text-xs mt-1">{{ form.errors.requester_email }}</div>
                            <p class="text-gray-600 text-xs mt-1">Konfirmasi akan dikirim ke email ini</p>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="px-6 pb-6 flex gap-3">
                        <button v-if="step > 1" @click="prevStep" type="button"
                                class="flex-1 border border-white/10 hover:border-white/20 text-gray-400 hover:text-white rounded-xl py-3 text-sm font-medium transition-all">
                            ← Kembali
                        </button>
                        <button v-if="step < totalSteps" @click="nextStep" type="button"
                                class="flex-1 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-xl py-3 text-sm transition-all">
                            Lanjut →
                        </button>
                        <button v-if="step === totalSteps" @click="submit" type="button"
                                :disabled="form.processing"
                                class="flex-1 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-semibold rounded-xl py-3 text-sm transition-all flex items-center justify-center gap-2">
                            <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ form.processing ? 'Mengirim...' : '✓ Kirim Permintaan' }}
                        </button>
                    </div>
                </div>

                <!-- Back link -->
                <div class="text-center mt-6">
                    <Link :href="route('tree.index')" class="text-gray-500 hover:text-gray-300 text-sm transition-colors">
                        ← Kembali ke pohon silsilah
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
