<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import ChessBoardComp from '@/Components/Chess/ChessBoard.vue'
import MoveList from '@/Components/Chess/MoveList.vue'
import EvalBar from '@/Components/Chess/EvalBar.vue'
import {
    fenToBoard, boardToCells,
    moveToNotation, buildMovePairs,
    isLight, FILES, uciSquare,
    createStockfish,
} from '@/Composables/useChess.js'

const { t } = useI18n()

const props = defineProps({
    gameId:      Number,
    moves:       { type: Array, default: () => [] },
    startFen:    String,
    winnerColor: { type: String, default: null },
    opponent:    { type: String, default: 'human' },
})


const currentIndex = ref(props.moves.length - 1)

const currentFen = computed(() => {
    if (currentIndex.value < 0) return props.startFen
    return props.moves[currentIndex.value].fen
})

const board = computed(() => fenToBoard(currentFen.value))
const cells = computed(() => boardToCells(board.value))

const currentMove = computed(() => {
    if (currentIndex.value < 0) return null
    return props.moves[currentIndex.value]
})

const fenBeforeCurrent = computed(() => {
    if (currentIndex.value <= 0) return props.startFen
    return props.moves[currentIndex.value - 1].fen
})

const movePairs = computed(() => buildMovePairs(props.moves))


let sf    = null
let sfEval = null

const bestMove  = ref(null)
const analysing = ref(false)
const evalCp    = ref(0)
const evalMate  = ref(null)

function initStockfish() {
    sf = createStockfish(
        (move) => {
            analysing.value = false
            bestMove.value  = move
        },
        null
    )
    sfEval = createStockfish(
        null,
        ({ cp, mate }) => {
            evalCp.value   = cp
            evalMate.value = mate
        }
    )
}

watch(currentIndex, () => {
    bestMove.value  = null
    analysing.value = true
    evalMate.value  = null
    sf?.analyse(fenBeforeCurrent.value, 800)
    sfEval?.analyse(currentFen.value, 800)
}, { immediate: false })

onMounted(() => {
    initStockfish()
    setTimeout(() => {
        analysing.value = true
        sf?.analyse(fenBeforeCurrent.value, 800)
        sfEval?.analyse(currentFen.value, 800)
    }, 500)
})

onUnmounted(() => { sf?.terminate(); sfEval?.terminate() })


function goTo(index) {
    currentIndex.value = Math.max(-1, Math.min(props.moves.length - 1, index))
}
function prev()  { goTo(currentIndex.value - 1) }
function next()  { goTo(currentIndex.value + 1) }
function first() { goTo(-1) }
function last()  { goTo(props.moves.length - 1) }

function onKeydown(e) {
    if (e.key === 'ArrowLeft')  prev()
    if (e.key === 'ArrowRight') next()
    if (e.key === 'Home')       first()
    if (e.key === 'End')        last()
}

onMounted(() => { window.addEventListener('keydown', onKeydown) })
onUnmounted(() => { window.removeEventListener('keydown', onKeydown) })



const isBestPlay = computed(() => {
    if (!bestMove.value || !currentMove.value) return false
    const m = currentMove.value, b = bestMove.value
    return m.from_x===b.from.x && m.from_y===b.from.y && m.to_x===b.to.x && m.to_y===b.to.y
})

function cellBg(x, y) {
    const isAFrom = currentMove.value?.from_x===x && currentMove.value?.from_y===y
    const isATo   = currentMove.value?.to_x===x   && currentMove.value?.to_y===y
    const isBTo   = bestMove.value?.to.x===x       && bestMove.value?.to.y===y
    const light   = isLight(x, y)
    if (isATo && isBTo) return light ? 'bg-yellow-300' : 'bg-yellow-500'
    if (isBTo)   return light ? 'bg-green-200' : 'bg-green-400'
    if (isAFrom) return light ? 'bg-yellow-200' : 'bg-yellow-400'
    if (isATo)   return light ? 'bg-yellow-300' : 'bg-yellow-500'
    return light ? 'bg-[#f0d9b5]' : 'bg-[#b58863]'
}

function overlays(x, y) {
    const isBTo   = bestMove.value?.to.x===x   && bestMove.value?.to.y===y
    const isBFrom = bestMove.value?.from.x===x  && bestMove.value?.from.y===y
    const result  = []
    if (isBFrom) {
        result.push({ class: 'inset-0', style: 'border-radius:2px;box-shadow:inset 0 0 0 4px rgba(0,180,0,0.5)' })
    }
    if (isBTo && !board.value[y][x]) {
        result.push({ class: 'rounded-full', style: 'width:33%;height:33%;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,150,0,0.4)' })
    }
    if (isBTo && board.value[y][x]) {
        result.push({ class: 'inset-0', style: 'border-radius:2px;box-shadow:inset 0 0 0 5px rgba(0,150,0,0.5)' })
    }
    return result
}

function resultLabel() {
    if (!props.winnerColor) return '½ - ½'
    return props.winnerColor === 'w' ? '1 - 0' : '0 - 1'
}

const isAtLastMove = computed(() => currentIndex.value === props.moves.length - 1)

const displayEvalCp = computed(() => {
    if (isAtLastMove.value && props.winnerColor) {
        return props.winnerColor === 'w' ? 10000 : -10000
    }
    return evalCp.value
})

const displayEvalMate = computed(() => {
    if (isAtLastMove.value && props.winnerColor) return null
    if (isAtLastMove.value && !props.winnerColor) return null
    return evalMate.value
})

const displayAnalysing = computed(() => {
    if (isAtLastMove.value) return false
    return analysing.value
})
</script>

<template>
    <Head :title="$t('gameReview')" />

    <AuthenticatedLayout>

        <div class="flex justify-center items-start gap-4 py-10 px-4">

            <EvalBar :eval-cp="displayEvalCp" :eval-mate="displayEvalMate" :analysing="displayAnalysing" />

            <ChessBoardComp
                :cells="cells"
                :cell-bg="cellBg"
                :overlays="overlays"
                :disabled="true"
            >
                <template #below>
                    <div class="flex justify-center items-center gap-2 mt-4">
                        <button @click="first" :disabled="currentIndex===-1"
                                class="px-3 py-2 rounded-lg bg-primary hover:bg-primary/80 text-secondary/70 text-sm transition disabled:opacity-30">⇐</button>
                        <button @click="prev" :disabled="currentIndex===-1"
                                class="px-4 py-2 rounded-lg bg-primary hover:bg-primary/80 text-secondary/70 text-sm transition disabled:opacity-30">←</button>
                        <span class="text-secondary/50 text-xs w-24 text-center">
                            {{ currentIndex===-1 ? $t('start') : `${$t('move')} ${currentIndex+1} / ${moves.length}` }}
                        </span>
                        <button @click="next" :disabled="currentIndex===moves.length-1"
                                class="px-4 py-2 rounded-lg bg-primary hover:bg-primary/80 text-secondary/70 text-sm transition disabled:opacity-30">→</button>
                        <button @click="last" :disabled="currentIndex===moves.length-1"
                                class="px-3 py-2 rounded-lg bg-primary hover:bg-primary/80 text-secondary/70 text-sm transition disabled:opacity-30">⇒</button>
                    </div>

                    <div class="mt-3 space-y-1">
                        <div class="flex items-center justify-center text-xs">
                            <span class="flex items-center gap-1 text-yellow-300">
                                <span class="inline-block w-3 h-3 rounded-sm bg-yellow-400"></span>
                                <span v-if="currentMove">
                                    {{ $t('played') }}: {{ moveToNotation(currentMove) }}
                                    ({{ uciSquare(currentMove.from_x, currentMove.from_y) }}→{{ uciSquare(currentMove.to_x, currentMove.to_y) }})
                                </span>
                                <span v-else class="text-secondary/30">{{ $t('startPosition') }}</span>
                            </span>
                        </div>
                        <div class="flex items-center justify-center text-xs">
                            <span class="flex items-center gap-1">
                                <span class="inline-block w-3 h-3 rounded-sm bg-green-400"></span>
                                <span v-if="analysing" class="text-green-400 animate-pulse">{{ $t('stockfishAnalyzing') }}</span>
                                <span v-else-if="bestMove" class="text-green-300">
                                    Stockfish: {{ uciSquare(bestMove.from.x, bestMove.from.y) }}→{{ uciSquare(bestMove.to.x, bestMove.to.y) }}
                                    <span v-if="isBestPlay" class="text-green-400 font-bold ml-1">{{ $t('bestMove') }}</span>
                                </span>
                                <span v-else class="text-secondary/30">{{ $t('noAnalysis') }}</span>
                            </span>
                        </div>
                    </div>
                </template>
            </ChessBoardComp>

            <div class="w-56 bg-primary rounded-xl flex flex-col overflow-hidden" style="height:560px;">
                <div class="px-4 py-3 border-b border-secondary/10">
                    <p class="text-xs font-semibold text-secondary/50 uppercase tracking-widest">{{ $t('moveList') }}</p>
                </div>

                <MoveList
                    :pairs="movePairs"
                    :active-index="currentIndex"
                    :to-notation="moveToNotation"
                    :clickable="true"
                    @click-move="goTo"
                >
                    <template #start>
                        <div @click="goTo(-1)"
                             class="flex items-center gap-1 rounded-lg px-2 py-1 cursor-pointer transition text-xs"
                             :class="currentIndex===-1 ? 'bg-secondary/20 text-secondary' : 'hover:bg-secondary/10 text-secondary/30'">
                            {{ $t('startPosition') }}
                        </div>
                    </template>
                </MoveList>

                <div class="px-4 py-3 border-t border-secondary/10 text-center">
                    <span class="text-secondary/50 text-xs font-semibold">{{ resultLabel() }}</span>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
