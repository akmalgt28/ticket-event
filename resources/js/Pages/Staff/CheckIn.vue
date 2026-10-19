<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const token = ref('');
const loading = ref(false);
const error = ref('');
const result = ref(null);

function csrfToken() {
    const value = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1];
    return value ? decodeURIComponent(value) : '';
}

async function checkIn() {
    loading.value = true;
    error.value = '';
    result.value = null;

    try {
        const response = await fetch(route('check-in.store'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ token: token.value.trim() }),
        });
        const body = await response.json();

        if (!response.ok) {
            error.value = body.errors?.token?.[0] || body.message || 'Check-in gagal.';
            return;
        }

        result.value = body.data;
        token.value = '';
    } catch {
        error.value = 'Koneksi gagal. Coba lagi.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Check-in staff" />
    <AuthenticatedLayout>
        <template #header>
            <div><p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-600">Event gate</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Check-in peserta</h2></div>
        </template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f7f3ec] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-sm leading-6 text-slate-500">Scan QR ticket dengan scanner perangkat, lalu masukkan hasil token ke kolom berikut.</p>
                    <form class="mt-6 space-y-4" @submit.prevent="checkIn">
                        <label><span class="text-sm font-bold text-slate-800">Token QR ticket</span><input v-model="token" autofocus required autocomplete="off" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-4 font-mono text-sm focus:border-amber-400 focus:ring-amber-400" placeholder="Tempel atau scan token QR" /></label>
                        <p v-if="error" class="rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ error }}</p>
                        <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-slate-950 px-5 py-4 font-black text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">{{ loading ? 'Memvalidasi...' : 'Validasi check-in' }}</button>
                    </form>
                </section>
                <section v-if="result" class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm sm:p-8"><p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-700">Check-in berhasil</p><h1 class="mt-3 text-3xl font-black text-emerald-950">{{ result.ticket_number }}</h1><p class="mt-2 text-sm text-emerald-800">Status ticket: {{ result.status }}</p><p class="mt-1 text-sm text-emerald-800">Ticket sudah tercatat digunakan.</p></section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>