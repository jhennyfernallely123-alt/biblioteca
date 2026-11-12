<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FloatingInput from '@/Components/FloatingInput.vue';
import LanguageSelect from '@/Components/LanguageSelect.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    user: { type: Object, required: true },
    locale: { type: String, default: 'es' },
    languages: { type: Array, default: () => [] },
    curated: { type: Array, default: () => [] },
});

const { t } = useI18n();

const profile = useForm({
    name: props.user.name,
    nickname: props.user.nickname ?? props.user.name,
    email: props.user.email,
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

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

        <form class="mb-6 space-y-3 rounded-card border border-edge bg-surface p-4" @submit.prevent="saveProfile">
            <h2 class="text-2xl">{{ t('ui.profile_heading') }}</h2>
            <FloatingInput v-model="profile.name" :label="t('ui.name')" :placeholder="t('ui.write_here')" autocomplete="name" :error="profile.errors.name" />
            <FloatingInput v-model="profile.nickname" :label="t('ui.nickname')" :placeholder="t('ui.write_here')" autocomplete="nickname" :maxlength="50" :error="profile.errors.nickname" />
            <FloatingInput v-model="profile.email" type="email" :label="t('ui.email')" :placeholder="t('ui.write_here')" autocomplete="email" :error="profile.errors.email" />
            <button type="submit" class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong" :disabled="profile.processing">
                {{ t('ui.save_profile') }}
            </button>
        </form>

        <form class="mb-6 space-y-3 rounded-card border border-edge bg-surface p-4" @submit.prevent="savePassword">
            <h2 class="text-2xl">{{ t('ui.password_heading') }}</h2>
            <FloatingInput v-model="password.current_password" type="password" :label="t('ui.current_password')" :placeholder="t('ui.write_here')" autocomplete="current-password" :error="password.errors.current_password" />
            <p v-if="password.errors.current_password" class="text-sm text-danger">{{ password.errors.current_password }}</p>
            <FloatingInput v-model="password.password" type="password" :label="t('ui.new_password')" :placeholder="t('ui.write_here')" autocomplete="new-password" :error="password.errors.password" />
            <p v-if="password.errors.password" class="text-sm text-danger">{{ password.errors.password }}</p>
            <FloatingInput v-model="password.password_confirmation" type="password" :label="t('ui.confirm_password')" :placeholder="t('ui.write_here')" autocomplete="new-password" :error="password.errors.password_confirmation" />
            <button type="submit" class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong" :disabled="password.processing">
                {{ t('ui.change_password') }}
            </button>
        </form>

        <section class="mb-6 space-y-3 rounded-card border border-edge bg-surface p-4">
            <h2 class="text-2xl">{{ t('ui.language_heading') }}</h2>
            <p class="text-sm text-ink-muted">{{ t('ui.language_current') }}: {{ currentLanguage }}</p>
            <LanguageSelect
                v-model="language"
                :languages="languages"
                :curated="curated"
                :locale="locale"
                :disabled="preparing"
            />
            <button
                type="button"
                class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong disabled:opacity-50"
                :disabled="preparing"
                @click="applyLanguage"
            >
                {{ t('ui.language_apply') }}
            </button>
            <p v-if="preparing" class="text-sm text-ink-muted">
                {{ t('ui.language_preparing', { percent: progress }) }}
            </p>
            <p v-if="languageError" class="text-sm text-danger">{{ languageError }}</p>
            <p v-if="languageSuccess" class="text-sm text-success">{{ t('ui.language_updated') }}</p>
        </section>
    </AuthenticatedLayout>
</template>
