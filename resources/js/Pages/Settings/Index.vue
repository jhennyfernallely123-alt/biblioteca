<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LanguageSelect from '@/Components/LanguageSelect.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    user: { type: Object, required: true },
    shelves: { type: Array, required: true },
    locale: { type: String, default: 'es' },
    languages: { type: Array, default: () => [] },
    curated: { type: Array, default: () => [] },
});

const { t } = useI18n();

const profile = useForm({
    name: props.user.name,
    email: props.user.email,
    default_view_mode: props.user.default_view_mode ?? 'covers',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const newShelf = useForm({ name: '' });

const names = ref(Object.fromEntries(props.shelves.map((shelf) => [shelf.id, shelf.name])));

const language = ref(props.locale);
const preparing = ref(false);
const progress = ref(0);
const languageError = ref('');
const languageSuccess = ref(false);

const currentLanguage = computed(() => {
    try {
        return new Intl.DisplayNames([props.locale], { type: 'language' }).of(props.locale) ?? props.locale;
    } catch {
        return props.locale;
    }
});

function saveProfile() {
    profile.patch('/settings/profile', { preserveScroll: true });
}

function savePassword() {
    password.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });
}

function createShelf() {
    newShelf.post('/shelves', {
        preserveScroll: true,
        onSuccess: () => newShelf.reset(),
    });
}

function renameShelf(shelf) {
    const name = (names.value[shelf.id] ?? '').trim();

    if (!name || name === shelf.name) {
        return;
    }

    router.patch(`/shelves/${shelf.id}`, { name }, { preserveScroll: true });
}

function moveShelf(index, direction) {
    const ids = props.shelves.map((shelf) => shelf.id);
    const target = index + direction;

    if (target < 0 || target >= ids.length) {
        return;
    }

    [ids[index], ids[target]] = [ids[target], ids[index]];

    router.post('/shelves/reorder', { ids }, { preserveScroll: true });
}

function destroyShelf(shelf) {
    if (window.confirm(t('ui.delete_shelf_confirm', { name: shelf.name }))) {
        router.delete(`/shelves/${shelf.id}`, { preserveScroll: true });
    }
}

async function applyLanguage() {
    if (language.value === props.locale) return;

    preparing.value = true;
    progress.value = 0;
    languageError.value = '';
    languageSuccess.value = false;

    try {
        const prepare = await axios.post('/settings/locale/prepare', { locale: language.value });
        const total = prepare.data.missing;
        let keys = prepare.data.keys;

        while (keys.length > 0) {
            const response = await axios.post('/settings/locale/translate', {
                locale: language.value,
                keys,
            });

            if (response.data.failed > 0) {
                throw new Error('translation-failed');
            }

            keys = response.data.keys;
            progress.value = total === 0 ? 100 : Math.round(((total - response.data.remaining) / total) * 100);
        }

        await router.patch('/settings/locale', { locale: language.value }, {
            preserveScroll: true,
            onSuccess: () => {
                languageSuccess.value = true;
            },
        });
    } catch {
        languageError.value = t('ui.language_prepare_failed');
    } finally {
        preparing.value = false;
    }
}
</script>

<template>
    <Head :title="t('ui.settings_title')" />
    <AuthenticatedLayout>
        <h1 class="mb-6 text-3xl">{{ t('ui.settings_title') }}</h1>

        <form class="mb-8 space-y-3" @submit.prevent="saveProfile">
            <h2 class="text-2xl">{{ t('ui.profile_heading') }}</h2>
            <input v-model="profile.name" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.name')">
            <p v-if="profile.errors.name" class="text-sm text-danger">{{ profile.errors.name }}</p>
            <input v-model="profile.email" type="email" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.email')">
            <p v-if="profile.errors.email" class="text-sm text-danger">{{ profile.errors.email }}</p>
            <select v-model="profile.default_view_mode" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist">
                <option value="covers">{{ t('ui.covers') }}</option>
                <option value="spines">{{ t('ui.spines') }}</option>
            </select>
            <button type="submit" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong" :disabled="profile.processing">
                {{ t('ui.save_profile') }}
            </button>
        </form>

        <form class="mb-8 space-y-3" @submit.prevent="savePassword">
            <h2 class="text-2xl">{{ t('ui.password_heading') }}</h2>
            <input v-model="password.current_password" type="password" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.current_password')">
            <p v-if="password.errors.current_password" class="text-sm text-danger">{{ password.errors.current_password }}</p>
            <input v-model="password.password" type="password" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.new_password')">
            <p v-if="password.errors.password" class="text-sm text-danger">{{ password.errors.password }}</p>
            <input v-model="password.password_confirmation" type="password" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.confirm_password')">
            <button type="submit" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong" :disabled="password.processing">
                {{ t('ui.change_password') }}
            </button>
        </form>

        <section class="mb-8 space-y-3">
            <h2 class="text-2xl">{{ t('ui.language_heading') }}</h2>
            <p class="text-sm text-mist">{{ t('ui.language_help') }}</p>
            <p class="text-sm text-mist">{{ t('ui.language_current') }}: {{ currentLanguage }}</p>
            <LanguageSelect
                v-model="language"
                :languages="languages"
                :curated="curated"
                :locale="locale"
                :disabled="preparing"
            />
            <button
                type="button"
                class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong disabled:opacity-50"
                :disabled="preparing"
                @click="applyLanguage"
            >
                {{ t('ui.language_apply') }}
            </button>
            <p v-if="preparing" class="text-sm text-mist">
                {{ t('ui.language_preparing', { percent: progress }) }}
            </p>
            <p v-if="languageError" class="text-sm text-danger">{{ languageError }}</p>
            <p v-if="languageSuccess" class="text-sm text-success">{{ t('ui.language_updated') }}</p>
        </section>

        <section>
            <h2 class="mb-3 text-2xl">{{ t('ui.shelves_heading') }}</h2>

            <form class="mb-3 flex gap-2" @submit.prevent="createShelf">
                <input v-model="newShelf.name" class="w-full rounded-control border border-line bg-panel px-3 py-2 text-moon placeholder:text-mist" :placeholder="t('ui.new_shelf_placeholder')">
                <button type="submit" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong" :disabled="newShelf.processing">
                    {{ t('ui.create') }}
                </button>
            </form>
            <p v-if="newShelf.errors.name" class="mb-2 text-sm text-danger">{{ newShelf.errors.name }}</p>

            <ul class="space-y-2">
                <li
                    v-for="(shelf, index) in props.shelves"
                    :key="shelf.id"
                    class="flex flex-wrap items-center gap-2 rounded-card border border-line bg-panel p-3"
                >
                    <input v-model="names[shelf.id]" class="min-w-0 flex-1 rounded-control border border-line bg-panel-raised px-2 py-1 text-moon" @keyup.enter="renameShelf(shelf)">
                    <button type="button" class="rounded-control border border-line px-2 py-1 text-sm text-moon hover:bg-panel-raised" @click="renameShelf(shelf)">
                        {{ t('ui.rename') }}
                    </button>
                    <button type="button" class="rounded-control border border-line px-2 py-1 text-sm text-moon hover:bg-panel-raised disabled:opacity-40" :disabled="index === 0" @click="moveShelf(index, -1)">
                        {{ t('ui.up') }}
                    </button>
                    <button type="button" class="rounded-control border border-line px-2 py-1 text-sm text-moon hover:bg-panel-raised disabled:opacity-40" :disabled="index === props.shelves.length - 1" @click="moveShelf(index, 1)">
                        {{ t('ui.down') }}
                    </button>
                    <button type="button" class="rounded-control border border-danger px-2 py-1 text-sm text-danger hover:bg-danger/10" @click="destroyShelf(shelf)">
                        {{ t('ui.delete') }}
                    </button>
                </li>
            </ul>
        </section>
    </AuthenticatedLayout>
</template>
