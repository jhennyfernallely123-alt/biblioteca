<script setup>
import { computed } from 'vue';
import Modal from './Modal.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    show: { type: Boolean, default: false },
    letter: { type: Object, default: null },
    copies: { type: Number, default: 0 },
    duplicate: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const { t } = useI18n();

const paragraphs = computed(
    () => (props.letter?.card?.letter ?? '')
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean)
);
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <article v-if="letter" class="card-letter p-5 md:p-8">
            <header class="mb-6 flex items-start justify-between gap-4">
                <h2 class="font-serif text-lg text-tinta">
                    {{ t('ui.cards_letter_from', { character: letter.card.character }) }}
                </h2>

                <button
                    type="button"
                    data-letter-close
                    class="min-h-11 rounded-control border border-dorado/50 px-3 py-2 text-sm text-tinta hover:bg-pergamino"
                    @click="emit('close')"
                >
                    {{ t('ui.cards_close') }}
                </button>
            </header>

            <div class="card-letter__body px-1 text-base md:px-6">
                <p
                    v-for="(paragraph, index) in paragraphs"
                    :key="index"
                    :class="{ 'card-letter__dropcap': index === 0 }"
                    class="mb-4"
                >
                    {{ paragraph }}
                </p>
            </div>

            <footer class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-dorado/30 pt-4 text-sm text-tinta">
                <span>{{ duplicate ? t('ui.cards_duplicate') : t('ui.cards_new_card') }}</span>
                <span v-if="copies > 1" class="font-serif text-base text-dorado">
                    {{ t('ui.cards_copies', { count: copies }) }}
                </span>
            </footer>
        </article>
    </Modal>
</template>
