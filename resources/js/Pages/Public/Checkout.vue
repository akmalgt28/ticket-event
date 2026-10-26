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
    <PublicLayout :catalog="true">
        <div class="min-h-[calc(100vh-4rem)] bg-[var(--brand-paper)]">
            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-12">
                <div class="mb-8 max-w-3xl">
                    <Link :href="route('public.events.show', event.slug)" class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:text-[var(--brand-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--brand-primary)]">
                        <PublicIcon name="arrow" class="h-4 w-4" />
                        Detail event
                    </Link>
                    <h1 class="mt-5 type-title-lg text-3xl text-[var(--brand-ink)] sm:text-4xl">Pilih tiket</h1>
                    <p class="mt-2 type-body-sm-relaxed text-slate-600">Pilih kategori tiket dan jumlah yang ingin kamu pesan untuk {{ event.title }}.</p>
                </div>

                <div class="grid min-w-0 gap-8 lg:grid-cols-[minmax(250px,0.75fr)_minmax(0,1.25fr)] lg:items-start xl:gap-12">
                    <aside class="min-w-0 lg:sticky lg:top-24">
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <EventPoster :event="event" :cover="true" />
                            <div class="p-5 sm:p-6">
                                <h2 class="type-title-md text-xl text-[var(--brand-ink)]">{{ event.title }}</h2>
                                <p class="mt-2 type-body-sm-relaxed text-slate-600">Tiket resmi dan aman untuk event pilihanmu.</p>
                            </div>
                        </div>
                    </aside>

                    <section class="min-w-0">
                        <p v-if="page.props.errors?.qty" role="alert" class="mb-5 border border-rose-200 bg-rose-50 p-4 type-body-sm text-rose-700">{{ page.props.errors.qty }}</p>
                        <div v-if="!event.ticket_types.length" class="border border-slate-200 bg-white p-6 type-body-sm text-slate-600">Tiket belum tersedia untuk dibeli.</div>
                        <div v-else class="space-y-4">
                            <article v-for="ticket in event.ticket_types" :key="ticket.id" class="overflow-hidden rounded-lg border bg-white transition-colors" :class="quantities[ticket.id] > 0 ? 'border-[var(--brand-primary)]' : 'border-slate-200'">
                                <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                                    <div class="min-w-0">
                                        <div class="mb-3 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center border px-2.5 py-1 type-caption-strong" :class="ticket.remaining_quota > 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-500'">{{ ticket.remaining_quota > 0 ? 'Tersedia' : 'Habis' }}</span>
                                            <span v-if="ticket.remaining_quota > 0" class="type-caption-md text-slate-500">{{ ticket.remaining_quota }} tiket tersisa</span>
                                        </div>
                                        <h2 class="type-title-md text-lg text-[var(--brand-ink)]">{{ ticket.name }}</h2>
                                        <p v-if="ticket.description" class="mt-2 max-w-xl type-body-sm-relaxed text-slate-600">{{ ticket.description }}</p>
                                    </div>
                                    <div class="shrink-0 sm:text-right">
                                        <p class="type-title-md text-lg text-[var(--brand-ink)]">{{ money(ticket.price) }}</p>
                                        <p class="mt-1 type-caption-md text-slate-500">per tiket</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                                    <span v-if="ticket.remaining_quota === 0" class="type-body-sm text-slate-500">Tiket habis</span>
                                    <span v-else class="type-body-sm text-slate-600">Jumlah tiket</span>
                                    <button v-if="ticket.remaining_quota > 0 && quantities[ticket.id] === 0" type="button" class="inline-flex min-h-10 items-center justify-center rounded-md bg-[var(--brand-primary)] px-6 type-body-sm-strong text-white transition hover:bg-[var(--brand-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-primary)]" :aria-label="'Pilih ' + ticket.name" @click="quantities[ticket.id] = 1">Pilih</button>
                                    <div v-else-if="ticket.remaining_quota > 0" class="flex items-center overflow-hidden rounded-md border border-slate-300 bg-white">
                                        <button type="button" class="flex h-10 w-10 items-center justify-center border-r border-slate-300 text-lg text-[var(--brand-ink)] transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[var(--brand-primary)]" :aria-label="'Kurangi ' + ticket.name" @click="quantities[ticket.id] = Math.max(0, quantities[ticket.id] - 1)">−</button>
                                        <output class="min-w-10 text-center type-body-sm-strong text-[var(--brand-ink)]" aria-live="polite">{{ quantities[ticket.id] }}</output>
                                        <button type="button" class="flex h-10 w-10 items-center justify-center border-l border-slate-300 text-lg text-[var(--brand-ink)] transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[var(--brand-primary)] disabled:cursor-not-allowed disabled:opacity-40" :aria-label="'Tambah ' + ticket.name" :disabled="quantities[ticket.id] >= ticketLimit(ticket)" @click="quantities[ticket.id] = Math.min(ticketLimit(ticket), quantities[ticket.id] + 1)">+</button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="mt-6 border border-slate-200 bg-white p-5 sm:p-6" aria-live="polite">
                            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                                <div class="min-w-0">
                                    <p class="type-body-sm-strong text-[var(--brand-ink)]">{{ totalQuantity ? totalQuantity + ' tiket dipilih' : 'Belum ada tiket dipilih' }}</p>
                                    <p v-if="selectedItems.length" class="mt-2 type-caption-md text-slate-600">{{ selectedItems.map(({ ticket, quantity }) => ticket.name + ' ×' + quantity).join(', ') }}</p>
                                    <p class="mt-3 type-title-md text-xl text-[var(--brand-ink)]">{{ money(estimatedTotal) }}</p>
                                </div>
                                <button type="button" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-[var(--brand-primary)] px-7 type-body-sm-strong text-white transition hover:bg-[var(--brand-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-primary)] disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" :disabled="totalQuantity === 0 || navigating" @click="lanjutKeDataDiri">{{ navigating ? 'Memproses...' : 'Lanjutkan' }}</button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
