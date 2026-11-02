<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    articles: Object,
    filters: Object,
    counts: Object,
});

const searchQuery = ref(props.filters?.q || '');
const activeCategory = ref(props.filters?.category || null);

const categoryMeta = {
    all:      { label: 'Semua Warta', icon: '📋', color: 'from-amber-500/20 to-yellow-500/10', border: 'border-amber-500/30' },
    kegiatan: { label: 'Kegiatan & Acara', icon: '🎉', color: 'from-emerald-500/20 to-teal-500/10', border: 'border-emerald-500/30' },
    berita:   { label: 'Berita & Pengumuman', icon: '📰', color: 'from-blue-500/20 to-indigo-500/10', border: 'border-blue-500/30' },
    sejarah:  { label: 'Sejarah & Tarombo', icon: '📜', color: 'from-amber-500/20 to-orange-500/10', border: 'border-amber-500/30' },
    budaya:   { label: 'Adat & Budaya', icon: '🏺', color: 'from-pink-500/20 to-rose-500/10', border: 'border-pink-500/30' },
};

const filterCategory = (cat) => {
    activeCategory.value = cat;
    router.get(
        route('articles.index'),
        {
            category: cat || undefined,
            q: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const handleSearch = () => {
    router.get(
        route('articles.index'),
        {
            category: activeCategory.value || undefined,
            q: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilter = () => {
    searchQuery.value = '';
    activeCategory.value = null;
    router.get(route('articles.index'));
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head>
        <title>Portal Berita & Kegiatan — Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</title>
        <meta name="description" content="Kumpulan berita, warta kegiatan, sejarah leluhur, dan adat budaya Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia." />
        <meta property="og:title" content="Portal Berita & Kegiatan — Punguan Sihotang" />
        <meta property="og:image" content="/images/logo.png" />
    </Head>

    <AppLayout>
        <div class="min-h-screen bg-gray-950 text-white pt-24 pb-20 px-4 sm:px-6 lg:px-8 selection:bg-amber-500 selection:text-gray-950">
            <!-- Background Ambient Glow -->
            <div class="max-w-7xl mx-auto relative">
                <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-xs text-gray-400 mb-8">
                    <Link href="/" class="hover:text-amber-400 transition-colors">Beranda</Link>
                    <span>/</span>
                    <span class="text-amber-400 font-medium">Portal Berita & Kegiatan</span>
                </nav>

                <!-- Page Header -->
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold tracking-wider uppercase mb-4 shadow-inner">
                        <span>📰</span>
                        <span>Warta & Agenda Komunitas</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4" style="font-family: 'Cinzel', serif;">
                        Portal Berita & Kegiatan
                    </h1>
                    <p class="text-gray-400 text-sm sm:text-base leading-relaxed">
                        Ikuti perkembangan agenda kegiatan, warta silaturahmi pomparan, serta artikel sejarah dan adat budaya Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia.
                    </p>
                </div>

                <!-- Filters & Search Bar -->
                <div class="bg-gray-900/80 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-5 mb-10 shadow-xl space-y-4">
                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        <!-- Category Tabs -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
                            <button
                                type="button"
                                @click="filterCategory(null)"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5',
                                    !activeCategory
                                        ? 'bg-amber-500 text-gray-950 font-bold shadow-lg shadow-amber-500/20'
                                        : 'bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white'
                                ]"
                            >
                                <span>📋 Semua</span>
                                <span v-if="counts?.all" class="px-1.5 py-0.2 rounded-full text-[10px]" :class="!activeCategory ? 'bg-gray-950/30 text-gray-950' : 'bg-white/10 text-gray-400'">
                                    {{ counts.all }}
                                </span>
                            </button>

                            <button
                                v-for="catKey in ['kegiatan', 'berita', 'sejarah', 'budaya']"
                                :key="catKey"
                                type="button"
                                @click="filterCategory(catKey)"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5',
                                    activeCategory === catKey
                                        ? 'bg-amber-500 text-gray-950 font-bold shadow-lg shadow-amber-500/20'
                                        : 'bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white'
                                ]"
                            >
                                <span>{{ categoryMeta[catKey]?.icon }}</span>
                                <span>{{ categoryMeta[catKey]?.label }}</span>
                                <span v-if="counts?.[catKey]" class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeCategory === catKey ? 'bg-gray-950/30 text-gray-950' : 'bg-white/10 text-gray-400'">
                                    {{ counts[catKey] }}
                                </span>
                            </button>
                        </div>

                        <!-- Search Box -->
                        <form @submit.prevent="handleSearch" class="relative w-full md:w-80">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari judul atau isi warta..."
                                class="w-full bg-gray-950/80 border border-white/10 rounded-xl pl-10 pr-9 py-2.5 text-xs sm:text-sm text-white placeholder-gray-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="searchQuery = ''; handleSearch()"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-white text-xs"
                            >
                                ✕
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Articles Grid -->
                <div v-if="articles?.data?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    <Link
                        v-for="art in articles.data"
                        :key="art.id"
                        :href="route('articles.show', art.slug || art.id)"
                        class="group bg-gray-900/70 border border-white/10 hover:border-amber-500/50 rounded-2xl overflow-hidden transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-amber-500/10 flex flex-col justify-between"
                    >
                        <div>
                            <!-- Cover Image -->
                            <div class="h-48 overflow-hidden bg-gray-800 relative">
                                <img
                                    v-if="art.image"
                                    :src="'/storage/' + art.image"
                                    :alt="art.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900 text-4xl">
                                    {{ categoryMeta[art.category]?.icon || '📰' }}
                                </div>

                                <!-- Category Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="text-[11px] font-bold tracking-wider uppercase text-amber-300 px-3 py-1 rounded-full bg-gray-950/80 backdrop-blur-md border border-amber-500/40 shadow-lg flex items-center gap-1">
                                        <span>{{ categoryMeta[art.category]?.icon || '📰' }}</span>
                                        <span>{{ categoryMeta[art.category]?.label || art.category }}</span>
                                    </span>
                                </div>

                                <!-- Event Date Badge (if kegiatan) -->
                                <div v-if="art.event_date" class="absolute bottom-3 right-3">
                                    <span class="text-[11px] font-semibold text-emerald-300 px-2.5 py-0.5 rounded-lg bg-emerald-950/90 backdrop-blur-md border border-emerald-500/40 shadow-lg">
                                        📅 {{ formatDate(art.event_date) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-white mb-2 leading-snug group-hover:text-amber-400 transition-colors line-clamp-2">
                                    {{ art.title }}
                                </h3>
                                <p class="text-gray-400 text-xs sm:text-sm leading-relaxed mb-4 line-clamp-3">
                                    {{ art.excerpt || art.content }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-white/5 flex items-center justify-between text-xs text-gray-500 mt-auto bg-white/[0.01]">
                            <span>{{ formatDate(art.created_at) }}</span>
                            <span class="text-amber-400 font-semibold group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                                Baca Selengkapnya →
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 bg-gray-900/40 rounded-3xl border border-white/5 p-8 max-w-xl mx-auto">
                    <div class="w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/20 text-3xl flex items-center justify-center mx-auto mb-4">
                        🔍
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Tidak Ada Warta yang Ditemukan</h3>
                    <p class="text-gray-400 text-sm mb-6">
                        Belum ada warta atau kegiatan yang cocok dengan kriteria pencarian Anda.
                    </p>
                    <button
                        type="button"
                        @click="resetFilter"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold text-xs transition-colors shadow-lg shadow-amber-500/20"
                    >
                        Reset Semua Filter
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="articles?.links?.length > 3" class="flex justify-center items-center gap-1.5 mt-8">
                    <template v-for="(link, i) in articles.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all',
                                link.active
                                    ? 'bg-amber-500 text-gray-950 font-bold shadow-lg shadow-amber-500/20'
                                    : 'bg-gray-900 text-gray-300 hover:bg-white/10 border border-white/10'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3.5 py-2 rounded-xl text-xs text-gray-600 bg-gray-900/50 border border-white/5 cursor-not-allowed"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
