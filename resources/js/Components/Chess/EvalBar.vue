<!-- resources/js/Components/Chess/EvalBar.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
    evalCp:    { type: Number, default: 0 },
    evalMate:  { type: Number, default: null },
    analysing: { type: Boolean, default: false },
})

const whitePercent = computed(() => {
    // Mat — pasek w 100% lub 0%
    if (props.evalMate !== null) {
        return props.evalMate > 0 ? 100 : 0
    }
    // Wygranie (cp ±10000 ustawiane przez displayEvalCp)
    if (props.evalCp >= 9999)  return 100
    if (props.evalCp <= -9999) return 0
    const clamped = Math.max(-500, Math.min(500, props.evalCp))
    return 50 + (clamped / 500) * 30
})

const isWhiteWin  = computed(() => props.evalCp >= 9999  && props.evalMate === null)
const isBlackWin  = computed(() => props.evalCp <= -9999 && props.evalMate === null)

const label = computed(() => {
    if (props.analysing) return '...'
    if (isWhiteWin.value)  return '1-0'
    if (isBlackWin.value)  return '0-1'
    if (props.evalMate !== null) {
        return props.evalMate > 0
            ? `M${props.evalMate}`
            : `M${Math.abs(props.evalMate)}`
    }
    const abs  = Math.abs(props.evalCp)
    const sign = props.evalCp >= 0 ? '+' : '-'
    return `${sign}${(abs / 100).toFixed(1)}`
})

const labelColor = computed(() => {
    if (isWhiteWin.value)  return 'text-white font-black'
    if (isBlackWin.value)  return 'text-gray-400 font-black'
    if (props.evalCp > 50)  return 'text-white'
    if (props.evalCp < -50) return 'text-gray-400'
    return 'text-gray-300'
})
</script>

<template>
    <div class="shrink-0 flex flex-col items-center gap-1" style="width: 28px;">
        <span class="text-white text-xs font-bold select-none">W</span>

        <div class="flex-1 w-5 rounded-full overflow-hidden bg-gray-600 relative"
             style="height: 512px; min-height: 512px;">
            <!-- Czarne (góra) -->
            <div class="absolute top-0 left-0 w-full bg-gray-900 transition-all duration-700"
                 :style="{ height: (100 - whitePercent) + '%' }" />
            <!-- Białe (dół) -->
            <div class="absolute bottom-0 left-0 w-full bg-white transition-all duration-700"
                 :style="{ height: whitePercent + '%' }" />
            <!-- Linia środkowa -->
            <div class="absolute left-0 w-full bg-gray-500" style="top: 50%; height: 1px;" />
        </div>

        <span class="text-gray-400 text-xs font-bold select-none">B</span>
        <span class="text-xs font-mono" :class="labelColor">{{ label }}</span>
    </div>
</template>
