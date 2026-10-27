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
        class="rounded-control border-line bg-panel px-3 py-2 text-moon shadow-soft placeholder:text-mist focus:border-magic focus:ring-magic"
        v-model="model"
        ref="input"
    />
</template>
