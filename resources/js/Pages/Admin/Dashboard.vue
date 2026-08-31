<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    recentRequests: Array,
});

const statusLabel = (s) => ({ pending: 'Menunggu', accepted: 'Disetujui', rejected: 'Ditolak' }[s]);
const statusClass = (s) => ({
    pending:  'bg-amber-500/15 text-amber-400 border-amber-500/30',
    accepted: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    rejected: 'bg-red-500/15 text-red-400 border-red-500/30',
}[s]);

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #title>Dashboard</template>

        <!-- Welcome -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white">Selamat Datang, {{ $page.props.auth.user?.name }} 👋</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola silsilah Batak dari panel ini.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">🌳</span>
                    <span class="text-xs text-gray-600 bg-white/5 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold text-white">{{ stats.total_nodes }}</div>
                <div class="text-gray-500 text-sm mt-1">Node Aktif</div>
            </div>

            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">🏷️</span>
                    <span class="text-xs text-gray-600 bg-white/5 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold text-white">{{ stats.total_marga }}</div>
                <div class="text-gray-500 text-sm mt-1">Marga Unik</div>
            </div>

            <div :class="['bg-gray-900 border rounded-2xl p-5 col-span-2 lg:col-span-1', stats.pending_requests > 0 ? 'border-amber-500/30' : 'border-white/5']">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-3xl">📬</span>
                    <span v-if="stats.pending_requests > 0" class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-1 rounded-full animate-pulse">
                        Perlu Ditinjau
                    </span>
                </div>
                <div :class="['text-3xl font-bold', stats.pending_requests > 0 ? 'text-amber-400' : 'text-white']">
                    {{ stats.pending_requests }}
                </div>
                <div class="text-gray-500 text-sm mt-1">Request Pending</div>
                <Link v-if="stats.pending_requests > 0" :href="route('admin.requests.index')"
                      class="mt-3 block text-center bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-400 text-xs rounded-lg py-1.5 transition-colors">
                    Tinjau Sekarang →
                </Link>
            </div>

            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5">
                <div class="text-3xl mb-3">✅</div>
                <div class="text-3xl font-bold text-emerald-400">{{ stats.accepted_requests }}</div>
                <div class="text-gray-500 text-sm mt-1">Request Disetujui</div>
            </div>

            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5">
                <div class="text-3xl mb-3">❌</div>
                <div class="text-3xl font-bold text-red-400">{{ stats.rejected_requests }}</div>
                <div class="text-gray-500 text-sm mt-1">Request Ditolak</div>
            </div>

            <div class="bg-gray-900 border border-white/5 rounded-2xl p-5">
                <div class="text-3xl mb-3">📊</div>
                <div class="text-3xl font-bold text-white">{{ stats.total_requests }}</div>
                <div class="text-gray-500 text-sm mt-1">Total Request</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            <Link :href="route('admin.nodes.create')"
                  class="bg-indigo-500/10 hover:bg-indigo-500/15 border border-indigo-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">➕</div>
                <div class="text-indigo-300 text-sm font-medium">Tambah Node</div>
            </Link>
            <Link :href="route('admin.requests.index')"
                  class="bg-amber-500/10 hover:bg-amber-500/15 border border-amber-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📬</div>
                <div class="text-amber-300 text-sm font-medium">Request Masuk</div>
            </Link>
            <Link :href="route('admin.nodes.index')"
                  class="bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🌳</div>
                <div class="text-emerald-300 text-sm font-medium">Kelola Node</div>
            </Link>
            <Link :href="route('tree.index')"
                  class="bg-violet-500/10 hover:bg-violet-500/15 border border-violet-500/20 rounded-xl p-4 text-center transition-all group">
                <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🌐</div>
                <div class="text-violet-300 text-sm font-medium">Lihat Website</div>
            </Link>
        </div>

        <!-- Recent Requests -->
        <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-white font-semibold">Request Terbaru</h3>
                <Link :href="route('admin.requests.index')" class="text-amber-400 text-sm hover:text-amber-300 transition-colors">Lihat semua →</Link>
            </div>
            <div class="divide-y divide-white/5">
                <div v-if="recentRequests.length === 0" class="py-12 text-center text-gray-600">
                    Belum ada request masuk
                </div>
                <div v-for="req in recentRequests" :key="req.id"
                     class="flex items-center gap-4 px-5 py-3 hover:bg-white/2 transition-colors">
                    <div class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center text-lg flex-shrink-0">
                        {{ req.gender === 'female' ? '👩' : '👨' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white text-sm font-medium truncate">{{ req.name }}</div>
                        <div class="text-gray-500 text-xs">
                            Di bawah: {{ req.parent_node?.name }} · {{ req.requester_email }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span :class="['text-xs px-2 py-0.5 rounded-full border', statusClass(req.status)]">
                            {{ statusLabel(req.status) }}
                        </span>
                        <span class="text-gray-600 text-xs">{{ formatDate(req.created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
