<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const formatDate = (value) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(value));
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
</script>

<template>
    <Head title="Jelajah event" />
    <PublicLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="grid gap-8 border-b border-slate-300 pb-12 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.28em] text-[var(--brand-primary)]">Kalender pilihan</p>
                    <h1 class="mt-4 max-w-4xl text-5xl font-black leading-[0.95] tracking-[-0.06em] text-[var(--brand-ink)] sm:text-7xl">Temukan alasan untuk keluar rumah.</h1>
                </div>
                <div class="border-l-2 border-[var(--brand-primary)] pl-5 text-base leading-7 text-slate-600">
                    <p>Event kecil, kelas praktis, dan pengalaman yang layak diingat.</p>
                    <p class="mt-3 font-bold text-[var(--brand-ink)]">{{ events.total }} event tersedia</p>
                </div>
            </section>
            <section class="mt-8 flex flex-col gap-3 border-b border-slate-300 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600"><span v-if="filters.search">Hasil pencarian untuk <strong class="text-[var(--brand-ink)]">“{{ filters.search }}”</strong></span><span v-else>Semua event yang sedang dibuka untuk umum</span></p>
                <Link v-if="filters.search" href="/events" class="text-sm font-black text-[var(--brand-primary)] hover:text-[var(--brand-primary-dark)]">Hapus pencarian</Link>
            </section>
            <section class="mt-8 grid gap-px border border-slate-300 bg-slate-300 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="event in events.data" :key="event.id" class="group bg-white">
                    <Link :href="route('public.events.show', event.slug)" class="block">
                        <div class="aspect-[4/3] bg-slate-900 bg-cover bg-center" :style="event.banner_url ? { backgroundImage: `url(${event.banner_url})` } : {}">
                            <div class="flex h-full items-end bg-gradient-to-t from-slate-950/75 via-transparent to-transparent p-5"><span class="text-xs font-black uppercase tracking-[0.18em] text-white">{{ formatDate(event.start_at) }}</span></div>
                        </div>
                        <div class="border-t border-slate-200 p-5 transition group-hover:bg-[var(--brand-paper)]">
                            <h2 class="text-2xl font-black tracking-tight text-[var(--brand-ink)]">{{ event.title }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ event.location_name || 'Lokasi diumumkan segera' }}</p>
                            <div class="mt-6 flex items-center justify-between gap-3 text-sm"><span v-if="event.ticket_types.length" class="font-bold text-slate-700">Mulai {{ money(event.ticket_types[0].price) }}</span><span v-else class="font-bold text-rose-600">Tiket segera hadir</span><span class="font-black text-[var(--brand-primary)]">Lihat detail →</span></div>
                        </div>
                    </Link>
                </article>
            </section>
            <div v-if="!events.data.length" class="mt-8 border border-dashed border-slate-400 bg-white p-12 text-center"><h2 class="text-2xl font-black text-[var(--brand-ink)]">Tidak ada event yang cocok.</h2><p class="mt-2 text-slate-600">Coba kata kunci lain atau lihat semua event.</p></div>
        </div>
    </PublicLayout>
</template>
