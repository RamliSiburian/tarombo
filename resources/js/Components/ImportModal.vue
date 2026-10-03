<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'imported']);

// ── State ────────────────────────────────────────────────────────────────────
const step = ref('upload');   // 'upload' | 'preview' | 'done'
const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const loading = ref(false);
const error = ref('');

const preview = ref(null);   // { total, to_insert[], skipped[], errors[] }
const importResult = ref(null); // { inserted, failed[] }

// ── Computed ─────────────────────────────────────────────────────────────────
const canConfirm = computed(() => preview.value?.to_insert?.length > 0);
const fileLabel = computed(() => {
    if (!selectedFile.value) return null;
    const kb = (selectedFile.value.size / 1024).toFixed(1);
    return `${selectedFile.value.name} (${kb} KB)`;
});

// ── Upload / Drag handlers ────────────────────────────────────────────────────
function onFilePick(e) {
    const f = e.target.files?.[0];
    if (f) {
        selectedFile.value = f;
        error.value = '';
    }
}

function onDrop(e) {
    isDragging.value = false;
    const f = e.dataTransfer.files?.[0];
    if (f) {
        const ext = f.name.split('.').pop().toLowerCase();
        if (!['csv', 'json'].includes(ext)) {
            error.value = 'Hanya file .csv atau .json yang diterima.';
            return;
        }
        selectedFile.value = f;
        error.value = '';
    }
}

// ── Step 1: Preview ───────────────────────────────────────────────────────────
async function doPreview() {
    if (!selectedFile.value) {
        error.value = 'Pilih file terlebih dahulu.';
        return;
    }

    loading.value = true;
    error.value = '';

    const form = new FormData();
    form.append('file', selectedFile.value);

    try {
        const res = await axios.post(route('admin.import.preview'), form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        preview.value = res.data;
        step.value = 'preview';
    } catch (e) {
        error.value = e.response?.data?.error
            || e.response?.data?.message
            || 'Terjadi kesalahan saat membaca file.';
    } finally {
        loading.value = false;
    }
}

// ── Step 2: Confirm Import ────────────────────────────────────────────────────
async function doConfirm() {
    if (!canConfirm.value) return;
    loading.value = true;
    error.value = '';

    try {
        const res = await axios.post(route('admin.import.confirm'), {
            rows: preview.value.to_insert,
        });
        importResult.value = res.data;
        step.value = 'done';
        emit('imported');
    } catch (e) {
        error.value = e.response?.data?.message || 'Gagal menyimpan data import.';
    } finally {
        loading.value = false;
    }
}

// ── Reset ─────────────────────────────────────────────────────────────────────
function reset() {
    step.value = 'upload';
    selectedFile.value = null;
    preview.value = null;
    importResult.value = null;
    error.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function close() {
    reset();
    emit('close');
}
</script>

<template>
    <!-- Backdrop -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl max-h-[92vh] flex flex-col bg-gray-900 border border-white/10 rounded-2xl shadow-2xl text-gray-100 overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-gray-950/60 shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📂</span>
                    <h3 class="text-lg font-bold text-white">Import Data Silsilah</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full border font-medium"
                          :class="step === 'upload' ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-300'
                               : step === 'preview' ? 'bg-amber-500/10 border-amber-500/30 text-amber-300'
                               : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'">
                        {{ step === 'upload' ? 'Langkah 1 · Upload File' : step === 'preview' ? 'Langkah 2 · Preview' : 'Selesai ✓' }}
                    </span>
                </div>
                <button @click="close" class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body (scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                <!-- ── STEP: UPLOAD ─────────────────────────────────────── -->
                <template v-if="step === 'upload'">

                    <!-- Format Info -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/20">
                            <div class="text-xs font-semibold text-emerald-400 mb-1">📄 CSV Format</div>
                            <p class="text-xs text-gray-400">File .csv dengan header kolom sesuai template. Cocok untuk import dari Excel.</p>
                        </div>
                        <div class="p-3 rounded-xl bg-blue-500/5 border border-blue-500/20">
                            <div class="text-xs font-semibold text-blue-400 mb-1">🔵 JSON Format</div>
                            <p class="text-xs text-gray-400">Array JSON flat dengan field yang sama seperti CSV. Cocok untuk backup/restore.</p>
                        </div>
                    </div>

                    <!-- Template Download -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/3 border border-white/8">
                        <div>
                            <div class="text-sm font-medium text-white">📋 Template CSV</div>
                            <div class="text-xs text-gray-500 mt-0.5">Download template siap pakai dengan contoh data dan semua kolom yang diperlukan</div>
                        </div>
                        <a :href="route('admin.import.template')"
                           class="shrink-0 text-xs bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-3 py-1.5 rounded-lg transition-colors">
                            ⬇ Download
                        </a>
                    </div>

                    <!-- Kolom yang dipakai -->
                    <div class="p-3 rounded-xl bg-gray-800/60 border border-white/5 text-xs text-gray-400">
                        <p class="font-semibold text-gray-300 mb-2">Kolom yang Didukung:</p>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                            <div><span class="text-amber-400 font-mono">name</span> — Nama tokoh <span class="text-red-400">*wajib</span></div>
                            <div><span class="text-amber-400 font-mono">gender</span> — male / female <span class="text-red-400">*wajib</span></div>
                            <div><span class="text-amber-400 font-mono">parent_name</span> — Nama ayah (kosong = root)</div>
                            <div><span class="text-amber-400 font-mono">marga</span> — Nama marga</div>
                            <div><span class="text-amber-400 font-mono">asal_daerah</span> — Asal daerah</div>
                            <div><span class="text-amber-400 font-mono">tahun_lahir</span> — Tahun lahir</div>
                            <div><span class="text-amber-400 font-mono">tahun_wafat</span> — Tahun wafat</div>
                            <div><span class="text-amber-400 font-mono">deskripsi</span> — Keterangan</div>
                            <div><span class="text-amber-400 font-mono">spouse_names</span> — Nama istri (pisah |)</div>
                            <div><span class="text-amber-400 font-mono">spouse_margas</span> — Marga istri (pisah |)</div>
                        </div>
                    </div>

                    <!-- Drop zone -->
                    <div class="relative"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="onDrop">
                        <div :class="['border-2 border-dashed rounded-xl px-6 py-10 text-center transition-all cursor-pointer',
                             isDragging ? 'border-indigo-400 bg-indigo-500/10' : 'border-white/15 bg-white/2 hover:border-white/25 hover:bg-white/4']"
                             @click="fileInput.click()">
                            <div class="text-3xl mb-2">📁</div>
                            <div class="text-sm font-medium text-gray-200 mb-1">
                                {{ selectedFile ? fileLabel : 'Drag & Drop file CSV / JSON di sini' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ selectedFile ? 'Klik untuk ganti file' : 'atau klik untuk memilih file · Maks. 5 MB' }}
                            </div>
                            <input ref="fileInput" type="file" accept=".csv,.json" class="hidden" @change="onFilePick" />
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                        ⚠️ {{ error }}
                    </div>
                </template>

                <!-- ── STEP: PREVIEW ────────────────────────────────────── -->
                <template v-else-if="step === 'preview' && preview">

                    <!-- Stats Summary Cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-gray-800/60 border border-white/5 text-center">
                            <div class="text-2xl font-bold text-white">{{ preview.total }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Total Baris</div>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-center">
                            <div class="text-2xl font-bold text-emerald-400">{{ preview.to_insert.length }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Siap Diimport</div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center">
                            <div class="text-2xl font-bold text-amber-400">{{ preview.skipped.length }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Dilewati (Duplikat)</div>
                        </div>
                    </div>

                    <div v-if="preview.errors.length > 0"
                         class="p-3 rounded-xl bg-red-500/10 border border-red-500/20">
                        <div class="text-xs font-semibold text-red-400 mb-2">❌ {{ preview.errors.length }} Baris Error (tidak akan diimport):</div>
                        <div v-for="e in preview.errors" :key="e.row"
                             class="text-xs text-red-300 py-1 border-t border-red-500/10 first:border-0">
                            <span class="font-mono text-gray-500">Baris {{ e.row }}:</span> {{ e.message }}
                        </div>
                    </div>

                    <!-- Nodes to be imported -->
                    <div v-if="preview.to_insert.length > 0">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                            ✅ Node yang akan diimport (status: Pending)
                        </div>
                        <div class="rounded-xl border border-white/8 overflow-hidden">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-white/8 bg-gray-800/60">
                                        <th class="text-left text-gray-500 px-3 py-2">Nama</th>
                                        <th class="text-left text-gray-500 px-3 py-2 hidden sm:table-cell">Marga</th>
                                        <th class="text-left text-gray-500 px-3 py-2 hidden sm:table-cell">Parent</th>
                                        <th class="text-center text-gray-500 px-3 py-2">Gen</th>
                                        <th class="text-center text-gray-500 px-3 py-2">Jenis</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="n in preview.to_insert" :key="n.row"
                                        class="hover:bg-white/2 transition-colors">
                                        <td class="px-3 py-2">
                                            <span class="font-semibold text-white">{{ n.name }}</span>
                                            <span v-if="n.spouse_names" class="ml-1 text-pink-400">💍</span>
                                        </td>
                                        <td class="px-3 py-2 hidden sm:table-cell text-gray-400">{{ n.marga || '—' }}</td>
                                        <td class="px-3 py-2 hidden sm:table-cell text-gray-400">{{ n.parent_name }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="bg-indigo-500/15 text-indigo-400 border border-indigo-500/20 px-1.5 py-0.5 rounded-full">
                                                Gen {{ (n.level || 0) + 1 }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ n.gender === 'female' ? '👩' : '👨' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Skipped -->
                    <div v-if="preview.skipped.length > 0" class="p-3 rounded-xl bg-amber-500/5 border border-amber-500/20">
                        <div class="text-xs font-semibold text-amber-400 mb-2">⏭ {{ preview.skipped.length }} Baris Dilewati (Duplikat):</div>
                        <div v-for="s in preview.skipped" :key="s.row"
                             class="text-xs text-gray-400 py-0.5">
                            <span class="font-mono text-gray-600">Baris {{ s.row }}:</span> {{ s.reason }}
                        </div>
                    </div>

                    <!-- Pending note -->
                    <div class="p-3 rounded-xl bg-indigo-500/5 border border-indigo-500/20 text-xs text-indigo-300">
                        ℹ️ Semua node yang diimport akan berstatus <strong>Pending</strong> dan tidak tampil di pohon publik hingga ACC oleh admin.
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                        ⚠️ {{ error }}
                    </div>
                </template>

                <!-- ── STEP: DONE ───────────────────────────────────────── -->
                <template v-else-if="step === 'done' && importResult">
                    <div class="text-center py-8">
                        <div class="text-5xl mb-4">🎉</div>
                        <h4 class="text-xl font-bold text-white mb-2">Import Selesai!</h4>
                        <p class="text-emerald-400 font-semibold text-lg">{{ importResult.inserted }} node berhasil diimport</p>
                        <p class="text-gray-400 text-sm mt-2">Semua node kini berstatus <strong class="text-amber-400">Pending</strong> — silakan ACC di halaman Kelola Node.</p>

                        <div v-if="importResult.failed?.length > 0"
                             class="mt-6 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-left">
                            <div class="text-xs font-semibold text-red-400 mb-2">⚠️ {{ importResult.failed.length }} Gagal:</div>
                            <div v-for="f in importResult.failed" :key="f.name" class="text-xs text-red-300">
                                {{ f.name }}: {{ f.message }}
                            </div>
                        </div>
                    </div>
                </template>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-white/10 bg-gray-950/50 shrink-0">
                <div>
                    <button v-if="step !== 'upload'" @click="step === 'done' ? reset() : (step = 'upload')"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-xl transition-colors">
                        {{ step === 'done' ? '↩ Import Lagi' : '← Kembali' }}
                    </button>
                </div>
                <div class="flex gap-3">
                    <button @click="close" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-xl transition-colors">
                        {{ step === 'done' ? 'Tutup' : 'Batal' }}
                    </button>
                    <button v-if="step === 'upload'" @click="doPreview" :disabled="!selectedFile || loading" type="button"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-xl shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="loading ? 'bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-600/30'">
                        <svg v-if="loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span>{{ loading ? 'Membaca...' : '🔍 Analisis File' }}</span>
                    </button>
                    <button v-else-if="step === 'preview'" @click="doConfirm"
                            :disabled="!canConfirm || loading" type="button"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-xl shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="loading ? 'bg-emerald-700' : 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30'">
                        <svg v-if="loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span>{{ loading ? 'Menyimpan...' : `✅ Import ${preview?.to_insert?.length || 0} Node` }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
