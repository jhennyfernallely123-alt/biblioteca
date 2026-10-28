<script setup>
import { useI18n } from '@/i18n';

const { t } = useI18n();
</script>

<template>
    <div class="rounded-md border border-dashed border-madera/35 p-6 text-center">
        <p class="font-serif text-lg text-crema">{{ t('ui.empty_shelf') }}</p>
    </div>
</template>
