<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ event: { type: Object, required: true } });
const quantities = reactive(Object.fromEntries(props.event.ticket_types.map((ticket) => [ticket.id, 0])));
const form = reactive({ customer_name: '', customer_email: '', customer_phone: '' });
const attendees = ref([]);
const submitting = ref(false);
const paymentSubmitting = ref(false);
const errors = ref({});
const order = ref(null);
const payment = ref(null);
const proofFile = ref(null);
const proofSubmitting = ref(false);

const selectedItems = computed(() => props.event.ticket_types.filter((ticket) => quantities[ticket.id] > 0).map((ticket) => ({ ticket, quantity: quantities[ticket.id] })));
const totalQuantity = computed(() => selectedItems.value.reduce((total, item) => total + item.quantity, 0));
const estimatedTotal = computed(() => selectedItems.value.reduce((total, item) => total + (Number(item.ticket.price) * item.quantity), 0));
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const dateTime = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

function syncAttendees(quantity) {
    const next = Array.from({ length: quantity }, (_, index) => attendees.value[index] || { name: '', email: '', phone: '' });
    attendees.value = next;
}
watch(totalQuantity, syncAttendees, { immediate: true });

async function submit() {
    if (submitting.value) {
        return;
    }

    errors.value = {};
    if (!totalQuantity.value) {
        errors.value.items = ['Pilih minimal satu tiket.'];
        return;
    }
    submitting.value = true;
    const payload = {
        event_id: props.event.id,
        ...form,
        items: selectedItems.value.map(({ ticket, quantity }) => ({ ticket_type_id: ticket.id, quantity })),
        attendees: attendees.value,
    };

    try {
        const response = await fetch('/api/orders', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const body = await response.json();
        if (!response.ok) {
            errors.value = body.errors || { form: [body.message || 'Order gagal dibuat.'] };
            return;
        }
        order.value = body.data;
    } catch {
        errors.value = { form: ['Koneksi gagal. Coba lagi.'] };
    } finally {
        submitting.value = false;
    }
}

async function selectManualPayment() {
    if (paymentSubmitting.value) {
        return;
    }

    if (!order.value) {
        return;
    }

    paymentSubmitting.value = true;
    errors.value = {};

    try {
        const response = await fetch(`/api/orders/${order.value.id}/payments/manual`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ method: 'bank_transfer', customer_email: form.customer_email }),
        });
        const body = await response.json();

        if (!response.ok) {
            errors.value = body.errors || { payment: [body.message || 'Metode pembayaran gagal dipilih.'] };
            return;
        }

        payment.value = body.data;
    } catch {
        errors.value = { payment: ['Koneksi gagal. Coba pilih metode pembayaran lagi.'] };
    } finally {
        paymentSubmitting.value = false;
    }
}

function handleProofChange(event) {
    proofFile.value = event.target.files?.[0] || null;
}

async function uploadProof() {
    if (proofSubmitting.value || !payment.value || !proofFile.value) {
        return;
    }

    proofSubmitting.value = true;
    errors.value = {};
    const payload = new FormData();
    payload.append('customer_email', form.customer_email);
    payload.append('proof', proofFile.value);

    try {
        const response = await fetch(`/api/orders/${order.value.id}/payments/manual/proof`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: payload,
        });
        const body = await response.json();
        if (!response.ok) {
            errors.value = body.errors || { payment: [body.message || 'Bukti transfer gagal diunggah.'] };
            return;
        }
        payment.value = body.data;
        proofFile.value = null;
    } catch {
        errors.value = { payment: ['Koneksi gagal. Coba unggah bukti lagi.'] };
    } finally {
        proofSubmitting.value = false;
    }
}</script>

<template>
    <Head :title="`Checkout · ${event.title}`" /><PublicLayout><div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8"><Link :href="route('public.events.show', event.slug)" class="text-sm font-bold text-slate-600 hover:text-amber-600">← Kembali ke event</Link><div v-if="order" class="mx-auto mt-10 max-w-2xl rounded-[2rem] bg-slate-950 p-8 text-white sm:p-12"><p class="text-xs font-black uppercase tracking-[0.28em] text-amber-300">Order pending</p><h1 class="mt-4 text-4xl font-black">Order berhasil dibuat.</h1><p class="mt-4 text-slate-300">Simpan nomor order untuk proses pembayaran.</p><div class="mt-8 rounded-2xl bg-white/10 p-5"><p class="text-sm text-slate-300">Nomor order</p><p class="mt-1 text-2xl font-black text-amber-300">{{ order.order_number }}</p><div class="mt-4 flex justify-between border-t border-white/10 pt-4"><span class="text-slate-300">Total</span><strong>{{ money(order.total_amount) }}</strong></div><div v-if="order.expires_at" class="mt-4 flex justify-between border-t border-white/10 pt-4"><span class="text-slate-300">Batas pembayaran</span><strong class="text-amber-300">{{ dateTime(order.expires_at) }}</strong></div></div><section class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-xs font-black uppercase tracking-[0.2em] text-amber-300">Pembayaran</p><div v-if="payment" class="mt-4"><p class="text-lg font-black">Transfer bank dipilih</p><p class="mt-2 text-sm leading-6 text-slate-300">Silakan transfer sesuai nominal berikut. Pembayaran akan diverifikasi organizer secara manual.</p><div class="mt-4 flex justify-between border-t border-white/10 pt-4"><span class="text-slate-300">Nominal transfer</span><strong class="text-amber-300">{{ money(payment.amount) }}</strong></div><p class="mt-3 text-xs text-slate-400">Status: {{ payment.status === 'pending' ? 'Menunggu pembayaran' : payment.status }}</p></div><div v-else><p class="mt-2 text-sm leading-6 text-slate-300">Pilih transfer bank untuk melanjutkan pembayaran order.</p><button type="button" class="mt-4 rounded-xl bg-amber-500 px-5 py-3 font-black text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50" :disabled="paymentSubmitting" @click="selectManualPayment">{{ paymentSubmitting ? 'Menyiapkan pembayaran...' : 'Pilih transfer bank' }}</button></div><p v-if="errors.payment" class="mt-3 rounded-xl bg-rose-400/10 p-3 text-sm text-rose-200">{{ errors.payment[0] }}</p><div v-if="payment && !payment.proof_url" class="mt-5 border-t border-white/10 pt-5"><p class="text-sm font-bold">Upload bukti transfer</p><p class="mt-1 text-xs leading-5 text-slate-400">Format JPG, PNG, atau PDF. Maksimal 5 MB.</p><input type="file" accept="image/jpeg,image/png,application/pdf" class="mt-3 block w-full text-sm text-slate-300 file:mr-3 file:border-0 file:bg-white file:px-3 file:py-2 file:font-bold file:text-slate-900" @change="handleProofChange" /><button type="button" class="mt-3 bg-[var(--brand-primary)] px-4 py-2 text-sm font-black text-white hover:bg-[var(--brand-primary-dark)] disabled:opacity-50" :disabled="proofSubmitting || !proofFile" @click="uploadProof">{{ proofSubmitting ? 'Mengunggah...' : 'Kirim bukti transfer' }}</button></div><p v-else-if="payment && payment.proof_url" class="mt-5 border-t border-white/10 pt-5 text-sm text-emerald-200">Bukti transfer sudah diterima dan menunggu verifikasi organizer.</p></section><Link :href="route('public.orders.status')" class="mt-4 inline-block text-sm font-bold text-amber-300 hover:text-amber-200">Cek status order</Link><Link :href="route('public.events.show', event.slug)" class="mt-8 inline-block rounded-xl bg-amber-500 px-5 py-3 font-black text-slate-950 hover:bg-amber-400">Kembali ke event</Link></div><div v-else class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]"><form class="space-y-6" @submit.prevent="submit"><section class="rounded-3xl border border-slate-200 bg-white p-6"><h1 class="text-2xl font-black">Pilih tiket</h1><p class="mt-1 text-sm text-slate-500">{{ event.title }}</p><div class="mt-5 space-y-3"><div v-for="ticket in event.ticket_types" :key="ticket.id" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-4"><div><p class="font-bold">{{ ticket.name }}</p><p class="text-sm text-amber-600">{{ money(ticket.price) }}</p></div><input v-model.number="quantities[ticket.id]" type="number" min="0" :max="Math.min(ticket.remaining_quota, 20)" class="w-24 rounded-xl border-slate-200 text-center" /></div></div><p v-if="errors.items" class="mt-3 text-sm text-rose-600">{{ errors.items[0] }}</p></section><section class="rounded-3xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-black">Data customer</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><label class="sm:col-span-2"><span class="text-sm font-bold">Nama</span><input v-model="form.customer_name" required class="mt-1 w-full rounded-xl border-slate-200" /></label><label><span class="text-sm font-bold">Email</span><input v-model="form.customer_email" required type="email" class="mt-1 w-full rounded-xl border-slate-200" /></label><label><span class="text-sm font-bold">Telepon</span><input v-model="form.customer_phone" required class="mt-1 w-full rounded-xl border-slate-200" /></label></div></section><section v-if="attendees.length" class="rounded-3xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-black">Data peserta</h2><div class="mt-5 space-y-5"><div v-for="(attendee, index) in attendees" :key="index" class="border-t border-slate-100 pt-5 first:border-0 first:pt-0"><p class="mb-3 text-sm font-black text-amber-600">Peserta {{ index + 1 }}</p><div class="grid gap-3 sm:grid-cols-3"><input v-model="attendee.name" required placeholder="Nama" class="rounded-xl border-slate-200" /><input v-model="attendee.email" required type="email" placeholder="Email" class="rounded-xl border-slate-200" /><input v-model="attendee.phone" required placeholder="Telepon" class="rounded-xl border-slate-200" /></div></div></div></section><p v-if="errors.form" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ errors.form[0] }}</p><button type="submit" :disabled="submitting" class="w-full rounded-2xl bg-amber-500 px-5 py-4 font-black text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">{{ submitting ? 'Membuat order...' : 'Buat order' }}</button></form><aside class="h-fit rounded-3xl bg-slate-950 p-6 text-white"><p class="text-xs font-black uppercase tracking-[0.24em] text-amber-300">Ringkasan</p><h2 class="mt-2 text-xl font-black">{{ totalQuantity }} tiket</h2><div class="mt-5 space-y-3 border-t border-white/10 pt-5"><div v-for="item in selectedItems" :key="item.ticket.id" class="flex justify-between gap-3 text-sm"><span class="text-slate-300">{{ item.ticket.name }} × {{ item.quantity }}</span><strong>{{ money(Number(item.ticket.price) * item.quantity) }}</strong></div></div><div class="mt-5 flex justify-between border-t border-white/10 pt-5 text-lg"><span>Total sementara</span><strong class="text-amber-300">{{ money(estimatedTotal) }}</strong></div><p class="mt-4 text-xs leading-5 text-slate-400">Total final dihitung ulang server saat order dibuat.</p></aside></div></div></PublicLayout>
</template>
