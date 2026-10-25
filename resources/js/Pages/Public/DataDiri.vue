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
    <PublicLayout>
        <div class="mx-auto max-w-4xl px-6 pb-12 pt-7 sm:px-8 md:py-12">
            <Link :href="route('public.events.checkout', { event: event.slug, qty: Object.fromEntries(items.map((item) => [item.ticket_type_id, item.quantity])) })" class="mb-5 inline-flex items-center gap-2 text-xs text-blue-100"><PublicIcon name="arrow" class="h-4 w-4" /> Pilih tiket</Link>
            <div class="mb-6"><h1 class="arc-badge">Isi Data Diri</h1></div>
            <div class="grid min-w-0 gap-6 md:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] md:items-start">
                <section class="arc-card min-w-0 px-6 py-7 sm:px-8">
                    <form class="space-y-4" @submit.prevent="submitForm">
                        <div v-if="generalErrors.length" role="alert" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700"><p v-for="[key, error] in generalErrors" :key="key">{{ error }}</p></div>
                        <div v-for="field in fields" :key="field.key">
                            <label :for="'buyer-' + field.key" class="mb-1.5 block text-sm font-medium">{{ field.label }}</label>
                            <input :id="'buyer-' + field.key" v-model="form[field.key]" :name="field.key" :type="field.type" :required="field.required" :maxlength="field.maxlength" :autocomplete="field.autocomplete" :aria-invalid="Boolean(form.errors[field.error])" :aria-describedby="form.errors[field.error] ? field.key + '-error' : undefined" class="arc-input py-1.5" :class="form.errors[field.error] ? 'border-rose-500' : ''" />
                            <p v-if="form.errors[field.error]" :id="field.key + '-error'" class="mt-1 text-xs text-rose-700">{{ form.errors[field.error] }}</p>
                        </div>
                        <div class="pt-3 text-center"><button type="submit" :disabled="form.processing" class="arc-button min-w-32">{{ form.processing ? 'Memproses...' : 'Submit' }}</button></div>
                    </form>
                </section>
                <aside class="rounded-2xl bg-[var(--arc-sky)] p-5 text-sm md:sticky md:top-24">
                    <h2 class="font-bold">{{ event.title }}</h2>
                    <div class="my-4 space-y-2 border-y border-blue-200 py-4"><p v-for="item in selectedItems" :key="item.ticket_type_id" class="flex justify-between gap-3"><span>{{ item.ticket?.name }} ×{{ item.quantity }}</span><span class="shrink-0 font-semibold">{{ money(Number(item.ticket?.price || 0) * item.quantity) }}</span></p></div>
                    <p class="flex justify-between gap-3 font-bold"><span>Total</span><span>{{ money(estimatedTotal) }}</span></p>
                    <p class="mt-4 text-xs leading-5 text-slate-600">Data pembeli digunakan untuk seluruh tiket dalam pesanan ini. E-ticket dikirim ke email pembeli setelah pembayaran diverifikasi.</p>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>