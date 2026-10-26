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
const tickets = computed(() => order.value?.tickets || []);
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
    <PublicLayout :catalog="true">
        <main class="min-h-[calc(100vh-4rem)] bg-white">
            <div class="mx-auto max-w-6xl px-5 pb-16 pt-8 sm:px-8 md:pt-10">
                <div class="mb-8 max-w-2xl">
                    <p class="type-caption-eyebrow text-[var(--brand-primary)]">{{ isPayment ? 'Pembayaran' : 'Pesanan' }}</p>
                    <h1 class="type-title-lg mt-2 text-2xl text-[#101014]">{{ isPayment ? (detailsVisible ? 'Selesaikan pembayaran' : 'Pilih metode pembayaran') : 'Cek status order' }}</h1>
                    <p class="type-body-md mt-3 text-[#64748b]">{{ isPayment ? 'Unggah bukti pembayaran setelah transfer agar organizer dapat memverifikasi pesanan.' : 'Masukkan nomor order dan email pembeli untuk melihat status pesanan.' }}</p>
                </div>

                <div class="grid w-full min-w-0 items-start gap-8" :class="isPayment && order ? 'lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]' : 'mx-auto max-w-4xl'">
                    <section v-if="isPayment && order" class="min-w-0 lg:sticky lg:top-24">
                        <div class="overflow-hidden rounded-lg border border-[#e2e8f0] bg-[#f8fafc]">
                            <EventPoster :event="order.event" :cover="true" />
                        </div>
                        <div class="mt-5 border-b border-[#e2e8f0] pb-5">
                            <p class="type-caption-eyebrow text-[#64748b]">Event</p>
                            <h2 class="type-title-lg mt-2 text-[#101014]">{{ order.event.title }}</h2>
                            <p class="type-body-sm mt-2 text-[#64748b]">Selesaikan pembayaran untuk menerima e-ticket.</p>
                        </div>
                    </section>

                    <section class="w-full min-w-0" :class="isPayment && order ? '' : 'order-lookup mx-auto'">
                        <section v-if="!isPayment || (!loading && !order)" class="mb-6 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-8">
                            <h2 class="type-title-md mb-5 text-[#101014]">Cari pesanan</h2>
                            <form class="space-y-5" @submit.prevent="lookup()">
                                <div><label for="order-number" class="type-body-sm-strong mb-2 block text-[#101014]">Nomor order</label><input id="order-number" v-model="form.order_number" required maxlength="50" class="arc-input" placeholder="ORD-..." /></div>
                                <div><label for="order-email" class="type-body-sm-strong mb-2 block text-[#101014]">Email pembeli</label><input id="order-email" v-model="form.customer_email" required type="email" maxlength="255" autocomplete="email" class="arc-input" /></div>
                                <button type="submit" :disabled="loading || paymentProcessing" class="arc-button w-full">{{ loading ? 'Mengecek...' : 'Cek status' }}</button>
                            </form>
                        </section>

                        <p v-if="loading" role="status" class="type-body-sm mb-5 text-[#64748b]">Memuat informasi pembayaran...</p>
                        <p v-if="error" role="alert" class="type-body-sm mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">{{ error }}</p>

                        <template v-if="order">
                            <section v-if="!isPayment || !canPay || order.payment?.proof_uploaded_at" class="mb-5 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-7">
                                <div class="mb-5 flex flex-wrap items-start justify-between gap-3"><div><p class="type-caption-eyebrow text-[#64748b]">Ringkasan pesanan</p><h2 class="type-title-md mt-2 text-[#101014]">{{ order.event.title }}</h2></div><span class="type-caption-strong shrink-0 rounded-full px-3 py-1.5" :class="statusKey === 'paid' ? 'bg-emerald-100 text-emerald-800' : statusKey === 'pending' ? 'bg-slate-100 text-[var(--brand-primary)]' : 'bg-rose-100 text-rose-800'">{{ paymentLabel(statusKey) }}</span></div>
                                <p class="type-caption-md text-[#64748b]">Nomor order</p><p class="type-body-sm-strong mt-1 break-all text-[#101014]">{{ order.order_number }}</p>
                                <div class="my-5 flex items-center justify-between gap-3 border-y border-[#e2e8f0] py-5"><span class="type-body-sm text-[#64748b]">Total pembayaran</span><strong class="type-title-md text-[var(--brand-primary)]">{{ money(order.total_amount) }}</strong></div>
                                <p v-if="statusKey === 'paid'" class="type-body-sm-relaxed text-emerald-800">Pembayaran sudah diverifikasi. E-ticket masuk antrean pengiriman ke email pembeli.</p>
                                <p v-else-if="statusKey === 'pending' && order.payment?.proof_uploaded_at" class="type-body-sm-relaxed text-[var(--brand-primary)]">Bukti pembayaran diterima. Menunggu verifikasi organizer.</p>
                                <p v-else-if="statusKey === 'pending' && deadlinePassed" class="type-body-sm-relaxed text-rose-700">Batas waktu pembayaran telah berakhir. Perbarui status untuk melihat kondisi pesanan terbaru.</p>
                                <p v-else-if="statusKey === 'pending'" class="type-body-sm-relaxed text-[#64748b]">Lakukan transfer dan unggah bukti pembayaran sebelum batas waktu.</p>
                                <p v-else class="type-body-sm-relaxed text-[#64748b]">Hubungi organizer jika membutuhkan bantuan terkait pesanan ini.</p>
                                <button type="button" class="type-body-sm-strong mt-5 text-[var(--brand-primary)] underline underline-offset-4" :disabled="loading || paymentProcessing" @click="lookup()">Perbarui status</button>
                            </section>

                            <section v-if="statusKey === 'paid' && tickets.length" class="mb-5 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-7">
                                <div class="mb-5 flex items-end justify-between gap-3">
                                    <div><h2 class="type-title-md text-[#101014]">E-ticket kamu</h2><p class="type-body-sm mt-1 text-[#64748b]">Tunjukkan QR ini saat check-in di lokasi event.</p></div>
                                    <span class="type-caption-strong text-[var(--brand-primary)]">{{ tickets.length }} tiket</span>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <article v-for="ticket in tickets" :key="ticket.ticket_number" class="rounded-lg border border-[#e2e8f0] bg-[#f8fafc] p-4">
                                        <div class="flex items-start justify-between gap-3"><div><p class="type-body-sm-strong text-[#101014]">{{ ticket.attendee_name }}</p><p class="type-caption-md mt-1 text-[#64748b]">{{ ticket.ticket_number }}</p></div><span class="type-caption-strong rounded-md bg-white px-2 py-1 text-[var(--brand-primary)]">{{ ticket.status === 'used' ? 'Sudah dipakai' : 'Aktif' }}</span></div>
                                        <div v-if="ticket.qr_svg" class="mx-auto mt-4 flex w-fit bg-white p-3" v-html="ticket.qr_svg"></div>
                                        <p v-else class="type-caption-md mt-4 text-rose-700">QR belum tersedia. Hubungi organizer.</p>
                                    </article>
                                </div>
                            </section>

                            <template v-if="canPay">
                                <section v-if="!detailsVisible || !order.payment || order.payment.status === 'failed'" class="mb-5 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-7">
                                    <div v-for="method in order.payment_options.methods" :key="method.id" class="flex items-center justify-between gap-4"><div class="flex items-center gap-3"><PublicIcon name="bank" class="h-5 w-5 shrink-0 text-[var(--brand-primary)]" /><span class="type-body-sm-strong text-[#101014]">{{ method.label }}</span></div><button type="button" :disabled="paymentProcessing" class="arc-button min-h-9 px-6 text-xs" @click="selectManualTransfer">{{ paymentProcessing ? 'Memproses...' : 'Pilih' }}</button></div>
                                </section>
                                <template v-else>
                                    <section class="mb-5 rounded-lg border border-[#e2e8f0] bg-white p-6 sm:p-7">
                                        <p class="type-caption-eyebrow mb-4 text-[#64748b]">Rekening tujuan</p>
                                        <div v-if="bankAccounts.length" class="space-y-4"><div v-for="(account, index) in bankAccounts" :key="index" class="type-body-sm"><p class="type-body-sm-strong text-[#101014]">{{ account.bank_name }}</p><div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[#64748b]"><span class="select-all type-body-sm-strong text-[#101014]">{{ account.account_number }}</span><span class="text-[#e2e8f0]" aria-hidden="true">|</span><span>{{ account.account_holder }}</span></div></div></div>
                                        <p v-else class="type-body-sm-relaxed text-[#64748b]">Rekening tujuan belum dikonfigurasi. Hubungi organizer untuk memperoleh rekening resmi sebelum melakukan transfer.</p>
                                    </section>
                                    <section class="mb-5 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] p-6 sm:p-7">
                                        <p class="type-caption-eyebrow text-center text-[#64748b]">Jumlah transfer</p><p class="type-title-lg my-4 text-center text-[var(--brand-primary)]">{{ money(order.total_amount) }}</p><p class="type-body-sm-relaxed text-[#64748b]">Transfer ke rekening resmi penyelenggara, lalu unggah bukti pembayaran dalam bentuk JPG, PNG, atau PDF (maksimal 5 MB).</p>
                                        <form class="mt-5 space-y-3" @submit.prevent="uploadProof">
                                            <label for="payment-proof" class="type-body-sm-strong flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-[var(--brand-primary)] bg-white px-3 py-3 text-center text-[var(--brand-primary)] focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--brand-primary)]" :class="paymentProcessing ? 'pointer-events-none opacity-50' : ''"><PublicIcon name="upload" class="h-4 w-4 shrink-0" /><span>{{ proof ? proof.name : 'Pilih bukti pembayaran' }}</span><input id="payment-proof" ref="proofInput" type="file" accept="image/jpeg,image/png,application/pdf" required :disabled="paymentProcessing" class="sr-only" :aria-invalid="Boolean(proofError)" aria-describedby="proof-help" @change="chooseProof" /></label>
                                            <p id="proof-help" class="type-caption-md" :class="proofError ? 'text-rose-700' : 'text-[#64748b]'">{{ proofError || 'JPG, PNG, atau PDF · Maksimal 5 MB' }}</p>
                                            <button type="submit" :disabled="paymentProcessing || !proof" class="arc-button w-full disabled:bg-[#cbd5e1] disabled:text-[#144ba3]">{{ paymentProcessing ? 'Mengunggah...' : 'Upload bukti pembayaran' }}</button>
                                        </form>
                                        <p v-if="notice" role="status" class="type-caption-md mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">{{ notice }}</p>
                                    </section>
                                </template>
                                <section class="rounded-lg border border-[#e2e8f0] bg-white p-6 text-[#101014] sm:p-7">
                                    <p class="type-caption-eyebrow mb-3 text-[#64748b]">Catatan pembayaran</p><p class="type-body-sm-relaxed">Pembayaran event ini tersedia melalui transfer bank dan diverifikasi oleh organizer.</p><p class="type-caption-md mt-5 text-[#64748b]">Nomor order</p><p class="type-body-sm-strong mt-1 break-all">{{ order.order_number }}</p><p v-if="order.expires_at" class="type-body-sm mt-4 text-[#64748b]">Batas pembayaran: <strong class="text-[#101014]">{{ dateTime(order.expires_at) }}</strong></p><a v-if="order.event.contact_email" :href="'mailto:' + order.event.contact_email" class="type-body-sm mt-3 block text-[var(--brand-primary)] underline">{{ order.event.contact_email }}</a><a v-if="order.event.contact_phone" :href="'tel:' + order.event.contact_phone" class="type-body-sm mt-2 block text-[var(--brand-primary)] underline">{{ order.event.contact_phone }}</a>
                                </section>
                            </template>
                            <Link v-if="isPayment" :href="route('public.orders.status')" class="type-body-sm-strong mt-6 block text-center text-[var(--brand-primary)] underline underline-offset-4">Cek status order</Link>
                            <Link :href="route('public.events.index')" class="type-body-sm mt-5 block text-center text-[#64748b] hover:text-[var(--brand-primary)]">Kembali ke daftar event</Link>
                        </template>
                    </section>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>