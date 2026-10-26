<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filterForm = useForm({
    search: props.filters.search,
    status: props.filters.status,
    payment_status: props.filters.payment_status,
});

const resendForm = useForm({});

const verificationForm = useForm({
    order_id: null,
    status: 'paid',
    provider_transaction_id: '',
});

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value));

const statusLabel = (status) => ({
    pending: 'Pending',
    paid: 'Paid',
    failed: 'Failed',
    expired: 'Expired',
    cancelled: 'Cancelled',
    refunded: 'Refunded',
}[status] || status);

function search() {
    filterForm.get(route('organizer.orders.index'), { preserveState: true, replace: true });
}

function resendEticket(orderId) {
    resendForm.post(route('organizer.orders.resend-eticket', orderId), { preserveScroll: true });
}

function verifyPayment(orderId) {
    verificationForm.order_id = orderId;
    verificationForm.post(route('payments.verify'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Order Organizer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Operations</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">{{ filters.payment_status === 'pending' ? 'Verifikasi pembayaran' : 'Daftar order' }}</h2>
                </div>
                <a :href="route('organizer.participants.export')" class="text-sm font-bold text-slate-900 underline decoration-[var(--brand-primary)] decoration-2 underline-offset-4">Export peserta CSV</a>
            </div>
        </template>

        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div v-if="verificationForm.hasErrors" role="alert" class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">
                    <p v-for="(error, key) in verificationForm.errors" :key="key">{{ error }}</p>
                </div>
                <section class="rounded-3xl border border-slate-200/80 bg-white p-5">
                    <form class="grid gap-3 md:grid-cols-[1fr_180px_180px_auto]" @submit.prevent="search">
                        <input v-model="filterForm.search" type="search" placeholder="Cari nomor, nama, email..." class="rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        <select v-model="filterForm.status" class="rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                            <option value="">Semua order</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select v-model="filterForm.payment_status" class="rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                            <option value="">Semua payment</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-primary)]">Cari</button>
                    </form>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 font-bold">Order</th>
                                    <th class="px-5 py-4 font-bold">Customer</th>
                                    <th class="px-5 py-4 font-bold">Event</th>
                                    <th class="px-5 py-4 font-bold">Total</th>
                                    <th class="px-5 py-4 font-bold">Status</th>
                                    <th class="px-5 py-4 font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="order in orders.data" :key="order.id" class="transition hover:bg-slate-50/40">
                                    <td class="whitespace-nowrap px-5 py-4 font-black text-slate-900">{{ order.order_number }}</td>
                                    <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ order.customer_name }}</p><p class="text-xs text-slate-500">{{ order.customer_email }}</p></td>
                                    <td class="px-5 py-4 text-slate-600">{{ order.event || '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-800">{{ formatCurrency(order.total_amount) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ statusLabel(order.payment_status) }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <a v-if="order.payment?.proof_url" :href="order.payment.proof_url" target="_blank" rel="noopener noreferrer" class="mr-3 text-xs font-bold text-[var(--brand-primary)] underline">Lihat bukti transfer</a>
                                        <button v-if="order.payment_status === 'pending' && order.payment" type="button" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700" :disabled="verificationForm.processing" @click="verifyPayment(order.id)">Verifikasi paid</button>
                                        <button v-else-if="order.payment_status === 'paid' && order.tickets_count > 0" type="button" class="rounded-xl bg-[var(--brand-primary)] px-3 py-2 text-xs font-bold text-white hover:bg-[var(--brand-primary-dark)]" :disabled="resendForm.processing" @click="resendEticket(order.id)">{{ resendForm.processing ? 'Mengirim...' : 'Kirim ulang e-ticket' }}</button>
                                        <span v-else class="text-xs text-slate-400">Tidak ada aksi</span>
                                    </td>
                                </tr>
                                <tr v-if="!orders.data.length"><td colspan="6" class="px-5 py-12 text-center text-slate-500">Order tidak ditemukan.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                        <span>Halaman {{ orders.current_page }} dari {{ orders.last_page }}</span>
                        <div class="flex gap-2">
                            <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Sebelumnya</Link>
                            <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Berikutnya</Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

