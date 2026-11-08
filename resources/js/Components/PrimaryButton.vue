<script setup>
defineProps({
    tone: {
        type: String,
        default: 'arcane',
        validator: (value) => ['arcane', 'orquidea'].includes(value),
    },
});

const tones = {
    arcane: 'bg-accent text-on-accent hover:bg-accent-strong focus:bg-accent-strong active:bg-accent-strong focus:ring-accent',
    orquidea: 'bg-tertiary text-on-accent hover:bg-tertiary/85 focus:bg-tertiary/85 active:bg-tertiary/75',
};
</script>

<template>
    <button
        class="inline-flex min-h-11 items-center justify-center rounded-control border border-transparent px-4 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-page disabled:opacity-50"
        :class="tones[tone]"
    >
        <slot />
    </button>
</template>
