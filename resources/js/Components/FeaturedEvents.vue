<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicIcon from '@/Components/PublicIcon.vue';

const props = defineProps({ events: { type: Array, default: () => [] } });
const active = ref(0);
const current = computed(() => props.events[active.value]);
const canBuy = computed(() => current.value?.ticket_types.some((ticket) => ticket.remaining_quota > 0));
const date = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Tanggal segera diumumkan';
const move = (step) => { active.value = (active.value + step + props.events.length) % props.events.length; };
watch(() => props.events, () => { active.value = 0; });
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-[#0d2d68] text-white" aria-label="Pilihan event" aria-roledescription="carousel" @keydown.left="events.length > 1 && move(-1)" @keydown.right="events.length > 1 && move(1)">
        <div v-if="current" class="grid min-w-0 md:grid-cols-[0.9fr_1.1fr]">
            <div class="order-2 flex min-w-0 flex-col justify-center px-6 py-6 sm:px-8 md:order-1 md:p-10">
                <div class="mb-4 flex flex-wrap items-center gap-2"><span class="rounded-full border border-blue-300/30 bg-blue-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-blue-100">Pilihan event</span><span v-if="current.category" class="text-xs text-blue-200">{{ current.category }}</span></div>
                <h2 class="max-w-lg text-2xl font-bold leading-tight sm:text-3xl lg:text-4xl">{{ current.title }}</h2>
                <div class="mt-5 space-y-3 text-sm text-blue-100"><p class="flex items-center gap-2.5"><PublicIcon name="calendar" class="h-4 w-4 shrink-0" />{{ date(current.start_at) }}</p><p class="flex items-start gap-2.5"><PublicIcon name="pin" class="h-4 w-4 shrink-0" /><span>{{ current.location_name || 'Lokasi segera diumumkan' }}</span></p></div>
                <div class="mt-7 flex flex-wrap gap-3"><Link :href="route('public.events.show', current.slug)" class="inline-flex min-h-11 items-center justify-center rounded-full bg-white px-6 py-2 text-sm font-bold text-[#123f8f] transition hover:bg-blue-100">Lihat Detail <span aria-hidden="true" class="ml-3">↗</span></Link><Link v-if="canBuy" :href="route('public.events.checkout', current.slug)" class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/35 px-6 py-2 text-sm font-semibold transition hover:bg-white/10">Beli Tiket</Link></div>
                <div v-if="events.length > 1" class="mt-7 flex items-center gap-4"><div class="flex gap-2" aria-label="Pilih banner"><button v-for="(event, index) in events" :key="event.id" type="button" :aria-label="'Tampilkan event ' + (index + 1) + ': ' + event.title" :aria-pressed="active === index" class="h-2 rounded-full transition-all" :class="active === index ? 'w-7 bg-white' : 'w-2 bg-white/35 hover:bg-white/70'" @click="active = index"></button></div><span class="text-xs tabular-nums text-blue-200" aria-live="polite">{{ active + 1 }} / {{ events.length }}</span></div>
            </div>
            <div class="relative order-1 flex min-w-0 items-center justify-center bg-[#092354] md:order-2">
                <img :key="current.id" :src="current.banner_url" :alt="'Poster ' + current.title" class="aspect-[16/9] max-h-[390px] w-full object-contain md:aspect-[4/3] md:min-h-[360px]" fetchpriority="high" />
                <div v-if="events.length > 1" class="absolute bottom-4 right-4 flex gap-2"><button type="button" aria-label="Event sebelumnya" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-[#092354]/85 text-lg transition hover:bg-blue-800" @click="move(-1)">←</button><button type="button" aria-label="Event berikutnya" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-[#092354]/85 text-lg transition hover:bg-blue-800" @click="move(1)">→</button></div>
            </div>
        </div>
        <div v-else class="relative grid overflow-hidden px-6 py-10 sm:p-10 md:grid-cols-[1.2fr_0.8fr] md:items-center md:py-14">
            <div class="relative z-10"><span class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Pengalaman dimulai di sini</span><h2 class="mt-4 max-w-xl text-3xl font-bold leading-tight sm:text-4xl">Satu tiket.<br />Banyak cerita baru.</h2><p class="mt-4 max-w-md text-sm leading-6 text-blue-100">Jelajahi jadwal event yang tersedia dan temukan pengalaman yang ingin kamu datangi berikutnya.</p><a href="#catalog" class="mt-7 inline-flex min-h-11 items-center rounded-full bg-white px-6 text-sm font-bold text-[#123f8f]">Jelajahi Event <span class="ml-3" aria-hidden="true">↗</span></a></div>
            <div class="pointer-events-none relative hidden h-56 items-center justify-center md:flex" aria-hidden="true"><div class="absolute h-72 w-72 rounded-full border border-blue-300/15"></div><div class="absolute h-52 w-52 rounded-full border border-blue-300/20"></div><svg class="relative h-32 w-48 -rotate-12 text-blue-100" viewBox="0 0 180 110" fill="none"><path d="M10 10h160v30a15 15 0 0 0 0 30v30H10V70a15 15 0 0 0 0-30V10Z" fill="#235bb3" stroke="currentColor" stroke-width="2" /><path d="M125 12v86" stroke="currentColor" stroke-width="2" stroke-dasharray="6 6" /><path d="M35 40h62M35 55h45M35 70h55" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></div>
        </div>
    </section>
</template>
