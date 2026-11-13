<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from '@/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    universe: { type: Object, required: true },
    types: { type: Array, required: true },
});

const { t } = useI18n();

const form = useForm({
    name: props.universe.name,
    description: props.universe.description ?? '',
    type: props.universe.type,
});

watch(() => form.type, () => {
    if (!props.types.some((type) => type.key === form.type)) {
        form.type = props.types[0]?.key ?? 'series';
    }
});
</script>

<template>
    <Head :title="t('ui.edit_universe')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-soft">{{ t('ui.edit_universe') }}</p>
                    <h1 class="truncate font-serif text-2xl text-heading sm:text-3xl">{{ universe.name }}</h1>
                </div>
                <Link
                    :href="`/universes/${universe.id}`"
                    class="shrink-0 rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface-raised sm:px-4"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form
                class="space-y-5 rounded-card border border-edge bg-surface p-5 shadow-soft"
                @submit.prevent="form.put(route('literary-universes.update', universe.id))"
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

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">
                        {{ t('ui.universe_description') }}
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent"
                        :placeholder="t('ui.universe_description_placeholder')"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-edge pt-4">
                    <Link
                        :href="`/universes/${universe.id}`"
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
