<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    nickname: { type: String, default: '' },
});

const emit = defineEmits(['close']);
const { t } = useI18n();
const enterButton = ref(null);
let previousOverflow = '';

function close() {
    emit('close');
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        close();
    }

    if (event.key === 'Tab') {
        event.preventDefault();
        enterButton.value?.focus();
    }
}

onMounted(() => {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKeydown);
    enterButton.value?.focus();
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousOverflow;
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="welcome-title"
        aria-describedby="welcome-subtitle"
        class="magic-welcome fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-night/80 p-4 backdrop-blur-md"
    >
        <div class="magic-welcome__aura" aria-hidden="true"></div>
        <div class="magic-welcome__sparkles" aria-hidden="true">
            <span v-for="spark in 10" :key="spark" class="magic-welcome__spark"></span>
        </div>

        <div class="magic-welcome__card relative w-full max-w-[480px] overflow-hidden rounded-modal border border-magic/30 bg-panel/70 px-6 py-9 text-center shadow-modal backdrop-blur-xl sm:px-10 sm:py-12">
            <div class="magic-welcome__seal mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-gold/40 bg-night-raised/70 text-gold" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-7 w-7" aria-hidden="true">
                    <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round" />
                    <path d="M19 16l.9 2.1L22 19l-2.1.9L19 22l-.9-2.1L16 19l2.1-.9L19 16Z" fill="currentColor" />
                </svg>
            </div>

            <h2 id="welcome-title" class="mt-5 text-balance break-words text-4xl leading-tight text-gold sm:text-5xl">
                {{ t('ui.welcome_title', { name: props.nickname }) }}
            </h2>
            <p id="welcome-subtitle" class="mx-auto mt-3 max-w-xs text-sm text-mist sm:text-base">
                {{ t('ui.welcome_subtitle') }}
            </p>

            <button
                ref="enterButton"
                type="button"
                data-welcome-enter
                class="magic-welcome__enter mt-8 inline-flex min-h-11 items-center justify-center rounded-control bg-arcane px-8 py-3 text-sm font-medium text-moon hover:bg-arcane-strong"
                @click="close"
            >
                {{ t('ui.enter') }}
            </button>
        </div>
    </div>
</template>
