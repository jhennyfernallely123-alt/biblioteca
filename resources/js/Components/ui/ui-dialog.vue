<script setup>
defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['update:show', 'close']);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" />
        <div
            class="relative bg-surface rounded-lg border border-edge shadow-2xl p-6 max-w-md w-full transform transition-all duration-300"
            @click.stop
        >
            <slot />
        </div>
    </div>
</template>
