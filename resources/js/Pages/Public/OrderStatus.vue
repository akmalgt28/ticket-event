<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { reactive, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const form = reactive({ order_number: '', customer_email: '' });
const order = ref(null);
const loading = ref(false);
const error = ref('');
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
const paymentLabel = (status) => ({ pending: 'Menunggu pembayaran', paid: 'Pembayaran berhasil', failed: 'Pembayaran gagal', expired: 'Pembayaran kedaluwarsa', refunded: 'Pembayaran dikembalikan' }[status] || status);
const dateTime = (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

async function lookup() {
    loading.value = true;
    error.value = '';
    order.value = null;
    try {
        const response = await fetch('/api/orders/status', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(form) });
        const body = await response.json();
        if (!response.ok) { error.value = body.message || Object.values(body.errors || {}).flat()[0] || 'Order tidak ditemukan.'; return; }
        order.value = body.data;
    } catch { error.value = 'Koneksi gagal. Coba lagi.'; } finally { loading.value = false; }
}
</script>

<template>
    <Head title="Cek status order" />
    <PublicLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <Link :href="route('public.events.index')" class="text-sm font-bold text-slate-500 hover:text-[var(--brand-primary)]">← Lihat event</Link>
            <div class="mt-6 grid overflow-hidden border border-slate-200 bg-white lg:grid-cols-[0.8fr_1.2fr]">
                <div class="bg-slate-950 p-7 text-white sm:p-10"><p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--brand-primary)]">Order publik</p><h1 class="mt-4 text-4xl font-black tracking-tight">Cek status pembayaran.</h1><p class="mt-4 text-sm leading-6 text-slate-300">Gunakan nomor order dan email checkout untuk melihat status terbaru.</p></div>
                <div class="p-7 sm:p-10">
                    <form class="grid gap-4" @submit.prevent="lookup">
                        <label><span class="text-sm font-bold">Nomor order</span><input v-model="form.order_number" required class="mt-1 w-full border-slate-300" placeholder="Contoh: ORD-..." /></label>
                        <label><span class="text-sm font-bold">Email customer</span><input v-model="form.customer_email" required type="email" class="mt-1 w-full border-slate-300" /></label>
                        <p v-if="error" class="border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</p>
                        <button type="submit" :disabled="loading" class="bg-[var(--brand-primary)] px-5 py-3 font-black text-white hover:bg-[var(--brand-primary-dark)] disabled:cursor-not-allowed disabled:opacity-50">{{ loading ? 'Mengecek...' : 'Cek status' }}</button>
                    </form>
                    <div v-if="order" class="mt-8 border-t border-slate-200 pt-6">
                        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wider text-slate-400">Nomor order</p><p class="mt-1 text-2xl font-black text-slate-950">{{ order.order_number }}</p></div><span class="border border-[var(--brand-primary)] px-3 py-1 text-xs font-bold text-[var(--brand-primary)]">{{ paymentLabel(order.payment_status) }}</span></div>
                        <div class="mt-5 grid gap-4 border-y border-slate-200 py-5 sm:grid-cols-2"><div><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-lg font-black">{{ money(order.total_amount) }}</p></div><div><p class="text-sm text-slate-500">Status order</p><p class="mt-1 text-lg font-black">{{ order.order_status }}</p></div><div v-if="order.expires_at && order.payment_status === 'pending'"><p class="text-sm text-slate-500">Batas pembayaran</p><p class="mt-1 text-lg font-black">{{ dateTime(order.expires_at) }}</p></div></div>
                        <p v-if="order.payment_status === 'paid'" class="border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">Pembayaran sudah diverifikasi. E-ticket dapat diproses organizer.</p>
                        <p v-else-if="order.payment_status === 'failed' || order.payment_status === 'expired'" class="border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">Pembayaran tidak dapat diproses. Hubungi organizer jika membutuhkan bantuan.</p>
                        <p v-else class="border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">Pembayaran masih menunggu verifikasi organizer.</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>