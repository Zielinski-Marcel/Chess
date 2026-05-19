<!-- resources/js/Components/Chess/MoveList.vue -->
<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const props = defineProps({
    pairs: {
        type: Array,
        required: true,
    },
    activeIndex: {
        type: Number,
        default: null,
    },
    toNotation: {
        type: Function,
        required: true,
    },
    clickable: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['click-move'])

function isActive(moveIndex) {
    return props.activeIndex === moveIndex
}
</script>

<template>
    <div class="flex-1 overflow-y-auto px-2 py-2 space-y-0.5">
        <slot name="start" />

        <div v-for="pair in pairs" :key="pair.number"
             class="flex items-center gap-1 rounded-lg px-1">
            <span class="text-secondary/30 text-xs w-6 shrink-0 text-right">{{ pair.number }}.</span>

            <!-- Biały -->
            <span
                class="flex-1 text-center text-sm font-mono py-1 px-1 rounded text-white"
                :class="[
                    clickable ? 'cursor-pointer hover:bg-secondary/10' : 'cursor-default',
                    isActive((pair.number - 1) * 2) ? 'bg-secondary/20 text-secondary font-bold' : '',
                ]"
                @click="clickable && pair.white && emit('click-move', (pair.number - 1) * 2)"
            >
                {{ toNotation(pair.white) }}
            </span>

            <!-- Czarny -->
            <span
                v-if="pair.black"
                class="flex-1 text-center text-sm font-mono py-1 px-1 rounded text-secondary/70"
                :class="[
                    clickable ? 'cursor-pointer hover:bg-secondary/10' : 'cursor-default',
                    isActive((pair.number - 1) * 2 + 1) ? 'bg-secondary/20 text-secondary font-bold' : '',
                ]"
                @click="clickable && emit('click-move', (pair.number - 1) * 2 + 1)"
            >
                {{ toNotation(pair.black) }}
            </span>
            <span v-else class="flex-1" />
        </div>

        <div v-if="!pairs.length" class="text-center text-secondary/30 text-xs py-8">
            {{ t('noMoves') }}
        </div>
    </div>
</template>
