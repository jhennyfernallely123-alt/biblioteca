<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
    <Head :title="t('ui.edit_universe', { name: universe.name })" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <div class="mb-6 flex items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl">{{ t('ui.edit_universe', { name: universe.name }) }}</h1>
                <Link
                    href="/universes"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.back') }}
                </Link>
            </div>

            <form @submit.prevent="form.put(route('literary-universes.update', universe.id))" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">
                        {{ t('ui.universe_name') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.universe_name_placeholder')"
                        :class="{ 'border-danger': form.errors.name }"
                    >
                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink mb-1">
                        {{ t('ui.universe_type') }}
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button
                            v-for="type in types"
                            :key="type.key"
                            type="button"
                            class="rounded-control border px-3 py-2 text-sm text-left"
                            :class="{
                                'bg-accent text-on-accent border-accent': form.type === type.key,
                                'border-edge text-ink hover:bg-surface-raised': form.type !== type.key,
                            }"
                            @click="form.type = type.key"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-danger">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink mb-1">
                        {{ t('ui.universe_description') }}
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink placeholder:text-ink-muted"
                        :placeholder="t('ui.universe_description_placeholder')"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Link
                        href="/universes"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                    >
                        {{ t('ui.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong"
                        :disabled="form.processing"
                    >
                        {{ t('ui.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
