<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicIcon from '@/Components/PublicIcon.vue';

const props = defineProps({ event: { type: Object, required: true } });
const available = computed(() => props.event.ticket_types.filter((ticket) => ticket.remaining_quota > 0));
const price = computed(() => available.value.length ? Math.min(...available.value.map((ticket) => Number(ticket.price))) : null);
const date = computed(() => props.event.start_at ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(props.event.start_at)) : 'Tanggal segera diumumkan');
const priceLabel = computed(() => price.value !== null ? (price.value === 0 ? 'Gratis' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price.value)) : props.event.ticket_types.length ? 'Tiket habis' : 'Segera tersedia');
</script>

<template>
    <article class="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white transition duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg motion-reduce:transform-none">
        <Link :href="route('public.events.show', event.slug)" class="flex h-full flex-col focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600" :aria-label="'Lihat detail ' + event.title">
            <div class="relative aspect-[4/3] overflow-hidden bg-[#eaf1fc]">
                <img v-if="event.banner_url" :src="event.banner_url" :alt="'Poster ' + event.title" loading="lazy" decoding="async" class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.025] motion-reduce:transform-none" />
                <div v-else class="flex h-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-blue-50 to-blue-100 px-5 text-center text-blue-500"><svg class="h-14 w-14" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M7 12h34v9a4 4 0 0 0 0 8v9H7v-9a4 4 0 0 0 0-8v-9Z" /><path d="M31 12v26M17 20h7m-7 7h7" stroke-dasharray="3 3" /></svg><span class="text-xs font-medium">Poster belum tersedia</span></div>
                <span v-if="event.schedule_status === 'ongoing'" class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1.5 text-[11px] font-bold text-blue-800"><span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span> Sedang berlangsung</span>
                <span v-else-if="event.schedule_status === 'ended'" class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600">Telah selesai</span>
            </div>
            <div class="flex flex-1 flex-col p-4 sm:p-5">
                <div class="mb-2 flex min-h-5 items-center justify-between gap-2"><span v-if="event.category" class="truncate text-[11px] font-bold uppercase tracking-wider text-[var(--arc-blue)]">{{ event.category }}</span><span v-else class="text-[11px] font-medium text-slate-400">Event</span><span v-if="event.schedule_status === 'upcoming'" class="text-[11px] font-medium text-slate-500">Mendatang</span></div>
                <h3 class="mb-3 line-clamp-2 min-h-12 text-base font-bold leading-6 text-[#122b53] group-hover:text-[var(--arc-blue)]">{{ event.title }}</h3>
                <div class="space-y-2 text-xs text-slate-500"><p class="flex items-start gap-2"><PublicIcon name="calendar" class="h-4 w-4 shrink-0 text-blue-500" /><span>{{ date }}</span></p><p class="flex items-start gap-2"><PublicIcon name="pin" class="h-4 w-4 shrink-0 text-blue-500" /><span class="line-clamp-1">{{ event.location_name || 'Lokasi segera diumumkan' }}</span></p></div>
                <div class="mt-5 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
                    <div><p class="mb-1 text-[10px] text-slate-400">{{ price !== null ? 'Harga mulai dari' : 'Ketersediaan tiket' }}</p><p class="text-sm font-bold text-[#123f8f]">{{ priceLabel }}</p></div>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-[var(--arc-blue)]">Lihat Detail <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" /></svg></span>
                </div>
            </div>
        </Link>
    </article>
</template>
