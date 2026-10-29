<script setup>
defineProps({
    tone: {
        type: String,
        default: 'arcane',
        validator: (value) => ['arcane', 'orquidea'].includes(value),
    },
});

const tones = {
    arcane: 'bg-arcane text-moon hover:bg-arcane-strong focus:bg-arcane-strong active:bg-arcane-strong',
    orquidea: 'bg-orquidea text-night hover:bg-orquidea/85 focus:bg-orquidea/85 active:bg-orquidea/75',
};
</script>

<template>
    <button
        class="inline-flex min-h-11 items-center justify-center rounded-control border border-transparent px-4 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-magic focus:ring-offset-2 focus:ring-offset-night disabled:opacity-50"
        :class="tones[tone]"
    >
        <slot />
    </button>
</template>
