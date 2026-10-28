<script setup>
import { computed } from 'vue';

const props = defineProps({
    providers: {
        type: Object,
        default: () => ({ google: false, facebook: false }),
    },
});

const items = computed(() => [
    {
        key: 'google',
        label: 'Google',
        enabled: Boolean(props.providers?.google),
    },
    {
        key: 'facebook',
        label: 'Facebook',
        enabled: Boolean(props.providers?.facebook),
    },
]);
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <template v-for="provider in items" :key="provider.key">
            <a
                v-if="provider.enabled"
                :href="`/auth/${provider.key}/redirect`"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-line bg-panel px-3 py-2 text-sm font-medium text-moon hover:bg-panel-raised focus:outline-none focus:ring-2 focus:ring-magic"
            >
                <svg v-if="provider.key === 'google'" class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M21.35 11.1h-9.17v2.96h5.3c-.24 1.6-1.8 4.7-5.3 4.7-3.19 0-5.79-2.64-5.79-5.9s2.6-5.9 5.79-5.9c1.82 0 3.04.77 3.74 1.43l2.55-2.46C16.9 4.44 14.97 3.6 12.18 3.6 6.99 3.6 2.8 7.79 2.8 12.98s4.19 9.38 9.38 9.38c5.41 0 9-3.8 9-9.16 0-.61-.07-1.08-.16-1.54Z"
                    />
                </svg>
                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5H16.7V4.6c-.3-.04-1.3-.13-2.47-.13-2.44 0-4.11 1.49-4.11 4.22v2.21H7.4V14h2.72v8h3.38Z"
                    />
                </svg>
                <span>{{ provider.label }}</span>
            </a>

            <button
                v-else
                type="button"
                disabled
                aria-disabled="true"
                class="inline-flex min-h-11 cursor-not-allowed items-center justify-center gap-2 rounded-control border border-line bg-panel/60 px-3 py-2 text-sm font-medium text-mist opacity-70"
            >
                <svg v-if="provider.key === 'google'" class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M21.35 11.1h-9.17v2.96h5.3c-.24 1.6-1.8 4.7-5.3 4.7-3.19 0-5.79-2.64-5.79-5.9s2.6-5.9 5.79-5.9c1.82 0 3.04.77 3.74 1.43l2.55-2.46C16.9 4.44 14.97 3.6 12.18 3.6 6.99 3.6 2.8 7.79 2.8 12.98s4.19 9.38 9.38 9.38c5.41 0 9-3.8 9-9.16 0-.61-.07-1.08-.16-1.54Z"
                    />
                </svg>
                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5H16.7V4.6c-.3-.04-1.3-.13-2.47-.13-2.44 0-4.11 1.49-4.11 4.22v2.21H7.4V14h2.72v8h3.38Z"
                    />
                </svg>
                <span>{{ provider.label }}</span>
                <span class="ms-auto rounded-full border border-line px-2 py-0.5 text-[10px] uppercase tracking-wide">
                    Próximamente
                </span>
            </button>
        </template>
    </div>
</template>
