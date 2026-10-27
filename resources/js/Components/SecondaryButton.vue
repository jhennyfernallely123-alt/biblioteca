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
        class="inline-flex min-h-11 items-center justify-center rounded-control border border-madera/35 bg-crema px-4 py-2 text-sm font-medium text-tinta shadow-soft transition duration-150 ease-in-out hover:bg-pergamino focus:outline-none focus:ring-2 focus:ring-salvia focus:ring-offset-2 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
