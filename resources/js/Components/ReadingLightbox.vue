<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
});

const emit = defineEmits(['close', 'updated']);

const { t } = useI18n();
const page = usePage();
const locale = computed(() => page.props.locale ?? 'es');

const state = ref(null);
const loading = ref(true);
const failed = ref(false);
const saving = ref(false);
const tooShort = ref(false);
const seconds = ref(0);
const running = ref(false);
const totalPages = ref('');
const currentPage = ref('');

let ticker = null;
let startedAt = 0;

const bookData = computed(() => state.value?.book ?? props.book);
const sessions = computed(() => state.value?.sessions ?? []);
const progress = computed(() => bookData.value.progress ?? 0);
const totalMinutes = computed(() => bookData.value.minutes ?? 0);
const coverUrl = computed(() => (bookData.value.cover_path ? `/covers/${bookData.value.id}` : null));

const timerLabel = computed(() => {
    const hours = Math.floor(seconds.value / 3600);
    const minutes = Math.floor((seconds.value % 3600) / 60);
    const rest = seconds.value % 60;

    return [hours, minutes, rest].map((value) => String(value).padStart(2, '0')).join(':');
});

const pageLabel = computed(() => {
    if (!bookData.value.total_pages) {
        return t('ui.reading_no_total');
    }

    return t('ui.reading_page_of', {
        page: bookData.value.current_page ?? 0,
        total: bookData.value.total_pages,
    });
});

const statusLabel = computed(() => {
    if (bookData.value.status === 'finished') {
        return t('ui.reading_status_finished');
    }

    if (bookData.value.status === 'reading') {
        return t('ui.reading_status_reading');
    }

    return t('ui.reading_status_none');
});

function durationLabel(minutes) {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours > 0 && rest > 0) {
        return t('ui.reading_hours_minutes', { hours, minutes: rest });
    }

    if (hours > 0) {
        return t('ui.reading_hours', { hours });
    }

    return t('ui.reading_minutes', { minutes: rest });
}

function dateLabel(value) {
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

function syncInputs(book) {
    totalPages.value = book.total_pages ?? '';
    currentPage.value = book.current_page ?? '';
}

async function load() {
    loading.value = true;
    failed.value = false;

    try {
        const { data } = await axios.get(`/books/${props.book.id}/reading`);
        state.value = data;
        syncInputs(data.book);
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

watch(() => props.book.id, load, { immediate: true });

function start() {
    if (running.value) {
        return;
    }

    running.value = true;
    tooShort.value = false;
    startedAt = Date.now();

    ticker = window.setInterval(() => {
        seconds.value = Math.floor((Date.now() - startedAt) / 1000);
    }, 500);
}

async function pause() {
    if (!running.value) {
        return;
    }

    window.clearInterval(ticker);
    ticker = null;
    running.value = false;
    seconds.value = Math.floor((Date.now() - startedAt) / 1000);

    const elapsed = seconds.value;
    seconds.value = 0;

    if (elapsed < 10) {
        tooShort.value = true;

        return;
    }

    await saveSession(Math.max(1, Math.round(elapsed / 60)));
}

async function saveSession(minutes) {
    saving.value = true;
    failed.value = false;

    try {
        const { data } = await axios.post(`/books/${props.book.id}/reading-sessions`, {
            minutes,
            page: currentPage.value === '' ? null : Number(currentPage.value),
        });

        state.value.book = data.book;
        state.value.sessions = [data.session, ...state.value.sessions];
        syncInputs(data.book);
        emit('updated', data.book);
    } catch {
        failed.value = true;
    } finally {
        saving.value = false;
    }
}

async function saveProgress() {
    saving.value = true;
    failed.value = false;

    try {
        const { data } = await axios.patch(`/books/${props.book.id}/reading`, {
            total_pages: totalPages.value === '' ? null : Number(totalPages.value),
            current_page: currentPage.value === '' ? null : Number(currentPage.value),
        });

        state.value.book = data.book;
        syncInputs(data.book);
        emit('updated', data.book);
    } catch {
        failed.value = true;
    } finally {
        saving.value = false;
    }
}

async function removeSession(session) {
    if (!window.confirm(t('ui.reading_delete_session_confirm'))) {
        return;
    }

    saving.value = true;
    failed.value = false;

    try {
        const { data } = await axios.delete(`/reading-sessions/${session.id}`);
        state.value.book = data.book;
        state.value.sessions = state.value.sessions.filter((item) => item.id !== session.id);
        emit('updated', data.book);
    } catch {
        failed.value = true;
    } finally {
        saving.value = false;
    }
}

function close() {
    if (running.value) {
        pause();
    }

    emit('close');
}

function onKey(event) {
    if (event.key === 'Escape') {
        close();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
    window.clearInterval(ticker);
    document.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-night/70 backdrop-blur-sm sm:items-center sm:p-6"
            @click.self="close"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="reading-lightbox-title"
                class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-card border border-line bg-panel p-4 text-moon shadow-modal sm:rounded-card sm:p-6"
            >
                <button
                    type="button"
                    class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full border border-line bg-night-raised text-mist transition-colors hover:text-gold"
                    :aria-label="t('ui.reading_close')"
                    :title="t('ui.reading_close')"
                    @click="close"
                >
                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </button>

                <div class="flex items-start gap-3 pr-8">
                    <div class="aspect-[2/3] w-16 shrink-0 overflow-hidden rounded-card bg-crema shadow-sm ring-1 ring-line">
                        <img
                            v-if="coverUrl"
                            :src="coverUrl"
                            :alt="t('ui.cover_alt', { title: bookData.title })"
                            class="h-full w-full object-cover"
                        >
                        <div v-else class="flex h-full items-center justify-center p-1 text-center font-serif text-[10px] leading-tight text-tinta">
                            {{ bookData.title }}
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 id="reading-lightbox-title" class="font-serif text-xl leading-tight">{{ bookData.title }}</h2>
                        <p class="mt-1 text-sm text-mist">{{ bookData.author || t('ui.unknown_author') }}</p>
                        <p class="mt-2 text-xs text-gold">
                            {{ statusLabel }}
                        </p>
                    </div>
                </div>

                <p v-if="loading" class="mt-6 text-center text-sm text-mist">{{ t('ui.reading_loading') }}</p>

                <template v-else>
                    <div class="mt-5">
                        <div class="mb-1 flex items-baseline justify-between gap-2 text-sm">
                            <span class="text-mist">{{ t('ui.reading_progress') }}</span>
                            <span class="font-semibold text-gold">{{ progress }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-night-raised">
                            <div class="h-full rounded-full bg-gold transition-[width]" :style="{ width: `${progress}%` }" />
                        </div>
                        <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-mist">
                            <span>{{ pageLabel }}</span>
                            <span>{{ t('ui.reading_total_time') }}: {{ durationLabel(totalMinutes) }}</span>
                        </div>
                    </div>

                    <div class="mt-5 rounded-card border border-line bg-night-raised/60 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-mono text-3xl font-semibold tabular-nums text-moon">{{ timerLabel }}</span>
                            <button
                                type="button"
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-arcane text-moon shadow-card transition-colors hover:bg-arcane-strong disabled:opacity-50"
                                :aria-label="running ? t('ui.reading_pause') : t('ui.reading_start')"
                                :title="running ? t('ui.reading_pause') : t('ui.reading_start')"
                                :disabled="saving"
                                @click="running ? pause() : start()"
                            >
                                <svg v-if="running" class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <rect x="4" y="3" width="3" height="10" rx="1" />
                                    <rect x="9" y="3" width="3" height="10" rx="1" />
                                </svg>
                                <svg v-else class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M5 3.2v9.6L13 8z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-mist">{{ t('ui.reading_timer_hint') }}</p>
                        <p v-if="tooShort" class="mt-1 text-xs text-gold">{{ t('ui.reading_too_short') }}</p>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="text-xs text-mist">
                                {{ t('ui.reading_current_page') }}
                                <input
                                    v-model="currentPage"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    class="mt-1 w-full rounded-control border border-line bg-panel px-2 py-1 text-sm text-moon"
                                >
                            </label>
                            <label class="text-xs text-mist">
                                {{ t('ui.reading_total_pages') }}
                                <input
                                    v-model="totalPages"
                                    type="number"
                                    min="1"
                                    inputmode="numeric"
                                    class="mt-1 w-full rounded-control border border-line bg-panel px-2 py-1 text-sm text-moon"
                                >
                            </label>
                        </div>

                        <button
                            type="button"
                            class="mt-2 w-full rounded-control border border-line px-3 py-2 text-sm text-moon transition-colors hover:border-magic hover:text-gold disabled:opacity-50"
                            :disabled="saving"
                            @click="saveProgress"
                        >
                            {{ t('ui.reading_save_progress') }}
                        </button>
                    </div>

                    <p v-if="failed" class="mt-3 text-sm text-danger">{{ t('ui.reading_error') }}</p>

                    <div class="mt-5">
                        <h3 class="mb-2 font-serif text-lg">{{ t('ui.reading_history') }}</h3>

                        <p v-if="sessions.length === 0" class="text-sm text-mist">
                            {{ t('ui.reading_history_empty') }}
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="session in sessions"
                                :key="session.id"
                                class="flex items-center justify-between gap-3 rounded-card border border-line bg-panel-raised/50 px-3 py-2 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="text-moon">{{ dateLabel(session.read_on) }}</p>
                                    <p class="text-xs text-mist">
                                        {{ durationLabel(session.minutes) }}
                                        <template v-if="session.page !== null"> · {{ t('ui.reading_page') }} {{ session.page }}</template>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-mist transition-colors hover:text-danger"
                                    :aria-label="t('ui.reading_delete_session')"
                                    :title="t('ui.reading_delete_session')"
                                    @click="removeSession(session)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>
