<script setup>
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

const page = usePage();

watchEffect(() => {
    document.documentElement.lang = page.props.locale ?? 'es';
    document.documentElement.dir = page.props.direction ?? 'ltr';
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
