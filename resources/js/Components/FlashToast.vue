<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    text: { type: String, required: true },
    duration: { type: Number, default: 4000 },
});

const visible = ref(true);
let timer = null;

function schedule() {
    window.clearTimeout(timer);
    visible.value = true;
    timer = window.setTimeout(() => {
        visible.value = false;
    }, props.duration);
}

watch(() => props.text, schedule, { immediate: true });

onBeforeUnmount(() => {
    window.clearTimeout(timer);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            role="status"
            aria-live="polite"
            class="pointer-events-none fixed bottom-20 left-1/2 z-50 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-card border border-success/40 bg-night-raised px-4 py-3 text-sm text-moon shadow-modal md:bottom-6"
        >
            {{ text }}
        </div>
    </Transition>
</template>
