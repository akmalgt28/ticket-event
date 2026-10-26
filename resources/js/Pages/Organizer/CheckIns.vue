<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ checkIns: { type: Object, required: true }, filters: { type: Object, required: true } });
const filterForm = useForm({ search: props.filters.search });
const date = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';
function search() { filterForm.get(route('organizer.check-ins.index'), { preserveState: true, replace: true }); }
</script>

<template>
    <Head title="Riwayat check-in" />
    <AuthenticatedLayout>
        <template #header><div><p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Operations</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Riwayat check-in</h2></div></template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-5"><form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="search"><input v-model="filterForm.search" type="search" placeholder="Cari ticket, peserta, nomor order..." class="min-w-0 flex-1 rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-primary)]">Cari</button></form></section>
                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4 font-bold">Ticket</th><th class="px-5 py-4 font-bold">Peserta</th><th class="px-5 py-4 font-bold">Event</th><th class="px-5 py-4 font-bold">Waktu</th><th class="px-5 py-4 font-bold">Petugas</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="checkIn in checkIns.data" :key="checkIn.id" class="transition hover:bg-slate-50/40"><td class="px-5 py-4 font-black text-slate-900">{{ checkIn.ticket_number }}<p class="text-xs font-normal text-slate-500">{{ checkIn.order_number }}</p></td><td class="px-5 py-4 text-slate-700">{{ checkIn.attendee || '-' }}</td><td class="px-5 py-4 text-slate-600">{{ checkIn.event || '-' }}</td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ date(checkIn.checked_in_at) }}</td><td class="px-5 py-4 font-semibold text-slate-700">{{ checkIn.checked_in_by || '-' }}</td></tr><tr v-if="!checkIns.data.length"><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada riwayat check-in.</td></tr></tbody></table></div><div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500"><span>Halaman {{ checkIns.current_page }} dari {{ checkIns.last_page }}</span><div class="flex gap-2"><Link v-if="checkIns.prev_page_url" :href="checkIns.prev_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Sebelumnya</Link><Link v-if="checkIns.next_page_url" :href="checkIns.next_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Berikutnya</Link></div></div></section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>