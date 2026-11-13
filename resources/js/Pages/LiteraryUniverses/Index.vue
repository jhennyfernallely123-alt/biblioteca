<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AddBooksModal from '@/Pages/LiteraryUniverses/AddBooksModal.vue';
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
const removing = ref(null);
const addingTo = ref(null);

const SPINES = { bilogy: 2, trilogy: 3, tetralogy: 4 };

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

function spines(type) {
    const count = SPINES[type] ?? 0;

    return Array.from({ length: count }, (_, index) => ({
        x: 3 + index * 4.5,
        height: 10 + (index % 2) * 3,
    }));
}

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

function confirmRemove(universe, book) {
    removing.value = { universe, book };
}

function removeBook() {
    if (!removing.value) {
        return;
    }

    const { universe, book } = removing.value;

    router.delete(`/universes/${universe.id}/books/${book.id}`, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = null;
        },
    });
}

function saveOrders(universe, list) {
    const orders = {};

    list.forEach((item, position) => {
        orders[item.id] = position + 1;
    });

    router.patch(`/universes/${universe.id}/reorder`, { orders }, { preserveScroll: true });
}

function move(universe, book, direction) {
    const list = [...universe.books];
    const index = list.findIndex((item) => item.id === book.id);
    const target = direction === 'up' ? index - 1 : index + 1;

    if (index < 0 || target < 0 || target >= list.length) {
        return;
    }

    [list[index], list[target]] = [list[target], list[index]];

    saveOrders(universe, list);
}

function moveTo(universe, book, event) {
    const list = [...universe.books];
    const from = list.findIndex((item) => item.id === book.id);
    const requested = Number.parseInt(event.target.value, 10);
    const to = Math.min(Math.max(Number.isNaN(requested) ? from + 1 : requested, 1), list.length) - 1;

    event.target.value = book.reading_order;

    if (from < 0 || from === to) {
        return;
    }

    const [item] = list.splice(from, 1);
    list.splice(to, 0, item);

    saveOrders(universe, list);
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
                <section
                    v-for="universe in filtered"
                    :key="universe.id"
                    class="overflow-hidden rounded-card border border-edge bg-surface shadow-soft transition-colors hover:border-edge-strong"
                >
                    <button
                        type="button"
                        class="relative flex w-full items-center gap-3 p-4 text-left"
                        :aria-expanded="expandedId === universe.id"
                        :aria-controls="`universe-panel-${universe.id}`"
                        @click="toggle(universe.id)"
                    >
                        <span
                            class="pointer-events-none absolute right-16 top-1/2 hidden h-16 w-16 -translate-y-1/2 text-edge-strong sm:block"
                            aria-hidden="true"
                        >
                            <svg class="h-full w-full" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M60 44c-7-5-16-7-26-7v44c10 0 19 2 26 7 7-5 16-7 26-7V37c-10 0-19 2-26 7Z" stroke-linejoin="round" />
                                <path d="M60 44v44" stroke-linecap="round" />
                                <path d="M90 22l1.2 2.6 2.8.4-2 2 .5 2.8L90 28.5l-2.5 1.3.5-2.8-2-2 2.8-.4Z" fill="currentColor" stroke="none" />
                            </svg>
                        </span>

                        <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-accent sm:h-12 sm:w-12">
                            <svg
                                v-if="spines(universe.type).length"
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <rect
                                    v-for="(spine, index) in spines(universe.type)"
                                    :key="index"
                                    :x="spine.x"
                                    :y="21 - spine.height"
                                    width="3"
                                    height="spine.height"
                                    rx="1"
                                />
                            </svg>
                            <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5v-13Zm9-1.5h6.5A1.5 1.5 0 0 1 21 5.5v13a1.5 1.5 0 0 1-1.5 1.5H13V4Z" opacity=".85" />
                            </svg>
                        </span>

                        <span class="relative min-w-0 flex-1">
                            <span class="block text-[11px] font-semibold uppercase tracking-wide text-ink-soft">
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
                            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-ink-muted transition-transform duration-300 sm:h-9 sm:w-9"
                            :class="{ 'rotate-180': expandedId === universe.id }"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </button>

                    <div
                        :id="`universe-panel-${universe.id}`"
                        class="grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none"
                        :class="expandedId === universe.id ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                        :inert="expandedId !== universe.id"
                    >
                        <div class="overflow-hidden">
                            <div class="border-t border-edge bg-surface-sunken/50 p-3 sm:p-4">
                                <div
                                    v-if="universe.books.length === 0"
                                    class="rounded-card border border-dashed border-edge bg-surface p-6 text-center"
                                >
                                    <p class="font-serif text-base text-heading">{{ t('ui.universe_empty_help') }}</p>
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="mt-4 inline-flex items-center gap-1.5 rounded-control bg-accent px-4 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong"
                                        @click="addingTo = universe"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                                        </svg>
                                        {{ t('ui.universe_add_books') }}
                                    </button>
                                </div>

                                <div v-else class="grid gap-2.5 lg:grid-cols-2">
                                    <article
                                        v-for="book in universe.books"
                                        :key="book.id"
                                        class="flex items-start gap-3 rounded-control border border-edge bg-surface p-3 shadow-soft"
                                    >
                                        <input
                                            v-if="canManage"
                                            type="number"
                                            min="1"
                                            :max="universe.books.length"
                                            :value="book.reading_order"
                                            class="h-9 w-11 shrink-0 rounded-control border border-edge bg-surface-sunken text-center font-serif text-sm font-bold text-accent-strong focus:border-accent focus:ring-accent"
                                            :aria-label="t('ui.universe_order')"
                                            :title="t('ui.universe_order')"
                                            @change="moveTo(universe, book, $event)"
                                        >
                                        <span
                                            v-else
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-soft font-serif text-sm font-bold text-accent-strong"
                                            :title="t('ui.universe_order')"
                                        >
                                            {{ book.reading_order }}
                                        </span>

                                        <span class="relative flex h-16 w-11 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-sunken font-serif text-sm text-accent shadow-soft">
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

                                        <div class="min-w-0 flex-1">
                                            <strong class="block truncate font-serif text-sm leading-snug text-heading">
                                                {{ book.title }}
                                            </strong>
                                            <span class="block truncate text-xs text-ink-muted">
                                                {{ book.author || t('ui.unknown_author') }}
                                            </span>
                                            <RatingStars
                                                v-if="book.rating"
                                                class="mt-1"
                                                :model-value="book.rating"
                                                :label="t('ui.rating_stars')"
                                            />
                                        </div>

                                        <div v-if="canManage" class="flex shrink-0 flex-col gap-1">
                                            <button
                                                type="button"
                                                class="rounded-control border border-edge p-1.5 text-ink hover:bg-surface-raised disabled:cursor-not-allowed disabled:opacity-35"
                                                :disabled="book.reading_order === 1"
                                                :aria-label="t('ui.move_up')"
                                                :title="t('ui.move_up')"
                                                @click="move(universe, book, 'up')"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M10 15V5m0 0-4 4m4-4 4 4" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-control border border-edge p-1.5 text-ink hover:bg-surface-raised disabled:cursor-not-allowed disabled:opacity-35"
                                                :disabled="book.reading_order === universe.books.length"
                                                :aria-label="t('ui.move_down')"
                                                :title="t('ui.move_down')"
                                                @click="move(universe, book, 'down')"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M10 5v10m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-control border border-danger p-1.5 text-danger hover:bg-danger/10"
                                                :aria-label="t('ui.universe_remove_book')"
                                                :title="t('ui.universe_remove_book')"
                                                @click="confirmRemove(universe, book)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4 10h12" stroke-linecap="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </article>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-control bg-accent px-3 py-2 text-sm font-medium text-on-accent hover:bg-accent-strong"
                                        @click="addingTo = universe"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                                        </svg>
                                        {{ t('ui.universe_add_books') }}
                                    </button>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            :href="`/universes/${universe.id}`"
                                            class="rounded-control border border-edge bg-surface px-3 py-2 text-sm text-ink hover:border-accent hover:text-accent"
                                        >
                                            {{ canManage ? t('ui.universe_manage') : t('ui.universe_open') }}
                                        </Link>
                                        <Link
                                            v-if="canManage"
                                            :href="`/universes/${universe.id}/edit`"
                                            class="rounded-control border border-edge bg-surface px-3 py-2 text-sm text-ink hover:border-accent hover:text-accent"
                                        >
                                            {{ t('ui.edit') }}
                                        </Link>
                                        <button
                                            v-if="canManage"
                                            type="button"
                                            class="rounded-control border border-danger px-3 py-2 text-sm text-danger hover:bg-danger/10"
                                            @click="confirmDelete(universe)"
                                        >
                                            {{ t('ui.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <AddBooksModal
            v-if="addingTo"
            :show="true"
            :universe="addingTo"
            @close="addingTo = null"
        />

        <Modal v-if="removing" :show="true" max-width="md" @close="removing = null">
            <div class="p-6">
                <h2 class="font-serif text-lg text-heading">{{ t('ui.universe_remove_book') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ t('ui.universe_remove_book_confirm') }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-control border border-edge px-4 py-2 text-ink hover:bg-surface-raised"
                        @click="removing = null"
                    >
                        {{ t('ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-control bg-danger px-4 py-2 text-on-accent hover:bg-danger-strong"
                        @click="removeBook"
                    >
                        {{ t('ui.remove') }}
                    </button>
                </div>
            </div>
        </Modal>

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
