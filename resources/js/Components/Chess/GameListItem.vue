<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
    game: { type: Object, required: true },
})

const emit = defineEmits(['click'])
const { t } = useI18n()

const PIECE_MAP = { k:'K', q:'Q', r:'R', b:'B', n:'N', p:'P' }

function pieceUrl(piece) {
    const color = piece === piece.toUpperCase() ? 'w' : 'b'
    const type  = PIECE_MAP[piece.toLowerCase()]
    return `/pieces/cburnett/${color}${type}.svg`
}

function fenToRows(fen) {
    return fen.split(' ')[0].split('/').map(row => {
        const cells = []
        for (const ch of row) {
            if (!isNaN(ch)) for (let i = 0; i < Number(ch); i++) cells.push(null)
            else cells.push(ch)
        }
        return cells
    })
}

function gameTitle(game) {
    if (game.opponent === 'stockfish') return 'Stockfish'
    if (game.opponent === 'myengine')  return 'MyEngine'
    return t('training')
}

function statusLabel(game) {
    if (game.status === 'finished') {
        if (!game.winner_color) return t('draw')
        if (game.opponent === 'stockfish') {
            return game.winner_color === game.player_color ? t('youWon') : t('stockfishWon')
        }
        return game.winner_color === 'w' ? t('whiteWon') : t('blackWon')
    }
    return game.turn === 'w' ? t('whiteTurn') : t('blackTurn')
}

function statusColor(game) {
    if (game.status === 'finished') return 'text-secondary/50'
    return game.turn === 'w' ? 'text-white' : 'text-sky-400'
}
</script>

<template>
    <div
        @click="emit('click', game.id)"
        class="group flex items-center gap-4 bg-primary hover:bg-primary/80 rounded-xl p-4 cursor-pointer transition-all duration-200"
    >
        <!-- Mini board -->
        <div class="shrink-0 rounded-lg overflow-hidden border border-secondary/20"
             style="width:80px; height:80px;">
            <div class="grid" style="grid-template-columns:repeat(8,1fr); width:80px; height:80px;">
                <template v-for="(row, y) in fenToRows(game.fen)" :key="y">
                    <div
                        v-for="(cell, x) in row" :key="x"
                        class="flex items-center justify-center"
                        style="width:10px; height:10px;"
                        :style="{ background: (x+y)%2===0 ? '#f0d9b5' : '#b58863' }"
                    >
                        <img v-if="cell" :src="pieceUrl(cell)"
                             style="width:9px;height:9px;" draggable="false" />
                    </div>
                </template>
            </div>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-white font-semibold text-sm truncate">
                    {{ gameTitle(game) }}
                </span>
                <span
                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="game.status === 'finished'
                        ? 'bg-background text-secondary/50'
                        : 'bg-secondary text-bg'"
                >
                    {{ game.status === 'finished' ? $t('finished') : $t('inProgress') }}
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-secondary/50">
                <template v-if="game.opponent === 'human'">
                    <span class="flex items-center gap-1">
                        <img src="/pieces/cburnett/wK.svg" class="w-4 h-4" draggable="false"/>
                        {{ $t('white') }}
                    </span>
                    <span class="text-secondary/20">vs</span>
                    <span class="flex items-center gap-1">
                        <img src="/pieces/cburnett/bK.svg" class="w-4 h-4" draggable="false"/>
                        {{ $t('black') }}
                    </span>
                </template>
                <template v-else>
                    <span class="flex items-center gap-1">
                        <img src="/pieces/cburnett/wK.svg" class="w-4 h-4" draggable="false"/>
                        <span :class="game.player_color === 'w' ? 'text-white font-medium' : ''">
                            {{ game.player_color === 'w' ? $t('you') : (game.opponent === 'stockfish' ? 'Stockfish' : 'MyEngine') }}
                        </span>
                    </span>
                    <span class="text-secondary/20">vs</span>
                    <span class="flex items-center gap-1">
                        <img src="/pieces/cburnett/bK.svg" class="w-4 h-4" draggable="false"/>
                        <span :class="game.player_color === 'b' ? 'text-white font-medium' : ''">
                            {{ game.player_color === 'b' ? $t('you') : (game.opponent === 'stockfish' ? 'Stockfish' : 'MyEngine') }}
                        </span>
                    </span>
                </template>
            </div>

            <div class="mt-1 text-xs" :class="statusColor(game)">
                {{ statusLabel(game) }}
            </div>
        </div>

        <!-- Arrow -->
        <div class="shrink-0 text-secondary/30 group-hover:text-secondary transition text-xl">→</div>
    </div>
</template>
