<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    articles: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.q || '');
const currentCategory = ref(props.filters?.category || '');

const filterCategory = (cat) => {
    currentCategory.value = cat;
    router.get(route('admin.articles.index'), {
        category: cat || undefined,
        q: searchQuery.value || undefined,
    }, { preserveState: true, replace: true });
};

const handleSearch = () => {
    router.get(route('admin.articles.index'), {
        category: currentCategory.value || undefined,
        q: searchQuery.value || undefined,
    }, { preserveState: true, replace: true });
};

const deleteArticle = (article) => {
    if (confirm(`Yakin ingin menghapus "${article.title}"?`)) {
        router.delete(route('admin.articles.destroy', article.id));
    }
};

const categoryBadge = (cat) => {
    const map = {
        kegiatan: { label: 'Kegiatan', class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' },
        berita:   { label: 'Berita', class: 'bg-blue-500/15 text-blue-400 border-blue-500/30' },
        sejarah:  { label: 'Sejarah', class: 'bg-primary-500/15 text-primary-400 border-primary-500/30' },
        budaya:   { label: 'Budaya', class: 'bg-pink-500/15 text-pink-400 border-pink-500/30' },
    };
    return map[cat] || { label: cat, class: 'bg-gray-500/15 text-gray-400 border-gray-500/30' };
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Kelola Berita & Kegiatan — Admin" />
    <AdminLayout>
        <template #title>Berita & Kegiatan</template>

        <!-- Top Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-white">Daftar Berita & Kegiatan Komunitas</h2>
                <p class="text-xs text-gray-400 mt-0.5">Kelola agenda kegiatan bona taon, pesta marga, sejarah, dan warta budaya Batak.</p>
            </div>
            <Link
                :href="route('admin.articles.create')"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-400 hover:to-primary-500 text-gray-950 font-bold text-xs sm:text-sm transition-all shadow-lg shadow-primary-500/20 flex items-center justify-center gap-2"
            >
                <span>➕</span>
                <span>Tambah Kegiatan / Berita</span>
            </Link>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 mb-6">
            <!-- Category Tabs -->
            <div class="flex flex-wrap gap-1 bg-gray-900 border border-white/5 p-1 rounded-xl">
                <button
                    type="button"
                    @click="filterCategory('')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', !currentCategory ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white']"
                >
                    Semua
                </button>
                <button
                    type="button"
                    @click="filterCategory('kegiatan')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', currentCategory === 'kegiatan' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white']"
                >
                    🎉 Kegiatan
                </button>
                <button
                    type="button"
                    @click="filterCategory('berita')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', currentCategory === 'berita' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white']"
                >
                    📰 Berita
                </button>
                <button
                    type="button"
                    @click="filterCategory('sejarah')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', currentCategory === 'sejarah' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white']"
                >
                    📜 Sejarah
                </button>
                <button
                    type="button"
                    @click="filterCategory('budaya')"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors', currentCategory === 'budaya' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white']"
                >
                    🏺 Budaya
                </button>
            </div>

            <!-- Search -->
            <form @submit.prevent="handleSearch" class="flex gap-2">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari judul..."
                    class="bg-gray-900 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-primary-500/50"
                />
                <button type="submit" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-xs font-medium border border-white/10">
                    Cari
                </button>
            </form>
        </div>

        <!-- Article List Grid -->
        <div v-if="articles.data.length === 0" class="text-center py-16 bg-gray-900 border border-white/5 rounded-2xl">
            <div class="text-4xl mb-3">📰</div>
            <p class="text-gray-400 text-sm">Belum ada data kegiatan atau berita</p>
            <Link :href="route('admin.articles.create')" class="inline-block mt-3 text-xs text-primary-400 hover:underline">
                Tambah kegiatan pertama →
            </Link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
                v-for="art in articles.data"
                :key="art.id"
                class="bg-gray-900 border border-white/5 hover:border-white/15 rounded-2xl overflow-hidden transition-all flex flex-col justify-between shadow-xl"
            >
                <div>
                    <!-- Image cover -->
                    <div class="h-44 bg-gray-800 relative overflow-hidden flex items-center justify-center">
                        <img
                            v-if="art.image"
                            :src="'/storage/' + art.image"
                            :alt="art.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="text-5xl opacity-40">
                            {{ art.category === 'kegiatan' ? '🎉' : art.category === 'berita' ? '📰' : '📜' }}
                        </div>

                        <!-- Badge Category -->
                        <div class="absolute top-3 left-3">
                            <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border backdrop-blur-md', categoryBadge(art.category).class]">
                                {{ categoryBadge(art.category).label }}
                            </span>
                        </div>

                        <!-- Published Status -->
                        <div class="absolute top-3 right-3">
                            <span v-if="art.is_published" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/80 text-white backdrop-blur-md">
                                Tayang
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-700/80 text-gray-300 backdrop-blur-md">
                                Draft
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5">
                        <div v-if="art.event_date" class="text-xs text-primary-400 font-semibold mb-1 flex items-center gap-1.5">
                            <span>📅 Tanggal Acara:</span>
                            <span>{{ formatDate(art.event_date) }}</span>
                        </div>

                        <h3 class="text-base font-bold text-white mb-2 line-clamp-2">{{ art.title }}</h3>
                        <p class="text-gray-400 text-xs line-clamp-3 leading-relaxed mb-4">
                            {{ art.excerpt || art.content }}
                        </p>
                    </div>
                </div>

                <!-- Footer / Action Buttons -->
                <div class="p-4 bg-white/3 border-t border-white/5 flex items-center justify-between text-xs">
                    <span class="text-gray-500">{{ formatDate(art.created_at) }}</span>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('admin.articles.edit', art.id)"
                            class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 border border-white/10 transition-colors"
                        >
                            ✏️ Edit
                        </Link>
                        <button
                            type="button"
                            @click="deleteArticle(art)"
                            class="px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 transition-colors"
                        >
                            🗑 Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="articles.links?.length > 3" class="flex items-center gap-1 mt-8">
            <template v-for="link in articles.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors',
                        link.active
                            ? 'bg-primary-500 text-gray-900 border-primary-500 font-bold'
                            : 'bg-gray-900 text-gray-400 border-white/10 hover:border-white/20 hover:text-white'
                    ]"
                />
            </template>
        </div>
    </AdminLayout>
</template>
