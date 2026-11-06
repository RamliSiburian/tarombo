<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    members: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || '');

let searchTimeout = null;
watch(search, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilter();
    }, 350);
});

const applyFilter = (statusVal = currentStatus.value) => {
    currentStatus.value = statusVal;
    router.get(
        route('admin.members.index'),
        {
            search: search.value || undefined,
            status: currentStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const approve = (user) => {
    if (confirm(`Setujui pendaftaran anggota "${user.name}"?`)) {
        router.post(route('admin.members.approve', user.id), {}, {
            preserveScroll: true,
        });
    }
};

const reject = (user) => {
    if (confirm(`Tolak pendaftaran anggota "${user.name}"?`)) {
        router.post(route('admin.members.reject', user.id), {}, {
            preserveScroll: true,
        });
    }
};

const destroy = (user) => {
    if (confirm(`Hapus permanen akun anggota "${user.name}"? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('admin.members.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const cleanPhone = (phone) => {
    if (!phone) return '';
    let p = phone.replace(/\D/g, '');
    if (p.startsWith('0')) p = '62' + p.slice(1);
    return p;
};
</script>

<template>
    <Head title="Daftar Anggota — Admin Panel" />

    <AdminLayout>
        <template #title>Manajemen Anggota</template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- ═══ HEADER & SUMMARY ═══ -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Daftar Anggota Komunitas</h1>
                    <p class="text-sm text-gray-400 mt-1">
                        Kelola data pendaftaran anggota, tinjau verifikasi email, dan berikan persetujuan keanggotaan.
                    </p>
                </div>
                <div v-if="stats.pending > 0" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-500/10 border border-primary-500/30 text-primary-400 text-xs font-semibold animate-pulse">
                    <span>⚠️</span>
                    <span>{{ stats.pending }} Pendaftaran Menunggu Persetujuan</span>
                </div>
            </div>

            <!-- ═══ STATS CARDS ═══ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total -->
                <button
                    @click="applyFilter('')"
                    :class="[
                        'p-5 rounded-2xl border text-left transition-all',
                        currentStatus === '' ? 'bg-primary-500/10 border-primary-500/40 shadow-lg shadow-primary-500/5' : 'bg-gray-900 border-white/5 hover:border-white/20'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400 text-xs font-medium uppercase tracking-wider">Total Anggota</span>
                        <span class="text-xl">👥</span>
                    </div>
                    <div class="text-3xl font-bold text-white">{{ stats.total }}</div>
                    <div class="text-xs text-gray-500 mt-1">Semua pendaftar</div>
                </button>

                <!-- Pending -->
                <button
                    @click="applyFilter('pending')"
                    :class="[
                        'p-5 rounded-2xl border text-left transition-all',
                        currentStatus === 'pending' ? 'bg-primary-500/15 border-primary-500 shadow-lg shadow-primary-500/10' : 'bg-gray-900 border-white/5 hover:border-primary-500/30'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-primary-400 text-xs font-medium uppercase tracking-wider">Menunggu Persetujuan</span>
                        <span class="text-xl">⏳</span>
                    </div>
                    <div class="text-3xl font-bold text-primary-400">{{ stats.pending }}</div>
                    <div class="text-xs text-primary-400/70 mt-1">Perlu ditinjau admin</div>
                </button>

                <!-- Approved -->
                <button
                    @click="applyFilter('approved')"
                    :class="[
                        'p-5 rounded-2xl border text-left transition-all',
                        currentStatus === 'approved' ? 'bg-emerald-500/15 border-emerald-500 shadow-lg shadow-emerald-500/10' : 'bg-gray-900 border-white/5 hover:border-emerald-500/30'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-emerald-400 text-xs font-medium uppercase tracking-wider">Disetujui / Aktif</span>
                        <span class="text-xl">✅</span>
                    </div>
                    <div class="text-3xl font-bold text-emerald-400">{{ stats.approved }}</div>
                    <div class="text-xs text-emerald-400/70 mt-1">Anggota resmi</div>
                </button>

                <!-- Rejected -->
                <button
                    @click="applyFilter('rejected')"
                    :class="[
                        'p-5 rounded-2xl border text-left transition-all',
                        currentStatus === 'rejected' ? 'bg-red-500/15 border-red-500 shadow-lg shadow-red-500/10' : 'bg-gray-900 border-white/5 hover:border-red-500/30'
                    ]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-red-400 text-xs font-medium uppercase tracking-wider">Ditolak</span>
                        <span class="text-xl">❌</span>
                    </div>
                    <div class="text-3xl font-bold text-red-400">{{ stats.rejected }}</div>
                    <div class="text-xs text-red-400/70 mt-1">Tidak disetujui</div>
                </button>
            </div>

            <!-- ═══ FILTER & SEARCH BAR ═══ -->
            <div class="p-4 rounded-2xl bg-gray-900/80 border border-white/5 flex flex-col md:flex-row gap-4 items-center justify-between">
                <!-- Search input -->
                <div class="relative w-full md:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-500 pointer-events-none">🔍</span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama, marga, email, no HP..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-950 border border-white/10 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-primary-500/50 transition-colors"
                    />
                </div>

                <!-- Tabs Filter -->
                <div class="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                    <button
                        @click="applyFilter('')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap',
                            currentStatus === '' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white hover:bg-white/5'
                        ]"
                    >
                        Semua ({{ stats.total }})
                    </button>
                    <button
                        @click="applyFilter('pending')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap flex items-center gap-1.5',
                            currentStatus === 'pending' ? 'bg-primary-500 text-gray-950 font-bold' : 'text-primary-400/80 hover:text-primary-300 hover:bg-primary-500/10'
                        ]"
                    >
                        <span>Menunggu</span>
                        <span v-if="stats.pending > 0" class="px-1.5 py-0.2 rounded-full text-[10px] bg-primary-400 text-gray-950 font-black">
                            {{ stats.pending }}
                        </span>
                    </button>
                    <button
                        @click="applyFilter('approved')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap',
                            currentStatus === 'approved' ? 'bg-emerald-500 text-gray-950 font-bold' : 'text-emerald-400/80 hover:text-emerald-300 hover:bg-emerald-500/10'
                        ]"
                    >
                        Disetujui ({{ stats.approved }})
                    </button>
                    <button
                        @click="applyFilter('rejected')"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap',
                            currentStatus === 'rejected' ? 'bg-red-500 text-gray-950 font-bold' : 'text-red-400/80 hover:text-red-300 hover:bg-red-500/10'
                        ]"
                    >
                        Ditolak ({{ stats.rejected }})
                    </button>
                </div>
            </div>

            <!-- ═══ TABLE LIST ═══ -->
            <div class="bg-gray-900/60 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-300">
                        <thead class="bg-gray-900 border-b border-white/5 text-xs text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="px-6 py-4">Anggota</th>
                                <th class="px-6 py-4">Email & Verifikasi</th>
                                <th class="px-6 py-4">Kontak</th>
                                <th class="px-6 py-4">Terdaftar</th>
                                <th class="px-6 py-4">Status Persetujuan</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="members.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="text-4xl mb-3">👥</div>
                                    <div class="text-base font-semibold text-gray-400">Tidak ada data anggota ditemukan</div>
                                    <p class="text-xs text-gray-600 mt-1">Coba ubah kata kunci pencarian atau filter status.</p>
                                </td>
                            </tr>

                            <tr
                                v-for="member in members.data"
                                :key="member.id"
                                class="hover:bg-white/[0.02] transition-colors"
                            >
                                <!-- Anggota -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500/20 to-primary-700/20 border border-primary-500/30 flex items-center justify-center text-primary-400 font-bold text-sm flex-shrink-0">
                                            {{ member.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ member.name }}</div>
                                            <div v-if="member.marga" class="text-xs text-primary-400 font-medium">
                                                Marga {{ member.marga }}
                                            </div>
                                            <div v-else class="text-xs text-gray-600 italic">Tanpa marga</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email & Verifikasi -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-300">{{ member.email }}</div>
                                    <div class="mt-1">
                                        <span
                                            v-if="member.email_verified_at"
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full"
                                            title="Email terverifikasi"
                                        >
                                            <span>✓</span> Email Terverifikasi
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-400 bg-gray-800 border border-white/5 px-2 py-0.5 rounded-full"
                                            title="Belum memverifikasi email"
                                        >
                                            <span>⏳</span> Belum Verifikasi
                                        </span>
                                    </div>
                                </td>

                                <!-- Kontak / No HP -->
                                <td class="px-6 py-4">
                                    <div v-if="member.phone" class="flex items-center gap-2">
                                        <span class="text-sm text-gray-300 font-mono">{{ member.phone }}</span>
                                        <a
                                            :href="`https://wa.me/${cleanPhone(member.phone)}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-xs px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-colors"
                                            title="Hubungi via WhatsApp"
                                        >
                                            WA ↗
                                        </a>
                                    </div>
                                    <span v-else class="text-xs text-gray-600">—</span>
                                </td>

                                <!-- Tanggal Daftar -->
                                <td class="px-6 py-4 text-xs text-gray-400">
                                    {{ formatDate(member.created_at) }}
                                </td>

                                <!-- Status Persetujuan -->
                                <td class="px-6 py-4">
                                    <!-- Pending -->
                                    <span
                                        v-if="member.membership_status === 'pending'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary-500/15 border border-primary-500/30 text-primary-400"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary-400 animate-pulse"></span>
                                        Menunggu Persetujuan
                                    </span>
                                    <!-- Approved -->
                                    <span
                                        v-else-if="member.membership_status === 'approved'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 border border-emerald-500/30 text-emerald-400"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Disetujui
                                    </span>
                                    <!-- Rejected -->
                                    <span
                                        v-else-if="member.membership_status === 'rejected'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/15 border border-red-500/30 text-red-400"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Ditolak
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Setujui -->
                                        <button
                                            v-if="member.membership_status !== 'approved'"
                                            @click="approve(member)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500 hover:text-gray-950 transition-all"
                                            title="Setujui Keanggotaan"
                                        >
                                            ✓ Setujui
                                        </button>

                                        <!-- Tombol Tolak -->
                                        <button
                                            v-if="member.membership_status !== 'rejected'"
                                            @click="reject(member)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/15 border border-red-500/30 text-red-400 hover:bg-red-500 hover:text-white transition-all"
                                            title="Tolak Keanggotaan"
                                        >
                                            ✕ Tolak
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <button
                                            @click="destroy(member)"
                                            class="p-1 rounded-lg text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                            title="Hapus Anggota"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ═══ PAGINATION ═══ -->
                <div v-if="members.links && members.links.length > 3" class="px-6 py-4 border-t border-white/5 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ members.from || 0 }} - {{ members.to || 0 }} dari {{ members.total }} anggota
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in members.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs transition-colors',
                                link.active ? 'bg-primary-500 text-gray-950 font-bold' : 'text-gray-400 hover:text-white hover:bg-white/5',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
