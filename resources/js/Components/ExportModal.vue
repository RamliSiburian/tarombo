<script setup>
import { ref, computed } from 'vue';
import {
    exportTreeAsPNG,
    exportTreeAsSVG,
    exportTreeAsCSV,
    exportTreeAsPrintPDF
} from '@/Utils/treeExport';

const props = defineProps({
    show: { type: Boolean, default: false },
    tree: { type: Object, default: null },
    selectedNode: { type: Object, default: null },
    ancestorIds: { type: [Set, Array], default: null },
    svgElement: { type: Object, default: null }
});

const emit = defineEmits(['close']);

const exportCategory = ref('visual'); // 'visual' | 'table'
const exportFormat = ref('PNG'); // 'PNG' | 'SVG' | 'CSV' | 'PDF_TABLE'

const ancestorSet = computed(() => {
    if (!props.ancestorIds) return null;
    if (props.ancestorIds instanceof Set) return props.ancestorIds;
    return new Set(props.ancestorIds);
});

function setCategory(cat) {
    exportCategory.value = cat;
    if (cat === 'visual') {
        exportFormat.value = 'PNG';
    } else {
        exportFormat.value = 'CSV';
    }
}

function handleExport() {
    const sanitizeName = (str) => (str || 'silsilah').toLowerCase().replace(/[^a-z0-9]/g, '-');
    const prefix = props.selectedNode 
        ? `tarombo-leluhur-${sanitizeName(props.selectedNode.name)}`
        : 'tarombo-seluruh-silsilah';

    if (exportFormat.value === 'PNG') {
        exportTreeAsPNG(props.svgElement, `${prefix}.png`);
    } else if (exportFormat.value === 'SVG') {
        exportTreeAsSVG(props.svgElement, `${prefix}.svg`);
    } else if (exportFormat.value === 'CSV') {
        exportTreeAsCSV(props.tree, ancestorSet.value, `${prefix}.csv`);
    } else if (exportFormat.value === 'PDF_TABLE') {
        exportTreeAsPrintPDF(props.tree, props.selectedNode, ancestorSet.value, 'Tarombo Silsilah Batak');
    }

    emit('close');
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-fade-in">
        <div class="relative w-full max-w-lg bg-gray-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden text-gray-100">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-gray-950/50">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📥</span>
                    <h3 class="text-lg font-bold text-white">Export Pohon Silsilah</h3>
                </div>
                <button @click="emit('close')" class="text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Scope Card -->
                <div class="p-4 rounded-xl border transition-all"
                     :class="selectedNode ? 'bg-amber-500/10 border-amber-500/30 text-amber-200' : 'bg-indigo-500/10 border-indigo-500/30 text-indigo-200'">
                    <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                        <span>{{ selectedNode ? '🎯 Scope: Garis Leluhur ke Atas' : '🌐 Scope: Seluruh Data Silsilah' }}</span>
                    </div>
                    <p class="text-xs text-gray-300">
                        <template v-if="selectedNode">
                            Mengexport khusus garis keturunan/leluhur dari <strong class="text-white underline">{{ selectedNode.name }}</strong> naik ke atas sampai Root Node.
                        </template>
                        <template v-else>
                            Tidak ada node yang dipilih. Mengexport <strong>semua data node</strong> yang ada di pohon silsilah.
                        </template>
                    </p>
                </div>

                <!-- Export Category Selection -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Pilih Tipe Export</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="setCategory('visual')"
                                type="button"
                                class="p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all"
                                :class="exportCategory === 'visual' ? 'bg-indigo-600/20 border-indigo-500 ring-2 ring-indigo-500/50' : 'bg-gray-800/50 border-white/10 hover:border-white/20'">
                            <span class="text-2xl">🌳</span>
                            <div>
                                <div class="font-bold text-sm text-white">Pohon Silsilah</div>
                                <div class="text-xs text-gray-400 mt-0.5">Visual Diagram / Gambar</div>
                            </div>
                        </button>

                        <button @click="setCategory('table')"
                                type="button"
                                class="p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all"
                                :class="exportCategory === 'table' ? 'bg-indigo-600/20 border-indigo-500 ring-2 ring-indigo-500/50' : 'bg-gray-800/50 border-white/10 hover:border-white/20'">
                            <span class="text-2xl">📊</span>
                            <div>
                                <div class="font-bold text-sm text-white">Data Table</div>
                                <div class="text-xs text-gray-400 mt-0.5">Tabel Excel / CSV / PDF</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Format Selection Options -->
                <div v-if="exportCategory === 'visual'">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Format File Gambar / Diagram</label>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-gray-800/40 hover:bg-gray-800 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="exportFormat" value="PNG" class="text-indigo-600 focus:ring-indigo-500 bg-gray-900 border-white/20">
                                <div>
                                    <div class="text-sm font-semibold text-white">PNG (Gambar Canvas HD)</div>
                                    <div class="text-xs text-gray-400">Cocok untuk dibagikan di media sosial / HP</div>
                                </div>
                            </div>
                            <span class="text-xs bg-gray-700 text-gray-300 font-mono px-2 py-0.5 rounded">.png</span>
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-gray-800/40 hover:bg-gray-800 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="exportFormat" value="SVG" class="text-indigo-600 focus:ring-indigo-500 bg-gray-900 border-white/20">
                                <div>
                                    <div class="text-sm font-semibold text-white">SVG (Grafik Vektor)</div>
                                    <div class="text-xs text-gray-400">Format vektor resolusi tinggi tanpa pecah saat zoom</div>
                                </div>
                            </div>
                            <span class="text-xs bg-gray-700 text-gray-300 font-mono px-2 py-0.5 rounded">.svg</span>
                        </label>
                    </div>
                </div>

                <div v-else-if="exportCategory === 'table'">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Format Data & Laporan</label>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-gray-800/40 hover:bg-gray-800 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="exportFormat" value="CSV" class="text-indigo-600 focus:ring-indigo-500 bg-gray-900 border-white/20">
                                <div>
                                    <div class="text-sm font-semibold text-white">Excel / CSV (.csv)</div>
                                    <div class="text-xs text-gray-400">Tabel data mentah terstruktur untuk diolah di Excel</div>
                                </div>
                            </div>
                            <span class="text-xs bg-emerald-950 text-emerald-300 font-mono px-2 py-0.5 rounded border border-emerald-500/30">.csv</span>
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-gray-800/40 hover:bg-gray-800 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="exportFormat" value="PDF_TABLE" class="text-indigo-600 focus:ring-indigo-500 bg-gray-900 border-white/20">
                                <div>
                                    <div class="text-sm font-semibold text-white">Cetak / Laporan PDF</div>
                                    <div class="text-xs text-gray-400">Tampilan cetak dokumen rapi lengkap dengan header Tarombo</div>
                                </div>
                            </div>
                            <span class="text-xs bg-rose-950 text-rose-300 font-mono px-2 py-0.5 rounded border border-rose-500/30">.pdf</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/10 bg-gray-950/50">
                <button @click="emit('close')"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-xl transition-colors">
                    Batal
                </button>
                <button @click="handleExport"
                        type="button"
                        class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/30 transition-colors flex items-center gap-2">
                    <span>📥 Download File</span>
                </button>
            </div>
        </div>
    </div>
</template>
