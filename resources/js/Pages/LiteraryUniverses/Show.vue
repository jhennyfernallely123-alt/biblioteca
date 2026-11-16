<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    universe: { type: Object, required: true },
    books: { type: Array, required: true },
});

const { t } = useI18n();

const readCount = computed(
    () => props.books.filter((book) => book.reading_status === 'finished').length,
);

const progress = computed(() => {
    if (props.books.length === 0) {
        return 0;
    }

    const total = props.books.reduce((sum, book) => sum + (Number(book.reading_progress) || 0), 0);

    return Math.round(total / props.books.length);
});

function statusClass(status) {
    if (status === 'finished') {
        return 'bg-success/10 text-success';
    }

    if (status === 'reading') {
        return 'bg-accent-soft text-accent-strong';
    }

    return 'bg-surface-sunken text-ink-muted';
}

function statusLabel(status) {
    if (status === 'finished') {
        return t('ui.status_finished');
    }

    if (status === 'reading') {
        return t('ui.status_reading');
    }

    return t('ui.status_none');
}
</script>

<template>
    <Head :title="t('ui.universe_detail', { name: universe.name })" />
    <AuthenticatedLayout>
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/universes"
                class="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-accent"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M11 4 5 10l6 6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ t('ui.universe_back') }}
            </Link>

            <section class="mb-6 overflow-hidden rounded-card border border-edge bg-surface shadow-soft">
                <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:p-5">
                    <div class="relative flex h-40 w-28 shrink-0 items-center justify-center overflow-hidden rounded-card bg-bosque font-serif text-4xl text-pergamino/80 shadow-cover">
                        {{ universe.name.charAt(0) }}
                        <img
                            v-if="books[0]?.cover_url"
                            :src="books[0].cover_url"
                            :alt="t('ui.cover_alt', { title: universe.name })"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>

                    <div class="min-w-0 flex-1">
                        <span class="inline-flex rounded-full border border-edge bg-surface-sunken px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">
                            {{ universe.type_label }}
                        </span>
                        <h1 class="mt-2 font-serif text-2xl leading-tight text-heading sm:text-3xl">
                            {{ universe.name }}
                        </h1>
                        <p v-if="universe.description" class="mt-1 text-sm text-ink-muted">
                            {{ universe.description }}
                        </p>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ universe.total_books }}</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_books_label') }}</span>
                            </div>
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ readCount }}</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_read_label') }}</span>
                            </div>
                            <div class="rounded-control bg-surface-raised px-3 py-2">
                                <span class="block font-serif text-lg leading-none text-heading">{{ progress }}%</span>
                                <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-soft">{{ t('ui.universe_progress_short') }}</span>
                            </div>
                        </div>

                        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-surface-sunken">
                            <div class="h-full rounded-full bg-accent transition-all" :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>
            </section>

            <h2 class="mb-3 font-serif text-lg text-heading">{{ t('ui.universe_books_section') }}</h2>

            <div
                v-if="books.length === 0"
                class="rounded-card border border-dashed border-edge bg-surface p-8 text-center"
            >
                <p class="font-serif text-base text-heading">{{ t('ui.universe_empty_help') }}</p>
            </div>

            <ol v-else class="space-y-2.5">
                <li
                    v-for="book in books"
                    :key="book.id"
                    class="flex flex-wrap items-center gap-3 rounded-card border border-edge bg-surface p-3 shadow-soft sm:flex-nowrap"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-dorado/40 bg-crema font-serif text-sm font-bold tabular-nums text-madera shadow-soft">
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
                        <strong class="block truncate font-serif text-base leading-snug text-heading">{{ book.title }}</strong>
                        <span class="block truncate text-sm text-ink-muted">{{ book.author || t('ui.unknown_author') }}</span>
                        <span v-if="book.publisher || book.published_year" class="mt-0.5 block truncate text-xs text-ink-soft">
                            {{ [book.publisher, book.published_year].filter(Boolean).join(' · ') }}
                        </span>

                        <div class="mt-1.5 flex items-center gap-2">
                            <span class="rounded px-1.5 py-0.5 text-[11px] font-medium" :class="statusClass(book.reading_status)">
                                {{ statusLabel(book.reading_status) }}
                            </span>
                            <span v-if="book.reading_progress > 0" class="h-1.5 w-16 overflow-hidden rounded-full bg-surface-sunken">
                                <span class="block h-full rounded-full bg-accent" :style="{ width: book.reading_progress + '%' }"></span>
                            </span>
                        </div>
                    </div>
                </li>
            </ol>
        </div>
    </AuthenticatedLayout>
</template>
