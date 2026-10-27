<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ event: { type: Object, required: true }, ticketTypes: { type: Object, required: true } });
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const formatDate = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Tanpa batas';
const saleStatus = (ticketType) => {
    const now = Date.now();
    if (ticketType.sale_start_at && new Date(ticketType.sale_start_at).getTime() > now) return 'Belum mulai';
    if (ticketType.sale_end_at && new Date(ticketType.sale_end_at).getTime() < now) return 'Penjualan berakhir';
    return ticketType.status || 'active';
};
</script>
<template>
    <Head title="Ticket Type Organizer" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <Link :href="route('organizer.events.index')" class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]" aria-label="Kembali ke daftar event">
                        <span aria-hidden="true">&larr;</span>
                        Kembali
                    </Link>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Event setup</p>
                        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Ticket type · {{ event.title }}</h2>
                    </div>
                </div>
                <Link :href="route('organizer.ticket-types.create', event.id)" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-[var(--brand-primary)]">Tambah ticket type</Link>
            </div>
        </template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8"><div class="mx-auto max-w-7xl"><section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4 font-bold">Nama</th><th class="px-5 py-4 font-bold">Harga</th><th class="px-5 py-4 font-bold">Kuota</th><th class="px-5 py-4 font-bold">Periode jual</th><th class="px-5 py-4 font-bold">Status</th><th class="px-5 py-4 font-bold">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="ticketType in ticketTypes.data" :key="ticketType.id" class="hover:bg-slate-50/40"><td class="px-5 py-4"><p class="font-black text-slate-900">{{ ticketType.name }}</p><p class="text-xs text-slate-500">{{ ticketType.description || '-' }}</p></td><td class="px-5 py-4 font-semibold text-slate-700">{{ money(ticketType.price) }}</td><td class="px-5 py-4 text-slate-600">{{ ticketType.sold_count }} / {{ ticketType.quota }} <span class="text-xs">({{ ticketType.remaining_quota }} sisa)</span></td><td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">{{ formatDate(ticketType.sale_start_at) }} - {{ formatDate(ticketType.sale_end_at) }}</td><td class="px-5 py-4"><span :class="saleStatus(ticketType) === 'Penjualan berakhir' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'" class="rounded-full px-3 py-1 text-xs font-bold">{{ saleStatus(ticketType) }}</span></td><td class="px-5 py-4"><Link :href="route('organizer.ticket-types.edit', [event.id, ticketType.id])" class="text-sm font-bold text-slate-900 underline decoration-[var(--brand-primary)] decoration-2 underline-offset-4">Edit</Link></td></tr><tr v-if="!ticketTypes.data.length"><td colspan="6" class="px-5 py-12 text-center text-slate-500">Belum ada ticket type.</td></tr></tbody></table></div><div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500"><span>Halaman {{ ticketTypes.current_page }} dari {{ ticketTypes.last_page }}</span><Link :href="route('organizer.events.index')" class="font-semibold hover:text-[var(--brand-primary)]">Kembali ke event</Link></div></section></div></div>
    </AuthenticatedLayout>
</template>

