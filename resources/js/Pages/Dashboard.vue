<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metrics: { type: Object, required: true },
    recentEvents: { type: Array, required: true },
});

const cards = [
    { label: 'Total order', value: props.metrics.orders, dot: 'bg-amber-400' },
    { label: 'Order terbayar', value: props.metrics.paid_orders, dot: 'bg-emerald-400' },
    { label: 'Tiket terjual', value: props.metrics.tickets_sold, dot: 'bg-sky-400' },
    { label: 'Tiket tersisa', value: props.metrics.tickets_remaining, dot: 'bg-rose-400' },
];

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
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-600">Control room</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Dashboard organizer</h2>
                </div>
                <p class="text-sm text-slate-500">Pantau penjualan dan kapasitas event dari satu layar.</p>
            </div>
        </template>

        <div class="min-h-[calc(100vh-9rem)] bg-[#f7f3ec] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-8">
                <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-10">
                    <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-amber-400/20 blur-3xl" />
                    <div class="relative max-w-2xl">
                        <p class="text-sm font-semibold text-amber-300">Pendapatan terkonfirmasi</p>
                        <p class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ formatCurrency(metrics.revenue) }}</p>
                        <p class="mt-3 text-sm text-slate-300">Berdasarkan order dengan status pembayaran paid.</p>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in cards" :key="card.label" class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-500">{{ card.label }}</p>
                            <span :class="['h-3 w-3 rounded-full', card.dot]" />
                        </div>
                        <p class="mt-5 text-3xl font-black tracking-tight text-slate-950">{{ card.value }}</p>
                    </article>
                </section>

                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-amber-600">Event aktif</p>
                            <h3 class="mt-1 text-xl font-black text-slate-950">Event terbaru</h3>
                        </div>
                        <Link :href="route('organizer.events.index')" class="text-sm font-bold text-slate-900 underline decoration-amber-400 decoration-2 underline-offset-4">Kelola event</Link>
                    </div>

                    <div v-if="recentEvents.length" class="mt-6 divide-y divide-slate-100">
                        <div v-for="event in recentEvents" :key="event.id" class="flex flex-col gap-2 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-slate-900">{{ event.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ event.location_name || 'Lokasi belum diisi' }}</p>
                            </div>
                            <p class="text-sm font-semibold text-slate-600">{{ formatDate(event.start_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="mt-6 rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Belum ada event published.</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
