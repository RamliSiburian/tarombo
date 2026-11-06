<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total_nodes: 0, total_marga: 0 }),
    },
    articles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const scrolled = ref(false);
const mobileMenu = ref(false);
const scrollProgress = ref(0);
const showScrollTop = ref(false);

// Smooth animated counter stats
const displayNodes = ref(0);
const displayMarga = ref(0);
let statsAnimated = false;

const animateValue = (start, end, duration, onUpdate) => {
    if (end <= 0) {
        onUpdate(0);
        return;
    }
    const startTime = performance.now();
    const step = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // easeOutCubic curve
        const ease = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (end - start) * ease);
        onUpdate(current);
        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            onUpdate(end);
        }
    };
    requestAnimationFrame(step);
};

const startStatsAnimation = () => {
    if (statsAnimated) return;
    statsAnimated = true;
    animateValue(0, props.stats.total_nodes || 0, 1500, (v) => { displayNodes.value = v; });
    animateValue(0, props.stats.total_marga || 0, 1300, (v) => { displayMarga.value = v; });
};

let observer = null;

const handleScroll = () => {
    const currentY = window.scrollY;
    scrolled.value = currentY > 50;
    showScrollTop.value = currentY > 400;

    const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
    if (totalHeight > 0) {
        scrollProgress.value = Math.min(100, Math.max(0, (currentY / totalHeight) * 100));
    }
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();

    // IntersectionObserver for lightweight scroll-triggered reveals
    if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    if (entry.target.hasAttribute('data-trigger-stats')) {
                        startStatsAnimation();
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px',
        });

        const revealElements = document.querySelectorAll('.motion-reveal');
        revealElements.forEach((el) => observer.observe(el));
    }

    // Trigger stats if already in viewport on initial load
    setTimeout(() => {
        startStatsAnimation();
    }, 350);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (observer) observer.disconnect();
});

// Map category to icon/color
const categoryMeta = {
    kegiatan: { icon: '🎉', color: 'from-emerald-500/20 to-teal-500/10', border: 'border-emerald-500/30', label: 'Kegiatan' },
    berita:   { icon: '📰', color: 'from-blue-500/20 to-indigo-500/10', border: 'border-blue-500/30', label: 'Berita' },
    sejarah:  { icon: '🏛️', color: 'from-primary-500/20 to-primary-500/10', border: 'border-primary-500/30', label: 'Sejarah' },
    budaya:   { icon: '🪔', color: 'from-purple-500/20 to-violet-500/10', border: 'border-purple-500/30', label: 'Budaya' },
};

// Static fallback articles for when no articles exist in DB yet
const staticFallback = [
    {
        id: 's1',
        category: 'sejarah',
        created_at: '2026-10-01',
        title: 'Si Raja Batak: Leluhur Marga-Marga Batak',
        excerpt: 'Si Raja Batak dipercaya sebagai moyang dari semua marga Batak yang mendiami kawasan Toba dan sekitarnya.',
    },
    {
        id: 's2',
        category: 'budaya',
        created_at: '2026-09-25',
        title: 'Dalihan Na Tolu: Sistem Kekerabatan Orang Batak',
        excerpt: 'Dalihan Na Tolu adalah falsafah hidup orang Batak yang mengatur hubungan antara Hula-hula, Dongan Tubu, dan Boru.',
    },
    {
        id: 's3',
        category: 'budaya',
        created_at: '2026-09-18',
        title: 'Tradisi Mangongkal Holi: Menghormati Leluhur',
        excerpt: 'Mangongkal Holi adalah upacara pemindahan tulang belulang leluhur yang menjadi salah satu ritual terpenting dalam budaya Batak.',
    },
];

// Use dynamic articles from backend, fallback to static if empty (max 6 items)
const displayArticles = computed(() => {
    const list = props.articles && props.articles.length > 0 ? props.articles : staticFallback;
    return list.slice(0, 6);
});

const formatArticleDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

const features = [
    {
        icon: '🌳',
        title: 'Pohon Silsilah Interaktif',
        desc: 'Jelajahi pohon keluarga Batak secara visual dan interaktif. Temukan hubungan leluhur dengan mudah.',
    },
    {
        icon: '🔍',
        title: 'Pencarian Nama & Marga',
        desc: 'Cari nama atau marga tertentu dan temukan posisinya dalam pohon silsilah secara instan.',
    },
    {
        icon: '📋',
        title: 'Ajukan Tambah Data',
        desc: 'Kontribusikan data silsilah keluarga Anda. Setiap pengajuan akan diverifikasi oleh admin.',
    },
    {
        icon: '🪪',
        title: 'Daftar Akun Anggota',
        desc: 'Ajukan data silsilah untuk diri sendiri dan otomatis daftar sebagai anggota Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna.',
    },
];
</script>

<template>
    <Head>
        <title>Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</title>
        <meta name="description" content="Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia - Platform digital untuk menelusuri, mendokumentasikan, dan melestarikan silsilah marga Sihotang. Temukan akar keluarga Anda." />
        <meta name="keywords" content="punguan sihotang, tarombo sihotang, silsilah sihotang, marga sihotang, raja sigodang ulu sihotang, sihotang dohot boruna" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-gray-950 text-white selection:bg-primary-500 selection:text-gray-950" style="font-family: 'Inter', sans-serif;">

        <!-- ═══ TOP SCROLL PROGRESS BAR ═══ -->
        <div
            class="fixed top-0 left-0 h-[2.5px] bg-gradient-to-r from-primary-500 via-primary-400 to-red-500 z-[60] pointer-events-none transition-all duration-75 ease-out shadow-sm shadow-primary-500/50"
            :style="{ width: scrollProgress + '%' }"
        ></div>

        <!-- ═══ NAVBAR ═══ -->
        <nav :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', scrolled ? 'bg-gray-950/90 backdrop-blur-md border-b border-white/10 shadow-xl shadow-black/40' : 'bg-transparent']">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5 group">
                        <img
                            src="/images/logo.png"
                            alt="Logo Punguan Sihotang"
                            class="h-10 w-auto object-contain group-hover:scale-105 transition-transform duration-200 drop-shadow-lg"
                        />
                        <div>
                            <span class="text-base font-bold text-white group-hover:text-primary-400 transition-colors duration-200" style="font-family: 'Cinzel', serif;">SIHOTANG</span>
                            <span class="block text-[10px] text-primary-400/70 leading-none tracking-widest uppercase">Dohot Boruna</span>
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center gap-1">
                        <Link href="/" class="px-4 py-2 text-sm text-primary-400 font-medium rounded-lg bg-primary-500/10 hover:bg-primary-500/20 transition-colors">Beranda</Link>
                        <Link :href="route('tree.index')" class="px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Pohon Silsilah</Link>
                        <Link :href="route('articles.index')" class="px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Berita & Kegiatan</Link>
                        <Link :href="route('request.create')" class="px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Ajukan Data</Link>
                    </div>

                    <!-- Auth Buttons -->
                    <div class="flex items-center gap-2">
                        <template v-if="$page.props.auth?.user">
                            <Link
                                v-if="$page.props.auth.user.role === 'admin'"
                                :href="route('admin.dashboard')"
                                class="px-4 py-1.5 text-sm font-medium text-white bg-gradient-to-r from-primary-600 to-red-700 rounded-lg hover:opacity-90 transition-opacity shadow-lg"
                            >
                                Admin Panel
                            </Link>
                            <Link
                                v-else
                                :href="route('member.dashboard')"
                                class="px-4 py-1.5 text-sm font-medium text-white bg-gradient-to-r from-primary-600 to-red-700 rounded-lg hover:opacity-90 transition-opacity shadow-lg"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="px-4 py-1.5 text-sm text-gray-300 hover:text-white border border-white/20 hover:border-white/40 rounded-lg transition-all">
                                Masuk
                            </Link>
                            <Link :href="route('request.create')" class="px-4 py-1.5 text-sm font-medium text-gray-900 bg-gradient-to-r from-primary-400 to-primary-500 hover:from-primary-300 hover:to-primary-400 rounded-lg transition-all shadow-lg shadow-primary-500/30 hover:scale-[1.02]">
                                Ajukan Silsilah
                            </Link>
                        </template>

                        <!-- Mobile menu toggle -->
                        <button @click="mobileMenu = !mobileMenu" class="md:hidden ml-1 p-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/10 transition-colors" aria-label="Toggle Menu">
                            <svg v-if="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div v-show="mobileMenu" class="md:hidden pb-4 border-t border-white/10 mt-1 pt-3 space-y-1">
                    <Link href="/" class="block px-4 py-2.5 text-sm text-primary-400 bg-primary-500/10 rounded-lg">Beranda</Link>
                    <Link :href="route('tree.index')" class="block px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Pohon Silsilah</Link>
                    <Link :href="route('articles.index')" class="block px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Berita & Kegiatan</Link>
                    <Link :href="route('request.create')" class="block px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Ajukan Data</Link>
                </div>
            </div>
        </nav>

        <!-- ═══ HERO SECTION ═══ -->
        <section class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden pt-16">
            <!-- Background layers with ambient animation -->
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950"></div>
                <!-- Ambient glowing orb (GPU-accelerated float) -->
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[650px] h-[380px] bg-primary-700/10 rounded-full blur-[120px] pointer-events-none animate-ambient-glow"></div>
                <div class="absolute bottom-1/4 left-1/3 w-[450px] h-[280px] bg-primary-900/15 rounded-full blur-[100px] pointer-events-none animate-ambient-glow" style="animation-delay: -5s;"></div>
                <!-- Batak pattern overlay (subtle) -->
                <div class="absolute inset-0 opacity-5" style="background-image: repeating-linear-gradient(45deg, #830000 0px, #830000 1px, transparent 1px, transparent 20px), repeating-linear-gradient(-45deg, #830000 0px, #830000 1px, transparent 1px, transparent 20px);"></div>
            </div>

            <!-- Decorative top border -->
            <div class="absolute top-16 left-0 right-0 h-px bg-gradient-to-r from-transparent via-primary-500/40 to-transparent"></div>

            <!-- Content -->
            <div class="relative z-10 text-center px-6 max-w-5xl mx-auto">
                <!-- Badge -->
                <!-- <div class="motion-reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-primary-700/50 bg-primary-900/40 text-primary-300 text-xs font-medium tracking-wider uppercase mb-8 backdrop-blur-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-400 animate-ping"></span>
                    Silsilah Marga Sihotang Digital
                </div> -->

                <!-- Main Title -->
                <h1 class="motion-reveal motion-delay-100 text-3xl sm:text-5xl lg:text-6xl font-bold mb-6 leading-tight tracking-tight" style="font-family: 'Cinzel', serif;">
                    <span class="text-white">PUNGUAN POMPARAN RAJA SIGODANG ULU SIHOTANG</span>
                    <span class="block text-primary-400/80 mt-2 text-2xl sm:text-4xl lg:text-5xl">DOHOT BORUNA SE-INDONESIA</span>
                </h1>

                <!-- Subtitle -->
                <p class="motion-reveal motion-delay-200 text-base sm:text-lg text-gray-300 max-w-3xl mx-auto mb-4 leading-relaxed">
                    Menelusuri, mendokumentasikan, dan melestarikan silsilah leluhur Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna untuk generasi mendatang.
                </p>
                <p class="motion-reveal motion-delay-200 text-sm text-gray-500 max-w-lg mx-auto mb-12 italic">
                    "Sisada lulu anak, sisada lulu boru" — Seia sekata dalam persaudaraan pomparan
                </p>

                <!-- CTA Buttons -->
                <div class="motion-reveal motion-delay-300 flex flex-col sm:flex-row gap-4 justify-center mb-16">
                    <Link
                        :href="route('tree.index')"
                        class="group inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-primary-700 hover:bg-primary-600 text-white font-bold rounded-xl transition-all duration-200 shadow-lg shadow-black/40 hover:shadow-primary-700/30 border border-primary-600/50 hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <span class="text-lg">🌳</span>
                        <span>Jelajahi Pohon Silsilah</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                    <Link
                        :href="route('request.create')"
                        class="inline-flex items-center justify-center gap-2.5 px-8 py-3.5 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 hover:border-primary-400/40 transition-all duration-200 backdrop-blur-sm hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <span class="text-lg">🌳</span>
                        <span>Ajukan & Daftar Anggota</span>
                    </Link>
                </div>

                <!-- Stats with Counter Animation -->
                <div
                    data-trigger-stats
                    class="motion-reveal motion-delay-400 flex flex-col sm:flex-row items-center justify-center gap-8 sm:gap-16 p-6 rounded-2xl bg-white/[0.02] border border-white/5 backdrop-blur-sm max-w-3xl mx-auto"
                >
                    <div class="text-center group">
                        <div class="text-4xl sm:text-5xl font-bold text-white tracking-tight" style="font-family: 'Cinzel', serif;">
                            {{ displayNodes.toLocaleString() }}
                        </div>
                        <div class="text-xs sm:text-sm text-gray-500 mt-1.5 font-medium tracking-wide">Tokoh Terdaftar</div>
                    </div>
                    <div class="hidden sm:block w-px h-14 bg-gradient-to-b from-transparent via-white/10 to-transparent"></div>
                    <div class="text-center group">
                        <div class="text-4xl sm:text-5xl font-bold text-white tracking-tight" style="font-family: 'Cinzel', serif;">
                            {{ displayMarga }}
                        </div>
                        <div class="text-xs sm:text-sm text-gray-500 mt-1.5 font-medium tracking-wide">Marga Tercatat</div>
                    </div>
                    <div class="hidden sm:block w-px h-14 bg-gradient-to-b from-transparent via-white/10 to-transparent"></div>
                    <div class="text-center group">
                        <div class="text-4xl sm:text-5xl font-bold text-white tracking-tight" style="font-family: 'Cinzel', serif;">
                            ∞
                        </div>
                        <div class="text-xs sm:text-sm text-gray-500 mt-1.5 font-medium tracking-wide">Warisan Budaya</div>
                    </div>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-gray-600 animate-bounce">
                <span class="text-xs tracking-widest uppercase text-gray-500 font-medium">Gulir</span>
                <svg class="w-4 h-4 text-primary-500/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </section>

        <!-- ═══ TENTANG / FITUR ═══ -->
        <section class="py-24 relative">
            <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-900/60 to-gray-950"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-6">
                <!-- Section header -->
                <div class="motion-reveal text-center mb-16">
                    <div class="inline-block px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-medium tracking-wider uppercase mb-4">
                        Apa itu Tarombo?
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight" style="font-family: 'Cinzel', serif;">
                        Platform Silsilah Terlengkap
                    </h2>
                    <p class="text-gray-400 max-w-2xl mx-auto text-base leading-relaxed">
                        Tarombo adalah sistem pencatatan garis keturunan (silsilah) dalam tradisi Batak.
                        Platform ini menghadirkan Tarombo secara digital agar mudah diakses oleh seluruh keturunan Batak di mana saja.
                    </p>
                </div>

                <!-- Features grid with staggered motion -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(f, idx) in features"
                        :key="f.title"
                        :class="[
                            'motion-reveal',
                            `motion-delay-${(idx + 1) * 100}`,
                            'group p-6 rounded-2xl bg-gray-900/60 border border-white/5 hover:border-primary-500/40 hover:bg-gray-900/90 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-primary-500/10 cursor-default'
                        ]"
                    >
                        <div class="text-3xl mb-4 group-hover:scale-110 transition-transform duration-200">{{ f.icon }}</div>
                        <h3 class="text-base font-semibold text-white mb-2 group-hover:text-primary-400 transition-colors">{{ f.title }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed group-hover:text-gray-400 transition-colors">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ PORTAL BERITA / ARTIKEL ═══ -->
        <section class="py-24 relative overflow-hidden">
            <div class="absolute inset-0" style="background: radial-gradient(ellipse 70% 50% at 50% 50%, rgba(180, 83, 9, 0.08) 0%, transparent 70%);"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-6">
                <!-- Header -->
                <div class="motion-reveal flex items-end justify-between mb-12">
                    <div>
                        <div class="inline-block px-3 py-1 rounded-full bg-primary-500/10 border border-primary-500/20 text-primary-400 text-xs font-medium tracking-wider uppercase mb-4">
                            Artikel & Sejarah
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight" style="font-family: 'Cinzel', serif;">
                            Kenali Budaya Batak
                        </h2>
                    </div>
                    <Link
                        :href="route('articles.index')"
                        class="hidden sm:inline-flex items-center gap-1.5 text-xs text-primary-400 hover:text-white font-medium px-3.5 py-1.5 rounded-lg bg-primary-500/10 hover:bg-primary-500/20 border border-primary-500/20 hover:border-primary-500/40 transition-all duration-200"
                    >
                        <span>Portal Berita & Kegiatan</span>
                        <span>→</span>
                    </Link>
                </div>

                <!-- Articles Grid with staggered motion -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        v-for="(art, idx) in displayArticles"
                        :key="art.id"
                        :href="art.slug ? route('articles.show', art.slug) : route('articles.index')"
                        :class="[
                            'motion-reveal',
                            `motion-delay-${(idx + 1) * 100}`,
                            'group p-6 rounded-2xl border transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-black/50 cursor-pointer bg-gradient-to-br flex flex-col justify-between block',
                            categoryMeta[art.category]?.color || 'from-primary-500/20 to-primary-500/10',
                            categoryMeta[art.category]?.border || 'border-primary-500/30',
                            'hover:border-primary-400/50'
                        ]"
                    >
                        <div>
                            <!-- Cover Image (if available from DB) -->
                            <div v-if="art.image" class="-mx-6 -mt-6 mb-4 h-44 overflow-hidden rounded-t-2xl">
                                <img :src="'/storage/' + art.image" :alt="art.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                            </div>

                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-2xl group-hover:scale-110 transition-transform duration-200">
                                    {{ categoryMeta[art.category]?.icon || '📰' }}
                                </span>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary-400/90 px-2.5 py-0.5 rounded-full bg-primary-500/15 border border-primary-500/30">
                                    {{ categoryMeta[art.category]?.label || art.category }}
                                </span>
                                <span v-if="art.event_date" class="text-xs text-emerald-400 ml-auto">📅 {{ formatArticleDate(art.event_date) }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3 leading-snug group-hover:text-primary-400 transition-colors">
                                {{ art.title }}
                            </h3>
                            <p class="text-sm text-gray-400 leading-relaxed mb-5 line-clamp-3">
                                {{ art.excerpt || art.content }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-white/5 mt-auto">
                            <span>{{ formatArticleDate(art.created_at) }}</span>
                            <span class="text-primary-400 font-semibold group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                                Baca Selengkapnya →
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Show More Articles Button (Poin: Button show more artikel merujuk ke portal berita) -->
                <div class="text-center mt-12 motion-reveal">
                    <Link
                        :href="route('articles.index')"
                        class="inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-gradient-to-r from-primary-500/15 via-primary-500/25 to-primary-500/15 hover:from-primary-500 hover:to-primary-600 hover:text-gray-950 text-primary-400 font-bold text-sm rounded-xl border border-primary-500/30 hover:border-primary-400 transition-all duration-300 shadow-xl shadow-black/40 hover:scale-[1.02]"
                    >
                        <span>📰 Lihat Semua Berita & Kegiatan</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══ CTA KEANGGOTAAN ═══ -->
        <section class="py-24 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-primary-950/10 to-gray-950"></div>
                <div class="absolute inset-0 opacity-3" style="background-image: repeating-linear-gradient(45deg, #4a1212 0px, #4a1212 1px, transparent 1px, transparent 40px), repeating-linear-gradient(-45deg, #4a1212 0px, #4a1212 1px, transparent 1px, transparent 40px);"></div>
            </div>

            <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
                <div class="motion-reveal p-8 sm:p-14 rounded-3xl border border-white/10 bg-gray-900/60 backdrop-blur-md shadow-2xl shadow-black/60 relative overflow-hidden group">
                    <!-- Subtle top glow inside card -->
                    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-80 h-40 bg-primary-800/20 rounded-full blur-2xl pointer-events-none group-hover:bg-primary-700/25 transition-all duration-500"></div>

                    <div class="text-5xl mb-6 group-hover:scale-110 transition-transform duration-300">🤝</div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight" style="font-family: 'Cinzel', serif;">
                        Bergabunglah dengan <span class="text-primary-400">Punguan Sihotang</span>
                    </h2>
                    <p class="text-gray-400 text-base leading-relaxed mb-8 max-w-2xl mx-auto">
                        Daftarkan diri sebagai anggota untuk melengkapi data silsilah keluarga Anda,
                        terhubung dengan sesama Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link
                            :href="route('request.create')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-primary-700 hover:bg-primary-600 text-white font-bold rounded-xl transition-all duration-200 shadow-lg shadow-black/40 border border-primary-600/50 hover:scale-[1.02] active:scale-100"
                        >
                            🌳 Ajukan Silsilah & Daftar Anggota
                        </Link>
                        <Link
                            :href="route('tree.index')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 hover:border-white/40 transition-all duration-200 hover:scale-[1.02] active:scale-100"
                        >
                            Jelajahi Dulu
                        </Link>
                    </div>
                    <p class="text-xs text-gray-500 mt-6">Verifikasi email diperlukan • Gratis selamanya • Tanpa biaya langganan</p>
                </div>
            </div>
        </section>

        <!-- ═══ FOOTER ═══ -->
        <footer class="border-t border-white/5 py-12 bg-gray-950/80">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <img src="/images/logo.png" alt="Logo" class="w-9 h-9 object-contain" />
                        <span class="text-xs sm:text-sm font-bold text-white tracking-wider" style="font-family: 'Cinzel', serif;">PUNGUAN POMPARAN RAJA SIGODANG ULU SIHOTANG DOHOT BORUNA SE-INDONESIA</span>
                    </div>

                    <!-- Links -->
                    <div class="flex flex-wrap items-center gap-6 text-sm text-gray-400">
                        <Link href="/" class="hover:text-primary-400 transition-colors">Beranda</Link>
                        <Link :href="route('tree.index')" class="hover:text-primary-400 transition-colors">Pohon Silsilah</Link>
                        <Link :href="route('articles.index')" class="hover:text-primary-400 transition-colors">Berita & Kegiatan</Link>
                        <Link :href="route('request.create')" class="hover:text-primary-400 transition-colors">Ajukan Data</Link>
                        <Link :href="route('login')" class="hover:text-primary-400 transition-colors">Masuk</Link>
                    </div>

                    <p class="text-xs text-gray-600">© {{ new Date().getFullYear() }} Punguan Sihotang Dohot Boruna</p>
                </div>
            </div>
        </footer>

        <!-- ═══ FLOATING SCROLL TO TOP BUTTON ═══ -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-3 scale-95"
        >
            <button
                v-show="showScrollTop"
                @click="scrollToTop"
                class="fixed bottom-6 right-6 z-40 p-3 rounded-full bg-gray-900/90 text-primary-400 border border-primary-500/30 hover:border-primary-400 hover:bg-primary-500/10 backdrop-blur-md shadow-xl shadow-black/50 hover:scale-110 active:scale-95 transition-all duration-200 group focus:outline-none"
                aria-label="Kembali ke atas"
                title="Kembali ke atas"
            >
                <svg class="w-5 h-5 group-hover:-translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </button>
        </transition>

    </div>
</template>

<style scoped>
/* Motion reveal styles - pure GPU acceleration (translate3d/opacity) */
.motion-reveal {
    opacity: 0;
    transform: translate3d(0, 26px, 0);
    transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.motion-reveal.is-visible {
    opacity: 1;
    transform: translate3d(0, 0, 0);
}

.motion-delay-100 { transition-delay: 90ms; }
.motion-delay-200 { transition-delay: 180ms; }
.motion-delay-300 { transition-delay: 270ms; }
.motion-delay-400 { transition-delay: 360ms; }

/* Subtle pulse glow for background lights */
@keyframes ambientGlow {
    0%, 100% {
        transform: scale(1) translate3d(0, 0, 0);
        opacity: 0.10;
    }
    50% {
        transform: scale(1.06) translate3d(0, -8px, 0);
        opacity: 0.17;
    }
}

.animate-ambient-glow {
    animation: ambientGlow 12s ease-in-out infinite;
}

/* Accessibility: Disable motion if user prefers reduced motion */
@media (prefers-reduced-motion: reduce) {
    .motion-reveal {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    .animate-ambient-glow {
        animation: none !important;
    }
}
</style>
