<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[var(--brand-paper)] text-[var(--brand-ink)]">
        <nav class="border-b border-slate-300 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex min-h-16 items-center justify-between gap-6">
                    <div class="flex min-w-0 items-center gap-8">
                        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-3" aria-label="Dashboard Ticket Event">
                            <span class="brand-bg-dark flex h-9 w-9 items-center justify-center text-sm font-black text-white">TE</span>
                            <span class="hidden text-base font-black sm:inline">Ticket Event</span>
                        </Link>
                        <div class="hidden items-center gap-5 text-sm font-bold lg:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                            <NavLink :href="route('organizer.events.index')" :active="route().current('organizer.events.*')">Events</NavLink>
                            <NavLink :href="route('organizer.orders.index')" :active="route().current('organizer.orders.*')">Orders</NavLink>
                            <NavLink :href="route('organizer.payments.index')" :active="route().current('organizer.payments.*')">Pembayaran</NavLink>
                            <NavLink :href="route('organizer.participants.index')" :active="route().current('organizer.participants.*')">Peserta</NavLink>
                            <NavLink :href="route('organizer.check-ins.index')" :active="route().current('organizer.check-ins.*')">Check-in</NavLink>
                        </div>
                    </div>
                    <div class="hidden items-center gap-4 sm:flex">
                        <span class="text-sm font-bold text-slate-700">{{ $page.props.auth.user.name }}</span>
                        <Link :href="route('profile.edit')" class="text-sm font-bold text-slate-500 hover:text-[var(--brand-primary)]">Profil</Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-sm font-bold text-slate-500 hover:text-rose-600">Keluar</Link>
                    </div>
                    <button type="button" class="border border-slate-300 px-3 py-2 text-sm font-bold lg:hidden" aria-label="Buka menu" @click="showingNavigationDropdown = !showingNavigationDropdown">Menu</button>
                </div>
            </div>
            <div v-if="showingNavigationDropdown" class="border-t border-slate-200 bg-white lg:hidden">
                <div class="mx-auto grid max-w-7xl gap-1 px-4 py-3 sm:px-6">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('organizer.events.index')" :active="route().current('organizer.events.*')">Events</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('organizer.orders.index')" :active="route().current('organizer.orders.*')">Orders</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('organizer.payments.index')" :active="route().current('organizer.payments.*')">Pembayaran</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('organizer.participants.index')" :active="route().current('organizer.participants.*')">Peserta</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('organizer.check-ins.index')" :active="route().current('organizer.check-ins.*')">Check-in</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('profile.edit')">Profil</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">Keluar</ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="border-b border-slate-300 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"><slot name="header" /></div>
        </header>
        <main><slot /></main>
    </div>
</template>
