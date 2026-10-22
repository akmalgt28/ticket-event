<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[var(--brand-primary)] text-start text-base font-bold text-[var(--brand-primary-dark)] bg-[var(--brand-paper)] focus:outline-none focus:text-[var(--brand-primary-dark)] focus:bg-[var(--brand-paper)] focus:border-[var(--brand-primary-dark)] transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-bold text-slate-600 hover:text-[var(--brand-ink)] hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:text-[var(--brand-ink)] focus:bg-slate-50 focus:border-slate-300 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
