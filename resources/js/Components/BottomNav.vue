<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const page = usePage();
const isActive = (href) =>
    href === '/' ? page.url === '/' : page.url.startsWith(href);

const menuItems = computed(() => [
    { label: t('ui.nav_library'), href: '/', iconPath: 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z' },
    { label: t('ui.nav_shelves'), href: '/shelves', iconPath: 'M4 5h16v14H4zM7 5h2v14H7zM12 5h2v14h-2zM17 5h2v14h-2z' },
    ...(page.props.auth.user?.is_master
        ? [{ label: t('ui.nav_catalog'), href: '/catalog', iconPath: 'M4 6h16M4 12h16M4 18h16' }]
        : []),
    { label: t('ui.nav_cards'), href: '/cards', iconPath: 'M5 2h14a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zM8 5h5v8H8zM16 6h1M16 9h1M8 16h8v3H8z' },
    { label: t('ui.nav_decorations'), href: '/decorations', iconPath: 'M12 3l1.9 5.8L20 10l-6.1 1.2L12 17l-1.9-5.8L4 10l6.1-1.2z' },
    { label: t('ui.nav_album'), href: '/album', iconPath: 'M2 7h20v10H2V7zm2 2h16v6H4V9z' },
    { label: t('ui.nav_settings'), href: '/settings', iconPath: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a2 2 0 0 0-1.82-.33 1 1 0 0 0-1 1.51V21a1 1 0 1 1-4 0v-.09A1 1 0 0 0 9 19.4a1 1 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1 1 0 0 0 .33-1.82 1 1 0 0 0-1.51-1H3a1 1 0 1 1 0-4h.09A1 1 0 0 0 4.6 9a1 1 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1 1 0 0 0 1.82.33H9a1 1 0 0 0 1-1.51V3a1 1 0 1 1 4 0v.09a1 1 0 0 0 1 1.51 1 1 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1 1 0 0 0-.33 1.82V9a1 1 0 0 0 1.51 1H21a1 1 0 1 1 0 4h-.09A1 1 0 0 0 19.4 15z' },
]);

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

const desktopQuery =
    typeof window !== 'undefined' && window.matchMedia
        ? window.matchMedia('(min-width: 768px)')
        : null;

function handleViewportChange(event) {
    if (event.matches) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    if (desktopQuery) {
        if (desktopQuery.matches) {
            closeMenu();
        }
        desktopQuery.addEventListener('change', handleViewportChange);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    desktopQuery?.removeEventListener('change', handleViewportChange);
});
</script>

<template>
    <nav class="bottom-nav md:hidden">
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
        class="album-menu-overlay md:hidden"
        :class="{ 'album-menu-overlay--open': menuOpen }"
        @click="closeMenu"
        aria-hidden="true"
    ></div>

    <div
        v-if="menuOpen"
        class="album-menu md:hidden"
        :class="{ 'album-menu--open': menuOpen }"
        role="menu"
        aria-label="Menú de la colección"
    >
        <div class="album-menu__list">
            <a
                v-for="(item, index) in menuItems"
                :key="index"
                :href="item.href"
                class="album-menu__item"
                @click="closeMenu"
            >
                <svg class="album-menu__item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path :d="item.iconPath" />
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
