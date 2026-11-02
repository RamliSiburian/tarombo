<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    recentRequests: Array,
    recentArticles: Array,
});

const statusLabel = (s) => ({ pending: 'Menunggu', accepted: 'Disetujui', rejected: 'Ditolak' }[s]);
const statusClass = (s) => ({
    pending:  'bg-amber-500/15 text-amber-400 border-amber-500/30',
    accepted: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    rejected: 'bg-red-500/15 text-red-400 border-red-500/30',
}[s]);

const categoryBadge = (cat) => {
    const map = {
        kegiatan: { label: 'Kegiatan', class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' },
        berita:   { label: 'Berita', class: 'bg-blue-500/15 text-blue-400 border-blue-500/30' },
        sejarah:  { label: 'Sejarah', class: 'bg-amber-500/15 text-amber-400 border-amber-500/30' },
        budaya:   { label: 'Budaya', class: 'bg-pink-500/15 text-pink-400 border-pink-500/30' },
    };
    return map[cat] || { label: cat, class: 'bg-gray-500/15 text-gray-400 border-gray-500/30' };
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #title>Dashboard</template>

        <!-- Welcome -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white">Selamat Datang, {{ $page.props.auth.user?.name }} 👋</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola silsilah Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna, permohonan node, dan warta kegiatan komunitas dari panel ini.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">🌳</span>
                    <span class="text-xs text-gray-400 bg-white/5 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold text-white">{{ stats.total_nodes }}</div>
                <div class="text-gray-400 text-sm mt-1">Node Aktif</div>
            </div>

            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">🏷️</span>
                    <span class="text-xs text-gray-400 bg-white/5 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold text-white">{{ stats.total_marga }}</div>
                <div class="text-gray-400 text-sm mt-1">Marga Unik</div>
            </div>

            <!-- Articles Stats Card -->
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">📰</span>
                    <span class="text-xs text-gray-400 bg-white/5 px-2 py-1 rounded-full">Warta</span>
                </div>
                <div class="text-3xl font-bold text-white">{{ stats.total_articles || 0 }}</div>
                <div class="text-gray-400 text-sm mt-1">Berita & Kegiatan</div>
            </div>

            <!-- Request Stats Card -->
            <div :class="['bg-gray-900 border rounded-2xl p-5 shadow-xl', stats.pending_requests > 0 ? 'border-amber-500/30' : 'border-white/5']">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">📬</span>
                    <span v-if="stats.pending_requests > 0" class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-1 rounded-full animate-pulse">
                        Perlu Ditinjau
                    </span>
                </div>
                <div :class="['text-3xl font-bold', stats.pending_requests > 0 ? 'text-amber-400' : 'text-white']">
                    {{ stats.pending_requests }}
                </div>
                <div class="text-gray-400 text-sm mt-1">Request Pending</div>
                <Link v-if="stats.pending_requests > 0" :href="route('admin.requests.index')"
                      class="mt-3 block text-center bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-400 text-xs rounded-lg py-1.5 transition-colors">
                    Tinjau Request →
                </Link>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8">
            <Link :href="route('admin.articles.create')"
                  class="bg-amber-500/10 hover:bg-amber-500/15 border border-amber-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">➕</div>
                <div class="text-amber-300 text-sm font-medium">Buat Kegiatan</div>
            </Link>
            <Link :href="route('admin.articles.index')"
                  class="bg-blue-500/10 hover:bg-blue-500/15 border border-blue-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📰</div>
                <div class="text-blue-300 text-sm font-medium">Kelola Berita</div>
            </Link>
            <Link :href="route('admin.requests.index')"
                  class="bg-rose-500/10 hover:bg-rose-500/15 border border-rose-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📬</div>
                <div class="text-rose-300 text-sm font-medium">Request Masuk</div>
            </Link>
            <Link :href="route('admin.nodes.index')"
                  class="bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🌳</div>
                <div class="text-emerald-300 text-sm font-medium">Kelola Silsilah</div>
            </Link>
            <Link :href="route('tree.index')"
                  class="bg-violet-500/10 hover:bg-violet-500/15 border border-violet-500/20 rounded-xl p-4 text-center transition-all group col-span-2 sm:col-span-1">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🌐</div>
                <div class="text-violet-300 text-sm font-medium">Lihat Website</div>
            </Link>
        </div>

        <!-- Grid 2 Kolom: Berita/Kegiatan Terbaru & Request Masuk Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Recent Articles / Events -->
            <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <span>📰</span> Kegiatan & Berita Terbaru
                    </h3>
                    <Link :href="route('admin.articles.index')" class="text-amber-400 text-xs hover:text-amber-300 transition-colors">Lihat semua →</Link>
                </div>
                <div class="divide-y divide-white/5">
                    <div v-if="!recentArticles || recentArticles.length === 0" class="py-12 text-center text-gray-500 text-xs">
                        Belum ada kegiatan atau warta berita.
                    </div>
                    <div v-for="art in recentArticles" :key="art.id"
                         class="flex items-center gap-3.5 px-5 py-3 hover:bg-white/[0.02] transition-colors">
                        <div class="w-9 h-9 rounded-lg bg-gray-800 border border-white/10 flex items-center justify-center text-base flex-shrink-0">
                            {{ art.category === 'kegiatan' ? '🎉' : art.category === 'berita' ? '📰' : '📜' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-white text-sm font-medium truncate">{{ art.title }}</div>
                            <div class="text-gray-400 text-xs flex items-center gap-2 mt-0.5">
                                <span :class="['px-1.5 py-0.2 rounded text-[10px] font-bold border', categoryBadge(art.category).class]">
                                    {{ categoryBadge(art.category).label }}
                                </span>
                                <span v-if="art.event_date" class="text-amber-300">📅 {{ formatDate(art.event_date) }}</span>
                                <span v-else>{{ formatDate(art.created_at) }}</span>
                            </div>
                        </div>
                        <Link :href="route('admin.articles.edit', art.id)" class="text-xs text-gray-400 hover:text-amber-400 p-1">
                            ✏️
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <span>📬</span> Request Tambah Data Terbaru
                    </h3>
                    <Link :href="route('admin.requests.index')" class="text-amber-400 text-xs hover:text-amber-300 transition-colors">Lihat semua →</Link>
                </div>
                <div class="divide-y divide-white/5">
                    <div v-if="!recentRequests || recentRequests.length === 0" class="py-12 text-center text-gray-500 text-xs">
                        Belum ada request masuk
                    </div>
                    <div v-for="req in recentRequests" :key="req.id"
                         class="flex items-center gap-4 px-5 py-3 hover:bg-white/2 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center text-lg flex-shrink-0">
                            {{ req.gender === 'female' ? '👩' : '👨' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-white text-sm font-medium truncate flex items-center gap-2">
                                <span>{{ req.name }}</span>
                                <span v-if="req.is_self" class="text-[10px] text-purple-300 bg-purple-500/20 px-1.5 py-0.2 rounded font-bold">
                                    Anggota
                                </span>
                            </div>
                            <div class="text-gray-400 text-xs">
                                Di bawah: {{ req.parent_node?.name }} · {{ req.requester_email }}
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span :class="['text-xs px-2 py-0.5 rounded-full border', statusClass(req.status)]">
                                {{ statusLabel(req.status) }}
                            </span>
                            <span class="text-gray-400 text-xs">{{ formatDate(req.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
