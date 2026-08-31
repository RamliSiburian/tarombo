<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
    pendingCount: Number,
});

const activeStatus = ref(props.filters?.status || 'pending');
const rejectModal = ref(null);
const acceptModal = ref(null);
const noteForm = useForm({ admin_note: '' });

const setStatus = (s) => {
    activeStatus.value = s;
    router.get(route('admin.requests.index'), { status: s }, { preserveState: true, replace: true });
};

const openAccept = (req) => {
    acceptModal.value = req;
    noteForm.admin_note = '';
};

const openReject = (req) => {
    rejectModal.value = req;
    noteForm.admin_note = '';
};

const submitAccept = () => {
    noteForm.post(route('admin.requests.accept', acceptModal.value.id), {
        onSuccess: () => { acceptModal.value = null; noteForm.reset(); },
    });
};

const submitReject = () => {
    noteForm.post(route('admin.requests.reject', rejectModal.value.id), {
        onSuccess: () => { rejectModal.value = null; noteForm.reset(); },
    });
};

const deleteRequest = (req) => {
    if (confirm('Hapus permintaan ini secara permanen?')) {
        router.delete(route('admin.requests.destroy', req.id));
    }
};

const statusTabs = [
    { key: 'pending', label: 'Pending', icon: '⏳' },
    { key: 'accepted', label: 'Disetujui', icon: '✅' },
    { key: 'rejected', label: 'Ditolak', icon: '❌' },
    { key: 'all', label: 'Semua', icon: '📋' },
];

const genderIcon = (g) => g === 'female' ? '👩' : '👨';
const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Request Masuk" />
    <AdminLayout>
        <template #title>
            Request Masuk
            <span v-if="pendingCount > 0" class="ml-2 bg-amber-500 text-gray-900 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ pendingCount }}
            </span>
        </template>

        <!-- Tabs -->
        <div class="flex gap-1 mb-6 bg-gray-900 border border-white/5 p-1 rounded-xl w-fit">
            <button v-for="tab in statusTabs" :key="tab.key" @click="setStatus(tab.key)"
                    :class="['px-4 py-2 rounded-lg text-sm font-medium transition-all', activeStatus === tab.key ? 'bg-amber-500 text-gray-900' : 'text-gray-400 hover:text-white']">
                {{ tab.icon }} {{ tab.label }}
                <span v-if="tab.key === 'pending' && pendingCount > 0"
                      class="ml-1.5 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                    {{ pendingCount }}
                </span>
            </button>
        </div>

        <!-- Requests List -->
        <div class="space-y-3">
            <div v-if="requests.data.length === 0" class="text-center py-16 bg-gray-900 border border-white/5 rounded-2xl">
                <div class="text-4xl mb-3">📭</div>
                <p class="text-gray-500">Tidak ada request untuk filter ini</p>
            </div>

            <div v-for="req in requests.data" :key="req.id"
                 class="bg-gray-900 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                <div class="flex flex-col md:flex-row md:items-start gap-4">
                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-xl bg-gray-800 flex items-center justify-center text-2xl flex-shrink-0">
                        {{ genderIcon(req.gender) }}
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h3 class="text-white font-semibold">{{ req.name }}</h3>
                            <span v-if="req.marga" class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">
                                Marga {{ req.marga }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-1 text-xs text-gray-500 mb-3">
                            <div>👤 Pemohon: <span class="text-gray-300">{{ req.requester_name }}</span></div>
                            <div>📧 <span class="text-gray-300">{{ req.requester_email }}</span></div>
                            <div>🌳 Di bawah: <span class="text-gray-300">{{ req.parent_node?.name }}</span></div>
                            <div>📅 <span class="text-gray-300">{{ formatDate(req.created_at) }}</span></div>
                            <div v-if="req.asal_daerah">📍 <span class="text-gray-300">{{ req.asal_daerah }}</span></div>
                            <div v-if="req.marga">🏷️ <span class="text-gray-300">Marga {{ req.marga }}</span></div>
                        </div>

                        <div v-if="req.deskripsi" class="text-xs text-gray-500 bg-white/3 rounded-lg px-3 py-2 mb-3">
                            {{ req.deskripsi }}
                        </div>

                        <!-- Spouse Info -->
                        <div v-if="req.spouse_name" class="text-xs bg-pink-500/5 border border-pink-500/15 rounded-lg px-3 py-2 mb-3">
                            <span class="text-pink-400">💑 Istri: </span>
                            <span class="text-pink-300 font-medium">{{ req.spouse_name }}</span>
                            <span v-if="req.spouse_marga" class="text-pink-500"> (Marga {{ req.spouse_marga }})</span>
                        </div>

                        <!-- Admin Note -->
                        <div v-if="req.admin_note" class="text-xs bg-amber-500/5 border border-amber-500/15 rounded-lg px-3 py-2">
                            <span class="text-amber-400">📝 Catatan Admin: </span>
                            <span class="text-amber-300">{{ req.admin_note }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex md:flex-col gap-2 flex-shrink-0">
                        <template v-if="req.status === 'pending'">
                            <button @click="openAccept(req)"
                                    class="bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-400 text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                                ✓ Setujui
                            </button>
                            <button @click="openReject(req)"
                                    class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                                ✗ Tolak
                            </button>
                        </template>
                        <span v-else-if="req.status === 'accepted'"
                              class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-lg text-center">
                            ✅ Disetujui
                        </span>
                        <span v-else-if="req.status === 'rejected'"
                              class="text-xs bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-lg text-center">
                            ❌ Ditolak
                        </span>
                        <button @click="deleteRequest(req)"
                                class="text-xs text-gray-600 hover:text-red-400 transition-colors px-2 py-1 text-center">
                            🗑 Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="requests.links?.length > 3" class="flex items-center gap-1 mt-4">
            <template v-for="link in requests.links" :key="link.label">
                <Link v-if="link.url" :href="link.url"
                      :class="['px-3 py-1.5 rounded-lg text-xs transition-colors', link.active ? 'bg-amber-500 text-gray-900 font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/5']"
                      v-html="link.label" />
            </template>
        </div>

        <!-- Accept Modal -->
        <div v-if="acceptModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 class="text-white font-bold text-lg mb-2">✅ Setujui Request</h3>
                <p class="text-gray-400 text-sm mb-4">
                    Anda akan menyetujui request dari <strong class="text-white">{{ acceptModal.name }}</strong> untuk bergabung di bawah <strong class="text-white">{{ acceptModal.parent_node?.name }}</strong>.
                    Data akan otomatis muncul di pohon silsilah publik.
                </p>
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-1.5">Catatan untuk pemohon (opsional)</label>
                    <textarea v-model="noteForm.admin_note" rows="2" placeholder="Catatan atau pesan untuk pemohon..."
                              class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-emerald-500/40 resize-none transition-colors"></textarea>
                </div>
                <div class="flex gap-3">
                    <button @click="acceptModal = null" class="flex-1 border border-white/10 text-gray-400 hover:text-white rounded-xl py-2.5 text-sm transition-colors">Batal</button>
                    <button @click="submitAccept" :disabled="noteForm.processing"
                            class="flex-1 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-semibold rounded-xl py-2.5 text-sm transition-all">
                        {{ noteForm.processing ? 'Memproses...' : '✓ Konfirmasi Setujui' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 max-w-md w-full shadow-2xl">
                <h3 class="text-white font-bold text-lg mb-2">❌ Tolak Request</h3>
                <p class="text-gray-400 text-sm mb-4">
                    Anda akan menolak request dari <strong class="text-white">{{ rejectModal.name }}</strong>. Pemohon akan menerima email pemberitahuan penolakan.
                </p>
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-1.5">Alasan penolakan (opsional)</label>
                    <textarea v-model="noteForm.admin_note" rows="3" placeholder="Jelaskan alasan penolakan untuk pemohon..."
                              class="w-full bg-gray-800 border border-white/10 rounded-xl px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-red-500/40 resize-none transition-colors"></textarea>
                </div>
                <div class="flex gap-3">
                    <button @click="rejectModal = null" class="flex-1 border border-white/10 text-gray-400 hover:text-white rounded-xl py-2.5 text-sm transition-colors">Batal</button>
                    <button @click="submitReject" :disabled="noteForm.processing"
                            class="flex-1 bg-red-500 hover:bg-red-400 disabled:opacity-50 text-white font-semibold rounded-xl py-2.5 text-sm transition-all">
                        {{ noteForm.processing ? 'Memproses...' : '✗ Konfirmasi Tolak' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
