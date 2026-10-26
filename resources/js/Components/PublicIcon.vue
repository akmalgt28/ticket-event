<script setup>
import { computed } from 'vue';

const props = defineProps({ name: { type: String, required: true } });

const icons = {
    calendar: 'fa-calendar-days',
    clock: 'fa-clock',
    pin: 'fa-location-dot',
    bank: 'fa-building-columns',
    check: 'fa-check',
    upload: 'fa-arrow-up-from-bracket',
    arrow: 'fa-arrow-left',
};

const iconClass = computed(() => icons[props.name] || icons.check);
</script>

<template>
    <i class="fa-solid fa-fw" :class="iconClass" aria-hidden="true"></i>
</template>