<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center rounded-control font-bold transition-colors duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed';
    const variants = {
        primary: 'bg-accent text-white hover:bg-accent-strong disabled:bg-accent-strong disabled:text-white',
        secondary: 'bg-surface text-ink border border-edge hover:bg-surface-raised hover:text-ink disabled:bg-surface disabled:text-ink-muted disabled:border-edge',
        ghost: 'text-accent hover:bg-accent-soft disabled:text-ink-muted',
        danger: 'bg-danger text-white hover:bg-danger-strong disabled:bg-danger-strong disabled:text-white',
    };
    const sizes = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
    };

    return `${base} ${variants[props.variant]} ${sizes[props.size]}`;
});
</script>

<template>
    <button
        type="button"
        :class="classes"
        :disabled="disabled"
        @click="emit('click')"
    >
        <slot />
    </button>
</template>
