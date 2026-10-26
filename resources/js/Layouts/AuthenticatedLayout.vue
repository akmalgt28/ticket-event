<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminIcon from '@/Components/AdminIcon.vue';

const showingNavigationDropdown = ref(false);

const navigationGroups = [
    {
        label: 'Workspace',
        items: [
            { label: 'Dashboard', href: 'dashboard', icon: 'grid', match: 'dashboard' },
            { label: 'Orders', href: 'organizer.orders.index', icon: 'receipt', match: 'organizer.orders.*' },
            { label: 'Pembayaran', href: 'organizer.payments.index', icon: 'wallet', match: 'organizer.payments.*' },
        ],
    },
    {
        label: 'Kelola event',
        items: [
            { label: 'Events', href: 'organizer.events.index', icon: 'calendar', match: 'organizer.events.*' },
            { label: 'Peserta', href: 'organizer.participants.index', icon: 'users', match: 'organizer.participants.*' },
            { label: 'Scanner', href: 'check-in.index', icon: 'scan', match: 'check-in.index' },
            { label: 'Riwayat check-in', href: 'organizer.check-ins.index', icon: 'receipt', match: 'organizer.check-ins.*' },
        ],
    },
];

function isActive(item) {
    const isPaymentQueue = route().current('organizer.orders.index')
        && new URL(window.location.href).searchParams.get('payment_status') === 'pending';

    if (item.href === 'organizer.payments.index') {
        return route().current('organizer.payments.*') || isPaymentQueue;
    }

    if (item.href === 'organizer.orders.index') {
        return route().current('organizer.orders.*') && !isPaymentQueue;
    }

    return route().current(item.match);
}

function closeMobileMenu() {
    showingNavigationDropdown.value = false;
}
</script>

<template>
    <div class="admin-shell min-h-screen bg-[#f4f7fb] text-[#122b53]">
        <div v-if="showingNavigationDropdown" class="fixed inset-0 z-30 bg-[#092354]/35 md:hidden" aria-hidden="true" @click="closeMobileMenu"></div>

        <aside :class="showingNavigationDropdown ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 md:translate-x-0">
            <div class="flex h-20 items-center justify-between border-b border-slate-200 px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3" aria-label="Dashboard Ticket Event" @click="closeMobileMenu">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#144ba3] text-sm font-black text-white">TE</span>
                    <span><strong class="block text-sm font-black tracking-tight text-[#122b53]">Ticket Event</strong><small class="block text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Admin panel</small></span>
                </Link>
                <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-50 hover:text-[#144ba3] md:hidden" aria-label="Tutup menu" @click="closeMobileMenu">×</button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6" aria-label="Menu admin">
                <div v-for="group in navigationGroups" :key="group.label" class="mb-7 last:mb-0">
                    <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ group.label }}</p>
                    <div class="space-y-1">
                        <Link v-for="item in group.items" :key="item.href" :href="route(item.href)" :class="isActive(item) ? 'bg-[var(--brand-primary)] font-bold text-white' : 'text-slate-600 hover:bg-slate-50 hover:text-[var(--brand-primary)]'" class="flex items-center gap-3 rounded-md border-l-2 border-transparent px-3 py-3 text-sm transition" :aria-current="isActive(item) ? 'page' : undefined" @click="closeMobileMenu">
                            <AdminIcon :name="item.icon" class="h-[18px] w-[18px] shrink-0" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-slate-200 p-4">
                <Link :href="route('profile.edit')" class="mb-2 flex items-center gap-3 rounded-md px-3 py-3 hover:bg-slate-50" @click="closeMobileMenu">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--brand-primary)] text-xs font-black text-white">{{ $page.props.auth.user.name.slice(0, 2).toUpperCase() }}</span>
                    <span class="min-w-0"><strong class="block truncate text-sm text-[#122b53]">{{ $page.props.auth.user.name }}</strong><small class="block text-xs text-slate-500">Buka profil</small></span>
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-3 rounded-md px-3 py-3 text-left text-sm font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-600" @click="closeMobileMenu">
                    <AdminIcon name="logout" class="h-[18px] w-[18px]" />
                    Keluar
                </Link>
            </div>
        </aside>

        <div class="min-h-screen md:pl-72">
            <div class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-10">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-md border border-slate-200 p-2 text-[#144ba3] hover:bg-slate-50 md:hidden" aria-label="Buka menu" @click="showingNavigationDropdown = true"><AdminIcon name="menu" class="h-5 w-5" /></button>
                    <div><p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Workspace</p><p class="text-sm font-bold text-[#122b53]">Panel penyelenggara</p></div>
                </div>
                <Link :href="route('public.events.index')" target="_blank" class="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-[#144ba3] hover:bg-slate-50"><AdminIcon name="external" class="h-4 w-4" /> Lihat halaman publik</Link>
            </div>

            <header v-if="$slots.header" class="border-b border-slate-200 bg-white px-4 py-7 sm:px-6 lg:px-10"><div class="mx-auto max-w-[1440px]"><slot name="header" /></div></header>
            <main class="min-h-[calc(100vh-5rem)]"><slot /></main>
        </div>
    </div>
</template>
