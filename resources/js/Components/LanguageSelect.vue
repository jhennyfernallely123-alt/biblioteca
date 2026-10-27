<script setup>
import { computed, ref } from 'vue';
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
const query = ref('');

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

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return props.languages
        .map((code) => ({ code, label: label(code), curated: props.curated.includes(code) }))
        .filter((item) => !term || item.label.toLowerCase().includes(term) || item.code.includes(term))
        .sort((a, b) => {
            if (a.curated !== b.curated) return a.curated ? -1 : 1;
            return a.label.localeCompare(b.label, props.locale);
        });
});
</script>

<template>
    <div class="space-y-2">
        <input
            v-model="query"
            type="search"
            class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
            :placeholder="t('ui.language_search')"
            :aria-label="t('ui.language_search')"
            :disabled="disabled"
        >
        <div class="max-h-64 overflow-y-auto rounded-control border border-madera/35 bg-crema">
            <button
                v-for="item in filtered"
                :key="item.code"
                type="button"
                class="flex w-full items-center justify-between gap-2 border-b border-madera/15 px-3 py-2 text-start last:border-b-0 hover:bg-pergamino disabled:opacity-50"
                :class="item.code === modelValue ? 'bg-pergamino font-semibold' : ''"
                :disabled="disabled"
                @click="emit('update:modelValue', item.code)"
            >
                <span>{{ item.label }}</span>
                <span class="text-xs text-tinta/60">
                    {{ item.curated ? t('ui.language_reviewed') : t('ui.language_automatic') }}
                </span>
            </button>
            <p v-if="filtered.length === 0" class="p-3 text-sm text-tinta/60">
                {{ t('ui.language_no_results') }}
            </p>
        </div>
    </div>
</template>
