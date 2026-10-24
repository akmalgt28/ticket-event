<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import EventPoster from '@/Components/EventPoster.vue';
import PublicIcon from '@/Components/PublicIcon.vue';
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({ event: { type: Object, required: true }, quantities: { type: Object, default: () => ({}) } });
const page = usePage();
const navigating = ref(false);
const ticketLimit = (ticket) => Math.min(20, ticket.remaining_quota);
const quantities = reactive(Object.fromEntries(props.event.ticket_types.map((ticket) => [ticket.id, Math.max(0, Math.min(ticketLimit(ticket), Math.trunc(Number(props.quantities[ticket.id]) || 0)))])));
const selectedItems = computed(() => props.event.ticket_types.filter((ticket) => quantities[ticket.id] > 0).map((ticket) => ({ ticket, quantity: quantities[ticket.id] })));
const totalQuantity = computed(() => selectedItems.value.reduce((total, item) => total + item.quantity, 0));
const estimatedTotal = computed(() => selectedItems.value.reduce((total, item) => total + Number(item.ticket.price) * item.quantity, 0));
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

function lanjutKeDataDiri() {
    if (totalQuantity.value === 0 || navigating.value) return;
    router.get(route('public.events.data-diri', props.event.slug), {
        qty: Object.fromEntries(selectedItems.value.map(({ ticket, quantity }) => [ticket.id, quantity])),
    }, {
        onStart: () => { navigating.value = true; },
        onFinish: () => { navigating.value = false; },
    });
}
</script>

<template>
    <Head :title="'Pilih Tiket · ' + event.title" />
    <PublicLayout>
        <div class="mx-auto max-w-5xl pb-32 md:px-8 md:py-8">
            <div class="grid min-w-0 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] md:items-start md:gap-10">
                <div class="min-w-0 md:sticky md:top-24">
                    <EventPoster :event="event" class="md:rounded-2xl" />
                    <div class="hidden pt-5 text-white md:block"><h1 class="text-2xl font-bold">{{ event.title }}</h1><p class="mt-2 text-sm text-blue-100">Pilih kategori dan jumlah tiket untuk melanjutkan.</p></div>
                </div>
                <div class="min-w-0 px-5 pt-4 md:px-0 md:pt-0">
                    <Link :href="route('public.events.show', event.slug)" class="mb-5 inline-flex items-center gap-2 text-xs text-blue-100"><PublicIcon name="arrow" class="h-4 w-4" /> Detail event</Link>
                    <h1 class="sr-only">Pilih tiket {{ event.title }}</h1>
                    <p v-if="page.props.errors?.qty" role="alert" class="mb-5 rounded-xl bg-white p-4 text-sm text-rose-700">{{ page.props.errors.qty }}</p>
                    <p v-if="!event.ticket_types.length" class="arc-card text-sm">Tiket belum tersedia untuk dibeli.</p>
                    <div class="space-y-7">
                        <article v-for="ticket in event.ticket_types" :key="ticket.id">
                            <span class="arc-badge mb-3 bg-[#d9dce2]">{{ ticket.remaining_quota > 0 ? 'Tersedia' : 'Habis' }}</span>
                            <div class="overflow-hidden rounded-2xl" :class="ticket.remaining_quota === 0 || quantities[ticket.id] > 0 ? 'bg-[#d9dce2]' : 'bg-white'">
                                <div class="px-6 py-5"><h2 class="text-sm font-semibold">{{ ticket.name }}</h2><p v-if="ticket.description" class="mt-1 text-xs leading-5 text-slate-600">{{ ticket.description }}</p></div>
                                <div class="relative flex min-h-16 items-center justify-between gap-3 border-t border-slate-300 px-6 py-3">
                                    <span class="absolute -left-2.5 -top-2.5 h-5 w-5 rounded-full bg-[var(--arc-blue)]" aria-hidden="true"></span><span class="absolute -right-2.5 -top-2.5 h-5 w-5 rounded-full bg-[var(--arc-blue)]" aria-hidden="true"></span>
                                    <p class="text-sm font-bold">{{ money(ticket.price) }}</p>
                                    <span v-if="ticket.remaining_quota === 0" class="sr-only">Tiket habis</span>
                                    <button v-else-if="quantities[ticket.id] === 0" type="button" class="arc-button min-h-9 px-7 py-1.5 text-xs" :aria-label="'Pilih ' + ticket.name" @click="quantities[ticket.id] = 1">Pilih</button>
                                    <div v-else class="flex shrink-0 items-center gap-2">
                                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--arc-blue)] text-xl text-white" :aria-label="'Kurangi ' + ticket.name" @click="quantities[ticket.id] = Math.max(0, quantities[ticket.id] - 1)">−</button>
                                        <output class="min-w-5 text-center text-sm font-semibold" aria-live="polite">{{ quantities[ticket.id] }}</output>
                                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--arc-blue)] text-xl text-white disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Tambah ' + ticket.name" :disabled="quantities[ticket.id] >= ticketLimit(ticket)" @click="quantities[ticket.id] = Math.min(ticketLimit(ticket), quantities[ticket.id] + 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="arc-dock mt-6 flex items-center justify-between gap-4">
                        <div class="min-w-0 text-sm" aria-live="polite"><p class="font-semibold">{{ totalQuantity ? totalQuantity + ' tiket dipilih' : 'Pilih tiket' }}</p><p v-if="selectedItems.length" class="mt-1 text-xs text-slate-600">{{ selectedItems.map(({ ticket, quantity }) => ticket.name + ' ×' + quantity).join(', ') }}</p><p class="mt-1 font-bold">{{ money(estimatedTotal) }}</p></div>
                        <button type="button" class="arc-button shrink-0 px-8" :disabled="totalQuantity === 0 || navigating" @click="lanjutKeDataDiri">{{ navigating ? 'Memproses...' : 'Beli' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
