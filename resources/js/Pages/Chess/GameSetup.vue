<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

const { t } = useI18n()

const props = defineProps({
    activeGame: { type: Object, default: null },
})

const color    = ref('w')
const opponent = ref('stockfish')
const loading  = ref(false)

function startGame() {
    loading.value = true
    router.post('/game', { color: color.value, opponent: opponent.value }, {
        onFinish: () => { loading.value = false }
    })
}

function goToActive() {
    router.visit(`/game/${props.activeGame.id}`)
}

function fenToRows(fen) {
    if (!fen) return []
    return fen.split(' ')[0].split('/').map(row => {
        const cells = []
        for (const ch of row) {
            if (!isNaN(ch)) for (let i = 0; i < Number(ch); i++) cells.push(null)
            else cells.push(ch)
        }
        return cells
    })
}

const PIECE_MAP = { k:'K', q:'Q', r:'R', b:'B', n:'N', p:'P' }
function pieceUrl(piece) {
    const color = piece === piece.toUpperCase() ? 'w' : 'b'
    return `/pieces/cburnett/${color}${PIECE_MAP[piece.toLowerCase()]}.svg`
}

const OPPONENTS = computed(() => [
    { id: 'stockfish', label: 'Stockfish', desc: t('stockfishDesc') },
    { id: 'myengine',  label: 'MyEngine',  desc: t('myEngineDesc') },
    { id: 'human',     label: t('human'),  desc: t('humanDesc') },
])

const isVsEngine = (o) => o === 'stockfish' || o === 'myengine'
</script>

<template>
    <Head :title="$t('newGame')" />

    <AuthenticatedLayout>

        <div class="flex items-center justify-center py-16 px-4">
            <div class="w-full max-w-md space-y-4">

                <!-- Active game block -->
                <div v-if="activeGame"
                     class="bg-primary rounded-2xl p-6 text-center">
                    <p class="text-secondary font-semibold mb-4">{{ $t('hasActiveGame') }}</p>

                    <div class="flex justify-center mb-4">
                        <div class="rounded-lg overflow-hidden border border-secondary/20"
                             style="width:96px; height:96px;">
                            <div class="grid" style="grid-template-columns:repeat(8,1fr); width:96px; height:96px;">
                                <template v-for="(row, y) in fenToRows(activeGame.fen)" :key="y">
                                    <div
                                        v-for="(cell, x) in row" :key="x"
                                        class="flex items-center justify-center"
                                        style="width:12px; height:12px;"
                                        :style="{ background: (x+y)%2===0 ? '#f0d9b5' : '#b58863' }"
                                    >
                                        <img v-if="cell" :src="pieceUrl(cell)"
                                             style="width:11px;height:11px;" draggable="false"/>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <p class="text-secondary/70 text-sm mb-4">{{ $t('activeGameInfo') }}</p>

                    <button @click="goToActive"
                            class="w-full py-3 rounded-xl bg-secondary hover:bg-secondary/80 text-white font-bold text-base transition">
                        {{ $t('backToActiveGame') }} →
                    </button>
                </div>

                <!-- New game form -->
                <div v-else class="bg-primary rounded-2xl p-8 shadow-2xl">

                    <!-- Opponent -->
                    <div class="mb-8">
                        <p class="text-xs font-semibold text-secondary/50 uppercase tracking-widest mb-3">
                            {{ $t('opponent') }}
                        </p>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="op in OPPONENTS" :key="op.id"
                                @click="opponent = op.id"
                                class="flex flex-col items-center gap-2 py-4 rounded-xl transition"
                                :class="opponent === op.id
                                    ? 'bg-secondary text-black'
                                    : 'bg-background text-white hover:bg-secondary/40'"
                            >
                                <span class="text-sm font-semibold">{{ op.label }}</span>
                                <span class="text-xs opacity-60 text-center leading-tight">{{ op.desc }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Color (only vs engine) -->
                    <div v-if="isVsEngine(opponent)" class="mb-8">
                        <p class="text-xs font-semibold text-secondary/50 uppercase tracking-widest mb-3">
                            {{ $t('yourColor') }}
                        </p>
                        <div class="grid grid-cols-3 gap-3">
                            <button @click="color = 'w'"
                                    class="flex flex-col items-center gap-2 py-4 rounded-xl transition"
                                    :class="color==='w'
                                        ? 'bg-secondary text-black'
                                        : 'bg-background text-white hover:bg-secondary/40'">
                                <img src="/pieces/cburnett/wK.svg" class="w-8 h-8" draggable="false"/>
                                <span class="text-sm font-semibold">{{ $t('white') }}</span>
                            </button>
                            <button @click="color = 'random'"
                                    class="flex flex-col items-center gap-2 py-4 rounded-xl transition"
                                    :class="color==='random'
                                        ? 'bg-secondary text-black'
                                        : 'bg-background text-white hover:bg-secondary/40'">
                                <span class="text-3xl">🎲</span>
                                <span class="text-sm font-semibold">{{ $t('random') }}</span>
                            </button>
                            <button @click="color = 'b'"
                                    class="flex flex-col items-center gap-2 py-4 rounded-xl transition"
                                    :class="color==='b'
                                        ? 'bg-secondary text-black'
                                        : 'bg-background text-white hover:bg-secondary/40'">
                                <img src="/pieces/cburnett/bK.svg" class="w-8 h-8" draggable="false"/>
                                <span class="text-sm font-semibold">{{ $t('black') }}</span>
                            </button>
                        </div>
                    </div>

                    <button
                        @click="startGame"
                        :disabled="loading"
                        class="w-full py-3 rounded-xl bg-secondary hover:bg-secondary/80 text-white font-bold text-base transition disabled:opacity-50"
                    >
                        {{ loading ? $t('creating') : $t('startGame') }}
                    </button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
