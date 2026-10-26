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
    <PublicLayout :catalog="true">
        <main class="bg-white">
            <div class="mx-auto max-w-7xl px-5 pb-32 pt-6 sm:px-8 md:pb-16 md:pt-10">
                <Link :href="route('public.events.index')" class="type-body-sm-strong mb-6 inline-flex items-center gap-2 text-[var(--brand-primary)] hover:underline" aria-label="Kembali ke daftar event">
                    <PublicIcon name="arrow" class="h-4 w-4" /> Kembali ke event
                </Link>

                <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-10">
                    <section class="min-w-0 lg:sticky lg:top-24 lg:self-start">
                        <div class="overflow-hidden rounded-lg border border-[#e2e8f0] bg-[#f8fafc]">
                            <EventPoster :event="event" :cover="true" />
                        </div>
                        <div class="mt-5 border-b border-[#e2e8f0] pb-6">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span v-if="event.category" class="type-caption-eyebrow text-[var(--brand-primary)]">{{ event.category }}</span>
                                <span class="type-caption-strong border-l border-[#e2e8f0] pl-2 text-[#64748b]">Detail event</span>
                            </div>
                            <h1 class="type-title-lg text-2xl text-[#101014]">{{ event.title }}</h1>
                        </div>
                        <div class="grid gap-4 py-6 text-[#101014] sm:grid-cols-2 lg:grid-cols-1">
                            <p class="type-meta-sm flex items-start gap-3"><PublicIcon name="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span>{{ formatDate(event.start_at) }}</span></p>
                            <p class="type-meta-sm flex items-start gap-3"><PublicIcon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span>{{ formatTime(event.start_at) }}<span v-if="event.end_at"> – {{ formatTime(event.end_at) }}</span></span></p>
                            <p class="type-meta-sm flex items-start gap-3"><PublicIcon name="pin" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--brand-primary)]" /><span>{{ event.location_name || 'Lokasi akan diumumkan' }}<span v-if="event.location_address" class="mt-1 block type-caption-md text-[#64748b]">{{ event.location_address }}</span></span></p>
                        </div>
                        <div class="border-t border-[#e2e8f0] pt-5">
                            <p class="type-caption-eyebrow mb-2 text-[#64748b]">Penyelenggara</p>
                            <p class="type-body-sm-strong text-[#101014]">{{ event.organizer_name || event.contact_email || event.contact_phone || 'Informasi penyelenggara belum tersedia.' }}</p>
                        </div>
                    </section>

                    <section class="min-w-0 lg:pt-1">
                        <div role="tablist" aria-label="Informasi event" class="flex border-b border-[#e2e8f0]" @keydown.right="activeTab = 'syarat'" @keydown.left="activeTab = 'deskripsi'">
                            <button id="description-tab" type="button" role="tab" :aria-selected="activeTab === 'deskripsi'" aria-controls="event-panel" :tabindex="activeTab === 'deskripsi' ? 0 : -1" class="type-body-sm-strong flex-1 border-b-2 px-4 py-3 text-left" :class="activeTab === 'deskripsi' ? 'border-[var(--brand-primary)] text-[var(--brand-primary)]' : 'border-transparent text-[#64748b] hover:text-[#101014]'" @click="activeTab = 'deskripsi'">Deskripsi</button>
                            <button id="terms-tab" type="button" role="tab" :aria-selected="activeTab === 'syarat'" aria-controls="event-panel" :tabindex="activeTab === 'syarat' ? 0 : -1" class="type-body-sm-strong flex-1 border-b-2 px-4 py-3 text-left" :class="activeTab === 'syarat' ? 'border-[var(--brand-primary)] text-[var(--brand-primary)]' : 'border-transparent text-[#64748b] hover:text-[#101014]'" @click="activeTab = 'syarat'">Syarat &amp; Ketentuan</button>
                        </div>
                        <section id="event-panel" role="tabpanel" :aria-labelledby="activeTab === 'deskripsi' ? 'description-tab' : 'terms-tab'" tabindex="0" class="mt-5 min-h-56 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-8">
                            <h2 class="type-title-md mb-4 text-[#101014]">{{ activeTab === 'deskripsi' ? 'Deskripsi' : 'Syarat & Ketentuan' }}</h2>
                            <div v-if="activeTab === 'deskripsi'" class="type-body-md whitespace-pre-line text-[#101014]">{{ event.description || 'Deskripsi event belum tersedia.' }}</div>
                            <div v-else class="type-body-md whitespace-pre-line text-[#101014]">{{ event.terms || 'Syarat dan ketentuan belum ditambahkan. Silakan hubungi penyelenggara untuk informasi lebih lanjut.' }}</div>
                        </section>

                        <div class="arc-dock border border-[#e2e8f0] bg-[#f8fafc] md:mt-5">
                            <div class="mb-4 flex items-end justify-between gap-3">
                                <span class="type-caption-md text-[#64748b]">{{ lowestPrice !== null ? 'Mulai dari' : 'Ketersediaan tiket' }}</span>
                                <span class="type-body-sm-strong text-[var(--brand-primary)]">{{ lowestPrice !== null ? (lowestPrice === 0 ? 'Gratis' : money(lowestPrice)) : 'Belum tersedia' }}</span>
                            </div>
                            <Link v-if="availableTickets.length" :href="route('public.events.checkout', event.slug)" class="arc-button w-full">Pesan Sekarang</Link>
                            <button v-else type="button" disabled class="arc-button w-full disabled:bg-[#cbd5e1] disabled:text-[#144ba3]">Tiket belum tersedia</button>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>