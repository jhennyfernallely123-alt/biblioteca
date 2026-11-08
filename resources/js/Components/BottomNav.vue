<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const page = usePage();
const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);

const menuItems = [
    { label: 'Inicio', href: '/', icon: { cx: 12, cy: 12, r: 3 } },
    { label: 'Galería', href: '/books', icon: { cx: 12, cy: 5, r: 3 } },
    { label: 'Recuerdos', href: '/cards', icon: { path: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5' } },
    { label: 'Actividades', href: '/events', icon: { cx: 12, cy: 8, r: 7 } },
    { label: 'Juegos', href: '/decorations', icon: { path: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' } },
    { label: 'Eventos', href: '/events', icon: { cx: 12, cy: 13, r: 3 } },
    { label: 'Fechas importantes', href: '/events', icon: { cx: 12, cy: 17, r: 3 } },
    { label: 'Preferencias', href: '/settings', icon: { cx: 12, cy: 12, r: 3 } },
    { label: 'Estanterías', href: '/shelves', icon: { cx: 12, cy: 8, r: 7 } },
];

const TABS = [
    { id: 'library', href: '/', label: 'Biblioteca', iconPath: 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z' },
    { id: 'shelves', href: '/shelves', label: 'Estanterías', iconPath: 'M4 5h16v14H4zM7 5h2v14H7zM12 5h2v14h-2zM17 5h2v14h-2z' },
    { id: 'menu', href: null, label: 'Menú', iconPath: 'M3 12h18M3 6h18M3 18h18' },
];

const activeTab = ref(isActive('/shelves') ? 'shelves' : 'library');
const menuOpen = ref(false);

const gliderTransform = computed(() => {
    const index = TABS.findIndex((t) => t.id === activeTab.value);
    if (index < 0) return 'translateX(0%)';
    return `translateX(${index * 100}%)`;
});

function toggleMenu() {
    menuOpen.value = !menuOpen.value;
}

function closeMenu() {
    menuOpen.value = false;
}

function goTab(tabId) {
    const tab = TABS.find((t) => t.id === tabId);
    if (!tab) return;
    if (tabId === 'menu') {
        toggleMenu();
        return;
    }
    closeMenu();
    activeTab.value = tabId;
    router.visit(tab.href);
}

function logout() {
    closeMenu();
    router.post('/logout', {}, {});
}

function handleKeydown(e) {
    if (e.key === 'Escape' && menuOpen.value) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <nav class="bottom-nav">
        <div class="tabs-container">
            <div class="tabs">
                <label
                    v-for="tab in TABS"
                    :key="tab.id"
                    class="tab"
                    :class="{ 'tab--active': activeTab === tab.id }"
                    @click="goTab(tab.id)"
                    :aria-label="tab.label"
                >
                    <svg
                        class="tab__icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path :d="tab.iconPath" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </label>
                <span class="glider" :style="{ transform: gliderTransform }"></span>
            </div>
        </div>
    </nav>

    <div
        v-if="menuOpen"
        class="album-menu-overlay"
        :class="{ 'album-menu-overlay--open': menuOpen }"
        @click="closeMenu"
        aria-hidden="true"
    ></div>

    <div
        v-if="menuOpen"
        class="album-menu"
        :class="{ 'album-menu--open': menuOpen }"
        role="menu"
        aria-label="Menú de la colección"
    >
        <div class="album-menu__header">
            <h2 class="album-menu__title">Nuestra Historia</h2>
            <p class="album-menu__subtitle">Todo nuestro álbum, en un solo lugar</p>
            <button
                type="button"
                class="album-menu__close"
                @click="closeMenu"
                aria-hidden="true"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <div class="album-menu__list">
            <a
                v-for="(item, index) in menuItems"
                :key="index"
                :href="item.href"
                class="album-menu__item"
                @click="closeMenu"
            >
                <svg class="album-menu__item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle :cx="item.icon.cx" :cy="item.icon.cy" :r="item.icon.r" />
                    <path v-if="item.icon.path" :d="item.icon.path" />
                    <line v-if="item.icon.line" :x1="item.icon.x1" :y1="item.icon.y1" :x2="item.icon.x2" :y2="item.icon.y2" />
                </svg>
                <span class="album-menu__item-label">{{ item.label }}</span>
                <span class="album-menu__item-badge" v-if="item.badge">{{ item.badge }}</span>
                <svg class="album-menu__item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </a>
        </div>

        <div class="album-menu__footer">
            <button type="button" class="album-menu__footer-item" @click="logout">
                <svg class="album-menu__footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="21" x2="9" y2="21" />
                </svg>
                {{ t('ui.logout') }}
            </button>
        </div>
    </div>
</template>
