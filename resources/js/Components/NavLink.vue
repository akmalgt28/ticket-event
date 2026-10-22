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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[var(--brand-primary)] text-sm font-bold leading-5 text-[var(--brand-ink)] focus:outline-none focus:border-[var(--brand-primary-dark)] transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold leading-5 text-slate-500 hover:text-[var(--brand-ink)] hover:border-slate-300 focus:outline-none focus:text-[var(--brand-ink)] focus:border-slate-300 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
