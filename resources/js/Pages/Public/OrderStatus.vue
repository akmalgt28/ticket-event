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

async function lookup() {
    loading.value = true;
    error.value = '';
    order.value = null;

    try {
        const response = await fetch('/api/orders/status', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(form),
        });
        const body = await response.json();

        if (!response.ok) {
            error.value = body.message || Object.values(body.errors || {}).flat()[0] || 'Order tidak ditemukan.';
            return;
        }

        order.value = body.data;
    } catch {
        error.value = 'Koneksi gagal. Coba lagi.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Cek status order" />
    <PublicLayout>
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
            <Link :href="route('public.events.index')" class="text-sm font-bold text-slate-600 hover:text-amber-600">← Lihat event</Link>
            <div class="mt-8 rounded-[2rem] bg-slate-950 p-8 text-white shadow-xl sm:p-12">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-amber-300">Order publik</p>
                <h1 class="mt-4 text-4xl font-black">Cek status pembayaran.</h1>
                <p class="mt-4 text-slate-300">Masukkan nomor order dan email saat checkout.</p>
                <form class="mt-8 grid gap-4" @submit.prevent="lookup">
                    <label><span class="text-sm font-bold">Nomor order</span><input v-model="form.order_number" required class="mt-1 w-full rounded-xl border-0 text-slate-950" placeholder="Contoh: ORD-..." /></label>
                    <label><span class="text-sm font-bold">Email customer</span><input v-model="form.customer_email" required type="email" class="mt-1 w-full rounded-xl border-0 text-slate-950" /></label>
                    <p v-if="error" class="rounded-xl bg-rose-400/10 p-4 text-sm text-rose-200">{{ error }}</p>
                    <button type="submit" :disabled="loading" class="rounded-xl bg-amber-500 px-5 py-3 font-black text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">{{ loading ? 'Mengecek...' : 'Cek status' }}</button>
                </form>
                <div v-if="order" class="mt-8 rounded-2xl bg-white/10 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm text-slate-300">Nomor order</p><p class="mt-1 text-2xl font-black text-amber-300">{{ order.order_number }}</p></div><span class="rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-200">{{ paymentLabel(order.payment_status) }}</span></div>
                    <div class="mt-5 grid gap-4 border-t border-white/10 pt-5 sm:grid-cols-2"><div><p class="text-sm text-slate-300">Total</p><p class="mt-1 text-lg font-black">{{ money(order.total_amount) }}</p></div><div><p class="text-sm text-slate-300">Status order</p><p class="mt-1 text-lg font-black">{{ order.order_status }}</p></div></div>
                    <p v-if="order.payment_status === 'paid'" class="mt-5 rounded-xl bg-emerald-400/10 p-4 text-sm text-emerald-200">Pembayaran sudah diverifikasi. E-ticket dapat diproses organizer.</p>
                    <p v-else-if="order.payment_status === 'failed' || order.payment_status === 'expired'" class="mt-5 rounded-xl bg-rose-400/10 p-4 text-sm text-rose-200">Pembayaran tidak dapat diproses. Hubungi organizer jika membutuhkan bantuan.</p>
                    <p v-else class="mt-5 rounded-xl bg-amber-400/10 p-4 text-sm text-amber-200">Pembayaran masih menunggu verifikasi organizer.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
