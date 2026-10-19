<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <div class="min-h-screen bg-[#f7f3ec] text-slate-950"><header class="border-b border-slate-200/80 bg-[#f7f3ec]/90 backdrop-blur"><div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8"><Link href="/" class="flex items-center gap-3"><ApplicationLogo class="h-9 w-9 fill-current text-amber-600" /><span class="text-lg font-black tracking-tight">Ticket Event</span></Link><nav class="flex items-center gap-5 text-sm font-bold"><Link href="/events" class="hover:text-amber-600">Events</Link><Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="hover:text-amber-600">Dashboard</Link><Link v-else :href="route('login')" class="hover:text-amber-600">Login</Link></nav></div></header><main><slot /></main></div>
</template>
