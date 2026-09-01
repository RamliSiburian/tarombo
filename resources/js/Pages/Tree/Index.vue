<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TreeCanvas from '@/Components/TreeCanvas.vue';
import NodeDetail from '@/Components/NodeDetail.vue';
import SearchBar from '@/Components/SearchBar.vue';
import ExportModal from '@/Components/ExportModal.vue';

const props = defineProps({
    tree: Object,
    stats: Object,
});

const selectedNode = ref(null);
const searchHighlightId = ref(null);
const treeRef = ref(null);
const panelOpen = ref(false);
const showExportModal = ref(false);

const svgElement = computed(() => treeRef.value?.getSvgElement());
const ancestorIds = computed(() => treeRef.value?.getHighlightedIds());

const onNodeClick = (node) => {
    selectedNode.value = node;
    panelOpen.value = !!node;
};

const onSearch = (node) => {
    if (node) {
        searchHighlightId.value = node.id;
    } else {
        searchHighlightId.value = null;
    }
};

const closePanel = () => {
    panelOpen.value = false;
    selectedNode.value = null;
    searchHighlightId.value = null;
    treeRef.value?.resetView();
};
</script>

<template>
    <Head title="Pohon Silsilah Batak" />
    <AppLayout>
        <!-- Hero Section -->
        <div class="pt-16 relative">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/50 via-gray-950 to-gray-950 pointer-events-none"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-900/20 via-transparent to-transparent pointer-events-none"></div>

            <!-- Stats Bar -->
            <div class="relative z-10 max-w-7xl mx-auto px-4 pt-10 pb-6">
                <div class="text-center mb-8">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 tracking-tight">
                        🌳 Tarombo <span class="text-amber-400">Batak</span>
                    </h1>
                    <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                        Pohon silsilah leluhur orang Batak dari Si Raja Batak hingga generasi kini
                    </p>
                </div>

                <!-- Stats -->
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3">
                        <span class="text-2xl">👥</span>
                        <div>
                            <div class="text-white font-bold text-xl">{{ stats.total_nodes }}</div>
                            <div class="text-gray-500 text-xs">Total Node</div>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3">
                        <span class="text-2xl">🏷️</span>
                        <div>
                            <div class="text-white font-bold text-xl">{{ stats.total_marga }}</div>
                            <div class="text-gray-500 text-xs">Total Marga</div>
                        </div>
                    </div>
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl px-6 py-3 flex items-center gap-3">
                        <span class="text-2xl">📝</span>
                        <div>
                            <div class="text-amber-400 font-bold text-sm">Belum ada di silsilah?</div>
                            <a :href="route('request.create')" class="text-amber-500 text-xs hover:text-amber-300 underline">Daftar sekarang →</a>
                        </div>
                    </div>
                </div>

                <!-- Search & Export Bar -->
                <div class="flex items-center justify-center gap-3 max-w-lg mx-auto">
                    <div class="flex-1">
                        <SearchBar @select="onSearch" />
                    </div>
                    <button @click="showExportModal = true"
                            type="button"
                            class="h-11 px-4 bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-500/40 text-indigo-200 hover:text-white rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-lg backdrop-blur-sm whitespace-nowrap">
                        <span>📥 Export Silsilah</span>
                    </button>
                </div>
            </div>

            <!-- Tree Canvas Area -->
            <div class="relative" style="height: calc(100vh - 320px); min-height: 500px;">
                <div class="absolute inset-0 mx-4 mb-4 rounded-2xl overflow-hidden border border-white/5 bg-gray-900/30 backdrop-blur-sm"
                     :class="panelOpen ? 'mr-[360px] md:mr-[380px]' : ''">
                    <TreeCanvas
                        ref="treeRef"
                        :tree="tree"
                        :search-highlight-id="searchHighlightId"
                        @node-click="onNodeClick"
                    />
                </div>

                <!-- Side Panel -->
                <transition
                    enter-active-class="transition-all duration-300 ease-out"
                    leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="translate-x-full opacity-0"
                    leave-to-class="translate-x-full opacity-0"
                >
                    <div v-if="panelOpen"
                         class="absolute right-4 top-0 bottom-4 w-[340px] md:w-[360px] bg-gray-900/95 backdrop-blur-md border border-white/10 rounded-2xl overflow-hidden shadow-2xl z-20">
                        <NodeDetail :node="selectedNode" @close="closePanel" @export-lineage="showExportModal = true" />
                    </div>
                </transition>
            </div>

            <!-- Export Modal -->
            <ExportModal
                :show="showExportModal"
                :tree="tree"
                :selected-node="selectedNode"
                :ancestor-ids="ancestorIds"
                :svg-element="svgElement"
                @close="showExportModal = false"
            />

            <!-- Empty State -->
            <div v-if="!tree" class="flex flex-col items-center justify-center py-24 text-center px-4">
                <div class="text-6xl mb-4">🌱</div>
                <h2 class="text-2xl font-bold text-white mb-2">Pohon Silsilah Belum Tersedia</h2>
                <p class="text-gray-500 max-w-md">Belum ada data silsilah yang tersedia. Silakan hubungi administrator.</p>
            </div>
        </div>
    </AppLayout>
</template>
