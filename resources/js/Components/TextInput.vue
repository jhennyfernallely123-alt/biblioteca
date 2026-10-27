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
        class="rounded-control border-madera/35 bg-crema px-3 py-2 text-tinta shadow-soft focus:border-oliva focus:ring-oliva"
        v-model="model"
        ref="input"
    />
</template>
