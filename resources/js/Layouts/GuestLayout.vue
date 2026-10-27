<script setup>
import { Link } from '@inertiajs/vue3';
import ArcaneStars from '@/Components/ArcaneStars.vue';
</script>

<template>
    <div class="arcane-sky flex min-h-screen flex-col items-center justify-center px-4 py-8">
        <ArcaneStars />
        <div class="mb-6 text-center">
            <Link href="/" class="font-serif text-3xl text-gold">Biblioteca</Link>
            <p class="mt-1 text-sm text-mist">Tu biblioteca personal</p>
        </div>

        <div class="w-full max-w-[420px] rounded-card border border-line bg-panel p-6 shadow-modal">
            <slot />
        </div>
    </div>
</template>
