<script setup>
defineProps({
    search:   { type: String, required: true },
    showOnly: { type: String, required: true },
})

const emit = defineEmits(['update:search', 'update:showOnly'])
</script>

<template>
    <div class="flex items-center gap-4">
        <input
            :value="search"
            @input="emit('update:search', $event.target.value)"
            type="text"
            :placeholder="$t('searchByNameOrEmail')"
            class="flex-1 px-4 py-2 rounded-lg bg-secondary border border-secondary/20 text-black text-sm placeholder-black/40 focus:outline-none focus:border-amber-400"
        />

        <div class="flex rounded-lg overflow-hidden border border-secondary/20 text-xs">
            <button
                @click="emit('update:showOnly', 'all')"
                class="px-3 py-2 transition"
                :class="showOnly === 'all'
                    ? 'bg-secondary text-gray-900 font-bold'
                    : 'bg-background text-white hover:bg-secondary/60'"
            >
                {{ $t('all') }}
            </button>
            <button
                @click="emit('update:showOnly', 'deleted')"
                class="px-3 py-2 transition"
                :class="showOnly === 'deleted'
                    ? 'bg-secondary text-gray-900 font-bold'
                    : 'bg-background text-white hover:bg-secondary/60'"
            >
                {{ $t('deleted') }}
            </button>
        </div>
    </div>
</template>
