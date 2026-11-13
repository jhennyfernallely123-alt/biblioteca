<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ArcaneStars from './ArcaneStars.vue';
import FlashToast from './FlashToast.vue';
import LocaleSync from './LocaleSync.vue';
import MagicWelcome from './MagicWelcome.vue';
import SeasonalDecor from './SeasonalDecor.vue';
import SeasonalGarland from './SeasonalGarland.vue';
import SeasonalParticles from './SeasonalParticles.vue';
import BottomNav from './BottomNav.vue';
import ThemeSwitch from './ThemeSwitch.vue';
import ThemeSync from './ThemeSync.vue';
import { resetWelcome, shouldShowWelcome } from './welcomeIntro';
import { useI18n } from '@/i18n';
import { currentSeason } from '@/seasons';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useI18n();
const showWelcome = ref(shouldShowWelcome());
const season = currentSeason();
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
    { label: t('ui.nav_shelves'), href: '/shelves' },
    ...(user.value?.is_master
        ? [{ label: t('ui.nav_catalog'), href: '/catalog' }]
        : []),
    { label: t('ui.nav_universes'), href: '/universes' },
    { label: t('ui.nav_cards'), href: '/cards' },
    { label: t('ui.nav_decorations'), href: '/decorations' },
    { label: t('ui.nav_album'), href: '/album' },
    { label: t('ui.nav_settings'), href: '/settings' },
]);

const primaryLinks = computed(() => links.value.slice(0, 2));
const menuLinks = computed(() => links.value.slice(2));

const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);

const menuOpen = ref(false);
const menuActive = computed(() => menuLinks.value.some((link) => isActive(link.href)));

function closeMenuOnOutside(event) {
    if (!event.target.closest?.('[data-nav-menu], [data-nav-menu-toggle]')) {
        menuOpen.value = false;
    }
}

function closeMenuOnEscape(event) {
    if (event.key === 'Escape') {
        menuOpen.value = false;
    }
}

watch(menuOpen, (open) => {
    if (open) {
        window.addEventListener('pointerdown', closeMenuOnOutside);
        window.addEventListener('keydown', closeMenuOnEscape);
    } else {
        window.removeEventListener('pointerdown', closeMenuOnOutside);
        window.removeEventListener('keydown', closeMenuOnEscape);
    }
});

watch(
    () => page.url,
    () => {
        menuOpen.value = false;
    }
);

onBeforeUnmount(() => {
    window.removeEventListener('pointerdown', closeMenuOnOutside);
    window.removeEventListener('keydown', closeMenuOnEscape);
});

function logout() {
    router.post('/logout', {}, { onSuccess: resetWelcome });
}
</script>

<template>
      <div class="arcane-sky min-h-screen md:flex" :data-season="season">
          <ThemeSync />
          <ArcaneStars />
        <SeasonalParticles :season="season" />
        <SeasonalDecor :season="season" />
        <LocaleSync />
        <FlashToast v-if="notice" :key="notice.id" :text="notice.text" />
        <MagicWelcome v-if="showWelcome" :nickname="user?.nickname || user?.name || ''" @close="showWelcome = false" />
        <aside class="relative z-10 hidden border-e border-edge bg-surface-raised/95 p-4 text-ink md:flex md:w-60 md:flex-col">
            <div class="mb-8">
                <Link href="/" class="font-serif text-2xl text-accent">
                    Biblioteca
                </Link>
            <div class="mt-4 flex flex-col items-start gap-2">
                <ThemeSwitch class="theme-switch--mobile" />
                <button type="button" class="min-h-11 rounded-control border border-edge px-3 py-2 text-start text-ink w-full hover:bg-surface" @click="logout">
                    {{ t('ui.logout') }}
                </button>
            </div>
            </div>
            <nav class="space-y-2">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="block rounded-control border-s-2 px-3 py-2 text-ink-muted hover:bg-surface hover:text-ink"
                    :class="isActive(link.href) ? 'border-secondary bg-surface text-ink' : 'border-transparent'"
                    :aria-current="isActive(link.href) ? 'page' : undefined"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </aside>

        <div class="relative z-10 flex-1 pb-20 md:pb-0">
            <header class="relative flex items-center justify-between border-b border-edge bg-surface-raised/95 px-4 py-3 text-ink md:hidden">
                <span class="font-serif text-xl text-accent">Biblioteca</span>
                <div class="flex items-center gap-2">
                    <ThemeSwitch class="theme-switch--mobile" />
                    <button type="button" class="min-h-11 rounded-control border border-edge px-3 py-2 text-sm text-ink hover:bg-surface" @click="logout">
                        {{ t('ui.exit') }}
                    </button>
                </div>

                <SeasonalGarland
                    :season="season"
                    compact
                    class="pointer-events-none absolute inset-x-0 top-full z-10"
                />
            </header>
            <main class="mx-auto w-full max-w-[1200px] p-4 md:p-6">
                <slot />
            </main>
        </div>

        <BottomNav />
    </div>
</template>
