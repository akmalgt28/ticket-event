<script setup>
import { onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({ catalog: { type: Boolean, default: false } });
const menuOpen = ref(false);
const menuButton = ref(null);
const search = ref('');
const stopNavigationListener = router.on('navigate', () => { menuOpen.value = false; });
onUnmounted(stopNavigationListener);

function closeMenu() {
    menuOpen.value = false;
    menuButton.value?.focus();
}

function submitSearch() {
    menuOpen.value = false;
    router.get(route('public.events.index'), search.value.trim() ? { search: search.value.trim() } : {}, { replace: true });
}
</script>

<template>
    <div class="public-site flex min-h-dvh flex-col text-slate-950" :class="catalog ? 'bg-white' : 'bg-[var(--arc-blue)]'">
        <a href="#main-content" class="sr-only z-[70] rounded-full bg-white px-4 py-2 text-sm focus:not-sr-only focus:fixed focus:left-4 focus:top-3">Lewati ke konten</a>
        <header class="sticky top-0 z-50 border-b border-slate-200" :class="catalog ? 'bg-[#ffffff]' : 'bg-white'" @keydown.esc="closeMenu">
            <div class="relative mx-auto flex h-16 items-center justify-between gap-5 px-5 sm:px-8" :class="catalog ? 'max-w-7xl' : 'max-w-6xl'">
                <Link :href="route('public.events.index')" aria-label="ARC — beranda Ticket Event" class="shrink-0" @click="menuOpen = false">
                    <img src="/images/arc-logo.png" alt="ARC — PT Anugerah Rezeki Cemerlang" class="h-14 w-24 object-contain" width="96" height="56" />
                </Link>
                <nav class="hidden items-center gap-7 text-sm font-semibold text-[#173c73] md:flex" aria-label="Navigasi utama">
                    <Link :href="route('public.events.index')" :aria-current="catalog ? 'page' : undefined" class="py-5 transition hover:text-[var(--brand-primary)]" :class="catalog ? 'text-[var(--brand-primary)]' : ''">Beranda</Link>
                    <Link :href="route('public.events.index') + '#catalog'" class="py-5 transition hover:text-[var(--brand-primary)]">Jelajahi Event</Link>
                    <Link :href="route('public.orders.status')" class="py-5 transition hover:text-[var(--brand-primary)]">Cek Order</Link>
                </nav>
                <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="arc-button hidden min-h-9 px-6 text-xs md:inline-flex">Dashboard<span aria-hidden="true" class="ml-3">↗</span></Link>
                <button ref="menuButton" type="button" class="flex h-11 w-11 items-center justify-center rounded-lg text-[var(--arc-blue)] transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-primary)] md:hidden" :aria-expanded="menuOpen" aria-controls="public-menu" :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'" @click="menuOpen = !menuOpen">
                    <i class="fa-solid h-5 w-5" :class="menuOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
                </button>
                <div v-if="menuOpen" id="public-menu" class="absolute inset-x-0 top-full border-b border-t border-slate-200 bg-white px-5 pb-5 pt-3 sm:px-8 md:hidden">
                    <nav class="grid gap-1 text-sm font-semibold text-[var(--arc-blue)]" aria-label="Menu publik">
                        <Link :href="route('public.events.index')" class="rounded-lg px-3 py-3 hover:bg-white" @click="menuOpen = false">Beranda</Link>
                        <Link :href="route('public.events.index') + '#catalog'" class="rounded-lg px-3 py-3 hover:bg-white" @click="menuOpen = false">Jelajahi Event</Link>
                        <Link :href="route('public.orders.status')" class="rounded-lg px-3 py-3 hover:bg-white" @click="menuOpen = false">Cek Order</Link>
                        <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="rounded-lg px-3 py-3 hover:bg-white" @click="menuOpen = false">Dashboard</Link>
                    </nav>
                    <form class="mt-3 flex gap-2" role="search" @submit.prevent="submitSearch">
                        <label for="public-search" class="sr-only">Cari event</label>
                        <input id="public-search" v-model="search" type="search" placeholder="Cari event atau lokasi..." class="arc-input min-w-0 flex-1" />
                        <button type="submit" class="arc-button px-5">Cari</button>
                    </form>
                </div>
            </div>
        </header>
        <main id="main-content" class="min-w-0 flex-1"><slot /></main>
    </div>
</template>
