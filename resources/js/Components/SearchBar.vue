<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue', 'select']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const showResults = ref(false);
let timeout = null;

const search = () => {
    clearTimeout(timeout);
    if (query.value.length < 2) {
        results.value = [];
        showResults.value = false;
        return;
    }
    loading.value = true;
    timeout = setTimeout(async () => {
        try {
            const res = await axios.get(route('tree.search'), { params: { q: query.value } });
            results.value = res.data;
            showResults.value = true;
        } catch (e) {
            console.error(e);
        } finally {
            loading.value = false;
        }
    }, 350);
};

const selectNode = (node) => {
    query.value = node.name;
    showResults.value = false;
    emit('update:modelValue', node.id);
    emit('select', node);
};

const clear = () => {
    query.value = '';
    results.value = [];
    showResults.value = false;
    emit('update:modelValue', null);
    emit('select', null);
};

const genderIcon = (gender) => gender === 'female' ? '👩' : '👨';
</script>

<template>
    <div class="relative">
        <div class="flex items-center gap-2 bg-gray-800/80 backdrop-blur-sm border border-white/10 rounded-xl px-4 py-2.5 focus-within:border-amber-500/50 transition-colors">
            <svg v-if="!loading" class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <div v-else class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin flex-shrink-0"></div>

            <input
                v-model="query"
                @input="search"
                @focus="showResults = results.length > 0"
                @blur="setTimeout(() => showResults = false, 200)"
                type="text"
                placeholder="Cari nama atau marga..."
                class="flex-1 bg-transparent text-white placeholder-gray-500 text-sm outline-none min-w-0"
            />

            <button v-if="query" @click="clear" class="text-gray-500 hover:text-white flex-shrink-0 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Results dropdown -->
        <div v-if="showResults && results.length > 0"
             class="absolute top-full left-0 right-0 mt-2 bg-gray-900 border border-white/10 rounded-xl shadow-2xl overflow-hidden z-50">
            <div class="p-2 max-h-64 overflow-y-auto">
                <button
                    v-for="node in results"
                    :key="node.id"
                    @mousedown.prevent="selectNode(node)"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors text-left"
                >
                    <span class="text-lg">{{ genderIcon(node.gender) }}</span>
                    <div class="min-w-0">
                        <div class="text-white text-sm font-medium truncate">{{ node.name }}</div>
                        <div class="text-gray-500 text-xs">{{ node.marga ? `Marga ${node.marga}` : 'Tanpa marga' }} · Level {{ node.level }}</div>
                    </div>
                </button>
            </div>
            <div class="px-3 py-2 border-t border-white/5 text-xs text-gray-500">
                {{ results.length }} hasil ditemukan
            </div>
        </div>

        <div v-if="showResults && results.length === 0 && query.length >= 2 && !loading"
             class="absolute top-full left-0 right-0 mt-2 bg-gray-900 border border-white/10 rounded-xl p-4 text-center text-gray-500 text-sm shadow-2xl z-50">
            Tidak ada hasil untuk "{{ query }}"
        </div>
    </div>
</template>
