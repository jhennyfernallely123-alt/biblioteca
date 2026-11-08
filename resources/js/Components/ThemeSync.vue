<script setup>
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

const page = usePage();

watchEffect(() => {
    const theme = page.props.auth?.user?.theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = theme;
});
</script>

<template>
    <span hidden aria-hidden="true" />
</template>
