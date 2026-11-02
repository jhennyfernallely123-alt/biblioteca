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
    { label: t('ui.nav_decorations'), href: '/decorations' },
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
        <ArcaneStars />
        <SeasonalParticles :season="season" />
        <SeasonalDecor :season="season" />
        <LocaleSync />
        <FlashToast v-if="notice" :key="notice.id" :text="notice.text" />
        <MagicWelcome v-if="showWelcome" :nickname="user?.nickname || user?.name || ''" @close="showWelcome = false" />
        <aside class="relative z-10 hidden border-e border-line bg-night-raised/95 p-4 text-moon md:flex md:w-60 md:flex-col">
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

        <div class="relative z-10 flex-1 pb-20 md:pb-0">
            <header class="relative flex items-center justify-between border-b border-line bg-night-raised/95 px-4 py-3 text-moon md:hidden">
                <span class="font-serif text-xl text-gold">Biblioteca</span>
                <button type="button" class="min-h-11 rounded-control border border-line px-3 py-2 text-sm text-moon hover:bg-panel" @click="logout">
                    {{ t('ui.exit') }}
                </button>

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

        <nav data-mobile-nav class="fixed inset-x-0 bottom-0 z-20 flex justify-around border-t border-line bg-night-raised/95 py-2 text-moon md:hidden">
            <Link
                v-for="link in primaryLinks"
                :key="link.href"
                :href="link.href"
                class="flex min-h-11 items-center border-b-2 px-3 py-2 text-sm"
                :class="isActive(link.href) ? 'border-magic text-gold' : 'border-transparent'"
                :aria-current="isActive(link.href) ? 'page' : undefined"
            >
                {{ link.label }}
            </Link>

            <div class="relative flex items-center">
                <button
                    type="button"
                    data-nav-menu-toggle
                    class="flex min-h-11 items-center border-b-2 px-3 py-2 text-sm"
                    :class="menuActive || menuOpen ? 'border-magic text-gold' : 'border-transparent'"
                    :aria-label="t('ui.menu')"
                    :title="t('ui.menu')"
                    :aria-expanded="menuOpen"
                    aria-haspopup="true"
                    aria-controls="mobile-nav-menu"
                    @click="menuOpen = !menuOpen"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </button>

                <div
                    v-if="menuOpen"
                    id="mobile-nav-menu"
                    data-nav-menu
                    role="menu"
                    class="absolute bottom-full end-0 z-50 mb-3 w-44 rounded-card border border-line bg-night-raised p-1 shadow-modal"
                >
                    <Link
                        v-for="link in menuLinks"
                        :key="link.href"
                        :href="link.href"
                        role="menuitem"
                        class="block rounded-control px-3 py-2 text-sm text-mist hover:bg-panel hover:text-moon"
                        :class="isActive(link.href) ? 'bg-panel text-gold' : ''"
                        :aria-current="isActive(link.href) ? 'page' : undefined"
                        @click="menuOpen = false"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </div>
        </nav>
    </div>
</template>
