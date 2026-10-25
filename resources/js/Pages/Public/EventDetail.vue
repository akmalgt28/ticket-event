<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import EventPoster from '@/Components/EventPoster.vue';
import PublicIcon from '@/Components/PublicIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ event: { type: Object, required: true } });
const activeTab = ref('deskripsi');
const availableTickets = computed(() => props.event.ticket_types.filter((ticket) => ticket.remaining_quota > 0));
const lowestPrice = computed(() => availableTickets.value.length ? Math.min(...availableTickets.value.map((ticket) => Number(ticket.price))) : null);
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const formatDate = (value) => value ? new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Tanggal akan diumumkan';
const formatTime = (value) => value ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(value)).replace('.', ':') + ' WIB' : 'Jam akan diumumkan';
</script>

<template>
    <Head :title="event.title" />
    <PublicLayout>
        <div class="mx-auto max-w-5xl pb-32 md:px-8 md:py-8">
            <div class="grid min-w-0 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] md:items-start md:gap-8">
                <div class="min-w-0 md:sticky md:top-24">
                    <EventPoster :event="event" class="md:rounded-2xl" />
                    <div class="px-5 pb-5 pt-4 text-white md:px-0 md:pt-6">
                        <span v-if="event.category" class="arc-badge">{{ event.category }}</span>
                        <h1 class="mt-2 text-xl font-bold leading-relaxed md:text-2xl">{{ event.title }}</h1>
                        <div class="mt-5 grid gap-3 text-sm">
                            <p class="flex items-start gap-3"><PublicIcon name="calendar" class="h-5 w-5 shrink-0" /><span>{{ formatDate(event.start_at) }}</span></p>
                            <p class="flex items-start gap-3"><PublicIcon name="clock" class="h-5 w-5 shrink-0" /><span>{{ formatTime(event.start_at) }}<span v-if="event.end_at"> – {{ formatTime(event.end_at) }}</span></span></p>
                            <p class="flex items-start gap-3"><PublicIcon name="pin" class="h-5 w-5 shrink-0" /><span>{{ event.location_name || 'Lokasi akan diumumkan' }}<span v-if="event.location_address" class="mt-1 block text-xs leading-5 text-blue-100">{{ event.location_address }}</span></span></p>
                        </div>
                        <div class="mt-6">
                            <p class="mb-2 text-sm font-semibold">Penyelenggara</p>
                            <span v-if="event.organizer_name" class="arc-badge bg-[#d9dce2]">{{ event.organizer_name }}</span>
                            <p v-else class="text-sm text-blue-100">{{ event.contact_email || event.contact_phone || 'Informasi penyelenggara belum tersedia.' }}</p>
                        </div>
                    </div>
                </div>
                <div class="min-w-0 md:pt-1">
                    <div role="tablist" aria-label="Informasi event" class="flex px-5 text-sm text-white md:px-0">
                        <button id="description-tab" type="button" role="tab" :aria-selected="activeTab === 'deskripsi'" aria-controls="event-panel" :tabindex="activeTab === 'deskripsi' ? 0 : -1" class="flex-1 border-b-2 px-2 py-3" :class="activeTab === 'deskripsi' ? 'border-white font-bold' : 'border-transparent text-blue-100'" @click="activeTab = 'deskripsi'" @keydown.right="activeTab = 'syarat'; $event.currentTarget.nextElementSibling.focus()">Deskripsi</button>
                        <button id="terms-tab" type="button" role="tab" :aria-selected="activeTab === 'syarat'" aria-controls="event-panel" :tabindex="activeTab === 'syarat' ? 0 : -1" class="flex-1 border-b-2 px-2 py-3" :class="activeTab === 'syarat' ? 'border-white font-bold' : 'border-transparent text-blue-100'" @click="activeTab = 'syarat'" @keydown.left="activeTab = 'deskripsi'; $event.currentTarget.previousElementSibling.focus()">Syarat &amp; Ketentuan</button>
                    </div>
                    <section id="event-panel" role="tabpanel" :aria-labelledby="activeTab === 'deskripsi' ? 'description-tab' : 'terms-tab'" tabindex="0" class="min-h-56 rounded-t-2xl bg-white p-6 md:mt-0 md:rounded-2xl">
                        <h2 class="mb-4 w-fit border-b-2 border-slate-900 text-xl font-bold">{{ activeTab === 'deskripsi' ? 'Deskripsi' : 'Syarat & Ketentuan' }}</h2>
                        <div v-if="activeTab === 'deskripsi'" class="whitespace-pre-line text-sm leading-6">{{ event.description || 'Deskripsi event belum tersedia.' }}</div>
                        <div v-else class="whitespace-pre-line text-sm leading-6">{{ event.terms || 'Syarat dan ketentuan belum ditambahkan. Silakan hubungi penyelenggara untuk informasi lebih lanjut.' }}</div>
                    </section>
                    <div class="arc-dock md:mt-5">
                        <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                            <span>{{ lowestPrice !== null ? 'Mulai dari' : 'Ketersediaan tiket' }}</span>
                            <span class="font-semibold text-[var(--arc-blue)]">{{ lowestPrice !== null ? (lowestPrice === 0 ? 'Gratis' : money(lowestPrice)) : 'Belum tersedia' }}</span>
                        </div>
                        <Link v-if="availableTickets.length" :href="route('public.events.checkout', event.slug)" class="arc-button w-full">Pesan Sekarang</Link>
                        <button v-else type="button" disabled class="arc-button w-full">Tiket belum tersedia</button>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>