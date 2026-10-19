<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    participants: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filterForm = useForm({ search: props.filters.search });

function search() {
    filterForm.get(route('organizer.participants.index'), { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Peserta Organizer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-amber-600">Operations</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Daftar peserta</h2>
                </div>
                <a :href="route('organizer.participants.export', { search: filters.search })" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-600">Download CSV</a>
            </div>
        </template>

        <div class="min-h-[calc(100vh-9rem)] bg-[#f7f3ec] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="search">
                        <input v-model="filterForm.search" type="search" placeholder="Cari nama, email, nomor order..." class="min-w-0 flex-1 rounded-2xl border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-amber-400 focus:ring-amber-400" />
                        <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-amber-600">Cari peserta</button>
                    </form>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 font-bold">Nama</th>
                                    <th class="px-5 py-4 font-bold">Kontak</th>
                                    <th class="px-5 py-4 font-bold">Nomor order</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="participant in participants.data" :key="participant.id" class="transition hover:bg-amber-50/40">
                                    <td class="px-5 py-4 font-bold text-slate-900">{{ participant.name }}</td>
                                    <td class="px-5 py-4"><p class="text-slate-700">{{ participant.email }}</p><p class="text-xs text-slate-500">{{ participant.phone }}</p></td>
                                    <td class="px-5 py-4 font-semibold text-slate-600">{{ participant.order_number || '-' }}</td>
                                </tr>
                                <tr v-if="!participants.data.length"><td colspan="3" class="px-5 py-12 text-center text-slate-500">Peserta tidak ditemukan.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                        <span>Halaman {{ participants.current_page }} dari {{ participants.last_page }}</span>
                        <div class="flex gap-2">
                            <Link v-if="participants.prev_page_url" :href="participants.prev_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Sebelumnya</Link>
                            <Link v-if="participants.next_page_url" :href="participants.next_page_url" class="rounded-xl border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Berikutnya</Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
