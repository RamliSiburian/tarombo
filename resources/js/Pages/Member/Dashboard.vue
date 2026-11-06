<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    node: Object,
    ancestors: {
        type: Array,
        default: () => [],
    },
    motherName: {
        type: String,
        default: null,
    },
    pendingRequest: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const justVerified = computed(() => {
    return new URLSearchParams(window.location.search).get('verified') === '1';
});

const isApproved = computed(() => {
    return props.user?.membership_status === 'approved' && props.node;
});

const isPending = computed(() => {
    return props.user?.membership_status === 'pending' || (!props.node && props.pendingRequest);
});

// Format member/KK number
const kkNumber = computed(() => {
    if (!props.node) return '—';
    const year = new Date().getFullYear();
    const idPad = String(props.node.id).padStart(5, '0');
    return `TB-${idPad}-${year}`;
});

const formatBoru = (marga) => {
    if (!marga) return '';
    const clean = marga.trim();
    if (/^br[\.\s]/i.test(clean)) {
        return clean;
    }
    return `br. ${clean}`;
};

// Rows for Kartu Keluarga table: [No, Nama, Tanggal Lahir, Istri/Pasangan, Ayah, Ibu]
const kkRows = computed(() => {
    if (!props.node) return [];

    const rows = [];
    const n = props.node;
    const fatherName = n.parent ? n.parent.name : '—';
    const motherStr = props.motherName || '—';
    const spouseListStr = n.spouses && n.spouses.length > 0
        ? n.spouses.map(s => s.name + (s.marga ? ` ${formatBoru(s.marga)}` : '')).join(', ')
        : '—';

    // 1. Kepala Keluarga (Anggota)
    rows.push({
        no: 1,
        nama: `${n.name} ${n.marga ? '(' + n.marga + ')' : ''}`,
        tanggal_lahir: n.tahun_lahir || '—',
        istri: spouseListStr,
        ayah: fatherName,
        ibu: motherStr,
        role: 'Kepala Keluarga',
    });

    // 2. Istri / Pasangan (kolom istri diisi tanda —)
    if (n.spouses && n.spouses.length > 0) {
        n.spouses.forEach((sp, idx) => {
            rows.push({
                no: rows.length + 1,
                nama: `${sp.name} ${sp.marga ? formatBoru(sp.marga) : ''}`,
                tanggal_lahir: sp.tahun_lahir || '—',
                istri: '—',
                ayah: '—',
                ibu: '—',
                role: `Istri ke-${idx + 1}`,
            });
        });
    }

    // 3. Anak-anak
    if (n.active_children && n.active_children.length > 0) {
        n.active_children.forEach((ch) => {
            const chSpouses = ch.spouses && ch.spouses.length > 0
                ? ch.spouses.map(s => s.name + (s.marga ? ` ${formatBoru(s.marga)}` : '')).join(', ')
                : '—';

            rows.push({
                no: rows.length + 1,
                nama: `${ch.name} ${ch.marga ? '(' + ch.marga + ')' : ''}`,
                tanggal_lahir: ch.tahun_lahir || '—',
                istri: chSpouses,
                ayah: n.name,
                ibu: n.spouses?.[0]?.name ? `${n.spouses[0].name} ${n.spouses[0].marga ? formatBoru(n.spouses[0].marga) : ''}` : motherStr,
                role: ch.gender === 'female' ? 'Anak (Perempuan)' : 'Anak (Laki-laki)',
            });
        });
    }

    return rows;
});

const printKK = () => {
    window.print();
};
</script>

<template>
    <Head title="Dashboard Anggota — Punguan Sihotang" />

    <div class="min-h-screen bg-gray-950 text-white" style="font-family: 'Inter', sans-serif;">
        <!-- Navbar (hidden on print) -->
        <nav class="print:hidden fixed top-0 left-0 right-0 z-50 bg-gray-900/90 backdrop-blur-md border-b border-white/10 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-2.5 group">
                        <img src="/images/logo.png" alt="Logo" class="w-8 h-8 object-contain group-hover:scale-105 transition-transform" />
                        <div>
                            <span class="text-xs sm:text-sm font-bold text-white tracking-wider block" style="font-family: 'Cinzel', serif;">PUNGUAN SIHOTANG</span>
                            <span class="text-[9px] text-primary-400/80 tracking-widest uppercase block -mt-0.5">Dohot Boruna</span>
                        </div>
                    </Link>
                    <div class="flex items-center gap-3">
                        <Link :href="route('tree.index')" class="text-xs text-gray-300 hover:text-primary-400 px-3 py-1.5 rounded-lg border border-white/10 hover:border-primary-500/30 transition-colors">
                            🌳 Lihat Pohon Silsilah
                        </Link>
                        <span class="text-xs text-gray-400 hidden sm:inline">{{ user?.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="px-3 py-1.5 text-xs border border-white/20 text-gray-300 hover:border-red-500/50 hover:text-red-400 rounded-lg transition-all"
                        >
                            Keluar
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="pt-24 pb-16 max-w-6xl mx-auto px-4 sm:px-6 print:p-0 print:pt-0 print:max-w-full">

            <!-- Toast verified if just landed from email link -->
            <div v-if="justVerified" class="print:hidden mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 flex items-center gap-3 shadow-lg">
                <span class="text-2xl">🎉</span>
                <div>
                    <div class="font-bold text-sm">Alamat Email Anda Berhasil Diverifikasi!</div>
                    <div class="text-xs text-emerald-200/80 mt-0.5">Akun keanggotaan Anda telah aktif dan permohonan silsilah Anda siap diverifikasi oleh Admin.</div>
                </div>
            </div>

            <!-- Header Welcome Card (print:hidden) -->
            <div class="print:hidden mb-8 p-6 rounded-2xl bg-gradient-to-r from-primary-950/40 via-gray-900 to-red-950/30 border border-primary-500/20 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-primary-500/20 border border-primary-500/30 flex items-center justify-center text-3xl flex-shrink-0">
                            👤
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-primary-500/10 border border-primary-500/20 text-primary-400 text-xs font-semibold mb-1">
                                🌟 Anggota Terdaftar
                            </div>
                            <h1 class="text-2xl font-bold text-white mb-0.5">Horas, {{ user?.name }}!</h1>
                            <p class="text-gray-400 text-xs sm:text-sm">
                                {{ user?.email }} · <span v-if="user?.marga" class="text-primary-400 font-medium">Marga {{ user.marga }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span v-if="user?.email_verified_at" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Email Terverifikasi
                        </span>
                        <span v-if="isApproved" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-500/15 border border-primary-500/30 text-primary-400 text-xs font-semibold">
                            ✅ Silsilah Disetujui
                        </span>
                    </div>
                </div>
            </div>

            <!-- STATUS: PENDING APPROVAL (print:hidden) -->
            <div v-if="isPending" class="print:hidden space-y-6">
                <!-- Status Banner -->
                <div class="p-6 rounded-2xl bg-primary-500/10 border border-primary-500/30 shadow-xl space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-500/20 text-primary-400 flex items-center justify-center text-2xl flex-shrink-0">
                            ⏳
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-primary-300">Permohonan Silsilah Sedang Ditinjau Admin</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-400/20 text-primary-300">Menunggu Persetujuan</span>
                            </div>
                            <p class="text-xs sm:text-sm text-primary-200/80 mt-1 leading-relaxed">
                                Email Anda telah terverifikasi dengan aman. Saat ini permohonan data silsilah Anda sedang dalam tahap peninjauan dan penempatan oleh tim pengelola (Admin Tarombo). Garis keturunan lengkap dan Kartu Keluarga Anda akan muncul otomatis di dashboard ini segera setelah disetujui.
                            </p>
                        </div>
                    </div>

                    <!-- Flow Stepper -->
                    <div class="pt-3 border-t border-primary-500/20 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-gray-900/60 p-3.5 rounded-xl border border-emerald-500/30 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">✓</div>
                            <div>
                                <div class="text-xs text-emerald-300 font-semibold">1. Pengajuan Data</div>
                                <div class="text-[11px] text-gray-400">Data silsilah dikirim</div>
                            </div>
                        </div>
                        <div class="bg-gray-900/60 p-3.5 rounded-xl border border-emerald-500/30 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">✓</div>
                            <div>
                                <div class="text-xs text-emerald-300 font-semibold">2. Verifikasi Email</div>
                                <div class="text-[11px] text-gray-400">Email terverifikasi</div>
                            </div>
                        </div>
                        <div class="bg-gray-900/60 p-3.5 rounded-xl border border-primary-500/40 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-500/20 text-primary-400 flex items-center justify-center font-bold text-sm animate-pulse">3</div>
                            <div>
                                <div class="text-xs text-primary-300 font-semibold">3. Tinjauan Admin</div>
                                <div class="text-[11px] text-primary-200/70">Sedang diperiksa</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Request Yang Diajukan -->
                <div v-if="pendingRequest" class="p-6 rounded-2xl bg-gray-900/60 border border-white/10 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📋</span>
                        <span>Detail Pengajuan Silsilah Anda</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Nama Diajukan</span>
                            <span class="text-white font-semibold text-sm">{{ pendingRequest.name }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Marga</span>
                            <span class="text-white font-semibold text-sm">{{ pendingRequest.marga || '—' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Ayah / Leluhur</span>
                            <span class="text-primary-400 font-semibold text-sm">{{ pendingRequest.parent_node?.name || '—' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Jenis Kelamin</span>
                            <span class="text-white">{{ pendingRequest.gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Tahun Lahir</span>
                            <span class="text-white">{{ pendingRequest.tahun_lahir || '—' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                            <span class="text-gray-400 block mb-0.5">Waktu Pengajuan</span>
                            <span class="text-white">{{ new Date(pendingRequest.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATUS: REJECTED (print:hidden) -->
            <div v-else-if="user?.membership_status === 'rejected'" class="print:hidden p-6 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-start gap-4">
                <span class="text-3xl flex-shrink-0">❌</span>
                <div>
                    <h3 class="text-base font-bold text-red-400">Permohonan Silsilah Belum Disetujui</h3>
                    <p class="text-xs sm:text-sm text-red-200/80 mt-1 leading-relaxed">
                        Mohon maaf, pengajuan silsilah Anda belum dapat disetujui oleh pengelola silsilah. Silakan hubungi admin atau ajukan kembali jika ada revisi nama/leluhur.
                    </p>
                    <Link :href="route('request.create')" class="inline-block mt-3 px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/30 text-xs font-semibold rounded-xl transition-colors">
                        Ajukan Ulang Silsilah →
                    </Link>
                </div>
            </div>

            <!-- STATUS: APPROVED & ACTIVE (LINEAGE + KARTU KELUARGA PRINT) -->
            <div v-else-if="isApproved" class="space-y-8">

                <!-- 1. GARIS KETURUNAN (ANCESTRAL LINEAGE) (print:hidden) -->
                <div class="print:hidden bg-gray-900/70 border border-white/10 rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/10">
                        <div>
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <span>🌳</span>
                                <span>Garis Keturunan Silsilah (Tarombo Leluhur)</span>
                            </h2>
                            <p class="text-xs text-gray-400 mt-0.5">Urutan silsilah lurus dari leluhur generasi awal hingga ke diri Anda.</p>
                        </div>
                        <Link :href="route('tree.index') + '?node=' + node.id" class="px-3.5 py-1.5 rounded-xl bg-primary-500/15 hover:bg-primary-500/25 border border-primary-500/30 text-primary-300 text-xs font-semibold transition-colors flex items-center gap-1.5">
                            <span>🔍 Buka di Pohon Interaktif</span>
                        </Link>
                    </div>

                    <!-- Ancestor Chain Cards -->
                    <div class="space-y-2">
                        <div v-for="(anc, index) in ancestors" :key="anc.id" class="relative flex items-center gap-3">
                            <!-- Generation pill -->
                            <div class="w-8 h-8 rounded-full bg-gray-800 border border-primary-500/30 text-primary-400 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                {{ index + 1 }}
                            </div>

                            <!-- Ancestor Card -->
                            <div class="flex-1 bg-white/5 hover:bg-white/10 border border-white/5 rounded-xl p-3 flex items-center justify-between transition-colors">
                                <div>
                                    <div class="text-white font-semibold text-sm">
                                        {{ anc.name }}
                                        <span v-if="anc.marga" class="text-primary-400 text-xs font-normal">({{ anc.marga }})</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        Generasi ke-{{ (anc.level || 0) + 1 }}
                                        <span v-if="anc.spouses && anc.spouses.length > 0" class="text-pink-300 ml-2">
                                            👩 Istri: {{ anc.spouses.map(s => s.name).join(', ') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500 font-mono">Leluhur</span>
                            </div>
                        </div>

                        <!-- Current Member Card (You) -->
                        <div class="relative flex items-center gap-3 pt-1">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-primary-500 to-primary-600 text-gray-950 text-xs font-black flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary-500/30">
                                ⭐
                            </div>
                            <div class="flex-1 bg-gradient-to-r from-primary-500/20 via-primary-600/10 to-transparent border border-primary-500/40 rounded-xl p-3.5 flex items-center justify-between shadow-md">
                                <div>
                                    <div class="text-white font-bold text-base flex items-center gap-2">
                                        <span>{{ node.name }}</span>
                                        <span v-if="node.marga" class="text-xs bg-primary-500 text-gray-950 font-bold px-2 py-0.5 rounded-full">
                                            Marga {{ node.marga }}
                                        </span>
                                        <span class="text-xs text-primary-300 font-normal">(Anda)</span>
                                    </div>
                                    <div class="text-xs text-gray-300 mt-0.5">
                                        Generasi ke-{{ (node.level || 0) + 1 }}
                                        <span v-if="motherName" class="text-pink-300 ml-2">👩 Ibu: {{ motherName }}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-primary-400 bg-primary-500/10 px-2.5 py-1 rounded-lg border border-primary-500/20">
                                    Anggota Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. KARTU ANGGOTA FORMAT KARTU KELUARGA (KK) -->
                <div class="space-y-4">
                    <!-- Action Bar (print:hidden) -->
                    <div class="print:hidden flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-900/80 border border-white/10 rounded-2xl p-4 shadow-xl">
                        <div>
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <span>🪪</span>
                                <span>Kartu Anggota (Format Kartu Keluarga)</span>
                            </h2>
                            <p class="text-xs text-gray-400">Format resmi data keluarga anggota, siap dicetak atau disimpan sebagai dokumen PDF.</p>
                        </div>
                        <button
                            type="button"
                            @click="printKK"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-400 hover:to-primary-500 text-gray-950 font-bold text-xs sm:text-sm transition-all shadow-lg shadow-primary-500/20 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>🖨️</span>
                            <span>Cetak / Simpan PDF</span>
                        </button>
                    </div>

                    <!-- PRINTABLE KARTU KELUARGA CONTAINER -->
                    <!-- In web mode: Dark Batak theme card. In print mode: Clean white A4 paper format -->
                    <div id="kartu-keluarga-area"
                         class="bg-gray-900/90 border-2 border-primary-500/30 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden print:border print:border-black print:bg-white print:text-black print:p-6 print:rounded-none print:shadow-none print:m-0">

                        <!-- KK Decorative Batak Gorga Header Border -->
                        <div class="h-2 w-full bg-gradient-to-r from-red-600 via-primary-500 to-black rounded-full mb-6 print:bg-black print:h-1"></div>

                        <!-- KK Header -->
                        <div class="text-center pb-6 border-b-2 border-primary-500/20 print:border-b-2 print:border-black mb-6">
                            <div class="flex items-center justify-center gap-3 mb-2">
                                <img src="/images/logo.png" alt="Logo" class="w-12 h-12 object-contain" />
                                <div class="text-left">
                                    <h3 class="text-sm sm:text-base font-black text-primary-400 tracking-wider print:text-black uppercase leading-tight" style="font-family: 'Cinzel', serif;">
                                        PUNGUAN POMPARAN RAJA SIGODANG ULU SIHOTANG DOHOT BORUNA SE-INDONESIA
                                    </h3>
                                    <p class="text-[10px] text-gray-400 print:text-gray-600 tracking-widest uppercase">
                                        Sekretariat Pusat & Tarombo Marga Sihotang
                                    </p>
                                </div>
                            </div>
                            <h4 class="text-sm sm:text-base font-bold text-white print:text-black uppercase tracking-widest mt-2">
                                KARTU ANGGOTA KELUARGA
                            </h4>
                            <div class="inline-block mt-2 px-4 py-1 rounded-full bg-white/5 border border-white/10 print:border-black print:bg-transparent text-xs font-mono text-gray-300 print:text-black font-semibold">
                                NO. KK: {{ kkNumber }}
                            </div>
                        </div>

                        <!-- KK Meta Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-xs sm:text-sm mb-6 pb-6 border-b border-white/10 print:border-b print:border-black">
                            <div class="flex justify-between sm:justify-start gap-4">
                                <span class="text-gray-400 print:text-black w-36">Nama Kepala Keluarga:</span>
                                <span class="text-white print:text-black font-bold uppercase">{{ node.name }}</span>
                            </div>
                            <div class="flex justify-between sm:justify-start gap-4">
                                <span class="text-gray-400 print:text-black w-36">Marga:</span>
                                <span class="text-white print:text-black font-bold">{{ node.marga || '—' }}</span>
                            </div>
                            <div class="flex justify-between sm:justify-start gap-4">
                                <span class="text-gray-400 print:text-black w-36">Asal Daerah:</span>
                                <span class="text-white print:text-black font-semibold">{{ node.asal_daerah || '—' }}</span>
                            </div>
                            <div class="flex justify-between sm:justify-start gap-4">
                                <span class="text-gray-400 print:text-black w-36">Tingkat Generasi:</span>
                                <span class="text-white print:text-black font-semibold">Generasi ke-{{ (node.level || 0) + 1 }}</span>
                            </div>
                        </div>

                        <!-- KARTU KELUARGA TABLE -->
                        <!-- Colums: NO, NAMA, TANGGAL LAHIR, ISTRI, AYAH, IBU -->
                        <div class="overflow-x-auto mb-8">
                            <table class="w-full text-left text-xs border-collapse border border-white/20 print:border-black">
                                <thead>
                                    <tr class="bg-primary-500/20 text-primary-300 print:bg-gray-100 print:text-black font-bold text-center border-b border-white/20 print:border-black">
                                        <th class="p-2.5 border-r border-white/20 print:border-black w-12">NO</th>
                                        <th class="p-2.5 border-r border-white/20 print:border-black">NAMA LENGKAP</th>
                                        <th class="p-2.5 border-r border-white/20 print:border-black w-28">TANGGAL LAHIR</th>
                                        <th class="p-2.5 border-r border-white/20 print:border-black">ISTRI / PASANGAN</th>
                                        <th class="p-2.5 border-r border-white/20 print:border-black">AYAH</th>
                                        <th class="p-2.5">IBU</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in kkRows" :key="row.no"
                                        class="border-b border-white/10 print:border-b print:border-black hover:bg-white/5 print:hover:bg-transparent">
                                        <td class="p-2.5 text-center font-semibold border-r border-white/10 print:border-black text-gray-300 print:text-black">
                                            {{ row.no }}
                                        </td>
                                        <td class="p-2.5 font-bold text-white print:text-black border-r border-white/10 print:border-black">
                                            {{ row.nama }}
                                            <span class="block text-[10px] text-gray-400 print:text-gray-600 font-normal">({{ row.role }})</span>
                                        </td>
                                        <td class="p-2.5 text-center text-gray-300 print:text-black border-r border-white/10 print:border-black">
                                            {{ row.tanggal_lahir }}
                                        </td>
                                        <td class="p-2.5 text-pink-300 print:text-black border-r border-white/10 print:border-black">
                                            {{ row.istri }}
                                        </td>
                                        <td class="p-2.5 text-gray-200 print:text-black border-r border-white/10 print:border-black">
                                            {{ row.ayah }}
                                        </td>
                                        <td class="p-2.5 text-gray-200 print:text-black">
                                            {{ row.ibu }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- KK Signatures Footer -->
                        <div class="grid grid-cols-2 text-center text-xs mt-10 pt-4 print:mt-12 text-gray-300 print:text-black">
                            <div>
                                <p class="mb-14">Mengetahui,<br><strong class="text-white print:text-black">Pengurus Punguan Sihotang</strong></p>
                                <p class="font-bold underline text-white print:text-black">( Pengurus Pusat Punguan Sihotang )</p>
                            </div>
                            <div>
                                <p class="mb-14">Diterbitkan pada {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}<br><strong class="text-white print:text-black">Kepala Keluarga</strong></p>
                                <p class="font-bold underline text-white print:text-black">( {{ node.name }} )</p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</template>

<style>
@media print {
    /* Hide everything except the kartu keluarga */
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    nav, .print\\:hidden {
        display: none !important;
    }
    #kartu-keluarga-area {
        position: static !important;
        width: 100% !important;
        background: #ffffff !important;
        color: #000000 !important;
        border: 2px solid #000000 !important;
        box-shadow: none !important;
        padding: 20px !important;
    }
    table, th, td {
        border-color: #000000 !important;
        color: #000000 !important;
    }
}
</style>
