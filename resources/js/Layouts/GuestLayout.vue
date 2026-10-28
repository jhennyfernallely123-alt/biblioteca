<script setup>
import { usePage } from '@inertiajs/vue3';
import ArcaneStars from '@/Components/ArcaneStars.vue';
import LibraryEmblem from '@/Components/LibraryEmblem.vue';

const page = usePage();
</script>

<template>
    <div class="arcane-sky min-h-screen md:grid md:grid-cols-2">
        <ArcaneStars />

        <div class="relative flex h-56 items-center justify-center overflow-hidden md:hidden">
            <LibraryEmblem compact />
        </div>

        <aside class="relative hidden overflow-hidden md:flex md:items-center md:justify-center md:p-8">
            <LibraryEmblem />
        </aside>

        <main class="relative flex min-h-[calc(100vh-14rem)] flex-col justify-center bg-night-raised px-4 py-10 md:min-h-screen md:px-10">
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
