<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    nodes: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

const doSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.nodes.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 400);
};

const deleteNode = (node) => {
    if (confirm(`Hapus node "${node.name}"? Semua keturunannya juga akan terhapus!`)) {
        router.delete(route('admin.nodes.destroy', node.id));
    }
};

const genderIcon = (g) => g === 'female' ? '👩' : '👨';
const levelBadge = (l) => ['Gen 1', 'Gen 2', 'Gen 3', 'Gen 4', 'Gen 5', 'Gen 6'][l] || `Gen ${l + 1}`;
</script>

<template>
    <Head title="Kelola Node" />
    <AdminLayout>
        <template #title>Kelola Node Silsilah</template>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex-1 max-w-sm">
                <div class="flex items-center gap-2 bg-gray-900 border border-white/10 rounded-xl px-3 py-2.5">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" @input="doSearch" type="text" placeholder="Cari nama atau marga..."
                           class="flex-1 bg-transparent text-white text-sm placeholder-gray-600 outline-none"/>
                </div>
            </div>
            <Link :href="route('admin.nodes.create')"
                  class="bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold text-sm px-4 py-2.5 rounded-xl transition-colors flex items-center gap-2 whitespace-nowrap">
                ➕ Tambah Node
            </Link>
        </div>

        <!-- Table -->
        <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs text-gray-500 font-medium px-5 py-3 uppercase tracking-wider">Nama</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden md:table-cell">Marga</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden lg:table-cell">Parent</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Level</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Istri</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="nodes.data.length === 0">
                        <td colspan="6" class="py-12 text-center text-gray-600">Tidak ada node ditemukan</td>
                    </tr>
                    <tr v-for="node in nodes.data" :key="node.id" class="hover:bg-white/2 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ genderIcon(node.gender) }}</span>
                                <div>
                                    <div class="text-white text-sm font-medium">{{ node.name }}</div>
                                    <div class="text-gray-600 text-xs">{{ node.asal_daerah || '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="text-gray-400 text-sm">{{ node.marga || '—' }}</span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="text-gray-400 text-sm">{{ node.parent?.name || '— (Root)' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-0.5 rounded-full">
                                    {{ levelBadge(node.level) }}
                                </span>
                                <span v-if="node.sort_order" class="text-xs bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full" title="Posisi/Urutan Anak">
                                    Anak ke-{{ node.sort_order }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="node.spouses?.length > 0" class="text-xs text-pink-400">
                                {{ node.spouses.length }} istri
                            </span>
                            <span v-else class="text-gray-700 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <Link :href="route('admin.nodes.edit', node.id)"
                                      class="text-xs bg-white/5 hover:bg-white/10 text-gray-300 px-3 py-1.5 rounded-lg transition-colors">
                                    Edit
                                </Link>
                                <button @click="deleteNode(node)"
                                        class="text-xs bg-red-500/10 hover:bg-red-500/20 text-red-400 px-3 py-1.5 rounded-lg transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="nodes.total > 0" class="px-5 py-4 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-gray-400">
                    Menampilkan <span class="font-medium text-white">{{ nodes.from || 0 }}</span> - <span class="font-medium text-white">{{ nodes.to || 0 }}</span> dari <span class="font-medium text-white">{{ nodes.total }}</span> node (10 per halaman)
                </div>
                <div v-if="nodes.links?.length > 3" class="flex items-center gap-1">
                    <template v-for="(link, key) in nodes.links" :key="key">
                        <Link v-if="link.url" :href="link.url"
                              :class="['px-3 py-1.5 rounded-lg text-xs transition-colors', link.active ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/5']"
                              v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-xs text-gray-700" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
