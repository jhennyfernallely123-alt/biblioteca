<script setup>
import { usePage } from '@inertiajs/vue3';
import ArcaneStars from '@/Components/ArcaneStars.vue';
import LibraryEmblem from '@/Components/LibraryEmblem.vue';

const page = usePage();
</script>

<template>
    <div class="arcane-sky min-h-screen md:grid md:h-screen md:grid-cols-2 md:overflow-hidden">
        <ArcaneStars />

        <div class="relative flex h-56 items-center justify-center overflow-hidden md:hidden">
            <div class="auth-book-frame auth-book-frame--compact">
                <ArcaneStars class="auth-book-frame__stars" />
                <LibraryEmblem compact class="auth-book-frame__emblem" />
            </div>
        </div>

        <aside class="relative hidden min-h-0 overflow-hidden md:flex md:items-center md:justify-center md:p-8">
            <div class="auth-book-frame">
                <ArcaneStars class="auth-book-frame__stars" />
                <LibraryEmblem class="auth-book-frame__emblem" />
            </div>
        </aside>

        <main class="relative flex min-h-[calc(100vh-14rem)] flex-col justify-center bg-night-raised px-4 py-10 md:h-full md:min-h-0 md:overflow-y-auto md:px-10">
            <div class="mx-auto w-full max-w-[420px]">
                <div
                    v-if="page.props.flash?.error"
                    role="alert"
                    class="mb-5 rounded-control border border-danger/50 bg-danger/10 px-4 py-3 text-sm text-danger"
                >
                    {{ page.props.flash.error }}
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.auth-book-frame {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: min(100%, 528px);
    max-width: 100%;
    padding: 24px;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--color-gold) 32%, transparent);
    border-radius: 24px;
    background:
        radial-gradient(
            ellipse 76% 66% at 50% 38%,
            color-mix(in srgb, var(--color-orquidea) 84%, var(--color-moon)) 0%,
            transparent 72%
        ),
        linear-gradient(
            180deg,
            color-mix(in srgb, var(--color-orquidea) 90%, var(--color-moon)) 0%,
            var(--color-orquidea) 48%,
            color-mix(in srgb, var(--color-orquidea) 82%, var(--color-night)) 100%
        );
    box-shadow:
        0 24px 60px rgba(0, 0, 0, 0.45),
        inset 0 0 0 1px color-mix(in srgb, var(--color-moon) 12%, transparent);
}

.auth-book-frame--compact {
    width: auto;
    padding: 10px;
    border-radius: 16px;
}

.auth-book-frame__stars {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.auth-book-frame__emblem {
    position: relative;
    z-index: 1;
}
</style>
