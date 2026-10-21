<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

const token = ref('');
const mode = ref('token');
const loading = ref(false);
const error = ref('');
const result = ref(null);
const video = ref(null);
const cameraActive = ref(false);
const cameraError = ref('');
const supportsCamera = typeof window !== 'undefined' && 'BarcodeDetector' in window;
let cameraStream = null;
let barcodeDetector = null;
let scanFrameId = null;

function csrfToken() {
    const value = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1];
    return value ? decodeURIComponent(value) : '';
}

function stopCamera() {
    cameraActive.value = false;
    if (scanFrameId) {
        cancelAnimationFrame(scanFrameId);
        scanFrameId = null;
    }
    cameraStream?.getTracks().forEach((track) => track.stop());
    cameraStream = null;
    if (video.value) {
        video.value.srcObject = null;
    }
}

async function scanFrame() {
    if (!cameraActive.value || !video.value || !barcodeDetector) {
        return;
    }

    try {
        const codes = await barcodeDetector.detect(video.value);
        if (codes[0]?.rawValue) {
            token.value = codes[0].rawValue;
            stopCamera();
            mode.value = 'token';
            await checkIn();
            return;
        }
    } catch {
        cameraError.value = 'QR tidak dapat dibaca dari kamera.';
    }

    scanFrameId = requestAnimationFrame(scanFrame);
}

async function startCamera() {
    if (!supportsCamera || cameraActive.value) {
        return;
    }

    cameraError.value = '';
    try {
        barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
        video.value.srcObject = cameraStream;
        await video.value.play();
        cameraActive.value = true;
        scanFrame();
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
    <Head title="Check-in staff" />
    <AuthenticatedLayout>
        <template #header>
            <div><p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-600">Event gate</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Check-in peserta</h2></div>
        </template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f7f3ec] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-sm leading-6 text-slate-500">Gunakan scanner QR atau cari ticket number secara manual sebagai fallback.</p><div class="mt-4 flex gap-2"><button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="mode === 'token' ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700'" @click="mode = 'token'">Scan token</button><button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="mode === 'ticket_number' ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700'" @click="mode = 'ticket_number'">Cari ticket number</button></div><div v-if="supportsCamera" class="mt-4 rounded-2xl bg-slate-50 p-4"><button type="button" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-400" @click="cameraActive ? stopCamera() : startCamera()">{{ cameraActive ? 'Tutup kamera' : 'Scan dengan kamera' }}</button><video v-show="cameraActive" ref="video" class="mt-4 aspect-video w-full rounded-xl bg-slate-950 object-cover" muted playsinline></video><p v-if="cameraError" class="mt-3 text-sm font-semibold text-rose-700">{{ cameraError }}</p></div>
                    <form class="mt-6 space-y-4" @submit.prevent="checkIn">
                        <label><span class="text-sm font-bold text-slate-800">{{ mode === 'token' ? 'Token QR ticket' : 'Ticket number' }}</span><input v-model="token" autofocus required autocomplete="off" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-4 font-mono text-sm focus:border-amber-400 focus:ring-amber-400" :placeholder="mode === 'token' ? 'Tempel atau scan token QR' : 'Contoh: TKT-12345678'" /></label>
                        <p v-if="error" class="rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ error }}</p>
                        <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-slate-950 px-5 py-4 font-black text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">{{ loading ? 'Memvalidasi...' : 'Validasi check-in' }}</button>
                    </form>
                </section>
                <section v-if="result" class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm sm:p-8"><p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-700">Check-in berhasil</p><h1 class="mt-3 text-3xl font-black text-emerald-950">{{ result.ticket_number }}</h1><p class="mt-2 text-sm text-emerald-800">Peserta: {{ result.attendee?.name || '-' }}</p><p class="mt-1 text-sm text-emerald-800">Event: {{ result.event?.title || '-' }}</p><p class="mt-1 text-sm text-emerald-800">Status ticket: {{ result.status }}</p><p class="mt-1 text-sm text-emerald-800">Ticket sudah tercatat digunakan.</p></section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>