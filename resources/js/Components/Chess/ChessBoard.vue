<!-- resources/js/Components/Chess/ChessBoard.vue -->
<script setup>
const props = defineProps({
    cells:    { type: Array,    required: true },
    cellBg:   { type: Function, required: true },
    overlays: { type: Function, default: () => [] },
    disabled: { type: Boolean,  default: false },
})

const emit = defineEmits(['click-cell'])

const FILES = ['a','b','c','d','e','f','g','h']

const PIECE_MAP = { k:'K', q:'Q', r:'R', b:'B', n:'N', p:'P' }

function pieceUrl(piece) {
    if (!piece) return null
    return `/pieces/cburnett/${piece.color}${PIECE_MAP[piece.type]}.svg`
}
</script>

<template>
    <div class="shrink-0">
        <!-- File labels top -->
        <div class="flex mb-1 pl-5">
            <div v-for="f in FILES" :key="f"
                 class="w-16 text-center text-xs text-gray-500 font-medium select-none">
                {{ f }}
            </div>
        </div>

        <div class="flex">
            <!-- Rank labels left -->
            <div class="flex flex-col mr-1">
                <div v-for="r in [8,7,6,5,4,3,2,1]" :key="r"
                     class="h-16 flex items-center justify-center text-xs text-gray-500 font-medium w-4 select-none">
                    {{ r }}
                </div>
            </div>

            <!-- Board -->
            <div class="relative">
                <div class="grid rounded-xl overflow-hidden border border-gray-700"
                     style="grid-template-columns: repeat(8, 4rem);">
                    <div
                        v-for="{ piece, x, y } in cells"
                        :key="`${x}-${y}`"
                        @click="!disabled && emit('click-cell', x, y)"
                        class="w-16 h-16 flex items-center justify-center relative transition-colors select-none"
                        :class="[cellBg(x, y), disabled ? 'cursor-default' : 'cursor-pointer']"
                    >
                        <!-- Overlays -->
                        <div
                            v-for="(overlay, i) in overlays(x, y)"
                            :key="i"
                            class="absolute pointer-events-none"
                            :class="overlay.class"
                            :style="overlay.style"
                        />

                        <!-- Neo piece image -->
                        <img
                            v-if="piece"
                            :src="pieceUrl(piece)"
                            :alt="`${piece.color}${piece.type}`"
                            class="w-12 h-12 relative z-10 pointer-events-none select-none drop-shadow-sm"
                            draggable="false"
                        />
                    </div>
                </div>

                <slot name="modals" />
            </div>

            <!-- Rank labels right -->
            <div class="flex flex-col ml-1">
                <div v-for="r in [8,7,6,5,4,3,2,1]" :key="r"
                     class="h-16 flex items-center justify-center text-xs text-gray-500 font-medium w-4 select-none">
                    {{ r }}
                </div>
            </div>
        </div>

        <!-- File labels bottom -->
        <div class="flex mt-1 pl-5">
            <div v-for="f in FILES" :key="f"
                 class="w-16 text-center text-xs text-gray-500 font-medium select-none">
                {{ f }}
            </div>
        </div>

        <slot name="below" />
    </div>
</template>
