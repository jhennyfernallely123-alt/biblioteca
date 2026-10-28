<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ArcaneStars from './ArcaneStars.vue';
import FlashToast from './FlashToast.vue';
import LocaleSync from './LocaleSync.vue';
import MagicWelcome from './MagicWelcome.vue';
import { resetWelcome, shouldShowWelcome } from './welcomeIntro';
import { useI18n } from '@/i18n';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useI18n();
const showWelcome = ref(shouldShowWelcome());
const notice = ref(null);
let noticeId = 0;
let noticeTimer = null;

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash?.success) {
            return;
        }

        window.clearTimeout(noticeTimer);
        notice.value = { id: ++noticeId, text: flash.success };
        noticeTimer = window.setTimeout(() => {
            notice.value = null;
        }, 4000);
    },
    { immediate: true, deep: true }
);

onBeforeUnmount(() => {
    window.clearTimeout(noticeTimer);
});

const links = computed(() => [
    { label: t('ui.nav_library'), href: '/' },
    { label: t('ui.nav_settings'), href: '/settings' },
]);

const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);

function logout() {
    router.post('/logout', {}, { onSuccess: resetWelcome });
}
</script>

<template>
    <div class="arcane-sky min-h-screen md:flex">
        <ArcaneStars />
        <LocaleSync />
        <FlashToast v-if="notice" :key="notice.id" :text="notice.text" />
        <MagicWelcome v-if="showWelcome" :nickname="user?.nickname || user?.name || ''" @close="showWelcome = false" />
        <aside class="hidden border-e border-line bg-night-raised/95 p-4 text-moon md:flex md:w-60 md:flex-col">
            <div class="mb-8">
                <Link href="/" class="font-serif text-2xl text-gold">
                    Biblioteca
                </Link>
                <button type="button" class="mt-4 min-h-11 w-full rounded-control border border-line px-3 py-2 text-start text-moon hover:bg-panel" @click="logout">
                    {{ t('ui.logout') }}
                </button>
            </div>
            <nav class="space-y-2">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="block rounded-control border-s-2 px-3 py-2 text-mist hover:bg-panel hover:text-moon"
                    :class="isActive(link.href) ? 'border-magic bg-panel text-moon' : 'border-transparent'"
                    :aria-current="isActive(link.href) ? 'page' : undefined"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </aside>

        <div class="flex-1 pb-20 md:pb-0">
            <header class="flex items-center justify-between border-b border-line bg-night-raised/95 px-4 py-3 text-moon md:hidden">
                <span class="font-serif text-xl text-gold">Biblioteca</span>
                <button type="button" class="min-h-11 rounded-control border border-line px-3 py-2 text-sm text-moon hover:bg-panel" @click="logout">
                    {{ t('ui.exit') }}
                </button>
            </header>
            <main class="mx-auto w-full max-w-[1200px] p-4 md:p-6">
                <slot />
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-0 flex justify-around border-t border-line bg-night-raised/95 py-2 text-moon md:hidden">
            <Link
                v-for="link in links"
                :key="link.href"
                :href="link.href"
                class="flex min-h-11 items-center border-b-2 px-3 py-2 text-sm"
                :class="isActive(link.href) ? 'border-magic text-gold' : 'border-transparent'"
                :aria-current="isActive(link.href) ? 'page' : undefined"
            >
                {{ link.label }}
            </Link>
        </nav>
    </div>
</template>
