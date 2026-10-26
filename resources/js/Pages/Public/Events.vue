<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import EventCatalogCard from '@/Components/EventCatalogCard.vue';
import FeaturedEvents from '@/Components/FeaturedEvents.vue';

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ all: 0, ongoing: 0, upcoming: 0 }) },
    featuredEvents: { type: Array, default: () => [] },
    ongoingEvents: { type: Array, default: () => [] },
    upcomingEvents: { type: Array, default: () => [] },
});
const defaults = { search: '', category: '', location: '', schedule: 'all' };
const form = reactive({ ...defaults, ...props.filters });
const processing = ref(false);
watch(() => props.filters, (filters) => Object.assign(form, defaults, filters));
const filtered = computed(() => Boolean(props.filters.search || props.filters.category || props.filters.location || (props.filters.schedule && props.filters.schedule !== 'all')));
const discover = computed(() => !filtered.value && props.events.current_page === 1);
const sections = computed(() => [
    { key: 'ongoing', title: 'Event Sedang Berlangsung', description: 'Masih ada momen yang bisa kamu ikuti hari ini.', events: props.ongoingEvents },
    { key: 'upcoming', title: 'Event Mendatang', description: 'Catat tanggalnya, siapkan pengalaman berikutnya.', events: props.upcomingEvents },
].filter((section) => section.events.length));
const schedules = [{ key: 'all', label: 'Semua Event' }, { key: 'ongoing', label: 'Berlangsung' }, { key: 'upcoming', label: 'Mendatang' }];
const chips = computed(() => [
    { key: 'search', label: props.filters.search ? 'Pencarian: ' + props.filters.search : '' },
    { key: 'category', label: props.filters.category },
    { key: 'location', label: props.filters.location },
].filter((chip) => chip.label));
const paginationNumbers = computed(() => (props.events.links || []).filter((link) => /^\d+$/.test(link.label) || link.label === '...'));

function applyFilters(overrides = {}) {
    Object.assign(form, overrides);
    const query = Object.fromEntries(Object.entries(form).filter(([key, value]) => value !== '' && !(key === 'schedule' && value === 'all')));
    router.get(route('public.events.index'), query, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { processing.value = true; },
        onFinish: () => { processing.value = false; },
        onSuccess: () => document.querySelector('#catalog')?.scrollIntoView({ block: 'start', behavior: 'instant' }),
    });
}

function resetFilters() {
    applyFilters(defaults);
}
</script>

<template>
    <Head title="ARC Ticket Event — Temukan Event Pilihanmu" />
    <PublicLayout catalog>
        <section class="border-b border-blue-100 bg-[#edf4ff]">
            <div class="mx-auto max-w-7xl px-5 pb-8 pt-7 sm:px-8 md:pb-10 md:pt-10">
                <div class="mb-7 flex flex-col justify-between gap-4 md:flex-row md:items-end md:gap-10">
                    <div><p class="mb-3 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.22em] text-blue-700"><span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span> ARC Ticket Event</p><h1 class="text-3xl font-bold leading-tight tracking-tight text-[#102c57] sm:text-4xl">Temukan event.<br class="sm:hidden" /> Ciptakan cerita.</h1></div>
                    <p class="max-w-sm text-sm leading-6 text-slate-500">Dari agenda favorit hingga pengalaman baru.<br class="hidden md:block" /> Temukan tiket untuk momen yang kamu tunggu.</p>
                </div>
                <FeaturedEvents v-if="discover" :events="featuredEvents" />
                <div v-else class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-white px-5 py-4 text-sm"><p class="text-slate-600">Cari pengalaman yang sesuai dengan rencanamu.</p><button type="button" class="font-bold text-blue-700 hover:underline" @click="resetFilters">Kembali ke semua event <span aria-hidden="true">↗</span></button></div>
            </div>
        </section>

        <section id="catalog" class="mx-auto max-w-7xl scroll-mt-20 px-5 pt-7 sm:px-8 md:pt-9" aria-labelledby="catalog-search-heading">
            <div class="rounded-2xl border border-blue-100 bg-white p-4 sm:p-5">
                <h2 id="catalog-search-heading" class="mb-4 text-base font-bold text-[#102c57]">Mau ke event apa?</h2>
                <form class="flex min-w-0 flex-col gap-3 md:flex-row md:flex-wrap lg:flex-nowrap" role="search" aria-label="Pencarian dan filter event" @submit.prevent="applyFilters()">
                    <div class="min-w-0 flex-1 md:basis-64 lg:basis-auto">
                        <label class="sr-only" for="catalog-search">Cari berdasarkan judul atau lokasi</label>
                        <div class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-[#f8faff] px-4 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                            <svg class="h-5 w-5 shrink-0 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
                            <input id="catalog-search" v-model="form.search" type="search" maxlength="100" placeholder="Cari nama event atau lokasi..." class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base text-slate-800 placeholder:text-sm placeholder:text-slate-400 focus:ring-0 md:text-sm" />
                        </div>
                    </div>
                    <div v-if="categories.length" class="min-w-0 md:w-44"><label for="catalog-category" class="sr-only">Kategori event</label><select id="catalog-category" v-model="form.category" class="min-h-12 w-full rounded-xl border-slate-200 bg-[#f8faff] py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-blue-500 focus:ring-blue-500" @change="applyFilters()"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.name" :value="category.name">{{ category.name }}</option></select></div>
                    <div class="min-w-0 md:w-48"><label for="catalog-location" class="sr-only">Lokasi event</label><select id="catalog-location" v-model="form.location" class="min-h-12 w-full rounded-xl border-slate-200 bg-[#f8faff] py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-blue-500 focus:ring-blue-500" @change="applyFilters()"><option value="">Semua lokasi</option><option v-for="location in locations" :key="location" :value="location">{{ location }}</option></select></div>
                    <button type="submit" :disabled="processing" class="arc-button min-h-12 rounded-xl px-7">{{ processing ? 'Mencari...' : 'Cari Event' }}<span aria-hidden="true" class="ml-3">→</span></button>
                </form>
            </div>
            <div v-if="categories.length" class="flex flex-wrap items-center gap-2 pb-1 pt-5" aria-label="Jelajahi kategori">
                <span class="mr-2 text-xs font-medium text-slate-500">Jelajahi minatmu</span>
                <button type="button" :aria-pressed="!filters.category" class="inline-flex min-h-9 items-center rounded-full border px-4 text-xs font-semibold transition" :class="!filters.category ? 'border-blue-700 bg-[#144ba3] text-white' : 'border-blue-100 bg-white text-slate-600 hover:border-blue-400'" @click="applyFilters({ category: '' })">Semua kategori</button>
                <button v-for="category in categories" :key="category.name" type="button" :aria-pressed="filters.category === category.name" class="inline-flex min-h-9 items-center gap-2 rounded-full border px-4 text-xs font-semibold transition" :class="filters.category === category.name ? 'border-blue-700 bg-[#144ba3] text-white' : 'border-blue-100 bg-white text-slate-600 hover:border-blue-400'" @click="applyFilters({ category: category.name })">{{ category.name }}<span class="text-[10px] opacity-70">{{ category.count }}</span></button>
            </div>
        </section>

        <div v-if="discover && sections.length" class="mx-auto max-w-7xl space-y-10 px-5 pt-10 sm:px-8 md:space-y-12">
            <section v-for="section in sections" :key="section.key" :aria-labelledby="section.key + '-heading'">
                <div class="mb-5 flex items-start justify-between gap-4"><div><h2 :id="section.key + '-heading'" class="flex items-center gap-2.5 text-xl font-bold tracking-tight text-[#102c57]"><span v-if="section.key === 'ongoing'" class="h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>{{ section.title }}</h2><p class="mt-1.5 text-xs leading-5 text-slate-500 sm:text-sm">{{ section.description }}</p></div><button type="button" class="shrink-0 pt-1 text-xs font-bold text-blue-700 hover:underline sm:text-sm" @click="applyFilters({ schedule: section.key })">Lihat semua <span aria-hidden="true">↗</span></button></div>
                <div class="grid min-w-0 gap-5 sm:grid-cols-2 lg:grid-cols-4"><EventCatalogCard v-for="event in section.events" :key="event.id" :event="event" /></div>
            </section>
        </div>

        <section class="mx-auto max-w-7xl px-5 pb-12 pt-10 sm:px-8 md:pb-16 md:pt-12" aria-labelledby="all-events-heading" :aria-busy="processing">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="mb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-blue-600">Temukan pilihanmu</p><h2 id="all-events-heading" class="text-2xl font-bold tracking-tight text-[#102c57]">{{ filtered ? 'Hasil pencarian event' : 'Semua Event' }}</h2></div><p class="text-xs text-slate-500" aria-live="polite">{{ events.total }} event {{ filtered ? 'ditemukan' : 'tersedia' }}</p></div>
            <div class="mb-6 mt-5 flex flex-wrap gap-2 border-b border-blue-100 pb-4" role="group" aria-label="Waktu event">
                <button v-for="schedule in schedules" :key="schedule.key" type="button" :aria-pressed="(filters.schedule || 'all') === schedule.key" class="inline-flex min-h-10 items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition sm:px-4 sm:text-sm" :class="(filters.schedule || 'all') === schedule.key ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:bg-white hover:text-blue-700'" @click="applyFilters({ schedule: schedule.key })">{{ schedule.label }}<span class="rounded-md px-1.5 py-0.5 text-[10px]" :class="(filters.schedule || 'all') === schedule.key ? 'bg-white/80 text-blue-800' : 'bg-slate-100 text-slate-500'">{{ counts[schedule.key] }}</span></button>
            </div>
            <div v-if="chips.length" class="mb-5 flex flex-wrap items-center gap-2"><button v-for="chip in chips" :key="chip.key" type="button" :aria-label="'Hapus filter ' + chip.label" class="inline-flex max-w-full items-center gap-2 rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs text-blue-800" @click="applyFilters({ [chip.key]: '' })"><span class="truncate">{{ chip.label }}</span><span aria-hidden="true">×</span></button><button type="button" class="px-2 text-xs font-semibold text-slate-500 underline underline-offset-4" @click="resetFilters">Reset filter</button></div>
            <div v-if="events.data.length" class="grid min-w-0 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"><EventCatalogCard v-for="event in events.data" :key="event.id" :event="event" /></div>
            <div v-else class="rounded-2xl border border-dashed border-blue-200 bg-white px-6 py-14 text-center">
                <svg class="mx-auto mb-5 h-14 w-14 text-blue-300" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="21" cy="21" r="13" /><path d="m31 31 10 10M16 21h10" /></svg>
                <h3 class="text-lg font-bold text-[#102c57]">{{ filtered ? 'Belum menemukan event yang cocok?' : 'Nantikan event berikutnya.' }}</h3><p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">{{ filtered ? 'Coba kata kunci lain atau ubah kategori, lokasi, dan waktu event.' : 'Belum ada event yang dipublikasikan. Kunjungi halaman ini kembali untuk melihat agenda terbaru.' }}</p><button v-if="filtered" type="button" class="arc-button mt-6" @click="resetFilters">Lihat semua event</button>
            </div>
            <nav v-if="events.last_page > 1" aria-label="Halaman katalog event" class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-blue-100 pt-6">
                <p class="text-xs text-slate-500">Halaman {{ events.current_page }} dari {{ events.last_page }}</p>
                <div class="flex flex-wrap items-center gap-1.5"><Link v-if="events.prev_page_url" :href="events.prev_page_url" preserve-scroll class="inline-flex h-10 items-center rounded-lg border border-blue-100 bg-white px-3 text-xs font-semibold text-blue-800" aria-label="Halaman sebelumnya">←</Link><template v-for="(link, index) in paginationNumbers" :key="index"><Link v-if="link.url" :href="link.url" preserve-scroll :aria-current="link.active ? 'page' : undefined" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-2 text-xs font-semibold" :class="link.active ? 'bg-[#144ba3] text-white' : 'border border-blue-100 bg-white text-slate-600 hover:bg-blue-50'">{{ link.label }}</Link><span v-else class="px-2 text-slate-400">…</span></template><Link v-if="events.next_page_url" :href="events.next_page_url" preserve-scroll class="inline-flex h-10 items-center rounded-lg border border-blue-100 bg-white px-3 text-xs font-semibold text-blue-800" aria-label="Halaman berikutnya">→</Link></div>
            </nav>
        </section>

        <footer class="border-t border-blue-100 bg-white">
            <div class="mx-auto grid max-w-7xl gap-6 px-5 py-8 sm:px-8 md:grid-cols-[1fr_auto] md:items-center">
                <div><p class="text-sm font-bold tracking-wide text-[#123f8f]">ARC <span class="mx-2 font-normal text-blue-200">/</span> TICKET EVENT</p><p class="mt-2 text-xs text-slate-500">Temukan event, pesan tiket, dan nikmati momennya.</p></div>
                <nav class="flex flex-wrap gap-x-6 gap-y-3 text-xs font-semibold text-slate-600" aria-label="Navigasi footer"><a href="#catalog" class="hover:text-blue-700">Jelajahi Event</a><Link :href="route('public.orders.status')" class="hover:text-blue-700">Cek Order</Link><Link :href="$page.props.auth?.user ? route('dashboard') : route('login')" class="hover:text-blue-700">{{ $page.props.auth?.user ? 'Dashboard' : 'Login' }}</Link></nav>
            </div>
        </footer>
    </PublicLayout>
</template>