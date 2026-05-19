<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    links:  { type: Array,  required: true },
    params: { type: Object, default: () => ({}) },
})

function label(text) {
    return text
        .replace(/&laquo;.*Previous/, '&larr;')
        .replace(/Next.*&raquo;/, '&rarr;')
}

function navigate(url) {
    if (!url) return
    const clean = Object.fromEntries(
        Object.entries(props.params).filter(([, v]) => v !== undefined && v !== null && v !== '')
    )
    router.get(url, clean, { preserveState: true, replace: true })
}
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex items-center justify-center gap-1"
    >
        <template v-for="link in links" :key="link.label">
            <button
                v-if="link.url"
                @click="navigate(link.url)"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                :class="link.active
                    ? 'bg-secondary text-gray-900 font-bold'
                    : 'bg-background text-secondary/70 hover:bg-primary/60'"
                v-html="label(link.label)"
            />
            <span
                v-else
                class="px-3 py-1.5 text-xs text-secondary/30"
                v-html="label(link.label)"
            />
        </template>
    </div>
</template>
