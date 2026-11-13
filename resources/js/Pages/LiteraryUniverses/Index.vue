<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    universes: { type: Array, required: true },
});

const { t } = useI18n();
const deleting = ref(null);

function confirmDelete(universe) {
    deleting.value = universe;
}

function destroy() {
    if (!deleting.value) {
        return;
    }

    router.delete(`/universes/${deleting.value.id}`, {
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.universes_heading')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.universes_heading') }}</h1>
                <Link
                    href="/universes/create"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.universe_create') }}
                </Link>
            </div>

            <p v-if="universes.length === 0" class="rounded-card border border-edge bg-surface p-6 text-center text-ink-muted">
                {{ t('ui.universes_empty') }}
            </p>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="universe in universes"
                    :key="universe.id"
                    class="rounded-card border border-edge bg-surface p-4 flex flex-col"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <span
                            class="inline-flex items-center rounded-full border border-edge px-2 py-0.5 text-xs font-medium text-ink-muted"
                        >
                            {{ universe.type_label }}
                        </span>
                        <button
                            v-if="deleting?.id === universe.id"
                            type="button"
                            class="text-danger hover:text-danger-strong"
                            @click="deleting = null"
                            aria-label="Cancelar"
                        >
                            ×
                        </button>
                    </div>

                    <h2 class="font-serif text-lg text-ink mb-1 line-clamp-1">
                        {{ universe.name }}
                    </h2>

                    <p v-if="universe.description" class="text-sm text-ink-muted mb-3 line-clamp-2">
                        {{ universe.description }}
                    </p>

                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs text-ink-muted">
                            {{ t('ui.universe_books_count', { count: universe.total_books }) }}
                        </span>
                        <Link
                            :href="`/universes/${universe.id}`"
                            class="rounded-control border border-edge px-2 py-1 text-xs text-ink hover:bg-surface-raised"
                        >
                            {{ t('ui.view') }}
                        </Link>
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2">
                        <Link
                            :href="`/universes/${universe.id}/edit`"
                            class="rounded-control border border-edge flex-1 sm:flex-initial px-3 py-2 text-sm text-ink hover:bg-surface-raised"
                        >
                            {{ t('ui.edit') }}
                        </Link>
                        <button
                            type="button"
                            class="rounded-control border border-danger flex-1 sm:flex-initial px-3 py-2 text-sm text-danger hover:bg-danger/10"
                            @click="confirmDelete(universe)"
                        >
                            {{ t('ui.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Modal v-if="deleting" :show="true" max-width="md" @close="deleting = null">
            <div class="p-6">
                <h2 class="font-serif text-lg">{{ t('ui.universe_delete_title') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_delete_confirm') }}</p>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="deleting = null"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="destroy"
                    >
                        {{ t('ui.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
