<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ events: { type: Object, required: true } });
const actionForm = useForm({ event_id: null });
const statusLabel = (status) => ({ draft: 'Draft', published: 'Published', closed: 'Closed', cancelled: 'Cancelled' }[status] || status);
const formatDate = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : '-';

function changeStatus(event, action) {
    const actionLabel = action === 'publish' ? 'publish event ini' : 'menutup event ini';

    if (! window.confirm(`Apakah kamu yakin ingin ${actionLabel}?`)) {
        return;
    }

    actionForm.event_id = event.id;
    actionForm.post(route(`organizer.events.${action}`, event.id), { preserveScroll: true });
}</script>

<template>
    <Head title="Event Organizer" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Operations</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Daftar event</h2></div>
                <div class="flex items-center gap-4"><span class="hidden text-sm text-slate-500 sm:inline">Kelola status publikasi event.</span><Link :href="route('organizer.events.create')" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-[var(--brand-primary)]">Buat event</Link></div>
            </div>
        </template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white">
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4 font-bold">Event</th><th class="px-5 py-4 font-bold">Waktu</th><th class="px-5 py-4 font-bold">Lokasi</th><th class="px-5 py-4 font-bold">Status</th><th class="px-5 py-4 font-bold">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="event in events.data" :key="event.id" class="transition hover:bg-slate-50/40">
                                <td class="px-5 py-4"><Link :href="route('organizer.events.edit', event.id)" class="font-black text-slate-900 underline decoration-[var(--brand-primary)] decoration-2 underline-offset-4 hover:text-[var(--brand-primary)]">{{ event.title }}</Link><p class="text-xs text-slate-500">{{ event.slug }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ formatDate(event.start_at) }} - {{ formatDate(event.end_at) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ event.location_name || '-' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ statusLabel(event.status) }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4"><Link :href="route('organizer.ticket-types.index', event.id)" class="mr-3 text-xs font-bold text-slate-900 underline decoration-[var(--brand-primary)] decoration-2 underline-offset-4">Ticket</Link><Link v-if="event.status === 'published'" :href="route('public.events.show', event.slug)" target="_blank" class="mr-3 text-xs font-bold text-[var(--brand-primary)] underline decoration-[var(--brand-primary)] decoration-2 underline-offset-4">Lihat event</Link><button v-if="event.status === 'draft'" type="button" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700" :disabled="actionForm.processing" @click="changeStatus(event, 'publish')">Publish</button><button v-else-if="event.status === 'published'" type="button" class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700" :disabled="actionForm.processing" @click="changeStatus(event, 'close')">Tutup event</button><span v-else class="text-xs text-slate-400">Tidak ada aksi</span></td>
                            </tr>
                            <tr v-if="!events.data.length"><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada event.</td></tr>
                        </tbody>
                    </table></div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500"><span>Halaman {{ events.current_page }} dari {{ events.last_page }}</span><div class="flex gap-2"><Link v-if="events.prev_page_url" :href="events.prev_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Sebelumnya</Link><Link v-if="events.next_page_url" :href="events.next_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Berikutnya</Link></div></div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>




