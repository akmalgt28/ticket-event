<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const search = ref('');

function submitSearch() {
    router.get(route('public.events.index'), search.value.trim() ? { search: search.value.trim() } : {}, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[var(--brand-paper)] text-[var(--brand-ink)]">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-[var(--brand-paper)]">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex shrink-0 items-center gap-3" aria-label="Ticket Event beranda">
                    <span class="brand-bg-dark flex h-9 w-9 items-center justify-center text-sm font-black text-white">TE</span>
                    <span class="hidden text-base font-black tracking-tight sm:inline">Ticket Event</span>
                </Link>

                <nav class="hidden items-center gap-5 text-sm font-bold md:flex" aria-label="Navigasi publik">
                    <Link href="/events" class="transition hover:text-[var(--brand-primary)]">Jelajah event</Link>
                    <Link href="/order-status" class="transition hover:text-[var(--brand-primary)]">Cek order</Link>
                </nav>

                <form class="ml-auto flex min-w-0 flex-1 justify-end sm:max-w-sm" @submit.prevent="submitSearch">
                    <label class="sr-only" for="public-search">Cari event</label>
                    <div class="flex w-full border border-slate-300 bg-white focus-within:border-[var(--brand-primary)]">
                        <input id="public-search" v-model="search" type="search" placeholder="Cari event, kota, topik..." class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm focus:ring-0" />
                        <button type="submit" class="brand-bg px-4 text-sm font-black text-white transition hover:bg-[var(--brand-primary-dark)]">Cari</button>
                    </div>
                </form>

                <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="hidden text-sm font-black md:inline">Dashboard</Link>
                <Link v-else :href="route('login')" class="hidden text-sm font-black md:inline">Login</Link>
            </div>
            <div class="border-t border-slate-200 px-4 py-2 md:hidden">
                <nav class="mx-auto flex max-w-7xl items-center gap-5 text-sm font-bold" aria-label="Navigasi mobile">
                    <Link href="/events" class="hover:text-[var(--brand-primary)]">Jelajah event</Link>
                    <Link href="/order-status" class="hover:text-[var(--brand-primary)]">Cek order</Link>
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="ml-auto">Dashboard</Link>
                    <Link v-else :href="route('login')" class="ml-auto">Login</Link>
                </nav>
            </div>
        </header>
        <main class="flex-1"><slot /></main>
        <footer class="mt-20 border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <span class="font-black text-[var(--brand-ink)]">Ticket Event</span>
                <span>Pesan tiket tanpa akun. Datang dengan tenang.</span>
            </div>
        </footer>
    </div>
</template>
