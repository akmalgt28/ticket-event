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
        <main class="catalog-page bg-[var(--catalog-ground)]">
            <section class="border-b border-[#e2e8f0] bg-[var(--catalog-surface)]">
                <div class="mx-auto max-w-7xl px-5 pb-8 pt-7 sm:px-8 md:pb-10 md:pt-9">
                    <div class="flex flex-col justify-between gap-5 border-b border-[#e2e8f0] pb-6 md:flex-row md:items-end">
                        <div>

                            <h1 class="type-title-lg max-w-xl text-[#101014]">Jelajahi event pilihanmu.</h1>
                        </div>
                        <p class="type-body-md max-w-sm text-[#101014]">Tiket, waktu, dan lokasi dalam satu katalog. Pilih event, lalu berangkat.</p>
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <div><p class="text-xs text-[#101014]">Event yang sedang terbuka untuk umum.</p></div>
                        <a href="#catalog" class="hidden text-xs font-bold text-[var(--brand-primary)] hover:underline sm:inline">Lihat semua event <span aria-hidden="true">↘</span></a>
                    </div>
                    <div class="mt-3"><FeaturedEvents v-if="discover" :events="featuredEvents" /><div v-else class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#e2e8f0] bg-[var(--catalog-field)] px-5 py-4 text-sm"><p class="text-white">Cari pengalaman yang sesuai dengan rencanamu.</p><button type="button" class="font-bold text-[var(--brand-primary)] hover:underline" @click="resetFilters">Kembali ke semua event <span aria-hidden="true">↗</span></button></div></div>
                </div>
            </section>

            <section id="catalog" class="mx-auto max-w-7xl scroll-mt-20 px-5 pb-10 pt-8 sm:px-8 md:pt-10" aria-labelledby="catalog-search-heading">
                <div class="flex flex-col justify-between gap-4 border-b border-[#e2e8f0] pb-5 md:flex-row md:items-end">
                    <div><h2 id="catalog-search-heading" class="type-title-lg text-[#101014]">Mau ke event apa?</h2></div>
                    <p class="text-xs text-[#101014]">{{ events.total }} event {{ filtered ? 'ditemukan' : 'tersedia' }}</p>
                </div>
                <form id="catalog-filter" class="mt-5 grid gap-3 border-b border-[#e2e8f0] pb-5 md:grid-cols-[minmax(0,1fr)_180px_200px_auto]" role="search" aria-label="Pencarian dan filter event" @submit.prevent="applyFilters()">
                    <div class="min-w-0"><label class="sr-only" for="catalog-search">Cari berdasarkan judul atau lokasi</label><div class="flex min-h-11 items-center gap-3 rounded-lg border border-[#e2e8f0] bg-white px-4 focus-within:border-[var(--brand-primary)] focus-within:ring-1 focus-within:ring-[var(--brand-primary)]"><i class="fa-solid fa-magnifying-glass h-5 w-5 shrink-0 text-[var(--brand-primary)]" aria-hidden="true"></i><input id="catalog-search" v-model="form.search" type="search" maxlength="100" placeholder="Cari nama event atau lokasi..." class="min-w-0 flex-1 border-0 bg-white p-0 text-sm text-slate-800 placeholder:text-[#101014] focus:ring-0" /></div></div>
                    <div v-if="categories.length"><label for="catalog-category" class="sr-only">Kategori event</label><select id="catalog-category" v-model="form.category" class="min-h-11 w-full rounded-lg border-[#e2e8f0] bg-white py-2.5 pl-4 pr-9 text-sm text-[#101014] focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @change="applyFilters()"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.name" :value="category.name">{{ category.name }}</option></select></div>
                    <div><label for="catalog-location" class="sr-only">Lokasi event</label><select id="catalog-location" v-model="form.location" class="min-h-11 w-full rounded-lg border-[#e2e8f0] bg-white py-2.5 pl-4 pr-9 text-sm text-[#101014] focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" @change="applyFilters()"><option value="">Semua lokasi</option><option v-for="location in locations" :key="location" :value="location">{{ location }}</option></select></div>
                    <button type="submit" :disabled="processing" class="arc-button min-h-11 rounded-lg px-6">{{ processing ? 'Mencari...' : 'Cari Event' }}</button>
                </form>
                <div v-if="categories.length" class="flex flex-wrap items-center gap-2 border-b border-[#e2e8f0] py-4" aria-label="Jelajahi kategori"><span class="mr-2 text-xs text-[#101014]">Kategori</span><button type="button" :aria-pressed="!filters.category" class="inline-flex min-h-8 items-center rounded-full border px-3 text-xs font-semibold transition" :class="!filters.category ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)] text-white' : 'border-[#e2e8f0] bg-white text-[#101014] hover:border-slate-300'" @click="applyFilters({ category: '' })">Semua</button><button v-for="category in categories" :key="category.name" type="button" :aria-pressed="filters.category === category.name" class="inline-flex min-h-8 items-center gap-2 rounded-full border px-3 text-xs font-semibold transition" :class="filters.category === category.name ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)] text-white' : 'border-[#e2e8f0] bg-white text-[#101014] hover:border-slate-300'" @click="applyFilters({ category: category.name })">{{ category.name }}<span class="text-[10px] opacity-70">{{ category.count }}</span></button></div>

                <div v-if="discover && sections.length" class="mt-9 space-y-10">
                    <section v-for="section in sections" :key="section.key" :aria-labelledby="section.key + '-heading'"><div class="mb-4 flex items-end justify-between gap-4"><div><h2 :id="section.key + '-heading'" class="text-lg font-bold tracking-tight text-[#101014]">{{ section.title }}</h2><p class="mt-1 text-xs leading-5 text-[#101014]">{{ section.description }}</p></div><button type="button" class="shrink-0 text-xs font-bold text-[var(--brand-primary)] hover:underline" @click="applyFilters({ schedule: section.key })">Lihat semua <span aria-hidden="true">↗</span></button></div><div class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-4"><EventCatalogCard v-for="(event, index) in section.events" :key="event.id" :event="event" :style="{ '--card-order': index }" /></div></section>
                </div>

                <div class="mt-12 flex flex-wrap items-end justify-between gap-3 border-b border-[#e2e8f0] pb-4"><div><h2 id="all-events-heading" class="type-title-lg text-[#101014]">{{ filtered ? 'Hasil pencarian event' : 'Semua Event' }}</h2></div><p class="text-xs text-[#101014]" aria-live="polite">{{ events.total }} event {{ filtered ? 'ditemukan' : 'tersedia' }}</p></div>
                <div class="mb-6 mt-4 flex flex-wrap gap-2" role="group" aria-label="Waktu event"><button v-for="schedule in schedules" :key="schedule.key" type="button" :aria-pressed="(filters.schedule || 'all') === schedule.key" class="inline-flex min-h-9 items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold transition" :class="(filters.schedule || 'all') === schedule.key ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-[#101014] hover:bg-white hover:text-[var(--brand-primary)]'" @click="applyFilters({ schedule: schedule.key })">{{ schedule.label }}<span class="rounded-md px-1.5 py-0.5 text-[10px]" :class="(filters.schedule || 'all') === schedule.key ? 'bg-white text-[var(--brand-primary)]' : 'bg-white text-[#101014]'">{{ counts[schedule.key] }}</span></button></div>
                <div v-if="chips.length" class="mb-5 flex flex-wrap items-center gap-2"><button v-for="chip in chips" :key="chip.key" type="button" :aria-label="'Hapus filter ' + chip.label" class="inline-flex max-w-full items-center gap-2 rounded-full border border-[var(--brand-primary)] bg-white px-3 py-1.5 text-xs text-[var(--brand-primary)]" @click="applyFilters({ [chip.key]: '' })"><span class="truncate">{{ chip.label }}</span><span aria-hidden="true">×</span></button><button type="button" class="px-2 text-xs font-semibold text-[#101014] underline underline-offset-4" @click="resetFilters">Reset filter</button></div>
                <div v-if="events.data.length" class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"><EventCatalogCard v-for="(event, index) in events.data" :key="event.id" :event="event" :style="{ '--card-order': index }" /></div>
                <div v-else class="rounded-lg border border-dashed border-[var(--brand-primary)] bg-white px-6 py-14 text-center"><svg class="mx-auto mb-5 h-14 w-14 text-[var(--brand-primary)]" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="21" cy="21" r="13" /><path d="m31 31 10 10M16 21h10" /></svg><h3 class="text-lg font-bold text-[#101014]">{{ filtered ? 'Belum menemukan event yang cocok?' : 'Nantikan event berikutnya.' }}</h3><p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#101014]">{{ filtered ? 'Coba kata kunci lain atau ubah kategori, lokasi, dan waktu event.' : 'Belum ada event yang dipublikasikan. Kunjungi halaman ini kembali untuk melihat agenda terbaru.' }}</p><button v-if="filtered" type="button" class="arc-button mt-6 rounded-lg" @click="resetFilters">Lihat semua event</button></div>
                <nav v-if="events.last_page > 1" aria-label="Halaman katalog event" class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-[#e2e8f0] pt-6"><p class="text-xs text-[#101014]">Halaman {{ events.current_page }} dari {{ events.last_page }}</p><div class="flex flex-wrap items-center gap-1.5"><Link v-if="events.prev_page_url" :href="events.prev_page_url" preserve-scroll class="inline-flex h-10 items-center rounded-lg border border-[#e2e8f0] bg-white px-3 text-xs font-semibold text-[var(--brand-primary)]" aria-label="Halaman sebelumnya">←</Link><template v-for="(link, index) in paginationNumbers" :key="index"><Link v-if="link.url" :href="link.url" preserve-scroll :aria-current="link.active ? 'page' : undefined" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-2 text-xs font-semibold" :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'border border-[#e2e8f0] bg-white text-[#101014] hover:bg-white'">{{ link.label }}</Link><span v-else class="px-2 text-[#101014]">…</span></template><Link v-if="events.next_page_url" :href="events.next_page_url" preserve-scroll class="inline-flex h-10 items-center rounded-lg border border-[#e2e8f0] bg-white px-3 text-xs font-semibold text-[var(--brand-primary)]" aria-label="Halaman berikutnya">→</Link></div></nav>
            </section>
        </main>
        <footer class="border-t border-[#e2e8f0] bg-white"><div class="mx-auto grid max-w-7xl gap-6 px-5 py-8 sm:px-8 md:grid-cols-[1fr_auto] md:items-center"><div><p class="text-sm font-bold tracking-wide text-[#101014]">ARC <span class="mx-2 font-normal text-slate-200">/</span> TICKET EVENT</p><p class="mt-2 text-xs text-[#101014]">Temukan event, pesan tiket, dan nikmati momennya.</p></div><nav class="flex flex-wrap gap-x-6 gap-y-3 text-xs font-semibold text-[#101014]" aria-label="Navigasi footer"><a href="#catalog" class="hover:text-[var(--brand-primary)]">Jelajahi Event</a><Link :href="route('public.orders.status')" class="hover:text-[var(--brand-primary)]">Cek Order</Link><Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="hover:text-[var(--brand-primary)]">Dashboard</Link></nav></div></footer>
    </PublicLayout>
</template>

<style scoped>
.catalog-page {
    --catalog-ground: #ffffff;
    --catalog-surface: #ffffff;
    --catalog-field: #101014;
    --catalog-wash: var(--brand-primary);
    --catalog-art: #ffffff;
    --catalog-signal: var(--brand-primary);
}
</style>
