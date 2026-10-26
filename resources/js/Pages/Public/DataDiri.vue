<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import PublicIcon from '@/Components/PublicIcon.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ event: { type: Object, required: true }, items: { type: Array, required: true } });
const form = useForm('checkout:' + props.event.slug + ':' + JSON.stringify(props.items), {
    nama: '', wa: '', email: '', profesi: '', brand: '', domisili: '',
});
const fields = [
    { key: 'nama', label: 'Nama lengkap', type: 'text', required: true, maxlength: 255, autocomplete: 'name', error: 'customer_name' },
    { key: 'wa', label: 'Nomor WhatsApp aktif', type: 'tel', required: true, maxlength: 30, autocomplete: 'tel', error: 'customer_phone' },
    { key: 'email', label: 'Email', type: 'email', required: true, maxlength: 255, autocomplete: 'email', error: 'customer_email' },
    { key: 'profesi', label: 'Status / Profesi saat ini', type: 'text', maxlength: 255, autocomplete: 'organization-title' },
    { key: 'brand', label: 'Nama Usaha / Brand', type: 'text', maxlength: 255, autocomplete: 'organization' },
    { key: 'domisili', label: 'Domisili', type: 'text', maxlength: 255, autocomplete: 'address-level2' },
];
const generalErrors = computed(() => Object.entries(form.errors).filter(([key]) => !fields.some((field) => field.error === key)));
const selectedItems = computed(() => props.items.map((item) => ({ ...item, ticket: props.event.ticket_types.find((ticket) => ticket.id === item.ticket_type_id) })));
const estimatedTotal = computed(() => selectedItems.value.reduce((total, item) => total + Number(item.ticket?.price || 0) * item.quantity, 0));
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

function submitForm() {
    if (form.processing) return;
    form.transform((data) => ({
        event_id: props.event.id,
        customer_name: data.nama,
        customer_email: data.email,
        customer_phone: data.wa,
        items: props.items,
        attendees: props.items.flatMap((item) => Array.from({ length: item.quantity }, () => ({
            name: data.nama,
            email: data.email,
            phone: data.wa,
            metadata: { profesi: data.profesi, brand: data.brand, domisili: data.domisili },
        }))),
    })).post(route('public.events.orders.store', props.event.slug), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => form.reset(),
        onError: () => document.querySelector('[aria-invalid="true"]')?.focus(),
    });
}
</script>

<template>
    <Head title="Isi Data Diri" />
    <PublicLayout :catalog="true">
        <div class="min-h-[calc(100vh-4rem)] bg-[var(--brand-paper)]">
            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-12">
                <div class="mb-8 max-w-3xl">
                    <Link :href="route('public.events.checkout', { event: event.slug, qty: Object.fromEntries(items.map((item) => [item.ticket_type_id, item.quantity])) })" class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:text-[var(--brand-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[var(--brand-primary)]">
                        <PublicIcon name="arrow" class="h-4 w-4" />
                        Pilih tiket
                    </Link>
                    <h1 class="mt-5 type-title-lg text-3xl text-[var(--brand-ink)] sm:text-4xl">Isi data pemesan</h1>
                    <p class="mt-2 type-body-sm-relaxed text-slate-600">Lengkapi data berikut untuk menerima konfirmasi pesanan dan e-ticket.</p>
                </div>

                <div class="grid min-w-0 gap-8 lg:grid-cols-[minmax(0,1.25fr)_minmax(300px,0.75fr)] lg:items-start xl:gap-12">
                    <section class="min-w-0 border border-slate-200 bg-white p-5 sm:p-7 lg:p-8">
                        <form class="space-y-5" @submit.prevent="submitForm">
                            <div v-if="generalErrors.length" role="alert" class="border border-rose-200 bg-rose-50 p-4 type-body-sm text-rose-700">
                                <p v-for="[key, error] in generalErrors" :key="key">{{ error }}</p>
                            </div>
                            <div v-for="field in fields" :key="field.key">
                                <label :for="'buyer-' + field.key" class="mb-2 block type-body-sm-strong text-[var(--brand-ink)]">{{ field.label }}<span v-if="field.required" class="ml-1 text-rose-600" aria-hidden="true">*</span></label>
                                <input :id="'buyer-' + field.key" v-model="form[field.key]" :name="field.key" :type="field.type" :required="field.required" :maxlength="field.maxlength" :autocomplete="field.autocomplete" :aria-invalid="Boolean(form.errors[field.error])" :aria-describedby="form.errors[field.error] ? field.key + '-error' : undefined" class="block min-h-11 w-full rounded-md border bg-white px-3.5 type-body-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/15" :class="form.errors[field.error] ? 'border-rose-500' : 'border-slate-300'" />
                                <p v-if="form.errors[field.error]" :id="field.key + '-error'" class="mt-2 type-caption-md text-rose-700">{{ form.errors[field.error] }}</p>
                            </div>
                            <div class="border-t border-slate-200 pt-6">
                                <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-[var(--brand-primary)] px-6 type-body-sm-strong text-white transition hover:bg-[var(--brand-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-primary)] disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:min-w-44">{{ form.processing ? 'Memproses...' : 'Lanjut ke pembayaran' }}</button>
                            </div>
                        </form>
                    </section>

                    <aside class="min-w-0 lg:sticky lg:top-24">
                        <div class="border border-slate-200 bg-white p-5 sm:p-6">
                            <h2 class="type-title-md text-xl text-[var(--brand-ink)]">Ringkasan pesanan</h2>
                            <p class="mt-2 type-body-sm text-slate-600">{{ event.title }}</p>
                            <div class="my-5 space-y-4 border-y border-slate-200 py-5">
                                <div v-for="item in selectedItems" :key="item.ticket_type_id" class="flex justify-between gap-4 type-body-sm">
                                    <span class="text-slate-700">{{ item.ticket?.name }} ×{{ item.quantity }}</span>
                                    <span class="shrink-0 type-body-sm-strong text-[var(--brand-ink)]">{{ money(Number(item.ticket?.price || 0) * item.quantity) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="type-body-sm-strong text-[var(--brand-ink)]">Total</span>
                                <span class="type-title-md text-lg text-[var(--brand-ink)]">{{ money(estimatedTotal) }}</span>
                            </div>
                            <p class="mt-5 border-t border-slate-200 pt-5 type-body-sm-relaxed text-slate-600">Data pembeli digunakan untuk seluruh tiket dalam pesanan ini. E-ticket dikirim ke email pembeli setelah pembayaran diverifikasi.</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
