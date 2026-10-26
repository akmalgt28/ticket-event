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
    <article class="reveal-card group flex h-full min-w-0 flex-col overflow-hidden rounded-lg border border-[#e2e8f0] bg-white transition duration-200 hover:border-[var(--brand-primary)] motion-reduce:transition-none">
        <Link :href="route('public.events.show', event.slug)" class="flex h-full flex-col focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[var(--brand-primary)]" :aria-label="'Lihat detail ' + event.title">
            <div class="relative aspect-[4/3] overflow-hidden bg-[var(--catalog-art)]">
                <img v-if="event.banner_url" :src="event.banner_url" :alt="'Poster ' + event.title" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02] motion-reduce:transform-none" />
                <div v-else class="flex h-full flex-col items-center justify-center gap-3 bg-[var(--catalog-art)] px-5 text-center text-[var(--brand-primary)]"><svg class="h-14 w-14" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M7 12h34v9a4 4 0 0 0 0 8v9H7v-9a4 4 0 0 0 0-8v-9Z" /><path d="M31 12v26M17 20h7m-7 7h7" stroke-dasharray="3 3" /></svg><span class="text-xs font-medium">Poster belum tersedia</span></div>
                <span v-if="event.schedule_status === 'ongoing'" class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1.5 text-[11px] font-bold text-[var(--brand-primary)]"><span class="h-1.5 w-1.5 rounded-full bg-[var(--brand-primary)]"></span> Sedang berlangsung</span>
                <span v-else-if="event.schedule_status === 'ended'" class="absolute left-3 top-3 rounded-full bg-white px-2.5 py-1.5 type-caption-strong text-[#101014]">Telah selesai</span>
            </div>
            <div class="flex flex-1 flex-col bg-white p-4 sm:p-5">
                <div class="mb-2 flex min-h-5 items-center justify-between gap-2"><span v-if="event.category" class="truncate type-caption-eyebrow text-[var(--brand-primary)]">{{ event.category }}</span><span v-else class="type-caption-md text-[#101014]">Event</span><span v-if="event.schedule_status === 'upcoming'" class="type-caption-md text-[#101014]">Mendatang</span></div>
                <h3 class="mb-3 line-clamp-2 min-h-12 text-base font-bold leading-6 text-[#101014] type-title-md group-hover:text-[var(--brand-primary)]">{{ event.title }}</h3>
                <div class="space-y-2 type-body-sm text-[#101014]"><p class="flex items-start gap-2"><PublicIcon name="calendar" class="h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span>{{ date }}</span></p><p class="flex items-start gap-2"><PublicIcon name="pin" class="h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span class="line-clamp-1">{{ event.location_name || 'Lokasi segera diumumkan' }}</span></p></div>
                <div class="mt-6 flex flex-wrap items-end justify-between gap-3 pt-1"><div><p class="mb-1 type-caption-md text-[#101014]">{{ price !== null ? 'Harga mulai dari' : 'Ketersediaan tiket' }}</p><p class="type-body-sm-strong text-[var(--brand-primary)]">{{ priceLabel }}</p></div><span class="inline-flex items-center gap-1 type-body-sm-strong text-[var(--brand-primary)]">Lihat Detail <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" /></svg></span></div>
            </div>
        </Link>
    </article>
</template>

<style scoped>
@keyframes card-enter {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

.reveal-card {
    animation: card-enter 500ms ease-out both;
    animation-delay: calc(var(--card-order, 0) * 70ms);
}

@media (prefers-reduced-motion: reduce) {
    .reveal-card { animation: none; }
}
</style>
