<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicIcon from '@/Components/PublicIcon.vue';

const props = defineProps({ events: { type: Array, default: () => [] } });
const active = ref(0);
const current = computed(() => props.events[active.value]);
const canBuy = computed(() => current.value?.ticket_types.some((ticket) => ticket.remaining_quota > 0));
const startingPrice = computed(() => {
    const prices = current.value?.ticket_types.filter((ticket) => ticket.remaining_quota > 0).map((ticket) => Number(ticket.price)) ?? [];

    return prices.length ? Math.min(...prices) : null;
});
const priceLabel = computed(() => startingPrice.value === null ? 'Tiket segera tersedia' : startingPrice.value === 0 ? 'Gratis' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(startingPrice.value));
const date = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Tanggal segera diumumkan';
const move = (step) => { active.value = (active.value + step + props.events.length) % props.events.length; };
watch(() => props.events, () => { active.value = 0; });
</script>

<template>
    <section class="featured-event overflow-hidden rounded-lg bg-[#f8fafc]" aria-label="Pilihan event" aria-roledescription="carousel" @keydown.left="events.length > 1 && move(-1)" @keydown.right="events.length > 1 && move(1)">
        <div v-if="current" class="grid min-w-0 md:grid-cols-[0.6fr_1.4fr]">
            <div class="feature-copy order-2 flex min-w-0 flex-col justify-start bg-white px-6 py-8 sm:px-8 md:order-1 md:h-[520px] md:p-12 md:pt-16">
                <div class="mb-5 flex flex-wrap items-center gap-2 text-xs font-semibold text-[var(--brand-primary)]"><span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[var(--brand-primary)]"></span> Pilihan event</span><span v-if="current.category" class="text-slate-600">{{ current.category }}</span></div>
                <h2 class="max-w-lg text-2xl font-bold leading-tight tracking-tight text-slate-950 sm:text-3xl md:text-4xl">{{ current.title }}</h2>
                <div class="mt-5 space-y-3 text-sm text-slate-700"><p class="flex items-center gap-2.5"><PublicIcon name="calendar" class="h-4 w-4 shrink-0 text-[var(--brand-primary)]" />{{ date(current.start_at) }}</p><p class="flex items-start gap-2.5"><PublicIcon name="pin" class="h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span>{{ current.location_name || 'Lokasi segera diumumkan' }}</span></p></div>
                <p v-if="current.description" class="mt-6 line-clamp-3 max-w-sm text-sm leading-6 text-slate-600">{{ current.description }}</p>
                <div class="mt-7 flex flex-wrap gap-3"><Link :href="route('public.events.show', current.slug)" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--brand-primary)] px-6 py-2 text-sm font-bold text-white transition hover:bg-[var(--brand-primary)]">Lihat Detail <span aria-hidden="true" class="ml-3">↗</span></Link><Link v-if="canBuy" :href="route('public.events.checkout', current.slug)" class="inline-flex min-h-11 items-center justify-center rounded-full border border-[var(--brand-primary)] bg-white px-6 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)] hover:text-[#101014]">Beli Tiket</Link></div>
                <div class="mt-6 flex flex-wrap items-end justify-between gap-5"><div><p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[var(--brand-primary)]">Mulai dari</p><p class="mt-1 text-lg font-bold text-[var(--brand-primary)]">{{ priceLabel }}</p></div><div v-if="events.length > 1" class="flex items-center gap-4"><div class="flex gap-2" aria-label="Pilih banner"><button v-for="(event, index) in events" :key="event.id" type="button" :aria-label="'Tampilkan event ' + (index + 1) + ': ' + event.title" :aria-pressed="active === index" class="h-2 rounded-full transition-all" :class="active === index ? 'w-7 bg-[var(--brand-primary)]' : 'w-2 bg-[var(--brand-primary)] hover:bg-[var(--brand-primary)]'" @click="active = index"></button></div><span class="text-xs tabular-nums text-slate-600" aria-live="polite">{{ active + 1 }} / {{ events.length }}</span></div></div>
            </div>
            <div class="feature-art relative order-1 flex min-h-[320px] min-w-0 items-center justify-center bg-[var(--catalog-art)] md:order-2 md:h-[520px] md:min-h-0">
                <img :key="current.id" :src="current.banner_url" :alt="'Poster ' + current.title" class="h-full min-h-[320px] w-full object-cover md:min-h-0" fetchpriority="high" />
                <div v-if="events.length > 1" class="absolute bottom-5 right-5 flex gap-2"><button type="button" aria-label="Event sebelumnya" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)] hover:text-[#101014]" @click="move(-1)">←</button><button type="button" aria-label="Event berikutnya" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)] hover:text-[#101014]" @click="move(1)">→</button></div>
            </div>
        </div>
        <div v-else class="grid overflow-hidden bg-white px-6 py-10 sm:p-10 md:grid-cols-[1.2fr_0.8fr] md:items-center md:py-14">
            <div><h2 class="mt-4 max-w-xl text-3xl font-bold leading-tight tracking-tight text-slate-950 sm:text-4xl">Satu tiket.<br />Banyak cerita baru.</h2><p class="mt-4 max-w-md text-sm leading-6 text-slate-600">Jelajahi jadwal event yang tersedia dan temukan pengalaman yang ingin kamu datangi berikutnya.</p><a href="#catalog" class="mt-7 inline-flex min-h-11 items-center rounded-full bg-[var(--brand-primary)] px-6 text-sm font-bold text-white hover:bg-[var(--brand-primary)]">Jelajahi Event <span class="ml-3" aria-hidden="true">↗</span></a></div>
            <div class="pointer-events-none relative hidden h-56 items-center justify-center md:flex" aria-hidden="true"><div class="absolute h-72 w-72 rounded-full border border-[var(--brand-primary)]"></div><div class="absolute h-52 w-52 rounded-full border border-[var(--brand-primary)]"></div><svg class="relative h-32 w-48 -rotate-12 text-[var(--brand-primary)]" viewBox="0 0 180 110" fill="none"><path d="M10 10h160v30a15 15 0 0 0 0 30v30H10V70a15 15 0 0 0 0-30V10Z" fill="var(--catalog-art)" stroke="currentColor" stroke-width="2" /><path d="M125 12v86" stroke="currentColor" stroke-width="2" stroke-dasharray="6 6" /><path d="M35 40h62M35 55h45M35 70h55" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></div>
        </div>
    </section>
</template>

<style scoped>
@keyframes feature-enter {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.feature-copy,
.feature-art {
    animation: feature-enter 600ms ease-out both;
}

.feature-art { animation-delay: 90ms; }

@media (prefers-reduced-motion: reduce) {
    .feature-copy,
    .feature-art { animation: none; }
}
</style>
