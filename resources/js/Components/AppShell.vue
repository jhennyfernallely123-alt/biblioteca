<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ArcaneStars from './ArcaneStars.vue';
import LocaleSync from './LocaleSync.vue';
import { useI18n } from '@/i18n';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useI18n();

const links = computed(() => [
    { label: t('ui.nav_library'), href: '/' },
    { label: t('ui.nav_settings'), href: '/settings' },
]);

const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="arcane-sky min-h-screen md:flex">
        <ArcaneStars />
        <LocaleSync />
        <aside class="hidden border-e border-line bg-night-raised/95 p-4 text-moon md:flex md:w-60 md:flex-col">
            <Link href="/" class="mb-8 font-serif text-2xl text-gold">
                Biblioteca
            </Link>
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
            <div class="mt-auto pt-6 text-sm text-mist">
                <p class="mb-2">{{ user?.name }}</p>
                <button type="button" class="min-h-11 w-full rounded-control border border-line px-3 py-2 text-start text-moon hover:bg-panel" @click="logout">
                    {{ t('ui.logout') }}
                </button>
            </div>
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
