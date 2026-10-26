<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { onBeforeUnmount, ref } from 'vue';

const token = ref('');
const mode = ref('token');
const loading = ref(false);
const error = ref('');
const result = ref(null);
const video = ref(null);
const cameraActive = ref(false);
const cameraError = ref('');
const supportsCamera = typeof navigator !== 'undefined' && Boolean(navigator.mediaDevices?.getUserMedia);
let scannerControls = null;
const scanner = new BrowserMultiFormatReader();

function csrfToken() {
    const value = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1];
    return value ? decodeURIComponent(value) : '';
}

function stopCamera() {
    scannerControls?.stop();
    scannerControls = null;
    cameraActive.value = false;
    if (video.value) {
        video.value.srcObject = null;
    }
}

async function startCamera() {
    if (!supportsCamera || cameraActive.value) {
        return;
    }

    cameraError.value = '';
    result.value = null;
    try {
        cameraActive.value = true;
        scannerControls = await scanner.decodeFromVideoDevice(undefined, video.value, async (scanResult) => {
            if (!scanResult || loading.value) {
                return;
            }

            token.value = scanResult.getText();
            stopCamera();
            mode.value = 'token';
            await checkIn();
        });
    } catch {
        stopCamera();
        cameraError.value = 'Kamera tidak tersedia atau izin belum diberikan.';
    }
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
            body: JSON.stringify(mode.value === 'token' ? { token: token.value.trim() } : { ticket_number: token.value.trim() }),
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
onBeforeUnmount(stopCamera);
</script>

<template>
    <Head title="Scanner ticket" />
    <AuthenticatedLayout>
        <template #header>
            <div><p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Event gate</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Scanner ticket</h2></div>
        </template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8">
                    <p class="text-sm leading-6 text-slate-500">Gunakan scanner QR atau cari ticket number secara manual sebagai fallback.</p><div class="mt-4 flex gap-2"><button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="mode === 'token' ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700'" @click="mode = 'token'">Scan token</button><button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="mode === 'ticket_number' ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700'" @click="mode = 'ticket_number'">Cari ticket number</button></div><div class="mt-4 rounded-2xl bg-slate-50 p-4"><button type="button" class="rounded-xl bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white hover:bg-[var(--brand-primary-dark)]" @click="cameraActive ? stopCamera() : startCamera()">{{ cameraActive ? 'Tutup kamera' : 'Buka kamera' }}</button><p v-if="!supportsCamera" class="mt-3 text-sm text-slate-600">Kamera membutuhkan izin browser dan koneksi HTTPS atau localhost.</p><video v-show="cameraActive" ref="video" class="mt-4 aspect-video w-full rounded-xl bg-slate-950 object-cover" muted playsinline></video><p v-if="cameraError" class="mt-3 text-sm font-semibold text-rose-700">{{ cameraError }}</p></div>
                    <form class="mt-6 space-y-4" @submit.prevent="checkIn">
                        <label><span class="text-sm font-bold text-slate-800">{{ mode === 'token' ? 'Token QR ticket' : 'Ticket number' }}</span><input v-model="token" autofocus required autocomplete="off" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-4 font-mono text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" :placeholder="mode === 'token' ? 'Tempel atau scan token QR' : 'Contoh: TKT-12345678'" /></label>
                        <p v-if="error" class="rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ error }}</p>
                        <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-slate-950 px-5 py-4 font-black text-white hover:bg-[var(--brand-primary)] disabled:cursor-not-allowed disabled:opacity-50">{{ loading ? 'Memvalidasi...' : 'Validasi check-in' }}</button>
                    </form>
                </section>
                <section v-if="result" class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8"><p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-700">Check-in berhasil</p><h1 class="mt-3 text-3xl font-black text-emerald-950">{{ result.ticket_number }}</h1><p class="mt-2 text-sm text-emerald-800">Peserta: {{ result.attendee?.name || '-' }}</p><p class="mt-1 text-sm text-emerald-800">Event: {{ result.event?.title || '-' }}</p><p class="mt-1 text-sm text-emerald-800">Status ticket: {{ result.status }}</p><p class="mt-1 text-sm text-emerald-800">Ticket sudah tercatat digunakan.</p></section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>