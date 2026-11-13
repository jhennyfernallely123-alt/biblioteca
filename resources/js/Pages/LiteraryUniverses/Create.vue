<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from '@/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    types: { type: Array, required: true },
});

const { t } = useI18n();

const form = useForm({
    name: '',
    type: 'series',
});

watch(() => form.type, () => {
    if (!props.types.some((type) => type.key === form.type)) {
        form.type = props.types[0]?.key ?? 'series';
    }
});

function create(withBooks) {
    form.transform((data) => ({ ...data, add: withBooks ? 1 : undefined }));
    form.post(route('literary-universes.store'));
}
</script>

<template>
    <Head :title="t('ui.universe_create')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-6 flex items-center justify-between gap-3">
                <h1 class="font-serif text-2xl text-heading sm:text-3xl">{{ t('ui.universe_create') }}</h1>
                <Link
                    href="/universes"
                    class="rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised sm:px-4"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form
                class="space-y-5 rounded-card border border-edge bg-surface p-5 shadow-soft"
                @submit.prevent="create(false)"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">
                        {{ t('ui.universe_name') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent"
                        :placeholder="t('ui.universe_name_placeholder')"
                        :class="{ 'border-danger': form.errors.name }"
                    >
                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                </div>

                <div>
                    <span class="mb-1 block text-sm font-medium text-ink">
                        {{ t('ui.universe_type') }}
                    </span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="type in types"
                            :key="type.key"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-sm transition-colors"
                            :class="form.type === type.key
                                ? 'border-accent bg-accent text-on-accent'
                                : 'border-edge text-ink hover:border-accent hover:text-accent'"
                            @click="form.type = type.key"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-danger">{{ form.errors.type }}</p>
                </div>

                <div class="rounded-control border border-dashed border-edge bg-surface-raised p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-accent">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Zm9-1.5h6.5A1.5 1.5 0 0 1 21 5.5v13a1.5 1.5 0 0 1-1.5 1.5H13V4Z" opacity=".85" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink">{{ t('ui.universe_add_books') }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ t('ui.universe_add_books_hint') }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong disabled:opacity-60 sm:w-auto"
                        :disabled="form.processing"
                        @click="create(true)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                        </svg>
                        {{ t('ui.universe_create_and_add') }}
                    </button>
                </div>

                <div class="flex justify-end gap-2 border-t border-edge pt-4">
                    <Link
                        href="/universes"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                    >
                        {{ t('ui.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ t('ui.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
