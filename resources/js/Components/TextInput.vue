<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl bg-gray-950/60 border border-white/10 text-white placeholder-gray-500 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors"
        v-model="model"
        ref="input"
    />
</template>
