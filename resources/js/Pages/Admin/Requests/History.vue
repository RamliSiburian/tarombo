<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    requests: Object,
});

const statusLabel = (s) => ({ accepted: 'Disetujui', rejected: 'Ditolak' }[s] || s);
const statusClass = (s) => ({
    accepted: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    rejected: 'bg-red-500/15 text-red-400 border-red-500/30',
}[s] || '');

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
}) : '—';

const genderIcon = (g) => g === 'female' ? '👩' : '👨';
</script>

<template>
    <Head title="Riwayat Request" />
    <AdminLayout>
        <template #title>Riwayat Request</template>

        <div class="mb-4 flex items-center justify-between">
            <p class="text-gray-500 text-sm">Riwayat semua request yang telah ditinjau oleh admin.</p>
            <Link :href="route('admin.requests.index')" class="text-amber-400 hover:text-amber-300 text-sm transition-colors">
                ← Request Aktif
            </Link>
        </div>

        <!-- Table -->
        <div class="bg-gray-900 border border-white/5 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs text-gray-500 font-medium px-5 py-3 uppercase tracking-wider">Nama</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden md:table-cell">Pemohon</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden lg:table-cell">Di Bawah</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider">Status</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden xl:table-cell">Ditinjau Oleh</th>
                        <th class="text-left text-xs text-gray-500 font-medium px-4 py-3 uppercase tracking-wider hidden lg:table-cell">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="requests.data.length === 0">
                        <td colspan="6" class="py-16 text-center text-gray-600">Belum ada riwayat review</td>
                    </tr>
                    <tr v-for="req in requests.data" :key="req.id" class="hover:bg-white/2 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span>{{ genderIcon(req.gender) }}</span>
                                <div>
                                    <div class="text-white text-sm font-medium">{{ req.name }}</div>
                                    <div v-if="req.marga" class="text-gray-600 text-xs">Marga {{ req.marga }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div class="text-gray-400 text-sm">{{ req.requester_name }}</div>
                            <div class="text-gray-600 text-xs">{{ req.requester_email }}</div>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="text-gray-400 text-sm">{{ req.parent_node?.name || '—' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <span :class="['text-xs px-2 py-0.5 rounded-full border', statusClass(req.status)]">
                                    {{ statusLabel(req.status) }}
                                </span>
                                <div v-if="req.admin_note" class="text-gray-600 text-xs mt-1 max-w-xs truncate" :title="req.admin_note">
                                    📝 {{ req.admin_note }}
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <span class="text-gray-500 text-sm">{{ req.reviewer?.name || '—' }}</span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="text-gray-500 text-xs">{{ formatDate(req.reviewed_at) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="requests.links?.length > 3" class="px-5 py-3 border-t border-white/5 flex items-center gap-1">
                <template v-for="link in requests.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                          :class="['px-3 py-1.5 rounded-lg text-xs transition-colors', link.active ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/5']"
                          v-html="link.label" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
