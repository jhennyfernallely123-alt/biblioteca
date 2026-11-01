<script setup>
defineProps({ name: { type: String, required: true } });
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'all'">
            <rect x="4" y="4" width="6" height="6" rx="1" />
            <rect x="14" y="4" width="6" height="6" rx="1" />
            <rect x="4" y="14" width="6" height="6" rx="1" />
            <rect x="14" y="14" width="6" height="6" rx="1" />
        </template>

        <template v-else-if="name === 'table'">
            <path d="M4 9h11v4.5A4.5 4.5 0 0 1 10.5 18h-2A4.5 4.5 0 0 1 4 13.5V9Z" />
            <path d="M15 10h1.8a2.7 2.7 0 0 1 0 5.4H15" />
            <path d="M3 21h13" />
            <path d="M7.5 5.5c0-1 1-1.5 1-2.5" />
            <path d="M11.5 5.5c0-1 1-1.5 1-2.5" />
        </template>

        <template v-else-if="name === 'plants'">
            <circle cx="12" cy="12" r="2.2" />
            <ellipse cx="12" cy="6.4" rx="2.4" ry="3.1" />
            <ellipse cx="12" cy="17.6" rx="2.4" ry="3.1" />
            <ellipse cx="6.4" cy="12" rx="3.1" ry="2.4" />
            <ellipse cx="17.6" cy="12" rx="3.1" ry="2.4" />
        </template>

        <template v-else-if="name === 'light'">
            <path d="M12 3c1.6 1.5 2.4 2.9 2.4 4.2a2.4 2.4 0 0 1-4.8 0C9.6 5.9 10.4 4.5 12 3Z" />
            <path d="M9 11h6v9H9z" />
            <path d="M12 8.6v2.4" />
        </template>

        <template v-else-if="name === 'curios'">
            <path d="M12 3.5 13.7 9l5.5 1.7-5.5 1.7L12 18l-1.7-5.6L4.8 10.7 10.3 9 12 3.5Z" />
            <path d="M18.5 16.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z" />
        </template>

        <template v-else-if="name === 'keepsakes'">
            <path d="M12 20s-7.5-4.4-7.5-9.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 3.2C19.5 15.6 12 20 12 20Z" />
        </template>
    </svg>
</template>
