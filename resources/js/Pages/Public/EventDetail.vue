<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ event: { type: Object, required: true } });

const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const formatDate = (value) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(value));
</script>

<template>
    <Head :title="event.title" />
    <PublicLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <Link href="/events" class="text-sm font-bold text-slate-500 hover:text-[var(--brand-primary)]">← Semua event</Link>
            <section class="mt-6 overflow-hidden border border-slate-200 bg-slate-950 text-white">
                <div class="grid lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="min-h-[22rem] bg-cover bg-center" :style="event.banner_url ? { backgroundImage: `url(${event.banner_url})` } : { backgroundColor: '#263246' }" role="img" :aria-label="`Banner ${event.title}`" />
                    <div class="flex flex-col justify-center border-t border-white/15 p-7 sm:p-10 lg:border-l lg:border-t-0">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--brand-primary)]">{{ formatDate(event.start_at) }}</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">{{ event.title }}</h1>
                        <p class="mt-5 text-sm leading-6 text-slate-300">{{ event.location_name }}<br>{{ event.location_address }}</p>
                    </div>
                </div>
            </section>
            <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_380px]">
                <article class="max-w-3xl">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--brand-primary)]">Tentang event</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight">Datang untuk pengalaman yang berkesan.</h2>
                    <p class="mt-5 whitespace-pre-line leading-7 text-slate-600">{{ event.description }}</p>
                    <div class="mt-10 grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-2">
                        <div><p class="text-xs font-black uppercase tracking-wider text-slate-400">Kontak email</p><p class="mt-2 font-semibold text-slate-800">{{ event.contact_email || '-' }}</p></div>
                        <div><p class="text-xs font-black uppercase tracking-wider text-slate-400">Kontak telepon</p><p class="mt-2 font-semibold text-slate-800">{{ event.contact_phone || '-' }}</p></div>
                    </div>
                </article>
                <aside class="h-fit border border-slate-200 bg-white p-6 lg:sticky lg:top-6">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--brand-primary)]">Tiket</p>
                    <h2 class="mt-2 text-2xl font-black">Pilih tiket kamu</h2>
                    <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                        <div v-for="ticket in event.ticket_types" :key="ticket.id" class="flex items-start justify-between gap-4 py-4">
                            <div><p class="font-bold text-slate-900">{{ ticket.name }}</p><p class="mt-1 text-xs text-slate-500">{{ ticket.remaining_quota }} tiket tersisa</p></div>
                            <p class="whitespace-nowrap font-black text-[var(--brand-primary)]">{{ money(ticket.price) }}</p>
                        </div>
                    </div>
                    <p v-if="!event.ticket_types.length" class="mt-5 text-sm text-rose-600">Tiket belum tersedia.</p>
                    <Link v-else :href="route('public.events.checkout', event.slug)" class="mt-6 block w-full bg-[var(--brand-primary)] px-5 py-3 text-center font-black text-white hover:bg-[var(--brand-primary-dark)]">Pesan tiket</Link>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>