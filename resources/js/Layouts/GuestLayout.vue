<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[var(--brand-paper)] pt-6 sm:justify-center sm:pt-0"
    >
        <Link href="/" class="flex items-center gap-3 text-[var(--brand-ink)]" aria-label="Ticket Event beranda">
            <span class="brand-bg-dark flex h-10 w-10 items-center justify-center text-sm font-black text-white">TE</span>
            <span class="text-lg font-black tracking-tight">Ticket Event</span>
        </Link>

        <div
            class="mt-6 w-full overflow-hidden border border-slate-200 bg-white px-6 py-4 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
