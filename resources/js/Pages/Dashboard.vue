<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metrics: { type: Object, required: true },
    recentEvents: { type: Array, required: true },
});

const cards = [
    { label: 'Total order', value: props.metrics.orders, icon: 'receipt', tone: 'blue' },
    { label: 'Order terbayar', value: props.metrics.paid_orders, icon: 'check', tone: 'green' },
    { label: 'Tiket terjual', value: props.metrics.tickets_sold, icon: 'ticket', tone: 'sky' },
    { label: 'Tiket tersisa', value: props.metrics.tickets_remaining, icon: 'stack', tone: 'violet' },
];

const toneClasses = {
    blue: 'bg-slate-50 text-[var(--brand-primary)]',
    green: 'bg-emerald-50 text-emerald-600',
    sky: 'bg-slate-50 text-[var(--brand-primary)]',
    violet: 'bg-violet-50 text-violet-600',
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value));

const formatDate = (value) => new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeZone: 'Asia/Jakarta',
}).format(new Date(value));
</script>

<template>
    <Head title="Dashboard Organizer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.28em] text-[var(--brand-primary)]">Overview</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-[#122b53]">Dashboard organizer</h2>
                </div>
                <p class="text-sm text-slate-500">Pantau penjualan dan kapasitas event dari satu layar.</p>
            </div>
        </template>

        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-[1440px] space-y-6">
                <section class="relative overflow-hidden rounded-lg bg-[#144ba3] px-6 py-8 text-white sm:px-10">
                    <div class="absolute -right-12 -top-20 h-64 w-64 rounded-full border-[28px] border-white/10"></div>
                    <div class="relative max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-white">Pendapatan terkonfirmasi</p>
                        <p class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ formatCurrency(metrics.revenue) }}</p>
                        <p class="mt-3 text-sm text-white">Berdasarkan order dengan status pembayaran paid.</p>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in cards" :key="card.label" class="rounded-lg border border-slate-200 bg-white p-5 transition hover:border-slate-300">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-sm font-semibold text-slate-500">{{ card.label }}</p>
                            <span :class="['flex h-9 w-9 items-center justify-center rounded-md', toneClasses[card.tone]]" aria-hidden="true">
                                <svg v-if="card.icon === 'receipt'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/></svg>
                                <svg v-else-if="card.icon === 'check'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="m8.5 12 2.3 2.3 4.7-5"/></svg>
                                <svg v-else-if="card.icon === 'ticket'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7a2 2 0 0 0 0 4v2a2 2 0 0 0 0 4h16v-4a2 2 0 0 1 0-4V7H4Z"/><path d="M13 7v10"/></svg>
                                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 7h14M5 12h14M5 17h14"/></svg>
                            </span>
                        </div>
                        <p class="mt-6 text-3xl font-black tracking-tight text-[#122b53]">{{ card.value }}</p>
                    </article>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--brand-primary)]">Event aktif</p>
                            <h3 class="mt-1 text-xl font-black text-[#122b53]">Event terbaru</h3>
                        </div>
                        <Link :href="route('organizer.events.index')" class="inline-flex items-center justify-center rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white hover:bg-[var(--brand-primary-dark)]">Kelola event</Link>
                    </div>

                    <div v-if="recentEvents.length" class="mt-6 divide-y divide-slate-50">
                        <div v-for="event in recentEvents" :key="event.id" class="flex flex-col gap-2 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-[#122b53]">{{ event.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ event.location_name || 'Lokasi belum diisi' }}</p>
                            </div>
                            <p class="text-sm font-semibold text-[#144ba3]">{{ formatDate(event.start_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="mt-6 border border-dashed border-slate-200 bg-slate-50/50 p-5 text-sm text-slate-500">Belum ada event published.</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
