<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    article: Object,
    relatedArticles: Array,
});

const copied = ref(false);

const categoryMeta = {
    kegiatan: { label: 'Kegiatan & Acara', icon: '🎉', color: 'text-emerald-400 bg-emerald-500/15 border-emerald-500/30' },
    berita:   { label: 'Berita & Pengumuman', icon: '📰', color: 'text-blue-400 bg-blue-500/15 border-blue-500/30' },
    sejarah:  { label: 'Sejarah & Tarombo', icon: '📜', color: 'text-amber-400 bg-amber-500/15 border-amber-500/30' },
    budaya:   { label: 'Adat & Budaya', icon: '🏺', color: 'text-pink-400 bg-pink-500/15 border-pink-500/30' },
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const copyLink = () => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const whatsappShareUrl = () => {
    const text = encodeURIComponent(`${props.article?.title}\n\nBaca selengkapnya di: ${window.location.href}`);
    return `https://api.whatsapp.com/send?text=${text}`;
};
</script>

<template>
    <Head>
        <title>{{ article?.title }} — Punguan Pomparan Raja Sigodang Ulu Sihotang</title>
        <meta name="description" :content="article?.excerpt || article?.title" />
        <meta property="og:title" :content="article?.title" />
        <meta property="og:description" :content="article?.excerpt || article?.title" />
        <meta property="og:type" content="article" />
        <meta v-if="article?.image" property="og:image" :content="'/storage/' + article.image" />
    </Head>

    <AppLayout>
        <div class="min-h-screen bg-gray-950 text-white pt-24 pb-20 px-4 sm:px-6 lg:px-8 selection:bg-amber-500 selection:text-gray-950">
            <article class="max-w-4xl mx-auto">
                <!-- Navigation & Breadcrumb -->
                <div class="flex items-center justify-between gap-4 mb-8">
                    <Link
                        :href="route('articles.index')"
                        class="inline-flex items-center gap-2 text-xs sm:text-sm text-gray-400 hover:text-amber-400 transition-colors py-1.5 px-3 rounded-lg hover:bg-white/5"
                    >
                        <span>←</span>
                        <span>Kembali ke Portal Berita</span>
                    </Link>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="copyLink"
                            class="px-3 py-1.5 rounded-lg bg-gray-900 border border-white/10 hover:border-amber-500/40 text-xs text-gray-300 hover:text-white transition-all flex items-center gap-1.5"
                        >
                            <span>{{ copied ? '✅ Tersalin' : '🔗 Salin Link' }}</span>
                        </button>
                        <a
                            :href="whatsappShareUrl()"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600/20 border border-emerald-500/40 hover:bg-emerald-600/30 text-xs text-emerald-300 transition-all flex items-center gap-1.5"
                        >
                            <span>💬 Bagikan</span>
                        </a>
                    </div>
                </div>

                <!-- Header Details -->
                <header class="mb-10 text-center sm:text-left">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-4">
                        <span
                            :class="[
                                'text-xs font-bold tracking-wider uppercase px-3 py-1 rounded-full border shadow-sm',
                                categoryMeta[article.category]?.color || 'text-amber-400 bg-amber-500/10 border-amber-500/30'
                            ]"
                        >
                            {{ categoryMeta[article.category]?.icon }} {{ categoryMeta[article.category]?.label || article.category }}
                        </span>
                        <span class="text-xs text-gray-500">•</span>
                        <span class="text-xs text-gray-400">{{ formatDate(article.created_at) }}</span>
                        <template v-if="article.author?.name">
                            <span class="text-xs text-gray-500">•</span>
                            <span class="text-xs text-amber-400/90 font-medium">Oleh: {{ article.author.name }}</span>
                        </template>
                    </div>

                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white leading-tight mb-6" style="font-family: 'Cinzel', serif;">
                        {{ article.title }}
                    </h1>

                    <!-- Event Date Banner (if kegiatan) -->
                    <div
                        v-if="article.event_date"
                        class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 flex items-center gap-3 mb-6"
                    >
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-2xl flex-shrink-0">
                            📅
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Jadwal Agenda / Kegiatan:</div>
                            <div class="text-white font-bold text-sm sm:text-base mt-0.5">
                                {{ formatDate(article.event_date) }}
                            </div>
                        </div>
                    </div>

                    <!-- Excerpt callout box -->
                    <div v-if="article.excerpt" class="p-4 sm:p-5 rounded-2xl bg-white/[0.03] border-l-4 border-amber-500 text-gray-300 text-sm sm:text-base italic leading-relaxed">
                        "{{ article.excerpt }}"
                    </div>
                </header>

                <!-- Featured Image -->
                <div v-if="article.image" class="mb-10 rounded-3xl overflow-hidden border border-white/10 shadow-2xl bg-gray-900 max-h-[500px]">
                    <img :src="'/storage/' + article.image" :alt="article.title" class="w-full h-full object-cover"/>
                </div>

                <!-- Main Body Content -->
                <div class="prose prose-invert max-w-none text-gray-300 leading-relaxed text-sm sm:text-base space-y-5 bg-gray-900/40 border border-white/5 rounded-3xl p-6 sm:p-10 mb-16 shadow-xl">
                    <div class="whitespace-pre-line leading-relaxed font-normal">
                        {{ article.content }}
                    </div>
                </div>

                <!-- Related Articles Section -->
                <section v-if="relatedArticles?.length > 0" class="pt-10 border-t border-white/10">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight" style="font-family: 'Cinzel', serif;">
                                Warta & Artikel Terkait
                            </h2>
                            <p class="text-xs text-gray-400 mt-1">Baca juga tulisan dan informasi menarik lainnya</p>
                        </div>
                        <Link :href="route('articles.index')" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors">
                            Lihat Semua →
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Link
                            v-for="rel in relatedArticles"
                            :key="rel.id"
                            :href="route('articles.show', rel.slug || rel.id)"
                            class="group bg-gray-900/70 border border-white/10 hover:border-amber-500/50 rounded-2xl p-5 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs font-bold text-amber-400">
                                        {{ categoryMeta[rel.category]?.icon }} {{ categoryMeta[rel.category]?.label || rel.category }}
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-2 mb-2 leading-snug">
                                    {{ rel.title }}
                                </h3>
                                <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed">
                                    {{ rel.excerpt || rel.content }}
                                </p>
                            </div>
                            <div class="pt-3 border-t border-white/5 mt-4 text-[11px] text-gray-500 flex items-center justify-between">
                                <span>{{ formatDate(rel.created_at) }}</span>
                                <span class="text-amber-400 font-semibold group-hover:translate-x-0.5 transition-transform">Baca →</span>
                            </div>
                        </Link>
                    </div>
                </section>
            </article>
        </div>
    </AppLayout>
</template>
