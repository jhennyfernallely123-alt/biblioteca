<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';
import AlbumSlot from '@/Components/AlbumSlot.vue';
import AlbumProgress from '@/Components/AlbumProgress.vue';
import AlbumFilter from '@/Components/AlbumFilter.vue';
import AlbumDetailModal from '@/Components/AlbumDetailModal.vue';

const props = defineProps({
    items: { type: Array, required: true },
    progress: { type: Object, required: true },
});

const { t } = useI18n();

const activeFilter = ref('all');
const modalItem = ref(null);

const filteredItems = computed(() => {
    if (activeFilter.value === 'all') return props.items;
    return props.items.filter((item) => item.type === activeFilter.value);
});

function openDetail(item) {
    modalItem.value = item;
}
</script>

<template>
    <Head :title="t('ui.album_title')" />

    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl text-accent">{{ t('ui.album_title') }}</h1>
                <p class="text-sm text-ink-muted">{{ t('ui.album_subtitle') }}</p>
            </div>
        </header>

        <AlbumProgress :progress="progress" />

        <AlbumFilter v-model:active="activeFilter" />

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <AlbumSlot
                v-for="item in filteredItems"
                :key="`${item.type}-${item.key}`"
                :item="item"
                @click="openDetail(item)"
            />
        </div>

        <AlbumDetailModal :show="modalItem !== null" :item="modalItem" @close="modalItem = null" />

        <div v-if="props.items.length === 0" class="mt-12 text-center text-ink-muted">
            <p>{{ t('ui.album_empty') }}</p>
        </div>
    </AuthenticatedLayout>
</template>
