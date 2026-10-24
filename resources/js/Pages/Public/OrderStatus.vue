<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import EventPoster from '@/Components/EventPoster.vue';
import PublicIcon from '@/Components/PublicIcon.vue';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ checkout: { type: Object, default: null }, mode: { type: String, default: 'status' } });
const form = reactive({ order_number: props.checkout?.order_number || '', customer_email: props.checkout?.customer_email || '' });
const order = ref(null);
const credentials = ref(null);
const loading = ref(false);
const error = ref('');
const paymentProcessing = ref(false);
const proof = ref(null);
const proofInput = ref(null);
const proofError = ref('');
const notice = ref('');
const detailsVisible = ref(props.mode !== 'payment');
const currentTime = ref(Date.now());
let clockTimer;
const isPayment = computed(() => props.mode === 'payment');
const deadlinePassed = computed(() => Boolean(order.value?.expires_at && new Date(order.value.expires_at).getTime() <= currentTime.value));
const canPay = computed(() => order.value?.order_status === 'pending' && ['pending', 'failed'].includes(order.value.payment_status) && !deadlinePassed.value);
const bankAccounts = computed(() => order.value?.payment_options?.bank_accounts || []);
const statusKey = computed(() => ['expired', 'cancelled', 'refunded'].includes(order.value?.order_status) ? order.value.order_status : order.value?.payment_status);
const responseError = (body) => Object.values(body.errors || {}).flat()[0] || body.message || 'Permintaan tidak dapat diproses.';
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const paymentLabel = (status) => ({ pending: 'Menunggu pembayaran', paid: 'Pembayaran berhasil', failed: 'Pembayaran ditolak', expired: 'Pembayaran kedaluwarsa', cancelled: 'Pesanan dibatalkan', refunded: 'Pembayaran dikembalikan' }[status] || status);
const dateTime = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' : '—';

async function lookup() {
    if (loading.value || paymentProcessing.value) return;
    loading.value = true;
    error.value = '';
    notice.value = '';
    proofError.value = '';
    proof.value = null;
    order.value = null;
    const payload = { order_number: form.order_number.trim(), customer_email: form.customer_email.trim() };
    try {
        const response = await fetch('/api/orders/status', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const body = await response.json();
        if (!response.ok) { error.value = responseError(body); return; }
        order.value = body.data;
        credentials.value = payload;
        currentTime.value = Date.now();
        if (body.data.payment?.proof_uploaded_at) detailsVisible.value = true;
    } catch { error.value = 'Koneksi gagal. Silakan coba lagi.'; } finally { loading.value = false; }
}

async function selectManualTransfer() {
    if (!canPay.value || paymentProcessing.value) return;
    paymentProcessing.value = true;
    error.value = '';
    notice.value = '';
    try {
        const response = await fetch('/api/orders/' + order.value.id + '/payments/manual', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_email: credentials.value.customer_email, method: 'bank_transfer' }),
        });
        const body = await response.json();
        if (!response.ok) { error.value = responseError(body); return; }
        order.value.payment = body.data;
        order.value.payment_status = body.data.status;
        detailsVisible.value = true;
    } catch { error.value = 'Koneksi gagal. Silakan coba lagi.'; } finally { paymentProcessing.value = false; }
}

function chooseProof(event) {
    const file = event.target.files?.[0] || null;
    proof.value = null;
    proofError.value = '';
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) {
        proofError.value = 'Gunakan file JPG, PNG, atau PDF.';
    } else if (file.size > 5 * 1024 * 1024) {
        proofError.value = 'Ukuran file maksimal 5 MB.';
    } else {
        proof.value = file;
    }
    if (proofError.value) event.target.value = '';
}

async function uploadProof() {
    if (!proof.value || !canPay.value || paymentProcessing.value) return;
    paymentProcessing.value = true;
    error.value = '';
    notice.value = '';
    proofError.value = '';
    const data = new FormData();
    data.append('customer_email', credentials.value.customer_email);
    data.append('proof', proof.value);
    try {
        const response = await fetch('/api/orders/' + order.value.id + '/payments/manual/proof', {
            method: 'POST', headers: { Accept: 'application/json' }, body: data,
        });
        const body = await response.json();
        if (!response.ok) { error.value = responseError(body); return; }
        order.value.payment = body.data;
        notice.value = 'Bukti pembayaran diterima. Pembayaran menunggu verifikasi organizer.';
        proof.value = null;
        if (proofInput.value) proofInput.value.value = '';
    } catch { error.value = 'Koneksi gagal. Silakan coba lagi.'; } finally { paymentProcessing.value = false; }
}

onMounted(() => {
    clockTimer = setInterval(() => { currentTime.value = Date.now(); }, 1000);
    if (props.checkout) lookup();
});
onUnmounted(() => clearInterval(clockTimer));
</script>

<template>
    <Head :title="isPayment ? 'Pembayaran' : 'Status Order'" />
    <PublicLayout>
        <div class="mx-auto max-w-5xl pb-12 md:px-8 md:py-8">
            <div class="grid min-w-0 md:items-start md:gap-10" :class="isPayment && order ? 'md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]' : 'mx-auto max-w-xl'">
                <div v-if="isPayment && order" class="min-w-0 md:sticky md:top-24">
                    <EventPoster :event="order.event" class="md:rounded-2xl" />
                    <div class="hidden pt-5 text-white md:block"><h1 class="text-2xl font-bold">{{ order.event.title }}</h1><p class="mt-3 text-sm text-blue-100">Selesaikan pembayaran untuk menerima e-ticket Anda.</p></div>
                </div>
                <div class="min-w-0 px-6 pt-5 md:px-0 md:pt-0">
                    <h1 class="arc-badge mb-6">{{ isPayment ? (detailsVisible ? 'Pembayaran' : 'Pilih Pembayaran') : 'Status Order' }}</h1>
                    <section v-if="!isPayment || (!loading && !order)" class="arc-card mb-6">
                        <h2 class="mb-4 text-lg font-bold">Cek status pesanan</h2>
                        <form class="space-y-4" @submit.prevent="lookup()">
                            <div><label for="order-number" class="mb-1.5 block text-sm font-medium">Nomor order</label><input id="order-number" v-model="form.order_number" required maxlength="50" class="arc-input" placeholder="ORD-..." /></div>
                            <div><label for="order-email" class="mb-1.5 block text-sm font-medium">Email pembeli</label><input id="order-email" v-model="form.customer_email" required type="email" maxlength="255" autocomplete="email" class="arc-input" /></div>
                            <button type="submit" :disabled="loading || paymentProcessing" class="arc-button w-full">{{ loading ? 'Mengecek...' : 'Cek status' }}</button>
                        </form>
                    </section>
                    <p v-if="loading" role="status" class="mb-5 text-sm text-white">Memuat informasi pembayaran...</p>
                    <p v-if="error" role="alert" class="mb-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>
                    <template v-if="order">
                        <section v-if="!isPayment || !canPay || order.payment?.proof_uploaded_at" class="arc-card mb-5">
                            <div class="mb-4 flex items-start justify-between gap-3"><h2 class="text-sm font-bold">{{ order.event.title }}</h2><span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold" :class="statusKey === 'paid' ? 'bg-emerald-100 text-emerald-800' : statusKey === 'pending' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800'">{{ paymentLabel(statusKey) }}</span></div>
                            <p class="text-xs text-slate-500">Nomor order</p><p class="mt-1 break-all text-sm font-semibold">{{ order.order_number }}</p>
                            <div class="my-4 flex items-center justify-between gap-3 border-y border-slate-200 py-4 text-sm"><span>Total pembayaran</span><strong class="text-[var(--arc-blue)]">{{ money(order.total_amount) }}</strong></div>
                            <p v-if="statusKey === 'paid'" class="text-sm leading-6 text-emerald-800">Pembayaran sudah diverifikasi. E-ticket masuk antrean pengiriman ke email pembeli.</p>
                            <p v-else-if="statusKey === 'pending' && order.payment?.proof_uploaded_at" class="text-sm leading-6 text-blue-800">Bukti pembayaran diterima. Menunggu verifikasi organizer.</p>
                            <p v-else-if="statusKey === 'pending' && deadlinePassed" class="text-sm leading-6 text-rose-700">Batas waktu pembayaran telah berakhir. Perbarui status untuk melihat kondisi pesanan terbaru.</p>
                            <p v-else-if="statusKey === 'pending'" class="text-sm leading-6 text-slate-600">Lakukan transfer dan unggah bukti pembayaran sebelum batas waktu.</p>
                            <p v-else class="text-sm leading-6 text-slate-600">Hubungi organizer jika membutuhkan bantuan terkait pesanan ini.</p>
                            <button type="button" class="mt-4 text-xs font-bold text-[var(--arc-blue)] underline underline-offset-4" :disabled="loading || paymentProcessing" @click="lookup()">Perbarui status</button>
                        </section>
                        <template v-if="canPay">
                            <section v-if="!detailsVisible || !order.payment || order.payment.status === 'failed'" class="arc-card mb-5">
                                <div v-for="method in order.payment_options.methods" :key="method.id" class="flex items-center justify-between gap-3"><div class="flex items-center gap-3 text-sm font-semibold"><PublicIcon name="bank" class="h-6 w-6 shrink-0 text-[var(--arc-blue)]" /><span>{{ method.label }}</span></div><button type="button" :disabled="paymentProcessing" class="arc-button min-h-9 px-6 text-xs" @click="selectManualTransfer">{{ paymentProcessing ? 'Memproses...' : 'Pilih' }}</button></div>
                            </section>
                            <template v-else>
                                <section class="arc-card mb-4">
                                    <h2 class="arc-badge mb-4 bg-[var(--arc-blue)] text-white">Rekening</h2>
                                    <div v-if="bankAccounts.length" class="space-y-4"><div v-for="(account, index) in bankAccounts" :key="index" class="text-sm"><p class="font-bold">{{ account.bank_name }}</p><div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1"><span class="select-all font-bold tracking-wide">{{ account.account_number }}</span><span class="text-slate-300" aria-hidden="true">|</span><span>{{ account.account_holder }}</span></div></div></div>
                                    <p v-else class="text-sm leading-6">Rekening tujuan belum dikonfigurasi. Hubungi organizer untuk memperoleh rekening resmi sebelum melakukan transfer.</p>
                                </section>
                                <section class="arc-card mb-4">
                                    <p class="text-center text-xs">Lakukan Transfer Sebanyak</p><p class="my-4 text-center text-lg font-bold text-[var(--arc-blue)]">{{ money(order.total_amount) }}</p>
                                    <p class="text-xs leading-5">Transfer ke rekening resmi penyelenggara, lalu unggah bukti pembayaran dalam bentuk JPG, PNG, atau PDF (maksimal 5 MB).</p>
                                    <form class="mt-4 space-y-3" @submit.prevent="uploadProof">
                                        <label for="payment-proof" class="flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-blue-300 bg-blue-50 px-3 py-3 text-center text-xs font-semibold text-[var(--arc-blue)] focus-within:outline focus-within:outline-2 focus-within:outline-blue-700" :class="paymentProcessing ? 'pointer-events-none opacity-50' : ''"><PublicIcon name="upload" class="h-4 w-4 shrink-0" /><span>{{ proof ? proof.name : 'Pilih bukti pembayaran' }}</span><input id="payment-proof" ref="proofInput" type="file" accept="image/jpeg,image/png,application/pdf" required :disabled="paymentProcessing" class="sr-only" :aria-invalid="Boolean(proofError)" aria-describedby="proof-help" @change="chooseProof" /></label>
                                        <p id="proof-help" class="text-xs" :class="proofError ? 'text-rose-700' : 'text-slate-500'">{{ proofError || 'JPG, PNG, atau PDF · Maksimal 5 MB' }}</p>
                                        <button type="submit" :disabled="paymentProcessing || !proof" class="arc-button w-full rounded-xl disabled:bg-slate-400 disabled:opacity-100">{{ paymentProcessing ? 'Mengunggah...' : 'Upload bukti pembayaran' }}</button>
                                    </form>
                                    <p v-if="notice" role="status" class="mt-4 rounded-lg bg-emerald-50 p-3 text-xs leading-5 text-emerald-800">{{ notice }}</p>
                                </section>
                            </template>
                            <section class="arc-card mt-6 text-sm">
                                <h2 class="arc-badge mb-4 bg-[var(--arc-blue)] text-white">Note</h2>
                                <p class="font-medium">Pembayaran event ini tersedia melalui transfer bank dan diverifikasi oleh organizer.</p>
                                <p class="mt-4 text-xs text-slate-500">Nomor order</p><p class="mt-1 break-all text-xs font-semibold">{{ order.order_number }}</p>
                                <p v-if="order.expires_at" class="mt-3 text-xs leading-5">Batas pembayaran: <strong>{{ dateTime(order.expires_at) }}</strong></p>
                                <a v-if="order.event.contact_email" :href="'mailto:' + order.event.contact_email" class="mt-3 block text-xs text-[var(--arc-blue)] underline">{{ order.event.contact_email }}</a>
                                <a v-if="order.event.contact_phone" :href="'tel:' + order.event.contact_phone" class="mt-2 block text-xs text-[var(--arc-blue)] underline">{{ order.event.contact_phone }}</a>
                            </section>
                        </template>
                        <Link v-if="isPayment" :href="route('public.orders.status')" class="mt-6 block text-center text-sm font-semibold text-white underline underline-offset-4">Cek status order</Link>
                        <Link :href="route('public.events.index')" class="mt-5 block text-center text-xs text-blue-100">Kembali ke daftar event</Link>
                    </template>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>