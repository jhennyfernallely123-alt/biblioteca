<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    modelValue: { type: String, required: true },
    languages: { type: Array, required: true },
    curated: { type: Array, default: () => [] },
    locale: { type: String, default: 'es' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const names = computed(() => {
    try {
        return new Intl.DisplayNames([props.locale], { type: 'language' });
    } catch {
        return null;
    }
});

function label(code) {
    try {
        return names.value?.of(code) ?? code;
    } catch {
        return code;
    }
}

const items = computed(() => {
    return props.languages
        .map((code) => ({ code, label: label(code), curated: props.curated.includes(code) }))
        .sort((a, b) => {
            if (a.curated !== b.curated) return a.curated ? -1 : 1;
            return a.label.localeCompare(b.label, props.locale);
        });
});
</script>

<template>
    <div class="max-h-64 overflow-y-auto rounded-control border border-edge bg-surface">
        <button
            v-for="item in items"
            :key="item.code"
            type="button"
            class="flex w-full items-center justify-between gap-2 border-b border-edge px-3 py-2 text-start text-ink last:border-b-0 hover:bg-surface-raised disabled:opacity-50"
            :class="item.code === modelValue ? 'bg-surface-raised font-semibold' : ''"
            :disabled="disabled"
            @click="emit('update:modelValue', item.code)"
        >
            <span>{{ item.label }}</span>
            <span class="text-xs text-ink-muted">
                {{ item.curated ? t('ui.language_reviewed') : t('ui.language_automatic') }}
            </span>
        </button>
    </div>
</template>
