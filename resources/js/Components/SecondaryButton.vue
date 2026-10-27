<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-11 items-center justify-center rounded-control border border-line bg-panel px-4 py-2 text-sm font-medium text-moon shadow-soft transition duration-150 ease-in-out hover:bg-panel-raised focus:outline-none focus:ring-2 focus:ring-magic focus:ring-offset-2 focus:ring-offset-night disabled:opacity-50"
    >
        <slot />
    </button>
</template>
