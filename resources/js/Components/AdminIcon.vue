<script setup>
import { computed } from 'vue';

const props = defineProps({ name: { type: String, required: true } });

const icons = {
    grid: 'fa-table-cells-large',
    receipt: 'fa-receipt',
    wallet: 'fa-wallet',
    calendar: 'fa-calendar-days',
    users: 'fa-users',
    scan: 'fa-qrcode',
    logout: 'fa-right-from-bracket',
    external: 'fa-arrow-up-right-from-square',
    menu: 'fa-bars',
};

const iconClass = computed(() => icons[props.name] || icons.menu);
</script>

<template>
    <i class="fa-solid fa-fw" :class="iconClass" aria-hidden="true"></i>
</template>