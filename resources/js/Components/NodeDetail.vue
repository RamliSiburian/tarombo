<script setup>
defineProps({
    node: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const formatYear = (year) => year || '—';
const genderLabel = (gender) => gender === 'female' ? '♀ Perempuan' : '♂ Laki-laki';
const genderClass = (gender) => gender === 'female' ? 'text-pink-400 bg-pink-500/10 border-pink-500/20' : 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20';
</script>

<template>
    <transition name="slide-panel">
        <div v-if="node" class="flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <div class="flex items-start justify-between p-5 border-b border-white/10">
                <div class="flex-1 min-w-0 pr-4">
                    <h2 class="text-white font-bold text-lg leading-tight truncate">{{ node.name }}</h2>
                    <p v-if="node.marga" class="text-amber-400 text-sm mt-0.5">Marga {{ node.marga }}</p>
                </div>
                <button @click="emit('close')" class="text-gray-500 hover:text-white transition-colors flex-shrink-0 mt-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <!-- Photo -->
                <div v-if="node.foto" class="w-full aspect-video rounded-xl overflow-hidden bg-gray-800">
                    <img :src="`/storage/${node.foto}`" :alt="node.name" class="w-full h-full object-cover"/>
                </div>
                <div v-else class="w-full h-32 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center border border-white/5">
                    <span class="text-5xl">{{ node.gender === 'female' ? '👩' : '👨' }}</span>
                </div>

                <!-- Gender badge -->
                <div :class="['inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full border', genderClass(node.gender)]">
                    {{ genderLabel(node.gender) }}
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

                <!-- Description -->
                <div v-if="node.deskripsi" class="bg-white/5 rounded-xl p-3">
                    <div class="text-gray-500 text-xs mb-2">Keterangan</div>
                    <p class="text-gray-300 text-sm leading-relaxed">{{ node.deskripsi }}</p>
                </div>

                <!-- Spouses section (only for male) -->
                <div v-if="node.gender === 'male' && node.spouses && node.spouses.length > 0">
                    <div class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-2">Data Istri</div>
                    <div class="space-y-2">
                        <div v-for="(spouse, i) in node.spouses" :key="spouse.id"
                             class="bg-pink-500/5 border border-pink-500/15 rounded-xl p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-pink-400 text-lg">💑</span>
                                <div>
                                    <div class="text-pink-300 font-semibold text-sm">{{ spouse.name }}</div>
                                    <div v-if="spouse.marga" class="text-pink-500 text-xs">Marga {{ spouse.marga }}</div>
                                </div>
                            </div>
                            <p v-if="spouse.deskripsi" class="text-gray-400 text-xs leading-relaxed">{{ spouse.deskripsi }}</p>
                        </div>
                    </div>
                </div>

                <!-- Note for female nodes -->
                <div v-if="node.gender === 'female'" class="bg-pink-500/5 border border-pink-500/15 rounded-xl p-3">
                    <p class="text-pink-300 text-xs leading-relaxed">
                        ℹ️ Dalam tradisi Batak, silsilah mengikuti garis patrilineal. Silsilah dari node perempuan tidak dilanjutkan dalam pohon ini.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-white/10">
                <p class="text-gray-600 text-xs text-center">Klik di luar panel atau tekan × untuk menutup</p>
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
