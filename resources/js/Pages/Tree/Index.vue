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
    <AppLayout :stats="stats">
        <!-- Search Bar Slot placed in Navbar beside Logo (Nomor 1) -->
        <template #search>
            <SearchBar @select="onSearch" />
        </template>

        <div class="pt-16 h-screen w-full relative overflow-hidden bg-gray-950 flex flex-col">
            <!-- Background glow -->
            <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 via-gray-950 to-gray-950 pointer-events-none"></div>

            <!-- Tree Canvas Area (Completely Clean & Full Screen) -->
            <div class="w-full flex-1 relative overflow-hidden">
                <div class="w-full h-full transition-all duration-300"
                     :class="panelOpen ? 'mr-0 md:mr-[360px] lg:mr-[380px]' : ''">
                    <TreeCanvas
                        ref="treeRef"
                        :tree="tree"
                        :search-highlight-id="searchHighlightId"
                        @node-click="onNodeClick"
                        @export="showExportModal = true"
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
                         class="absolute right-4 top-4 bottom-4 w-[340px] md:w-[360px] bg-gray-900/95 backdrop-blur-md border border-white/10 rounded-2xl overflow-hidden shadow-2xl z-30">
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
            <div v-if="!tree" class="absolute inset-0 flex flex-col items-center justify-center py-24 text-center px-4 z-10 pointer-events-none">
                <div class="text-6xl mb-4">🌱</div>
                <h2 class="text-2xl font-bold text-white mb-2">Pohon Silsilah Belum Tersedia</h2>
                <p class="text-gray-500 max-w-md">Belum ada data silsilah yang tersedia. Silakan hubungi administrator.</p>
            </div>
        </div>
    </AppLayout>
</template>
