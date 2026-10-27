<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="paper-texture flex min-h-screen flex-col items-center justify-center px-4 py-8">
        <div class="mb-6 text-center">
            <Link href="/" class="font-serif text-3xl text-bosque">Biblioteca</Link>
            <p class="mt-1 text-sm text-tinta/60">Tu biblioteca personal</p>
        </div>

        <div class="w-full max-w-[420px] rounded-card bg-crema p-6 shadow-card">
            <slot />
        </div>
    </div>
</template>
