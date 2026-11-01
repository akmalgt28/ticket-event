<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: { type: Object, required: true },
    roles: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const filterForm = useForm({ search: props.filters.search });
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: props.roles[0] || 'staff',
});

function search() {
    filterForm.get(route('organizer.users.index'), { preserveState: true, replace: true });
}

function createUser() {
    createForm.post(route('organizer.users.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name', 'email', 'password', 'password_confirmation'),
    });
}

function roleLabel(role) {
    return { owner: 'Owner', admin: 'Admin', staff: 'Staff' }[role] || role;
}
</script>

<template>
    <Head title="Pengguna Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.28em] text-[var(--brand-primary)]">Access control</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Pengguna admin</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">Tambah akun untuk owner, admin, atau staff tanpa membuka pendaftaran publik.</p>
            </div>
        </template>

        <div class="min-h-[calc(100vh-9rem)] bg-[#f4f7fb] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-6 xl:grid-cols-[360px_1fr]">
                <section class="h-fit border border-slate-200 bg-white p-6">
                    <div class="mb-6">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Akun baru</p>
                        <h3 class="mt-1 text-lg font-black text-slate-900">Tambah pengguna</h3>
                    </div>

                    <div v-if="createForm.hasErrors" role="alert" class="mb-5 border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                        <p v-for="(error, key) in createForm.errors" :key="key">{{ error }}</p>
                    </div>

                    <form class="space-y-4" @submit.prevent="createUser">
                        <div>
                            <label for="user-name" class="text-sm font-bold text-slate-700">Nama</label>
                            <input id="user-name" v-model="createForm.name" type="text" autocomplete="name" placeholder="Contoh: Budi Santoso" class="mt-1 w-full border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        </div>
                        <div>
                            <label for="user-email" class="text-sm font-bold text-slate-700">Email login</label>
                            <input id="user-email" v-model="createForm.email" type="email" autocomplete="email" placeholder="nama@contoh.com" class="mt-1 w-full border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        </div>
                        <div>
                            <label for="user-role" class="text-sm font-bold text-slate-700">Role</label>
                            <select id="user-role" v-model="createForm.role" class="mt-1 w-full border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                                <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Owner hanya dapat dibuat oleh owner.</p>
                        </div>
                        <div>
                            <label for="user-password" class="text-sm font-bold text-slate-700">Password</label>
                            <input id="user-password" v-model="createForm.password" type="password" autocomplete="new-password" placeholder="Minimal 8 karakter" class="mt-1 w-full border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        </div>
                        <div>
                            <label for="user-password-confirmation" class="text-sm font-bold text-slate-700">Ulangi password</label>
                            <input id="user-password-confirmation" v-model="createForm.password_confirmation" type="password" autocomplete="new-password" placeholder="Ulangi password" class="mt-1 w-full border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        </div>
                        <button type="submit" :disabled="createForm.processing" class="w-full bg-[var(--brand-primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-primary-dark)] disabled:cursor-not-allowed disabled:opacity-60">{{ createForm.processing ? 'Menyimpan...' : 'Tambah pengguna' }}</button>
                    </form>
                </section>

                <section class="overflow-hidden border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 p-5">
                        <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="search">
                            <input v-model="filterForm.search" type="search" placeholder="Cari nama, email, atau role..." class="min-w-0 flex-1 border-slate-200 px-3 py-2.5 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                            <button type="submit" class="bg-slate-950 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[var(--brand-primary)]">Cari</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr><th class="px-5 py-4 font-bold">Pengguna</th><th class="px-5 py-4 font-bold">Role</th><th class="px-5 py-4 font-bold">Bergabung</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="user in users.data" :key="user.id" class="transition hover:bg-slate-50/50">
                                    <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ user.name }}</p><p class="text-xs text-slate-500">{{ user.email }}</p></td>
                                    <td class="px-5 py-4"><span v-for="role in user.roles" :key="role" class="mr-1 inline-flex border border-blue-100 bg-blue-50 px-2 py-1 text-xs font-bold text-[var(--brand-primary)]">{{ roleLabel(role) }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID') : '-' }}</td>
                                </tr>
                                <tr v-if="!users.data.length"><td colspan="3" class="px-5 py-12 text-center text-slate-500">Belum ada pengguna.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-500"><span>Halaman {{ users.current_page }} dari {{ users.last_page }}</span><div class="flex gap-2"><Link v-if="users.prev_page_url" :href="users.prev_page_url" class="border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Sebelumnya</Link><Link v-if="users.next_page_url" :href="users.next_page_url" class="border border-slate-200 px-3 py-2 font-semibold hover:bg-slate-50">Berikutnya</Link></div></div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
