<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    parents: Array,
    node: { type: Object, default: null },
});

const isEdit = computed(() => !!props.node);

const form = useForm({
    parent_id:   props.node?.parent_id  || '',
    name:        props.node?.name       || '',
    gender:      props.node?.gender     || 'male',
    marga:       props.node?.marga      || '',
    asal_daerah: props.node?.asal_daerah || '',
    tahun_lahir: props.node?.tahun_lahir || '',
    tahun_wafat: props.node?.tahun_wafat || '',
    foto:        null,
    deskripsi:   props.node?.deskripsi  || '',
    status:      props.node?.status     || 'active',
    spouses: props.node?.spouses?.map(s => ({
        name: s.name, marga: s.marga || '', deskripsi: s.deskripsi || '',
    })) || [],
});

const fotoPreview = ref(props.node?.foto ? `/storage/${props.node.foto}` : null);

const onFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

const addSpouse = () => form.spouses.push({ name: '', marga: '', deskripsi: '' });
const removeSpouse = (i) => form.spouses.splice(i, 1);

const submit = () => {
    if (isEdit.value) {
        form.post(route('admin.nodes.update', props.node.id), {
            forceFormData: true,
            _method: 'PUT',
        });
    } else {
        form.post(route('admin.nodes.store'), { forceFormData: true });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Node' : 'Tambah Node'" />
    <AdminLayout>
        <template #title>{{ isEdit ? 'Edit Node' : 'Tambah Node Baru' }}</template>

        <div class="max-w-2xl">
            <!-- Back -->
            <Link :href="route('admin.nodes.index')" class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-6 transition-colors">
                ← Kembali ke daftar node
            </Link>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Basic Info Card -->
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-6 space-y-5">
                    <h2 class="text-white font-semibold">Informasi Dasar</h2>

                    <!-- Parent -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Parent (Induk)</label>
                        <select v-model="form.parent_id"
                                class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-colors">
                            <option value="">— Tidak ada (root) —</option>
                            <option v-for="p in parents" :key="p.id" :value="p.id">
                                {{ p.name }} {{ p.marga ? `(${p.marga})` : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Nama <span class="text-red-400">*</span></label>
                        <input v-model="form.name" type="text" required placeholder="Nama lengkap"
                               class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Gender & Status -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Gender <span class="text-red-400">*</span></label>
                            <select v-model="form.gender"
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-colors">
                                <option value="male">♂ Laki-laki</option>
                                <option value="female">♀ Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Status</label>
                            <select v-model="form.status"
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 transition-colors">
                                <option value="active">✅ Aktif</option>
                                <option value="pending">⏳ Pending</option>
                            </select>
                        </div>
                    </div>

                    <!-- Marga & Asal -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Marga</label>
                            <input v-model="form.marga" type="text" placeholder="Marga"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Asal Daerah</label>
                            <input v-model="form.asal_daerah" type="text" placeholder="Asal daerah"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        </div>
                    </div>

                    <!-- Tahun -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Tahun Lahir</label>
                            <input v-model="form.tahun_lahir" type="text" placeholder="Tahun lahir"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Tahun Wafat</label>
                            <input v-model="form.tahun_wafat" type="text" placeholder="Kosongkan jika masih hidup"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                        </div>
                    </div>
                </div>

                <!-- Additional Info Card -->
                <div class="bg-gray-900 border border-white/5 rounded-2xl p-6 space-y-5">
                    <h2 class="text-white font-semibold">Informasi Tambahan</h2>

                    <!-- Foto -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Foto</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-gray-800 border border-white/10 flex items-center justify-center">
                                <img v-if="fotoPreview" :src="fotoPreview" class="w-full h-full object-cover"/>
                                <span v-else class="text-2xl">{{ form.gender === 'female' ? '👩' : '👨' }}</span>
                            </div>
                            <label class="flex-1 cursor-pointer border-2 border-dashed border-white/10 hover:border-amber-500/30 rounded-xl px-4 py-3 text-center transition-colors">
                                <div class="text-gray-400 text-sm">Klik untuk upload / ganti foto</div>
                                <div class="text-gray-600 text-xs mt-0.5">JPG, PNG, WebP maks 2MB</div>
                                <input type="file" class="hidden" accept="image/*" @change="onFotoChange"/>
                            </label>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Deskripsi</label>
                        <textarea v-model="form.deskripsi" rows="3" placeholder="Keterangan singkat..."
                                  class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-amber-500/50 transition-colors resize-none"></textarea>
                    </div>
                </div>

                <!-- Spouses Card (male only) -->
                <div v-if="form.gender === 'male'" class="bg-gray-900 border border-white/5 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-white font-semibold">Data Istri</h2>
                        <button type="button" @click="addSpouse"
                                class="text-xs bg-pink-500/10 hover:bg-pink-500/20 border border-pink-500/20 text-pink-400 px-3 py-1.5 rounded-lg transition-colors">
                            + Tambah Istri
                        </button>
                    </div>

                    <div v-if="form.spouses.length === 0" class="text-center py-6 text-gray-600 text-sm border border-dashed border-white/5 rounded-xl">
                        Belum ada data istri. Klik "Tambah Istri" untuk menambahkan.
                    </div>

                    <div v-for="(spouse, i) in form.spouses" :key="i" class="mb-3 bg-pink-500/5 border border-pink-500/15 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-pink-300 text-sm font-medium">Istri {{ i + 1 }}</span>
                            <button type="button" @click="removeSpouse(i)" class="text-red-400 hover:text-red-300 text-xs transition-colors">Hapus</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-500 text-xs mb-1">Nama <span class="text-red-400">*</span></label>
                                <input v-model="spouse.name" type="text" required placeholder="Nama istri"
                                       class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-xs mb-1">Marga</label>
                                <input v-model="spouse.marga" type="text" placeholder="Marga istri"
                                       class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-500 text-xs mb-1">Deskripsi</label>
                            <input v-model="spouse.deskripsi" type="text" placeholder="Keterangan singkat istri"
                                   class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <Link :href="route('admin.nodes.index')"
                          class="flex-1 text-center border border-white/10 hover:border-white/20 text-gray-400 hover:text-white rounded-xl py-3 text-sm font-medium transition-all">
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-gray-900 font-semibold rounded-xl py-3 text-sm transition-all flex items-center justify-center gap-2">
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-gray-900 border-t-transparent rounded-full animate-spin"></span>
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? '✓ Update Node' : '✓ Simpan Node') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
