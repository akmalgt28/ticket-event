<script setup>
defineProps({ event: { type: Object, required: true } });
</script>

<template>
    <div class="flex w-full items-center justify-center overflow-hidden bg-[#102c57]">
        <img v-if="event.banner_url" :src="event.banner_url" :alt="`Poster ${event.title}`" class="max-h-[440px] w-full object-contain" fetchpriority="high" />
        <div v-else class="flex min-h-48 w-full items-center justify-center px-6 py-12 text-center text-sm text-blue-100">{{ event.title }}</div>
    </div>
</template>
