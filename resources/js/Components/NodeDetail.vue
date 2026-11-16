<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: { type: Object, default: null },
});

const emit = defineEmits(['close', 'exportLineage', 'selectNode']);

const formatYear = (year) => year || '—';
const genderLabel = (gender) => gender === 'female' ? '♀ Perempuan' : '♂ Laki-laki';
const genderClass = (gender) => gender === 'female' ? 'text-pink-400 bg-pink-500/10 border-pink-500/20' : 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20';

const getWifeLabel = (index) => {
    const labels = ['Istri Pertama (1)', 'Istri Kedua (2)', 'Istri Ketiga (3)', 'Istri Keempat (4)', 'Istri Kelima (5)'];
    return labels[index] || `Istri Ke-${index + 1} (${index + 1})`;
};

const extractedMotherFromDesc = computed(() => {
    if (props.node?.mother) {
        return props.node.mother.name + (props.node.mother.marga ? ` (Marga ${props.node.mother.marga})` : '');
    }
    if (!props.node?.deskripsi) return null;
    const match = props.node.deskripsi.match(/Ibu Kandung:\s*([^\n\r]+)/i);
    return match ? match[1].trim() : null;
});

const cleanDeskripsi = computed(() => {
    if (!props.node?.deskripsi) return '';
    return props.node.deskripsi.replace(/Ibu Kandung:\s*[^\n\r]+[\r\n]*/gi, '').trim();
});
</script>

<template>
    <transition name="slide-panel">
        <div v-if="node" class="flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <div class="flex items-start justify-between p-5 border-b border-white/10">
                <div class="flex-1 min-w-0 pr-4">
                    <h2 class="text-white font-bold text-lg leading-tight truncate">{{ node.name }}</h2>
                    <p v-if="node.marga" class="text-primary-400 text-sm mt-0.5 font-medium">Marga {{ node.marga }}</p>
                </div>
                <button @click="emit('close')" class="text-gray-500 hover:text-white transition-colors flex-shrink-0 mt-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <!-- Photo / Avatar -->
                <div v-if="node.foto" class="w-full aspect-video rounded-xl overflow-hidden bg-gray-800">
                    <img :src="`/storage/${node.foto}`" :alt="node.name" class="w-full h-full object-cover"/>
                </div>
                <div v-else class="w-full h-28 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center border border-white/5">
                    <span class="text-5xl">{{ node.gender === 'female' ? '👩' : '👨' }}</span>
                </div>

                <!-- Badges row -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Gender badge -->
                    <div :class="['inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border', genderClass(node.gender)]">
                        {{ genderLabel(node.gender) }}
                    </div>

                    <!-- Birth order badge -->
                    <div v-if="node.level > 0" 
                         :class="[
                            'inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border',
                            node.has_sibling_conflict
                                ? 'bg-amber-500/15 border-amber-500/30 text-amber-300'
                                : 'bg-white/5 border-white/10 text-gray-300'
                         ]">
                        <span v-if="node.has_sibling_conflict">⚠️</span>
                        <span>Anak ke-{{ node.sort_order || 1 }}</span>
                    </div>

                    <!-- Conflict status indicator -->
                    <div v-if="node.has_sibling_conflict"
                         class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        <span>Urutan Tabrakan</span>
                    </div>
                </div>

                <!-- ⚠️ Conflict Urutan Kelahiran Section -->
                <div v-if="node.has_sibling_conflict"
                     class="bg-gradient-to-br from-amber-950/50 to-yellow-950/30 border border-amber-500/40 rounded-xl p-3.5 space-y-3 shadow-xl shadow-amber-950/20">
                    <div class="flex items-start gap-2.5">
                        <span class="text-amber-400 text-lg flex-shrink-0 mt-0.5">⚠️</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-amber-300 font-bold text-xs uppercase tracking-wider">
                                    Urutan Belum Pasti
                                </h4>
                                <span class="text-[10px] bg-amber-500/25 text-amber-200 font-bold px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0">
                                    Urutan Anak ke-{{ node.sort_order || 1 }}
                                </span>
                            </div>
                            <p class="text-amber-200/90 text-xs mt-1.5 leading-relaxed">
                                Node ini tercatat pada urutan <strong class="text-white font-bold underline decoration-amber-400">Anak ke-{{ node.sort_order || 1 }}</strong>, namun urutan ini <span class="text-amber-300 font-semibold">tabrakan / sama</span> dengan saudaranya:
                            </p>
                        </div>
                    </div>

                    <!-- Daftar Saudara yang Urutannya Sama -->
                    <div class="space-y-1.5 pt-2 border-t border-amber-500/20">
                        <div class="text-[11px] text-amber-300/80 font-medium flex items-center justify-between">
                            <span>Saudara dengan urutan ke-{{ node.sort_order || 1 }} yang sama:</span>
                            <span v-if="node.conflicting_siblings?.length" class="text-[10px] text-amber-400/90 font-semibold">
                                {{ node.conflicting_siblings.length }} Orang
                            </span>
                        </div>

                        <div v-if="node.conflicting_siblings && node.conflicting_siblings.length > 0" class="space-y-1.5">
                            <div v-for="sibling in node.conflicting_siblings" :key="sibling.id"
                                 @click="emit('selectNode', sibling)"
                                 class="flex items-center justify-between bg-black/40 hover:bg-amber-500/15 border border-amber-500/20 hover:border-amber-500/40 rounded-lg p-2.5 transition-all cursor-pointer group"
                                 title="Klik untuk melihat node ini di pohon silsilah">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-base flex-shrink-0">{{ sibling.gender === 'female' ? '👩' : '👨' }}</span>
                                    <div class="min-w-0">
                                        <div class="text-white text-xs font-bold truncate group-hover:text-amber-200 transition-colors">
                                            {{ sibling.name }}
                                        </div>
                                        <div class="text-[10px] text-amber-300/70 truncate">
                                            <span v-if="sibling.marga">Marga {{ sibling.marga }} • </span>
                                            <span>Sama-sama tercatat sebagai Anak ke-{{ sibling.sort_order }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-amber-400 text-xs font-semibold group-hover:translate-x-0.5 transition-transform flex-shrink-0 ml-2">
                                    <span>Lihat</span>
                                    <span>→</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-[11px] text-amber-300/70 italic bg-black/20 rounded-lg p-2">
                            Tidak ada saudara konflik lainnya.
                        </div>
                    </div>

                    <div class="text-[10px] text-amber-300/60 italic leading-snug pt-0.5">
                        💡 Nomor urut akan dipastikan setelah disetujui oleh admin punguan berdasarkan bukti tarombo yang valid.
                    </div>
                </div>

                <!-- Info grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-gray-500 text-xs mb-1">Asal Daerah</div>
                        <div class="text-white text-sm font-medium">{{ node.asal_daerah || '—' }}</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-gray-500 text-xs mb-1">Level Generasi</div>
                        <div class="text-white text-sm font-medium">Generasi {{ node.level + 1 }}</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-gray-500 text-xs mb-1">Tahun Lahir</div>
                        <div class="text-white text-sm font-medium">{{ formatYear(node.tahun_lahir) }}</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-gray-500 text-xs mb-1">Tahun Wafat</div>
                        <div class="text-white text-sm font-medium">{{ formatYear(node.tahun_wafat) }}</div>
                    </div>
                </div>

                <!-- Orang Tua & Ibu Kandung Section -->
                <div class="space-y-2 pt-1">
                    <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5">
                        <span>👨‍👩‍👦</span>
                        <span>Orang Tua & Asal Usul</span>
                    </div>

                    <!-- Ayah -->
                    <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-indigo-400 text-lg">👨</span>
                            <div>
                                <div class="text-[11px] text-indigo-300/80 font-medium">Ayah Kandung</div>
                                <div class="text-white text-sm font-bold">
                                    {{ node.parent?.name || (node.level === 0 ? 'Leluhur Pertama (Root)' : 'Tidak Tercatat') }}
                                    <span v-if="node.parent?.marga" class="text-indigo-300 font-normal text-xs">(Marga {{ node.parent.marga }})</span>
                                </div>
                            </div>
                        </div>
                        <span v-if="node.parent" class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-medium">
                            Gen {{ node.parent.level + 1 }}
                        </span>
                    </div>

                    <!-- Ibu Kandung -->
                    <div class="bg-pink-500/10 border border-pink-500/20 rounded-xl p-3">
                        <div class="flex items-start gap-2.5">
                            <span class="text-pink-400 text-lg mt-0.5">👩</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] text-pink-300/80 font-medium">Ibu Kandung</div>
                                <div v-if="extractedMotherFromDesc" class="text-white text-sm font-bold mt-0.5">
                                    {{ extractedMotherFromDesc }}
                                </div>
                                <div v-else-if="node.parent?.spouses && node.parent.spouses.length > 0" class="mt-0.5 space-y-1">
                                    <div v-for="(spouse, idx) in node.parent.spouses" :key="spouse.id" class="flex items-center gap-2">
                                        <span class="text-white text-sm font-bold">{{ spouse.name }}</span>
                                        <span v-if="spouse.marga" class="text-pink-400 text-xs font-normal">(Marga {{ spouse.marga }})</span>
                                        <span v-if="node.parent.spouses.length > 1" class="text-[10px] bg-pink-500/20 text-pink-300 px-1.5 py-0.5 rounded font-medium">
                                            {{ getWifeLabel(idx) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-gray-400 text-xs italic mt-0.5">
                                    {{ node.level === 0 ? '— (Root Silsilah)' : 'Belum ada data istri dari ayah di silsilah' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spouses Section (only for male) -->
                <div v-if="node.gender === 'male'" class="space-y-2 pt-1">
                    <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span>💑</span>
                            <span>Data Istri (Pasangan)</span>
                        </span>
                        <span v-if="node.spouses?.length" class="text-[11px] px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 font-bold">
                            {{ node.spouses.length }} Istri
                        </span>
                    </div>

                    <div v-if="node.spouses && node.spouses.length > 0" class="space-y-2">
                        <div v-for="(spouse, i) in node.spouses" :key="spouse.id"
                             class="bg-pink-500/5 border border-pink-500/20 rounded-xl p-3.5 space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 font-bold">
                                        👰 {{ getWifeLabel(i) }}
                                    </span>
                                    <span class="text-white font-bold text-sm">{{ spouse.name }}</span>
                                </div>
                            </div>
                            <div v-if="spouse.marga" class="text-pink-400/90 text-xs font-medium pl-1">
                                Marga {{ spouse.marga }}
                            </div>
                            <p v-if="spouse.deskripsi" class="text-gray-300 text-xs leading-relaxed pt-1.5 border-t border-pink-500/10 mt-1 pl-1">
                                {{ spouse.deskripsi }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="bg-gray-800/40 border border-white/5 rounded-xl p-3 text-center">
                        <p class="text-gray-500 text-xs">Belum ada data istri yang terdaftar untuk node ini</p>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="cleanDeskripsi" class="bg-white/5 rounded-xl p-3">
                    <div class="text-gray-500 text-xs mb-1 font-medium">Keterangan</div>
                    <p class="text-gray-300 text-sm leading-relaxed">{{ cleanDeskripsi }}</p>
                </div>

                <!-- Note for female nodes -->
                <div v-if="node.gender === 'female'" class="bg-pink-500/5 border border-pink-500/15 rounded-xl p-3">
                    <p class="text-pink-300 text-xs leading-relaxed">
                        ℹ️ Dalam tradisi Batak, silsilah mengikuti garis patrilineal. Silsilah dari node perempuan tidak dilanjutkan dalam pohon ini.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-white/10 space-y-2">
                <button @click="emit('exportLineage')"
                        class="w-full py-2.5 px-4 bg-primary-500/10 hover:bg-primary-500/20 border border-primary-500/30 text-primary-300 font-semibold text-xs rounded-xl flex items-center justify-center gap-2 transition-colors">
                    <span>📥 Export Garis Leluhur Node Ini</span>
                </button>
                <p class="text-gray-600 text-[11px] text-center">Klik di luar panel atau tekan × untuk menutup</p>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center h-full p-6 text-center">
            <div class="text-5xl mb-4">🌳</div>
            <h3 class="text-white font-semibold mb-2">Pilih Node</h3>
            <p class="text-gray-500 text-sm">Klik pada salah satu node di pohon silsilah untuk melihat detail dan jalur leluhurnya.</p>
        </div>
    </transition>
</template>

<style scoped>
.slide-panel-enter-active,
.slide-panel-leave-active {
    transition: opacity 0.2s ease;
}
.slide-panel-enter-from,
.slide-panel-leave-to {
    opacity: 0;
}
</style>
