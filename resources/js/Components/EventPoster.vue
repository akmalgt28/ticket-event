<script setup>
defineProps({
    event: { type: Object, required: true },
    cover: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex w-full items-center justify-center overflow-hidden bg-[#f8fafc]" :class="cover ? 'aspect-[4/3]' : ''">
        <img v-if="event.banner_url" :src="event.banner_url" :alt="`Poster ${event.title}`" class="h-full w-full transition duration-300" :class="cover ? 'object-cover object-center' : 'max-h-[440px] object-contain'" fetchpriority="high" />
        <div v-else class="flex min-h-48 w-full items-center justify-center px-6 py-12 text-center type-body-sm text-[#64748b]">{{ event.title }}</div>
    </div>
</template>