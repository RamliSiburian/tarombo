<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';

const props = defineProps({
    initialNodes: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    parent_node_id: '',
    mother_spouse_id: '',
    name: '',
    gender: 'male',
    marga: '',
    anak_ke: '',
    asal_daerah: '',
    tahun_lahir: '',
    tahun_wafat: '',
    foto: null,
    deskripsi: '',
    spouses: [
        { name: '', marga: '', tahun_lahir: '', tahun_wafat: '', deskripsi: '' }
    ],
    is_self: false,
    requester_name: '',
    requester_email: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const step = ref(1);
const totalSteps = 3;
const fotoPreview = ref(null);

// Search Leluhur with 1.5s debounce & 12 items limit
const parentSearchQuery = ref('');
const parentSearchResults = ref(props.initialNodes && props.initialNodes.length > 0 ? [...props.initialNodes] : []);
const isSearchingParent = ref(false);
const showParentDropdown = ref(false);
const selectedParent = ref(null);
const searchContainerRef = ref(null);
let searchTimeout = null;

const fetchDefaultNodes = async () => {
    try {
        const res = await axios.get(route('tree.search'), {
            params: {
                gender: 'male',
                limit: 12,
            }
        });
        if (res.data && res.data.length > 0) {
            parentSearchResults.value = res.data;
        }
    } catch (e) {
        console.error('Failed to fetch default nodes', e);
    }
};

onMounted(() => {
    if (!parentSearchResults.value.length) {
        fetchDefaultNodes();
    }
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (searchContainerRef.value && !searchContainerRef.value.contains(e.target)) {
        showParentDropdown.value = false;
    }
};

const onParentSearchInput = () => {
    clearTimeout(searchTimeout);
    if (!parentSearchQuery.value.trim()) {
        if (props.initialNodes && props.initialNodes.length > 0) {
            parentSearchResults.value = [...props.initialNodes];
        } else {
            fetchDefaultNodes();
        }
        showParentDropdown.value = true;
        isSearchingParent.value = false;
        return;
    }

    isSearchingParent.value = true;
    showParentDropdown.value = true;

    searchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get(route('tree.search'), {
                params: {
                    q: parentSearchQuery.value.trim(),
                    gender: 'male',
                    limit: 12,
                }
            });
            parentSearchResults.value = res.data || [];
        } catch (e) {
            console.error('Failed to search parent', e);
        } finally {
            isSearchingParent.value = false;
        }
    }, 1500);
};

const onFocusParentInput = () => {
    if (!parentSearchQuery.value.trim()) {
        if (props.initialNodes && props.initialNodes.length > 0) {
            parentSearchResults.value = [...props.initialNodes];
        } else if (!parentSearchResults.value.length) {
            fetchDefaultNodes();
        }
    }
    showParentDropdown.value = true;
};

const selectParent = (node) => {
    selectedParent.value = node;
    form.parent_node_id = node.id;
    form.mother_spouse_id = ''; // reset mother selection
    parentSearchQuery.value = `${node.name} ${node.marga ? '(Marga ' + node.marga + ')' : ''}`;
    showParentDropdown.value = false;
};

const clearParentSelection = () => {
    selectedParent.value = null;
    form.parent_node_id = '';
    form.mother_spouse_id = '';
    parentSearchQuery.value = '';
    if (props.initialNodes && props.initialNodes.length > 0) {
        parentSearchResults.value = [...props.initialNodes];
    } else {
        fetchDefaultNodes();
    }
};

const selectedMother = computed(() => {
    if (!selectedParent.value?.spouses || !form.mother_spouse_id) return null;
    return selectedParent.value.spouses.find(s => s.id == form.mother_spouse_id);
});

// Step 1 Validation & Next Button state:
// Tombol Lanjut dinonaktifkan jika field required belum terisi atau parent tidak memiliki istri
const isStep1Disabled = computed(() => {
    // 1. Leluhur / Ayah harus dipilih
    if (!form.parent_node_id || !selectedParent.value) return true;
    // 2. Ayah HARUS memiliki data istri di silsilah
    if (!selectedParent.value.spouses || selectedParent.value.spouses.length === 0) return true;
    // 3. Salah satu istri harus dipilih sebagai Ibu Kandung
    if (!form.mother_spouse_id) return true;
    // 4. Nama lengkap harus diisi
    if (!form.name || !form.name.trim()) return true;

    return false;
});

const isStep3Disabled = computed(() => {
    if (form.processing) return true;
    if (form.is_self) {
        if (!form.email || !form.email.trim()) return true;
        if (!form.password || form.password.length < 8) return true;
        if (form.password !== form.password_confirmation) return true;
        return false;
    } else {
        if (!form.requester_name || !form.requester_name.trim()) return true;
        if (!form.requester_email || !form.requester_email.trim()) return true;
        return false;
    }
});

const getWifeLabel = (index) => {
    const labels = ['Istri Pertama (1)', 'Istri Kedua (2)', 'Istri Ketiga (3)', 'Istri Keempat (4)', 'Istri Kelima (5)'];
    return labels[index] || `Istri Ke-${index + 1} (${index + 1})`;
};

const addSpouse = () => {
    form.spouses.push({ name: '', marga: '', tahun_lahir: '', tahun_wafat: '', deskripsi: '' });
};

const removeSpouse = (index) => {
    form.spouses.splice(index, 1);
};

const onFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

const nextStep = () => {
    if (step.value === 1) {
        if (isStep1Disabled.value) {
            if (!form.parent_node_id) {
                alert('Silakan cari dan pilih leluhur / ayah terlebih dahulu.');
            } else if (!selectedParent.value?.spouses || selectedParent.value.spouses.length === 0) {
                alert('Leluhur / ayah yang dipilih belum memiliki data istri di silsilah. Harap perbarui data istri ayah terlebih dahulu sebelum mendaftarkan anak.');
            } else if (!form.mother_spouse_id) {
                alert('Silakan pilih salah satu istri dari ayah sebagai Ibu Kandung.');
            } else if (!form.name.trim()) {
                alert('Silakan isi nama lengkap Anda.');
            }
            return;
        }
    }
    if (step.value < totalSteps) step.value++;
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submit = () => {
    if (form.processing) return;

    // Validate Step 1 first
    if (isStep1Disabled.value) {
        if (!form.parent_node_id) {
            alert('Silakan cari dan pilih leluhur / ayah terlebih dahulu pada Langkah 1.');
        } else if (!selectedParent.value?.spouses || selectedParent.value.spouses.length === 0) {
            alert('Leluhur / ayah yang dipilih belum memiliki data istri di silsilah. Harap perbarui data istri ayah terlebih dahulu.');
        } else if (!form.mother_spouse_id) {
            alert('Silakan pilih salah satu istri dari ayah sebagai Ibu Kandung pada Langkah 1.');
        } else if (!form.name || !form.name.trim()) {
            alert('Silakan isi nama lengkap Anda pada Langkah 1.');
        }
        step.value = 1;
        return;
    }

    // Validate Step 3
    if (form.is_self) {
        if (!form.email || !form.email.trim()) {
            alert('Silakan masukkan alamat email aktif Anda untuk akun anggota.');
            return;
        }
        if (!form.password) {
            alert('Silakan buat kata sandi untuk akun anggota Anda.');
            return;
        }
        if (form.password.length < 8) {
            alert('Kata sandi minimal harus 8 karakter.');
            return;
        }
        if (form.password !== form.password_confirmation) {
            alert('Konfirmasi kata sandi tidak cocok. Harap periksa kembali.');
            return;
        }
    } else {
        if (!form.requester_name || !form.requester_name.trim()) {
            alert('Silakan masukkan nama lengkap pemohon.');
            return;
        }
        if (!form.requester_email || !form.requester_email.trim()) {
            alert('Silakan masukkan alamat email pemohon.');
            return;
        }
    }

    // Filter out spouses with empty name before sending
    const validSpouses = (form.spouses || []).filter(
        (s) => s && s.name && s.name.trim() !== ''
    );

    form.transform((data) => ({
        ...data,
        is_self: data.is_self ? 1 : 0,
        requester_name: data.is_self ? data.name : data.requester_name,
        requester_email: data.is_self ? data.email : data.requester_email,
        spouses: validSpouses,
    })).post(route('request.store'), {
        forceFormData: true,
        onError: (errors) => {
            console.error('Validation errors:', errors);
            if (errors.parent_node_id || errors.mother_spouse_id || errors.name || errors.gender) {
                alert('Terdapat data yang belum lengkap pada Langkah 1: ' + (errors.mother_spouse_id || errors.parent_node_id || errors.name || errors.gender));
                step.value = 1;
            } else if (Object.keys(errors).some(k => k.startsWith('spouses') || k === 'foto')) {
                alert('Terdapat kesalahan pada data di Langkah 2.');
                step.value = 2;
            } else if (errors.email) {
                alert('Pendaftaran gagal: ' + errors.email);
            } else if (errors.password) {
                alert('Pendaftaran gagal: ' + errors.password);
            } else {
                const firstErr = Object.values(errors)[0];
                alert('Gagal mengirim permintaan: ' + (firstErr || 'Harap periksa kembali data Anda.'));
            }
        },
    });
};

const levelLabel = (level) => {
    const labels = ['Generasi 1 (Root)', 'Generasi 2', 'Generasi 3', 'Generasi 4', 'Generasi 5', 'Generasi 6'];
    return labels[level] || `Generasi ${level + 1}`;
};
</script>

<template>
    <Head>
        <title>Ajukan Silsilah — Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</title>
        <meta name="description" content="Ajukan pendaftaran data silsilah ke dalam pohon silsilah Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia." />
        <meta name="keywords" content="silsilah sihotang, daftar tarombo sihotang, raja sigodang ulu sihotang, punguan sihotang dohot boruna" />
        <meta property="og:title" content="Ajukan Silsilah — Punguan Sihotang Dohot Boruna" />
        <meta property="og:description" content="Ajukan data silsilah keluarga Anda ke dalam pohon silsilah Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia." />
        <meta property="og:image" content="/images/logo.png" />
        <meta property="og:type" content="website" />
    </Head>
    <AppLayout>
        <div class="min-h-screen pt-24 pb-16 px-4">
            <div class="max-w-2xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 rounded-full px-4 py-1.5 text-amber-400 text-sm font-medium mb-4">
                        📝 Form Pengajuan Silsilah
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-3" style="font-family: 'Cinzel', serif;">
                        Silsilah Pomparan Raja Sigodang Ulu Sihotang
                    </h1>
                    <p class="text-gray-400 text-sm">Isi data di bawah ini untuk mengajukan penambahan data ke dalam pohon silsilah atau mendaftar sebagai anggota keluarga.</p>
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
                        <h2 class="text-white font-semibold text-lg mb-1">Langkah 1: Data Diri & Asal Usul</h2>

                        <!-- Parent Node Search (Debounced 1.5s & Limit 12) -->
                        <div ref="searchContainerRef" class="relative">
                            <label class="block text-gray-400 text-sm mb-1.5">
                                Cari & Pilih Leluhur / Ayah <span class="text-red-400">*</span>
                            </label>

                            <div class="relative flex items-center">
                                <input
                                    v-model="parentSearchQuery"
                                    @input="onParentSearchInput"
                                    @focus="onFocusParentInput"
                                    type="text"
                                    placeholder="Ketik nama atau marga leluhur..."
                                    class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 pr-10 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"
                                />

                                <div class="absolute right-3 flex items-center gap-2">
                                    <div v-if="isSearchingParent" class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                                    <button v-else-if="parentSearchQuery" @click="clearParentSelection" type="button" class="text-gray-400 hover:text-white text-xs p-1">
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Ketik nama untuk mencari leluhur (pencarian otomatis berjalan setelah 1.5 detik jeda mengetik, menampilkan maks 12 hasil).</p>
                            <div v-if="form.errors.parent_node_id" class="text-red-400 text-xs mt-1">{{ form.errors.parent_node_id }}</div>

                            <!-- Dropdown Search Results / Default 12 -->
                            <div v-if="showParentDropdown && parentSearchResults.length > 0"
                                 class="absolute top-full left-0 right-0 mt-2 bg-gray-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden z-50 max-h-64 overflow-y-auto divide-y divide-white/5">
                                
                                <div class="p-2.5 px-3 bg-white/5 text-gray-400 text-[11px] font-semibold uppercase tracking-wider flex items-center justify-between">
                                    <span>{{ parentSearchQuery.trim() ? 'Hasil Pencarian Leluhur' : '12 Leluhur Pertama (Pilih Salah Satu)' }}</span>
                                    <span v-if="!parentSearchQuery.trim()" class="text-[10px] text-gray-500 font-normal lowercase">atau ketik untuk cari</span>
                                </div>

                                <button
                                    v-for="node in parentSearchResults"
                                    :key="node.id"
                                    type="button"
                                    @mousedown.prevent="selectParent(node)"
                                    class="w-full flex items-center justify-between p-3 hover:bg-white/5 text-left transition-colors"
                                >
                                    <div>
                                        <div class="text-white font-medium text-sm">{{ node.name }}</div>
                                        <div class="text-gray-400 text-xs">{{ node.marga ? `Marga ${node.marga} · ` : '' }}{{ levelLabel(node.level) }}</div>
                                    </div>
                                    <div class="text-xs text-amber-400 font-semibold flex items-center gap-1">
                                        <span v-if="node.spouses?.length" class="text-pink-300">👩 {{ node.spouses.length }} Istri</span>
                                        <span v-else class="text-gray-500">Tanpa Istri</span>
                                        <span>➔</span>
                                    </div>
                                </button>
                            </div>

                            <!-- Empty result message ONLY when search query is NOT empty -->
                            <div v-if="showParentDropdown && parentSearchQuery.trim() && parentSearchResults.length === 0 && !isSearchingParent"
                                 class="absolute top-full left-0 right-0 mt-2 bg-gray-900 border border-white/10 rounded-2xl p-4 text-center text-gray-400 text-xs shadow-2xl z-50">
                                Tidak ada leluhur yang cocok dengan "{{ parentSearchQuery }}"
                            </div>
                        </div>

                        <!-- Selected Parent & Mother Selection (Poin 3) -->
                        <div v-if="selectedParent" class="space-y-4 pt-1">
                            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3.5 flex items-center justify-between">
                                <div>
                                    <div class="text-indigo-400 text-xs font-semibold uppercase tracking-wider">Ayah / Leluhur Terpilih:</div>
                                    <div class="text-white font-bold text-base mt-0.5">{{ selectedParent.name }} {{ selectedParent.marga ? `(Marga ${selectedParent.marga})` : '' }}</div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-semibold">
                                    {{ levelLabel(selectedParent.level) }}
                                </span>
                            </div>

                            <!-- Mother Selection: List of Parent's Wives -->
                            <div v-if="selectedParent.spouses && selectedParent.spouses.length > 0"
                                 class="bg-pink-950/30 border border-pink-500/30 rounded-2xl p-4.5 space-y-3">
                                <div class="flex items-center gap-2 text-pink-300 font-semibold text-sm">
                                    <span>👩</span>
                                    <span>Pilih Ibu Kandung Anda <span class="text-red-400">*</span></span>
                                </div>
                                <p class="text-gray-300 text-xs leading-relaxed">
                                    Pilih salah satu istri dari <strong>{{ selectedParent.name }}</strong> yang merupakan ibu kandung Anda:
                                </p>

                                <div class="space-y-2 pt-1">
                                    <label v-for="(spouse, idx) in selectedParent.spouses" :key="spouse.id"
                                           :class="['flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all', form.mother_spouse_id == spouse.id ? 'border-amber-500 bg-amber-500/15 shadow-md shadow-amber-500/10' : 'border-white/10 bg-gray-900/80 hover:border-white/20']">
                                        <input type="radio" :value="spouse.id" v-model="form.mother_spouse_id"
                                               class="text-amber-500 bg-gray-800 border-white/20 focus:ring-amber-500 w-4 h-4"/>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-pink-500/25 text-pink-300 font-semibold">
                                                    {{ getWifeLabel(idx) }}
                                                </span>
                                                <span class="text-white font-bold text-sm">{{ spouse.name }}</span>
                                            </div>
                                            <div v-if="spouse.marga" class="text-pink-300/80 text-xs mt-0.5">Marga {{ spouse.marga }}</div>
                                        </div>
                                    </label>
                                </div>
                                <div v-if="form.errors.mother_spouse_id" class="text-red-400 text-xs mt-1">{{ form.errors.mother_spouse_id }}</div>
                            </div>

                            <!-- Notice if Parent Has No Spouses -->
                            <div v-else class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-3">
                                <span class="text-amber-400 text-xl flex-shrink-0">⚠️</span>
                                <div class="space-y-1">
                                    <div class="text-amber-300 font-semibold text-sm">Data Istri Belum Tersedia di Silsilah</div>
                                    <p class="text-amber-200/80 text-xs leading-relaxed">
                                        Leluhur <strong>{{ selectedParent.name }}</strong> belum memiliki data istri yang terdaftar di silsilah. Harap perbarui data istri ayah terlebih dahulu sebelum mendaftarkan anak.
                                    </p>
                                    <div class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-400/90 bg-amber-500/10 px-2.5 py-1 rounded-md mt-1 border border-amber-500/20">
                                        <span>🔒</span>
                                        <span>Tombol "Lanjut" dinonaktifkan</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5">Nama Lengkap Anda <span class="text-red-400">*</span></label>
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
                                <label class="block text-gray-400 text-sm mb-1.5 flex items-center justify-between">
                                    <span>Tanggal / Tahun Lahir</span>
                                    <span class="text-[11px] text-amber-400/90 font-mono">MM/DD/YYYY</span>
                                </label>
                                <DatePicker v-model="form.tahun_lahir" placeholder="MM/DD/YYYY" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm mb-1.5 flex items-center justify-between">
                                <span>Tanggal / Tahun Wafat (jika sudah almarhum)</span>
                                <span class="text-[11px] text-gray-500 font-mono">MM/DD/YYYY</span>
                            </label>
                            <DatePicker v-model="form.tahun_wafat" placeholder="Kosongkan jika masih hidup" />
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

                        <!-- Multi-Spouse Section with Ordered Wife Badges (Poin 2) -->
                        <div v-if="form.gender === 'male'" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-pink-300 text-sm font-semibold flex items-center gap-1.5">
                                    <span>💑</span>
                                    <span>Data Istri Anda (opsional)</span>
                                </h3>
                                <button type="button" @click="addSpouse"
                                        class="text-xs px-3 py-1.5 bg-pink-500/15 hover:bg-pink-500/25 border border-pink-500/30 text-pink-300 rounded-lg transition-colors flex items-center gap-1 font-medium">
                                    <span>+ Tambah Istri</span>
                                </button>
                            </div>

                            <div v-for="(spouse, index) in form.spouses" :key="index"
                                 class="bg-pink-500/5 border border-pink-500/15 rounded-2xl p-4 space-y-3 relative">
                                <div class="flex items-center justify-between border-b border-pink-500/10 pb-2.5 mb-1">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-500/20 text-pink-300 text-xs font-bold">
                                        👰 {{ getWifeLabel(index) }}
                                    </span>
                                    <button v-if="form.spouses.length > 1" type="button" @click="removeSpouse(index)"
                                            class="text-gray-500 hover:text-red-400 text-xs p-1 transition-colors" title="Hapus Istri Ini">
                                        ✕ Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-gray-400 text-xs mb-1">Nama Istri</label>
                                        <input v-model="spouse.name" type="text" placeholder="Nama lengkap istri"
                                               class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 text-xs mb-1">Marga Istri</label>
                                        <input v-model="spouse.marga" type="text" placeholder="Contoh: Simanjuntak, Panjaitan"
                                               class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2.5 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-gray-400 text-xs mb-1 flex items-center justify-between">
                                            <span>Tanggal / Tahun Lahir Istri</span>
                                            <span class="text-[10px] text-pink-400/80 font-mono">MM/DD/YYYY</span>
                                        </label>
                                        <DatePicker v-model="spouse.tahun_lahir" placeholder="MM/DD/YYYY" />
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 text-xs mb-1 flex items-center justify-between">
                                            <span>Tanggal / Tahun Wafat Istri</span>
                                            <span class="text-[10px] text-gray-500 font-mono">MM/DD/YYYY (opsional)</span>
                                        </label>
                                        <DatePicker v-model="spouse.tahun_wafat" placeholder="Kosongkan jika hidup" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-gray-400 text-xs mb-1">Keterangan Istri (opsional)</label>
                                    <input v-model="spouse.deskripsi" type="text" placeholder="Keterangan tambahan"
                                           class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-xs placeholder-gray-600 focus:outline-none focus:border-pink-500/40 transition-colors"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Data Pemohon / Registrasi Anggota -->
                    <div v-if="step === 3" class="p-6 space-y-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-white font-semibold text-lg mb-1">Langkah 3: Data Pemohon & Keanggotaan</h2>
                                <p class="text-gray-400 text-xs">Pilih apakah data silsilah ini diajukan untuk orang lain atau untuk diri Anda sendiri.</p>
                            </div>
                        </div>

                        <!-- Toggle Tab: Untuk Orang Lain vs Untuk Diri Sendiri -->
                        <div class="grid grid-cols-2 gap-2 p-1 bg-gray-800/90 rounded-xl border border-white/10">
                            <button
                                type="button"
                                @click="form.is_self = false"
                                :class="[
                                    'py-3 px-3 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-2',
                                    !form.is_self
                                        ? 'bg-amber-500 text-gray-950 shadow-lg shadow-amber-500/20 font-bold'
                                        : 'text-gray-400 hover:text-white hover:bg-white/5'
                                ]"
                            >
                                <span class="text-base">👥</span>
                                <span>Untuk Orang Lain</span>
                            </button>
                            <button
                                type="button"
                                @click="form.is_self = true"
                                :class="[
                                    'py-3 px-3 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-2',
                                    form.is_self
                                        ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-gray-950 shadow-lg shadow-amber-500/20 font-bold'
                                        : 'text-gray-400 hover:text-white hover:bg-white/5'
                                ]"
                            >
                                <span class="text-base">👤</span>
                                <span>Untuk Diri Sendiri (Daftar Anggota)</span>
                            </button>
                        </div>

                        <!-- Global Validation Errors Alert -->
                        <div v-if="Object.keys(form.errors).length > 0" class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1.5 animate-pulse">
                            <div class="font-bold text-red-400 flex items-center gap-1.5 text-sm">
                                <span>⚠️</span>
                                <span>Mohon periksa data berikut:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-0.5 text-red-300/90 pl-1">
                                <li v-for="(msg, field) in form.errors" :key="field">
                                    {{ msg }}
                                </li>
                            </ul>
                        </div>

                        <!-- Summary -->
                        <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-2">
                            <h3 class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-2">Ringkasan Silsilah yang Diajukan</h3>
                            <div class="grid grid-cols-2 gap-2 text-xs sm:text-sm">
                                <div class="text-gray-400">Nama:</div><div class="text-white font-semibold">{{ form.name || '—' }}</div>
                                <div class="text-gray-400">Jenis Kelamin:</div><div class="text-white">{{ form.gender === 'male' ? '♂ Laki-laki' : '♀ Perempuan' }}</div>
                                <div class="text-gray-400">Marga:</div><div class="text-white">{{ form.marga || '—' }}</div>
                                <div class="text-gray-400">Ayah / Leluhur:</div><div class="text-white">{{ selectedParent?.name || '—' }}</div>
                                <div class="text-gray-400">Ibu Kandung:</div><div class="text-pink-300 font-medium">{{ selectedMother?.name ? `${selectedMother.name} (Marga ${selectedMother.marga || '-'})` : '—' }}</div>
                                <div v-if="form.gender === 'male' && form.spouses.some(s => s.name)" class="text-gray-400">Istri Terdaftar:</div>
                                <div v-if="form.gender === 'male' && form.spouses.some(s => s.name)" class="text-white">
                                    {{ form.spouses.filter(s => s.name).map((s, i) => `${getWifeLabel(i)}: ${s.name}`).join(', ') }}
                                </div>
                            </div>
                        </div>

                        <!-- Form MODE 1: Untuk Orang Lain -->
                        <div v-if="!form.is_self" class="space-y-4 pt-1">
                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Nama Pemohon <span class="text-red-400">*</span></label>
                                <input v-model="form.requester_name" type="text" placeholder="Nama lengkap Anda sebagai pemohon"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                <div v-if="form.errors.requester_name" class="text-red-400 text-xs mt-1">{{ form.errors.requester_name }}</div>
                            </div>

                            <div>
                                <label class="block text-gray-400 text-sm mb-1.5">Email Pemohon <span class="text-red-400">*</span></label>
                                <input v-model="form.requester_email" type="email" placeholder="email@contoh.com"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                <div v-if="form.errors.requester_email" class="text-red-400 text-xs mt-1">{{ form.errors.requester_email }}</div>
                                <p class="text-gray-500 text-xs mt-1">Konfirmasi dan notifikasi status pengajuan akan dikirimkan ke email ini.</p>
                            </div>
                        </div>

                        <!-- Form MODE 2: Untuk Diri Sendiri (Daftar Akun Anggota) -->
                        <div v-else class="space-y-4 pt-1">
                            <!-- Banner info pendaftar -->
                            <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3.5 flex items-center justify-between">
                                <div>
                                    <div class="text-amber-400 text-xs font-semibold uppercase tracking-wider">Nama Akun Anggota:</div>
                                    <div class="text-white font-bold text-base mt-0.5">{{ form.name || '(Isi Nama Anda di Langkah 1)' }}</div>
                                    <div v-if="form.marga" class="text-amber-200/80 text-xs">Marga {{ form.marga }}</div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-semibold">
                                    Calon Anggota
                                </span>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-gray-300 text-sm mb-1.5">Alamat Email <span class="text-red-400">*</span></label>
                                <input v-model="form.email" type="email" placeholder="email@contoh.com"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
                                <p class="text-gray-400 text-xs mt-1">Digunakan untuk login dan menerima link verifikasi email.</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-gray-300 text-sm mb-1.5">No. Handphone / WhatsApp (opsional)</label>
                                <input v-model="form.phone" type="tel" placeholder="08xxxxxxxxxx"
                                       class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                <div v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <!-- Password & Confirmation -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-300 text-sm mb-1.5">Kata Sandi <span class="text-red-400">*</span></label>
                                    <input v-model="form.password" type="password" placeholder="Minimal 8 karakter"
                                           class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                    <div v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
                                </div>
                                <div>
                                    <label class="block text-gray-300 text-sm mb-1.5">Konfirmasi Kata Sandi <span class="text-red-400">*</span></label>
                                    <input v-model="form.password_confirmation" type="password" placeholder="Ketik ulang kata sandi"
                                           class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-amber-500/50 transition-colors"/>
                                    <div v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="text-red-400 text-xs mt-1">
                                        Kata sandi tidak cocok.
                                    </div>
                                </div>
                            </div>

                            <!-- Notice: Alur Verifikasi Email & Admin -->
                            <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-xl p-4 flex items-start gap-3">
                                <span class="text-2xl flex-shrink-0">📧</span>
                                <div class="space-y-1">
                                    <div class="text-indigo-300 font-bold text-xs">Alur Pendaftaran Anggota:</div>
                                    <p class="text-gray-300 text-xs leading-relaxed">
                                        Setelah formulir dikirimkan, akun anggota Anda otomatis dibuat dan link verifikasi akan dikirim ke email Anda. Silakan verifikasi email Anda, kemudian Admin akan menyetujui penempatan data silsilah Anda.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="px-6 pb-6 flex gap-3">
                        <button v-if="step > 1" @click="prevStep" type="button"
                                class="flex-1 border border-white/10 hover:border-white/20 text-gray-400 hover:text-white rounded-xl py-3 text-sm font-medium transition-all">
                            ← Kembali
                        </button>
                        <button v-if="step === 1" @click="nextStep" type="button"
                                :disabled="isStep1Disabled"
                                :class="[
                                    'flex-1 font-semibold rounded-xl py-3 text-sm transition-all flex items-center justify-center gap-1.5',
                                    isStep1Disabled 
                                        ? 'bg-amber-500/20 text-gray-500 border border-amber-500/10 cursor-not-allowed' 
                                        : 'bg-amber-500 hover:bg-amber-400 text-gray-900 cursor-pointer shadow-lg shadow-amber-500/10'
                                ]">
                            Lanjut →
                        </button>
                        <button v-else-if="step < totalSteps" @click="nextStep" type="button"
                                class="flex-1 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-xl py-3 text-sm transition-all shadow-lg shadow-amber-500/10 cursor-pointer flex items-center justify-center gap-1.5">
                            Lanjut →
                        </button>
                        <button v-if="step === totalSteps" @click="submit" type="button"
                                :disabled="form.processing"
                                class="flex-1 font-semibold rounded-xl py-3 text-sm transition-all flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white shadow-lg shadow-emerald-500/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
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
