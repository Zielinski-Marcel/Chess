<script setup>
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChessBoardComp from '@/Components/Chess/ChessBoard.vue'
import MoveList from '@/Components/Chess/MoveList.vue'
import {
    fenToBoard, boardToRaw, boardToCells,
    moveToNotation, buildMovePairs,
    getLegalMoves, isLight, FILES,
    createStockfish,
} from '@/Composables/useChess.js'

const props = defineProps({
    gameId:             Number,
    fen:                String,
    initialMoves:       { type: Array, default: () => [] },
    initialTurn:        { type: String, default: 'w' },
    opponent:           { type: String, default: 'human' },
    playerColor:        { type: String, default: 'w' },
    initialStatus:      { type: String, default: 'playing' },
    initialWinnerColor: { type: String, default: null },
})

const { t } = useI18n()

const board          = ref(fenToBoard(props.fen))
const selected       = ref(null)
const turn           = ref(props.initialTurn)
const loading        = ref(false)
const error          = ref(null)
const promotion      = ref(null)
const gameOver       = ref(null)
const inCheck        = ref(false)
const movePairs = ref(props.initialMoves)
const moveListEl     = ref(null)
const engineThinking = ref(false)
const currentFen     = ref(props.fen)
const legalMoves     = ref([])
const hint           = ref(null)
const hintLoading    = ref(false)

const isVsComputer = props.opponent === 'stockfish' || props.opponent === 'myengine'
const playerColor  = props.playerColor

const isPlayerTurn = computed(() => !isVsComputer || turn.value === playerColor)
const totalMoves   = computed(() => movePairs.value.reduce((a, p) => a + (p.white?1:0) + (p.black?1:0), 0))
const canUndo      = computed(() => !loading.value && !engineThinking.value && totalMoves.value > 0)
const canHint      = computed(() => !isVsComputer && !gameOver.value && !loading.value && !hintLoading.value)

const cells = computed(() => boardToCells(board.value))

watch(movePairs, async () => {
    await nextTick()
    if (moveListEl.value) moveListEl.value.scrollTop = moveListEl.value.scrollHeight
}, { deep: true })


let sf = null
let hintCallback = null

function enginePath() {
    return props.opponent === 'myengine'
        ? '/engines/my-engine.js'
        : '/engines/stockfish.js'
}

function initStockfish() {
    sf = createStockfish(
        // onBestMove
        (move) => {
            if (hintCallback) {
                const cb = hintCallback
                hintCallback      = null
                hintLoading.value = false
                if (move) {
                    hint.value = { from: move.from, to: move.to }
                    setTimeout(() => { hint.value = null }, 3000)
                }
            } else {
                engineThinking.value = false
                if (move) applyEngineMove(move.uci)
            }
        },
        null,
        enginePath()
    )
}

function askEngine(fen) {
    if (!sf || gameOver.value) return
    engineThinking.value = true
    sf.analyse(fen, 1000)
}

function askHint() {
    if (!canHint.value || !sf) return
    hint.value        = null
    hintLoading.value = true
    selected.value    = null
    legalMoves.value  = []
    hintCallback      = true
    sf.analyse(currentFen.value, 800)
}

async function applyEngineMove(uciMove) {
    if (gameOver.value) return
    const files = { a:0,b:1,c:2,d:3,e:4,f:5,g:6,h:7 }
    const fromX = files[uciMove[0]], fromY = 8 - parseInt(uciMove[1])
    const toX   = files[uciMove[2]], toY   = 8 - parseInt(uciMove[3])
    const promo = uciMove[4] ?? null
    loading.value = true
    try {
        const payload = { game_id: props.gameId, from_x: fromX, from_y: fromY, to_x: toX, to_y: toY }
        if (promo) payload.promotion = promo
        const res = await axios.post('/moves', payload)
        handleMoveResponse(res.data)
    } catch { error.value = 'Błąd silnika' }
    finally { loading.value = false }
}

onMounted(() => {
    initStockfish()
    if (props.initialStatus === 'finished') {
        gameOver.value = {
            status: props.initialWinnerColor ? 'checkmate' : 'stalemate',
            winner: props.initialWinnerColor,
        }
        return
    }
    if (isVsComputer && playerColor === 'b') askEngine(props.fen)
})

onUnmounted(() => { sf?.terminate() })

function onCellClick(x, y) {
    if (loading.value || promotion.value || gameOver.value) return
    if (!isPlayerTurn.value || engineThinking.value) return
    if (hint.value) hint.value = null

    const piece = board.value[y][x]

    if (selected.value) {
        const { x: fx, y: fy } = selected.value
        if (fx===x && fy===y) { selected.value=null; legalMoves.value=[]; return }
        if (piece && piece.color===turn.value) {
            selected.value   = { x, y }
            legalMoves.value = getLegalMoves(boardToRaw(board.value), currentFen.value, x, y)
            return
        }
        const mp = board.value[fy][fx]
        if (mp?.type==='p' && ((mp.color==='w'&&y===0)||(mp.color==='b'&&y===7))) {
            promotion.value  = { from: { x: fx, y: fy }, to: { x, y } }
            selected.value   = null
            legalMoves.value = []
            return
        }
        sendMove({ x:fx, y:fy }, { x, y }, null)
        selected.value   = null
        legalMoves.value = []
        return
    }

    if (piece && piece.color===turn.value) {
        selected.value   = { x, y }
        legalMoves.value = getLegalMoves(boardToRaw(board.value), currentFen.value, x, y)
    }
}

function choosePromotion(type) {
    if (!promotion.value) return
    const { from, to } = promotion.value
    promotion.value = null
    sendMove(from, to, type)
}

async function sendMove(from, to, promo) {
    hint.value    = null
    loading.value = true
    error.value   = null
    inCheck.value = false
    try {
        const payload = { game_id: props.gameId, from_x: from.x, from_y: from.y, to_x: to.x, to_y: to.y }
        if (promo) payload.promotion = promo
        const res = await axios.post('/moves', payload)
        handleMoveResponse(res.data)
    } catch (e) {
        error.value      = e.response?.data?.error ?? 'Illegal move'
        selected.value   = null
        legalMoves.value = []
    } finally { loading.value = false }
}

async function undo() {
    if (!canUndo.value) return
    hint.value       = null
    selected.value   = null
    legalMoves.value = []
    loading.value    = true
    try {
        const movesToUndo = isVsComputer ? Math.min(2, totalMoves.value) : 1
        const res = await axios.post('/moves/undo', { game_id: props.gameId, moves_to_undo: movesToUndo })
        board.value      = fenToBoard(res.data.fen)
        turn.value       = res.data.turn
        movePairs.value  = res.data.moves
        currentFen.value = res.data.fen
        inCheck.value    = false
        gameOver.value   = null
        if (isVsComputer && res.data.turn !== playerColor) askEngine(res.data.fen)
    } catch (e) { error.value = e.response?.data?.error ?? 'Błąd cofania' }
    finally { loading.value = false }
}

async function resign() {
    if (gameOver.value || loading.value) return
    if (!confirm(t('confirmResign'))) return
    loading.value = true
    try {
        const res = await axios.post('/game/resign', { game_id: props.gameId })
        gameOver.value = { status: 'resigned', winner: res.data.winner_color }
    } catch { error.value = 'Błąd' }
    finally { loading.value = false }
}

function handleMoveResponse(data) {
    board.value      = fenToBoard(data.fen)
    turn.value       = data.turn
    movePairs.value  = data.moves
    currentFen.value = data.fen
    inCheck.value    = false
    legalMoves.value = []

    if (data.status==='checkmate'||data.status==='stalemate') {
        gameOver.value = { status: data.status, winner: data.status==='checkmate'?(data.turn==='w'?'b':'w'):null }
        return
    }
    if (data.status==='check') inCheck.value = true
    if (isVsComputer && data.turn!==playerColor && !gameOver.value) askEngine(data.fen)
}


function findKingSquare(color) {
    for (let y = 0; y < 8; y++)
        for (let x = 0; x < 8; x++)
            if (board.value[y]?.[x]?.type==='k' && board.value[y][x].color===color)
                return { x, y }
    return null
}

function getKingInDanger() {
    if (inCheck.value && !gameOver.value) return findKingSquare(turn.value)
    if (gameOver.value?.status==='checkmate') {
        const loser = gameOver.value.winner==='w' ? 'b' : 'w'
        return findKingSquare(loser)
    }
    return null
}

function cellBg(x, y) {
    const kd = getKingInDanger()
    if (kd?.x===x && kd?.y===y)                           return 'bg-red-500'
    if (hint.value?.from.x===x && hint.value?.from.y===y) return isLight(x,y) ? 'bg-green-300' : 'bg-green-500'
    if (hint.value?.to.x===x   && hint.value?.to.y===y)   return isLight(x,y) ? 'bg-green-200' : 'bg-green-400'
    if (selected.value?.x===x  && selected.value?.y===y)  return isLight(x,y) ? 'bg-yellow-300' : 'bg-yellow-500'
    return isLight(x,y) ? 'bg-[#f0d9b5]' : 'bg-[#b58863]'
}

function overlays(x, y) {
    const result = []
    const isLegal = legalMoves.value.some(([lx,ly]) => lx===x && ly===y)
    if (isLegal && !board.value[y][x]) {
        result.push({ class: 'rounded-full', style: 'width:33%;height:33%;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.18)' })
    }
    if (isLegal && board.value[y][x]) {
        result.push({ class: 'inset-0', style: 'border-radius:2px;box-shadow:inset 0 0 0 5px rgba(0,0,0,0.22)' })
    }
    if (hint.value?.to.x===x && hint.value?.to.y===y) {
        result.push({ class: 'inset-0 flex items-center justify-center', style: '' })
    }
    return result
}

const PROMOTION_PIECES = ['q','r','b','n']
const PIECE_MAP = { k:'K', q:'Q', r:'R', b:'B', n:'N', p:'P' }
function pieceUrl(color, type) {
    return `https://lichess1.org/assets/piece/neo/${color}${PIECE_MAP[type] ?? 'P'}.svg`
}
function promotionPieceUrl(type) { return pieceUrl(turn.value, type) }

function gameOverMessage() {
    if (!gameOver.value) return ''
    if (gameOver.value.status==='stalemate') return t('draw') + '!'
    const winner = gameOver.value.winner==='w' ? t('white') : t('black')
    if (gameOver.value.status==='resigned')  return winner + ' ' + t('winsResign')
    return winner + ' ' + t('winsCheckmate')
}

function statusText() {
    if (hintLoading.value)    return { text: t('stockfishAnalyzing'),  class: 'text-green-400 animate-pulse' }
    if (hint.value)           return { text: `${t('hint')}: ${FILES[hint.value.from.x]}${8-hint.value.from.y} → ${FILES[hint.value.to.x]}${8-hint.value.to.y}`, class: 'text-green-400' }
    if (engineThinking.value) return { text: props.opponent === 'myengine' ? t('myEngineThinking') : t('stockfishThinking'), class: 'text-secondary/70 animate-pulse' }
    if (loading.value)        return { text: t('processing'),              class: 'text-secondary/50' }
    if (gameOver.value)       return { text: t('gameOver'),                class: 'text-secondary font-semibold' }
    if (error.value)          return { text: error.value,                  class: 'text-red-400' }
    if (inCheck.value)        return { text: t('check'),                   class: 'text-red-400 font-semibold animate-pulse' }
    return turn.value === 'w'
        ? { text: t('whiteTurn'), class: 'text-white' }
        : { text: t('blackTurn'), class: 'text-secondary/70' }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span v-if="isVsComputer" class="text-xs px-2 py-1 rounded-full bg-secondary/20 text-secondary font-medium">
                        vs {{ opponent === 'myengine' ? 'MyEngine' : 'Stockfish' }}
                    </span>
                </div>
            </div>
        </template>

        <div class="flex justify-center items-start gap-6 py-10 px-4">

            <ChessBoardComp
                :cells="cells"
                :cell-bg="cellBg"
                :overlays="overlays"
                :disabled="!isPlayerTurn || loading || !!promotion || !!gameOver || engineThinking"
                @click-cell="onCellClick"
            >
                <template #modals>
                    <div v-if="promotion" class="absolute inset-0 flex items-center justify-center bg-black/60 rounded-xl z-10">
                        <div class="bg-gray-800 rounded-2xl p-6 shadow-2xl text-center">
                            <p class="text-white text-sm font-semibold mb-4">{{ $t('choosePiece') }}</p>
                            <div class="flex gap-3 justify-center mb-4">
                                <button v-for="t in PROMOTION_PIECES" :key="t"
                                        @click="choosePromotion(t)"
                                        class="w-14 h-14 text-4xl rounded-xl bg-gray-700 hover:bg-blue-600 transition flex items-center justify-center">
                                    <img :src="promotionPieceUrl(t)" class="w-10 h-10" draggable="false" />
                                </button>
                            </div>
                            <button @click="promotion=null; legalMoves=[]" class="text-xs text-gray-400 hover:text-white transition">{{ $t('cancel') }}</button>
                        </div>
                    </div>
                    <div v-if="gameOver && gameOver.status !== 'finished'" class="absolute inset-0 flex items-center justify-center bg-black/70 rounded-xl z-10">
                        <div class="bg-gray-800 rounded-2xl p-8 shadow-2xl text-center">
                            <div class="text-5xl mb-4">{{ gameOver.status==='stalemate' ? '🤝' : gameOver.status==='resigned' ? '🏳' : '🏆' }}</div>
                            <p class="text-white text-xl font-bold mb-2">{{ gameOverMessage() }}</p>
                            <a href="/game/create" class="mt-4 inline-block px-6 py-2 rounded-lg bg-secondary text-gray-900 font-bold text-sm hover:bg-amber-300 transition">{{ $t('newGame') }}</a>
                        </div>
                    </div>
                </template>
            </ChessBoardComp>

            <div class="w-52 bg-primary rounded-xl flex flex-col overflow-hidden" style="height:512px;">
                <div class="px-4 py-3 border-b border-secondary/10">
                    <p class="text-xs font-semibold text-secondary/50 uppercase tracking-widest">{{ $t('moves') }}</p>
                </div>

                <MoveList
                    ref="moveListEl"
                    :pairs="movePairs"
                    :to-notation="moveToNotation"
                />

                <div class="px-3 py-2 border-t border-secondary/10 flex flex-col gap-2">
                    <div class="flex gap-2">
                        <button @click="undo" :disabled="!canUndo"
                                class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-secondary/10 hover:bg-secondary/20 text-secondary/70 transition disabled:opacity-30 disabled:cursor-not-allowed">
                            {{ $t('undo') }}
                        </button>
                        <button v-if="!gameOver" @click="resign" :disabled="loading||engineThinking"
                                class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-red-900/50 hover:bg-red-800/70 text-red-400 transition disabled:opacity-30 disabled:cursor-not-allowed">
                            {{ $t('resign') }}
                        </button>
                    </div>
                    <button v-if="!isVsComputer && !gameOver" @click="askHint" :disabled="!canHint"
                            class="w-full py-1.5 rounded-lg text-xs font-medium bg-green-900/40 hover:bg-green-800/60 text-green-400 transition disabled:opacity-30 disabled:cursor-not-allowed">
                        {{ hintLoading ? $t('searching') : $t('hint') }}
                    </button>
                </div>

                <div class="px-4 py-2 border-t border-secondary/10 text-xs text-center">
                    <span :class="statusText().class">{{ statusText().text }}</span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
