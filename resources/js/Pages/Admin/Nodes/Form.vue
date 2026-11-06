<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';

const props = defineProps({
    parents: Array,
    node: { type: Object, default: null },
});

const isEdit = computed(() => !!props.node);

// Helper to format initial dates to MM/DD/YYYY
const formatInitialDate = (val) => {
    if (!val) return '';
    const str = String(val).trim();
    if (/^\d{4}$/.test(str)) {
        return `01/01/${str}`;
    }
    const ymd = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (ymd) {
        return `${String(ymd[2]).padStart(2, '0')}/${String(ymd[3]).padStart(2, '0')}/${ymd[1]}`;
    }
    return str;
};

// Helper to clean "Ibu Kandung: ..." from deskripsi for display in textarea
const extractCleanDeskripsi = (desc) => {
    if (!desc) return '';
    return desc.replace(/Ibu Kandung:\s*[^\n\r]+[\r\n]*/gi, '').trim();
};

// Helper to get initial mother_spouse_id
const getInitialMotherId = () => {
    if (props.node?.mother_spouse_id) return props.node.mother_spouse_id;
    if (props.node?.parent_id) {
        const parent = props.parents?.find(p => p.id == props.node.parent_id);
        if (parent?.spouses?.length === 1) {
            return parent.spouses[0].id;
        }
        if (parent?.spouses?.length > 1 && props.node?.deskripsi) {
            const match = props.node.deskripsi.match(/Ibu Kandung:\s*([^\n\r]+)/i);
            if (match) {
                const text = match[1].toLowerCase();
                const found = parent.spouses.find(s =>
                    (s.name && text.includes(s.name.toLowerCase())) ||
                    (s.marga && text.includes(s.marga.toLowerCase()))
                );
                if (found) return found.id;
            }
        }
    }
    return '';
};

const form = useForm({
    parent_id:        props.node?.parent_id        || '',
    mother_spouse_id: getInitialMotherId(),
    name:             props.node?.name             || '',
    gender:           props.node?.gender           || 'male',
    marga:            props.node?.marga            || '',
    asal_daerah:      props.node?.asal_daerah      || '',
    tahun_lahir:      formatInitialDate(props.node?.tahun_lahir),
    tahun_wafat:      formatInitialDate(props.node?.tahun_wafat),
    foto:             null,
    deskripsi:        extractCleanDeskripsi(props.node?.deskripsi),
    status:           props.node?.status           || 'active',
    sort_order:       props.node?.sort_order       || 1,
    reparent_children: [],
    spouses: props.node?.spouses?.map(s => ({
        name: s.name,
        marga: s.marga || '',
        tahun_lahir: formatInitialDate(s.tahun_lahir),
        tahun_wafat: formatInitialDate(s.tahun_wafat),
        deskripsi: s.deskripsi || '',
    })) || [],
});

const selectedParent = computed(() => {
    if (!form.parent_id) return null;
    return props.parents?.find(p => p.id == form.parent_id);
});

const parentSpouses = computed(() => {
    return selectedParent.value?.spouses || [];
});

const parentChildren = computed(() => {
    return selectedParent.value?.children || [];
});

// Watch when parent changes: ensure selected mother still belongs to the parent
watch(() => form.parent_id, (newParentId) => {
    if (!newParentId) {
        form.mother_spouse_id = '';
        return;
    }
    const currentParent = props.parents?.find(p => p.id == newParentId);
    const spouses = currentParent?.spouses || [];
    if (!spouses.some(s => s.id == form.mother_spouse_id)) {
        if (spouses.length === 1) {
            form.mother_spouse_id = spouses[0].id;
        } else {
            form.mother_spouse_id = '';
        }
    }
});

const fotoPreview = ref(props.node?.foto ? `/storage/${props.node.foto}` : null);

const onFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

const addSpouse = () => form.spouses.push({ name: '', marga: '', tahun_lahir: '', tahun_wafat: '', deskripsi: '' });
const removeSpouse = (i) => form.spouses.splice(i, 1);

const submit = () => {
    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('admin.nodes.update', props.node.id), {
            forceFormData: true,
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
                        <label class="block text-gray-400 text-sm mb-1.5">Parent (Orang Tua / Induk)</label>
                        <select v-model="form.parent_id"
                                class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors">
                            <option value="">— Tidak ada (root) —</option>
                            <option v-for="p in parents" :key="p.id" :value="p.id">
                                {{ p.name }} {{ p.marga ? `(${p.marga})` : '' }} — Gen {{ p.level + 1 }}
                            </option>
                        </select>
                    </div>

                    <!-- Mother Selection (Ibu Kandung) -->
                    <div v-if="form.parent_id" class="p-4 rounded-xl border border-pink-500/20 bg-pink-500/5 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-pink-300 text-sm font-semibold flex items-center gap-2">
                                <span>👩</span>
                                <span>Ibu Kandung (Istri dari {{ selectedParent?.name }})</span>
                            </label>
                            <span v-if="parentSpouses.length > 0" class="text-xs text-pink-400 bg-pink-500/10 px-2.5 py-1 rounded-full border border-pink-500/20">
                                {{ parentSpouses.length }} Istri Terdata
                            </span>
                        </div>

                        <!-- If parent has spouses -->
                        <div v-if="parentSpouses.length > 0" class="space-y-2">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    v-for="(spouse, idx) in parentSpouses"
                                    :key="spouse.id"
                                    :class="[
                                        'flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all text-sm',
                                        form.mother_spouse_id == spouse.id
                                            ? 'border-pink-500 bg-pink-500/20 shadow-md shadow-pink-500/10 text-white font-medium'
                                            : 'border-white/10 bg-gray-800/80 hover:border-pink-500/30 text-gray-300'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        :value="spouse.id"
                                        v-model="form.mother_spouse_id"
                                        class="text-pink-500 focus:ring-pink-500 bg-gray-800 border-white/20 w-4 h-4"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="truncate font-semibold">{{ spouse.name }}</div>
                                        <div class="text-xs text-pink-400/80">
                                            {{ spouse.marga ? `Marga ${spouse.marga}` : 'Marga belum diset' }}
                                            <span class="text-gray-500 ml-1">• Istri ke-{{ idx + 1 }}</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <button
                                v-if="form.mother_spouse_id"
                                type="button"
                                @click="form.mother_spouse_id = ''"
                                class="text-xs text-gray-400 hover:text-red-400 transition-colors flex items-center gap-1 mt-1"
                            >
                                ✕ Kosongkan pilihan Ibu Kandung
                            </button>
                        </div>

                        <!-- If parent has NO spouses -->
                        <div v-else class="text-xs text-primary-300/90 bg-primary-500/10 border border-primary-500/20 rounded-lg p-3 leading-relaxed">
                            ⚠️ <strong>{{ selectedParent?.name }}</strong> belum memiliki data istri di silsilah.
                            Untuk menetapkan ibu kandung, silakan tambahkan data istri pada node <strong>{{ selectedParent?.name }}</strong> terlebih dahulu.
                        </div>

                        <div v-if="form.errors.mother_spouse_id" class="text-red-400 text-xs">{{ form.errors.mother_spouse_id }}</div>
                    </div>

                    <!-- Reparent Existing Children Checklist (Kasus Sisip Generasi Perantara) -->
                    <div v-if="parentChildren.length > 0 && !isEdit" class="bg-indigo-950/40 border border-indigo-500/30 rounded-xl p-4 space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-indigo-400 text-lg flex-shrink-0">🔀</span>
                            <div>
                                <div class="text-white text-sm font-semibold">Sisipkan sebagai Orang Tua dari Keturunan yang Ada?</div>
                                <div class="text-gray-400 text-xs mt-0.5 leading-relaxed">
                                    Centang anak-anak di bawah ini jika node baru ini adalah ayah/orang tua mereka. Anak yang dicentang akan otomatis dipindahkan menjadi anak dari node baru ini (generasinya akan otomatis turun 1 tingkat):
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                            <label v-for="child in parentChildren" :key="child.id"
                                   class="flex items-center gap-3 bg-gray-900/90 border border-white/10 hover:border-indigo-500/50 rounded-xl p-3 cursor-pointer transition-colors">
                                <input type="checkbox" :value="child.id" v-model="form.reparent_children"
                                       class="rounded bg-gray-800 border-white/20 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-gray-900 w-4 h-4"/>
                                <div>
                                    <div class="text-gray-200 text-sm font-medium">{{ child.name }}</div>
                                    <div v-if="child.marga" class="text-gray-500 text-xs">Marga {{ child.marga }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Nama <span class="text-red-400">*</span></label>
                        <input v-model="form.name" type="text" required placeholder="Nama lengkap"
                               class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-primary-500/50 transition-colors"/>
                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Gender, Status & Sort Order -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Gender <span class="text-red-400">*</span></label>
                            <select v-model="form.gender"
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors">
                                <option value="male">♂ Laki-laki</option>
                                <option value="female">♀ Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Status</label>
                            <select v-model="form.status"
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors">
                                <option value="active">✅ Aktif</option>
                                <option value="pending">⏳ Pending</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Urutan Posisi (Sort Order)</label>
                            <input v-model="form.sort_order" type="number" min="1" required
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors"/>
                        </div>
                    </div>

                    <!-- Marga & Asal -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Marga</label>
                            <input v-model="form.marga" type="text" placeholder="Marga"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-primary-500/50 transition-colors"/>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Asal Daerah</label>
                            <input v-model="form.asal_daerah" type="text" placeholder="Asal daerah"
                                   class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-primary-500/50 transition-colors"/>
                        </div>
                    </div>

                    <!-- Tanggal / Tahun Lahir & Wafat -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5 flex items-center justify-between">
                                <span>Tanggal / Tahun Lahir</span>
                                <span class="text-[11px] text-primary-400/90 font-mono">MM/DD/YYYY</span>
                            </label>
                            <DatePicker v-model="form.tahun_lahir" placeholder="MM/DD/YYYY" />
                            <div v-if="form.errors.tahun_lahir" class="text-red-400 text-xs mt-1">{{ form.errors.tahun_lahir }}</div>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5 flex items-center justify-between">
                                <span>Tanggal / Tahun Wafat</span>
                                <span class="text-[11px] text-gray-500 font-mono">MM/DD/YYYY (opsional)</span>
                            </label>
                            <DatePicker v-model="form.tahun_wafat" placeholder="Kosongkan jika masih hidup" />
                            <div v-if="form.errors.tahun_wafat" class="text-red-400 text-xs mt-1">{{ form.errors.tahun_wafat }}</div>
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
                            <label class="flex-1 cursor-pointer border-2 border-dashed border-white/10 hover:border-primary-500/30 rounded-xl px-4 py-3 text-center transition-colors">
                                <div class="text-gray-400 text-sm">Klik untuk upload / ganti foto</div>
                                <div class="text-gray-600 text-xs mt-0.5">JPG, PNG, WebP maks 2MB</div>
                                <input type="file" class="hidden" accept="image/*" @change="onFotoChange"/>
                            </label>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1.5">Deskripsi / Riwayat Tambahan</label>
                        <textarea v-model="form.deskripsi" rows="3" placeholder="Keterangan singkat..."
                                  class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-primary-500/50 transition-colors resize-none"></textarea>
                        <p class="text-[11px] text-gray-500 mt-1">Catatan: Data Ibu Kandung yang dipilih di atas akan otomatis disinkronkan ke profil dan deskripsi silsilah.</p>
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
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-500 text-xs mb-1 flex items-center justify-between">
                                    <span>Tgl Lahir Istri</span>
                                    <span class="text-[10px] text-pink-400/80 font-mono">MM/DD/YYYY</span>
                                </label>
                                <DatePicker v-model="spouse.tahun_lahir" placeholder="MM/DD/YYYY" />
                            </div>
                            <div>
                                <label class="block text-gray-500 text-xs mb-1 flex items-center justify-between">
                                    <span>Tgl Wafat Istri</span>
                                    <span class="text-[10px] text-gray-500 font-mono">Kosongkan jika hidup</span>
                                </label>
                                <DatePicker v-model="spouse.tahun_wafat" placeholder="Kosongkan jika hidup" />
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
                            class="flex-1 bg-primary-500 hover:bg-primary-400 disabled:opacity-50 text-gray-900 font-semibold rounded-xl py-3 text-sm transition-all flex items-center justify-center gap-2">
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-gray-900 border-t-transparent rounded-full animate-spin"></span>
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? '✓ Update Node' : '✓ Simpan Node') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
