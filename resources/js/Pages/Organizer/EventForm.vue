<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ event: { type: Object, default: null } });
const editing = Boolean(props.event);
const form = useForm({
    title: props.event?.title || '', description: props.event?.description || '', banner: null,
    start_at: props.event?.start_at || '', end_at: props.event?.end_at || '',
    location_name: props.event?.location_name || '', location_address: props.event?.location_address || '',
    contact_email: props.event?.contact_email || '', contact_phone: props.event?.contact_phone || '',
});

function submit() {
    const options = { forceFormData: true, preserveScroll: true };
    if (editing) form.patch(route('organizer.events.update', props.event.id), options);
    else form.post(route('organizer.events.store'), options);
}
</script>

<template>
    <Head :title="editing ? 'Edit Event' : 'Buat Event'" />
    <AuthenticatedLayout>
        <template #header><div><p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-600">Operations</p><h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">{{ editing ? 'Edit event' : 'Buat event' }}</h2></div></template>
        <div class="min-h-[calc(100vh-9rem)] bg-[#f7f3ec] px-4 py-8 sm:px-6 lg:px-8"><div class="mx-auto max-w-4xl"><form class="space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2"><InputLabel value="Judul event" /><input v-model="form.title" type="text" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.title" /></div>
                <div class="sm:col-span-2"><InputLabel value="Deskripsi" /><textarea v-model="form.description" rows="5" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.description" /></div><div class="sm:col-span-2"><InputLabel value="Banner event" /><input type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" @input="form.banner = $event.target.files[0]" /><InputError class="mt-2" :message="form.errors.banner" /></div>
                <div><InputLabel value="Mulai" /><input v-model="form.start_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.start_at" /></div>
                <div><InputLabel value="Selesai" /><input v-model="form.end_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.end_at" /></div>
                <div><InputLabel value="Nama lokasi" /><input v-model="form.location_name" type="text" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.location_name" /></div>
                <div><InputLabel value="Telepon kontak" /><input v-model="form.contact_phone" type="text" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.contact_phone" /></div>
                <div class="sm:col-span-2"><InputLabel value="Alamat lokasi" /><textarea v-model="form.location_address" rows="3" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.location_address" /></div>
                <div class="sm:col-span-2"><InputLabel value="Email kontak" /><input v-model="form.contact_email" type="email" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400" /><InputError class="mt-2" :message="form.errors.contact_email" /></div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6"><Link :href="route('organizer.events.index')" class="rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</Link><button type="submit" :disabled="form.processing" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-600 disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan event' }}</button></div>
        </form></div></div>
    </AuthenticatedLayout>
</template>

