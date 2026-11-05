<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 24 },
});

const emblems = {
    dragon: [
        'M4 16c3-6 8-10 16-11-1 4-3 7-7 9 2 0 4-.5 5-1.5-1 3-4 5-7 5-3 0-5-1-7-1.5Z',
        'M9 19c-2 1-4 1-5 0',
    ],
    shadow: [
        'M12 3a9 9 0 1 0 9 9c0-.6-.1-1.2-.2-1.8A7 7 0 0 1 12 3Z',
    ],
    wing: [
        'M3 18C5 10 11 5 21 4c-2 8-7 13-13 14l-5 2Z',
        'M8 16c3-4 7-7 10-8',
    ],
    feather: [
        'M20 4C12 5 6 10 5 18l-1 2 2-1c8-1 13-7 14-15Z',
        'M5 18C9 13 13 10 18 7',
    ],
    shield: [
        'M12 3 20 6v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3Z',
    ],
    sword: [
        'M4 20l2-6L18 2l4 4-12 12-6 2Z',
        'M6 16l2 2',
    ],
    compass: [
        'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
        'M15 9l-2 5-4 1 2-5 4-1Z',
    ],
    flame: [
        'M12 3c1 4 5 5 5 10a5 5 0 0 1-10 0c0-3 2-4 2-7 1 1 2 2 3 2 0-2 0-3 0-5Z',
    ],
    knot: [
        'M8 8a4 4 0 1 1 8 0c0 3-8 5-8 8a4 4 0 1 0 8 0',
    ],
    map: [
        'M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2V6Z',
        'M9 4v14M15 6v14',
    ],
    lantern: [
        'M9 4h6M12 4v2',
        'M8 6h8v3a4 4 0 0 1-8 0V6ZM10 13h4v5a2 2 0 0 1-4 0v-5Z',
        'M9 21h6',
    ],
    star: [
        'M12 3l2.5 6 6.5.5-5 4.2 1.6 6.3L12 16.6 6.4 20l1.6-6.3-5-4.2 6.5-.5L12 3Z',
    ],
    moon: [
        'M20 13A8 8 0 1 1 11 4a7 7 0 0 0 9 9Z',
    ],
    chess: [
        'M12 3a2 2 0 0 1 1 3.7C14.5 8 15 10 15 12H9c0-2 .5-4 2-5.3A2 2 0 0 1 12 3Z',
        'M7 16a5 5 0 0 1 10 0M5 21h14M6 18h12',
    ],
    cat: [
        'M5 5l3 3a7 7 0 0 1 8 0l3-3v7a7 7 0 0 1-14 0V5Z',
        'M10 12h.01M14 12h.01M11 16c.7.5 1.3.5 2 0',
    ],
    music: [
        'M9 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM19 16a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z',
        'M9 18V6l10-2v12',
    ],
    mask: [
        'M4 5c2.5 1 5.5 1 8 1s5.5 0 8-1v6a8 8 0 0 1-16 0V5Z',
        'M9 11h.01M15 11h.01M10 15c1.3.8 2.7.8 4 0',
    ],
    crown: [
        'M4 8l4 4 4-7 4 7 4-4v9H4V8Z',
    ],
    key: [
        'M14 3a6 6 0 1 0-5.6 8.2L3 17v4h4v-3h3v-3l2.4-2.4A6 6 0 0 0 14 3Z',
        'M15 8h.01',
    ],
    rose: [
        'M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z',
        'M12 16v5M12 19c-3 0-5-2-5-5M12 21c3 0 5-2 5-5',
    ],
};

const paths = computed(() => emblems[props.name] ?? emblems.star);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.4"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-for="(d, index) in paths" :key="index" :d="d" />
    </svg>
</template>
