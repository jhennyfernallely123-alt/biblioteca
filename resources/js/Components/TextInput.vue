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
        class="rounded-control border-edge bg-surface px-3 py-2 text-ink shadow-soft placeholder:text-ink-muted focus:border-secondary focus:ring-secondary"
        v-model="model"
        ref="input"
    />
</template>
