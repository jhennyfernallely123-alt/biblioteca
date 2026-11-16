<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import RatingStars from '@/Components/RatingStars.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    universes: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const { t } = useI18n();

const search = ref('');
const activeType = ref('all');
const expandedId = ref(null);
const deleting = ref(null);

const types = computed(() => {
    const seen = new Map();

    props.universes.forEach((universe) => {
        if (universe.type && !seen.has(universe.type)) {
            seen.set(universe.type, universe.type_label);
        }
    });

    return Array.from(seen, ([key, label]) => ({ key, label }));
});

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.universes.filter((universe) => {
        const matchesType = activeType.value === 'all' || universe.type === activeType.value;
        const haystack = `${universe.name} ${universe.description ?? ''}`.toLowerCase();

        return matchesType && (!query || haystack.includes(query));
    });
});

function toggle(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

function confirmDelete(universe) {
    deleting.value = universe;
}

function destroy() {
    if (!deleting.value) {
        return;
    }

    router.delete(`/universes/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.universes_heading')" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-4xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-serif text-2xl text-heading sm:text-3xl">{{ t('ui.universes_heading') }}</h1>
                <Link
                    v-if="canManage"
                    href="/universes/create"
                    class="rounded-control bg-accent px-3 py-2 text-sm text-on-accent hover:bg-accent-strong sm:px-4 sm:text-base"
                >
                    {{ t('ui.universe_create') }}
                </Link>
            </div>

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-soft"
                        viewBox="0 0 20 20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        aria-hidden="true"
                    >
                        <circle cx="8.5" cy="8.5" r="5.5" />
                        <path d="m13 13 4 4" stroke-linecap="round" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        class="w-full rounded-control border border-edge bg-surface py-2 pl-9 pr-3 text-sm text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent"
                        :placeholder="t('ui.universe_search_placeholder')"
                    >
                </div>

                <div v-if="types.length" class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="activeType === 'all'
                            ? 'border-accent bg-accent text-on-accent'
                            : 'border-edge bg-surface text-ink-muted hover:border-accent hover:text-accent'"
                        @click="activeType = 'all'"
                    >
                        {{ t('ui.universe_filter_all') }}
                    </button>
                    <button
                        v-for="type in types"
                        :key="type.key"
                        type="button"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="activeType === type.key
                            ? 'border-accent bg-accent text-on-accent'
                            : 'border-edge bg-surface text-ink-muted hover:border-accent hover:text-accent'"
                        @click="activeType = type.key"
                    >
                        {{ type.label }}
                    </button>
                </div>
            </div>

            <div
                v-if="universes.length === 0"
                class="rounded-card border border-edge bg-surface p-8 text-center shadow-soft"
            >
                <p class="font-serif text-lg text-heading">{{ t('ui.universes_empty') }}</p>
                <Link
                    v-if="canManage"
                    href="/universes/create"
                    class="mt-4 inline-block rounded-control bg-accent px-4 py-2 text-sm text-on-accent hover:bg-accent-strong"
                >
                    {{ t('ui.universe_create') }}
                </Link>
            </div>

            <p
                v-else-if="filtered.length === 0"
                class="rounded-card border border-dashed border-edge bg-surface p-8 text-center text-sm text-ink-muted"
            >
                {{ t('ui.universe_filter_empty') }}
            </p>

            <div v-else class="space-y-3">
                <div
                    v-for="universe in filtered"
                    :key="universe.id"
                >
                    <section class="overflow-hidden rounded-full border border-edge bg-surface shadow-soft transition-colors hover:border-dorado/60 hover:bg-surface-raised">
                        <button
                            type="button"
                            class="relative flex w-full items-center gap-3 px-4 py-3 text-left sm:gap-4 sm:px-5"
                            :aria-expanded="expandedId === universe.id"
                            :aria-controls="`universe-panel-${universe.id}`"
                            @click="toggle(universe.id)"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-dorado/40 bg-crema text-dorado shadow-soft sm:h-11 sm:w-11"
                                aria-hidden="true"
                            >
                                <svg class="h-6 w-6" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.4">
                                    <path d="M16 10.5c-2.4-1.7-5.2-2.4-8.5-2.4v14c3.3 0 6.1.7 8.5 2.4 2.4-1.7 5.2-2.4 8.5-2.4v-14c-3.3 0-6.1.7-8.5 2.4Z" stroke-linejoin="round" />
                                    <path d="M16 10.5v14" stroke-linecap="round" />
                                    <path d="M8 13c2 .2 3.8.7 5.3 1.6M8 16.4c2 .2 3.8.7 5.3 1.6M24 13c-2 .2-3.8.7-5.3 1.6M24 16.4c-2 .2-3.8.7-5.3 1.6" stroke-linecap="round" opacity=".55" />
                                    <path d="M24.5 4.5l.7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7Z" class="text-malva" fill="currentColor" stroke="none" />
                                </svg>
                            </span>

                            <span class="relative min-w-0 flex-1">
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                                    {{ universe.type_label }}
                                </span>
                                <span class="mt-0.5 block truncate font-serif text-lg leading-tight text-heading sm:text-xl">
                                    {{ universe.name }}
                                </span>
                                <span class="mt-0.5 block text-xs text-ink-muted">
                                    {{ t('ui.universe_books_count', { count: universe.total_books }) }}
                                </span>
                            </span>

                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-ink-muted transition-transform duration-300 sm:h-9 sm:w-9"
                                :class="{ 'rotate-180': expandedId === universe.id }"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </section>

                    <div
                        :id="`universe-panel-${universe.id}`"
                        class="grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none"
                        :class="expandedId === universe.id ? 'mt-2 grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                        :inert="expandedId !== universe.id"
                    >
                        <div class="overflow-hidden">
                            <div class="rounded-modal border border-edge bg-surface-sunken/50 p-3 shadow-soft sm:p-4">
                                <div
                                    v-if="universe.books.length === 0"
                                    class="rounded-card border border-dashed border-edge bg-surface p-6 text-center"
                                >
                                    <p class="font-serif text-base text-heading">{{ t('ui.universe_empty_help') }}</p>
                                </div>

                                <div v-else class="space-y-2.5">
                                    <article
                                        v-for="book in universe.books"
                                        :key="book.id"
                                        class="flex items-center gap-3 rounded-full border border-edge bg-surface py-1.5 pl-2 pr-4 shadow-soft transition-colors hover:border-dorado/60 hover:bg-surface-raised"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dorado/40 bg-crema font-serif text-sm font-bold tabular-nums text-madera shadow-soft"
                                            :title="t('ui.universe_order')"
                                        >
                                            {{ book.reading_order }}
                                        </span>

                                        <span class="relative flex h-11 w-8 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface-sunken font-serif text-xs text-madera ring-1 ring-edge/60">
                                            {{ book.title.charAt(0) }}
                                            <img
                                                v-if="book.cover_url"
                                                :src="book.cover_url"
                                                :alt="t('ui.cover_alt', { title: book.title })"
                                                class="absolute inset-0 h-full w-full object-cover"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        </span>

                                        <div class="flex min-w-0 flex-1 items-baseline gap-1.5">
                                            <strong class="truncate font-serif text-sm leading-snug text-heading">
                                                {{ book.title }}
                                            </strong>
                                            <span class="shrink-0 text-xs text-ink-soft" aria-hidden="true">·</span>
                                            <span class="truncate text-xs text-ink-muted">
                                                {{ book.author || t('ui.unknown_author') }}
                                            </span>
                                        </div>

                                        <RatingStars
                                            v-if="book.rating"
                                            class="shrink-0"
                                            :model-value="book.rating"
                                            :label="t('ui.rating_stars')"
                                        />
                                    </article>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-edge pt-3">
                                    <Link
                                        :href="`/universes/${universe.id}`"
                                        class="rounded-control border border-edge bg-surface px-4 py-2 text-sm text-ink hover:bg-surface-raised"
                                    >
                                        {{ t('ui.view') }}
                                    </Link>
                                    <Link
                                        v-if="canManage"
                                        :href="`/universes/${universe.id}/edit`"
                                        class="rounded-control border border-edge bg-surface px-4 py-2 text-sm text-ink hover:bg-surface-raised"
                                    >
                                        {{ t('ui.edit_universe') }}
                                    </Link>
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="rounded-control border border-danger bg-surface px-4 py-2 text-sm text-danger hover:bg-danger/10"
                                        @click="confirmDelete(universe)"
                                    >
                                        {{ t('ui.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal v-if="deleting" :show="true" max-width="md" @close="deleting = null">
            <div class="p-6">
                <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_delete_title') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_delete_confirm') }}</p>

                <div class="mt-5 flex justify-end gap-2">
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
