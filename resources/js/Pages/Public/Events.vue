<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import EventPoster from '@/Components/EventPoster.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ events: { type: Object, required: true }, filters: { type: Object, required: true } });
const formatDate = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Segera diumumkan';
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const firstPrice = (event) => event.ticket_types.find((ticket) => ticket.remaining_quota > 0)?.price;
</script>

<template>
    <Head title="Jelajah Event" />
    <PublicLayout>
        <div class="mx-auto max-w-6xl px-5 py-8 sm:px-8 md:py-12">
            <section class="mb-8 text-white">
                <span class="arc-badge mb-4">Ticket Event</span>
                <h1 class="max-w-2xl text-3xl font-bold leading-tight sm:text-4xl">Temukan event dan pengalaman berikutnya.</h1>
                <p class="mt-4 text-sm text-blue-100">{{ events.total }} event tersedia. Pilih event, pesan tiket, dan sampai jumpa di sana.</p>
            </section>
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 text-sm text-white">
                <p>{{ filters.search ? 'Hasil pencarian: “' + filters.search + '”' : 'Event yang sedang dibuka untuk umum' }}</p>
                <Link v-if="filters.search" :href="route('public.events.index')" class="underline underline-offset-4">Hapus pencarian</Link>
            </div>
            <section class="grid min-w-0 gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-label="Daftar event">
                <article v-for="event in events.data" :key="event.id" class="min-w-0 overflow-hidden rounded-2xl bg-white">
                    <Link :href="route('public.events.show', event.slug)" class="group block h-full">
                        <EventPoster :event="event" />
                        <div class="p-5">
                            <p class="text-xs font-semibold text-[var(--arc-blue)]">{{ formatDate(event.start_at) }}</p>
                            <h2 class="mt-2 text-lg font-bold leading-6">{{ event.title }}</h2>
                            <p class="mt-2 text-sm text-slate-600">{{ event.location_name || 'Lokasi akan diumumkan' }}</p>
                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm"><span class="font-semibold">{{ firstPrice(event) !== undefined ? 'Mulai ' + money(firstPrice(event)) : 'Tiket belum tersedia' }}</span><span class="font-semibold text-[var(--arc-blue)] group-hover:underline">Lihat detail →</span></div>
                        </div>
                    </Link>
                </article>
            </section>
            <div v-if="!events.data.length" class="arc-card mt-6 text-center"><h2 class="text-lg font-bold">Tidak ada event yang cocok.</h2><p class="mt-2 text-sm text-slate-600">Coba kata kunci lain melalui menu pencarian.</p></div>
            <nav v-if="events.last_page > 1" aria-label="Halaman event" class="mt-8 flex flex-wrap items-center justify-between gap-4 text-sm text-white">
                <span>Halaman {{ events.current_page }} dari {{ events.last_page }}</span>
                <div class="flex gap-4"><Link v-if="events.prev_page_url" :href="events.prev_page_url" class="underline underline-offset-4">Sebelumnya</Link><Link v-if="events.next_page_url" :href="events.next_page_url" class="underline underline-offset-4">Berikutnya</Link></div>
            </nav>
        </div>
    </PublicLayout>
</template>