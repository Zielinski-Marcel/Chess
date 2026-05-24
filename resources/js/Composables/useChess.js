export const FILES = ['a','b','c','d','e','f','g','h']


export function fenToBoard(fen) {
    const [position] = fen.split(' ')
    const board = []
    for (const row of position.split('/')) {
        const boardRow = []
        for (const char of row) {
            if (!isNaN(char)) {
                for (let i = 0; i < Number(char); i++) boardRow.push(null)
            } else {
                const color = char === char.toUpperCase() ? 'w' : 'b'
                boardRow.push({ type: char.toLowerCase(), color })
            }
        }
        board.push(boardRow)
    }
    return board
}

export function boardToRaw(b) {
    return b.map(row => row.map(cell => {
        if (!cell) return null
        return cell.color === 'w' ? cell.type.toUpperCase() : cell.type
    }))
}

export function boardToCells(board) {
    return board.flatMap((row, y) => row.map((piece, x) => ({ piece, x, y })))
}


export function moveToNotation(m) {
    if (!m) return ''
    const piece = m.piece
    const isCapture = m.captured !== null && m.captured !== undefined

    if (piece.toLowerCase() === 'k' && Math.abs(m.to_x - m.from_x) === 2)
        return m.to_x > m.from_x ? 'O-O' : 'O-O-O'

    const toFile    = FILES[m.to_x]
    const toRank    = 8 - m.to_y
    const pieceType = piece.toLowerCase()

    if (pieceType === 'p') {
        let n = isCapture ? FILES[m.from_x] + 'x' : ''
        n += toFile + toRank
        if (m.promotion) n += '=' + m.promotion.toUpperCase()
        return n + (m.suffix ?? '')
    }

    const symbols = { r:'R', n:'N', b:'B', q:'Q', k:'K' }
    return (symbols[pieceType] ?? '') + (isCapture ? 'x' : '') + toFile + toRank + (m.suffix ?? '')
}

export function buildMovePairs(moves) {
    const pairs = []
    for (let i = 0; i < moves.length; i += 2) {
        pairs.push({
            number: Math.floor(i / 2) + 1,
            white:  moves[i]   ?? null,
            black:  moves[i+1] ?? null,
        })
    }
    return pairs
}

export function uciSquare(x, y) { return FILES[x] + (8 - y) }

export function isLight(x, y) { return (x + y) % 2 === 0 }

export function squareColor(x, y, light = true) {
    return (x + y) % 2 === 0 ? (light ? '#f0d9b5' : '#b58863') : (light ? '#b58863' : '#f0d9b5')
}


function sign(n) { return n > 0 ? 1 : n < 0 ? -1 : 0 }
function rawColor(p) { return p === p.toUpperCase() ? 'w' : 'b' }

function pathClear(b, fx, fy, tx, ty) {
    const sx = sign(tx - fx), sy = sign(ty - fy)
    let x = fx + sx, y = fy + sy
    while (x !== tx || y !== ty) {
        if (b[y][x] !== null) return false
        x += sx; y += sy
    }
    return true
}

function parseEp(fen) {
    const ep = fen.split(' ')[3] ?? '-'
    if (ep === '-') return null
    const files = { a:0,b:1,c:2,d:3,e:4,f:5,g:6,h:7 }
    return [files[ep[0]], 8 - parseInt(ep[1])]
}

function parseCastling(fen) {
    const c = fen.split(' ')[2] ?? '-'
    return c === '-' ? [] : c.split('')
}

export function canPieceMoveTo(b, fen, piece, fx, fy, tx, ty) {
    const target = b[ty][tx]
    const color  = rawColor(piece)
    const dx = tx - fx, dy = ty - fy
    const type = piece.toLowerCase()

    if (target !== null && rawColor(target) === color) return false

    if (type === 'p') {
        const dir = color === 'w' ? -1 : 1, startRow = color === 'w' ? 6 : 1
        if (dx === 0 && dy === dir && target === null) return true
        if (dx === 0 && dy === 2*dir && fy === startRow && target === null && b[fy+dir][fx] === null) return true
        if (Math.abs(dx) === 1 && dy === dir && target !== null) return true
        if (Math.abs(dx) === 1 && dy === dir && target === null) {
            const ep = parseEp(fen)
            if (ep && ep[0] === tx && ep[1] === ty) return true
        }
        return false
    }
    if (type === 'n') return (Math.abs(dx)===1&&Math.abs(dy)===2)||(Math.abs(dx)===2&&Math.abs(dy)===1)
    if (type === 'b') return Math.abs(dx)===Math.abs(dy) && dx!==0 && pathClear(b,fx,fy,tx,ty)
    if (type === 'r') return (dx===0||dy===0) && !(dx===0&&dy===0) && pathClear(b,fx,fy,tx,ty)
    if (type === 'q') {
        return ((dx===0||dy===0)&&!(dx===0&&dy===0) || Math.abs(dx)===Math.abs(dy)&&dx!==0) && pathClear(b,fx,fy,tx,ty)
    }
    if (type === 'k') {
        if (Math.abs(dx) === 2 && dy === 0) {
            const castling = parseCastling(fen)
            const kingside = tx > fx
            const right = color === 'w' ? (kingside?'K':'Q') : (kingside?'k':'q')
            if (!castling.includes(right)) return false
            const rookX = kingside ? 7 : 0, stepX = kingside ? 1 : -1
            let cx = fx + stepX
            while (cx !== rookX) { if (b[fy][cx] !== null) return false; cx += stepX }
            return true
        }
        return Math.abs(dx)<=1 && Math.abs(dy)<=1 && !(dx===0&&dy===0)
    }
    return false
}

function findKingRaw(b, color) {
    const king = color === 'w' ? 'K' : 'k'
    for (let y = 0; y < 8; y++)
        for (let x = 0; x < 8; x++)
            if (b[y][x] === king) return [x, y]
    return null
}

function isAttacked(b, x, y, color) {
    const enemy = color === 'w' ? 'b' : 'w'
    for (let ey = 0; ey < 8; ey++) {
        for (let ex = 0; ex < 8; ex++) {
            const p = b[ey][ex]
            if (!p || rawColor(p) !== enemy) continue
            const dx = x-ex, dy = y-ey, type = p.toLowerCase()
            if (type==='p') { const dir=rawColor(p)==='w'?-1:1; if(Math.abs(dx)===1&&dy===dir) return true }
            else if (type==='n') { if((Math.abs(dx)===1&&Math.abs(dy)===2)||(Math.abs(dx)===2&&Math.abs(dy)===1)) return true }
            else if (type==='b') { if(Math.abs(dx)===Math.abs(dy)&&dx!==0&&pathClear(b,ex,ey,x,y)) return true }
            else if (type==='r') { if((dx===0||dy===0)&&!(dx===0&&dy===0)&&pathClear(b,ex,ey,x,y)) return true }
            else if (type==='q') { const s=(dx===0||dy===0)&&!(dx===0&&dy===0),d=Math.abs(dx)===Math.abs(dy)&&dx!==0; if((s||d)&&pathClear(b,ex,ey,x,y)) return true }
            else if (type==='k') { if(Math.abs(dx)<=1&&Math.abs(dy)<=1&&!(dx===0&&dy===0)) return true }
        }
    }
    return false
}

function moveLeavesCheck(b, fen, color, fx, fy, tx, ty) {
    const copy = b.map(r => [...r])
    const piece = copy[fy][fx]
    copy[ty][tx] = piece
    copy[fy][fx] = null

    if (piece && piece.toLowerCase() === 'p' && tx !== fx && b[ty][tx] === null) {
        const ep = parseEp(fen)
        if (ep && ep[0] === tx && ep[1] === ty) {
            copy[fy][tx] = null
        }
    }

    const kp = findKingRaw(copy, color)
    return kp ? isAttacked(copy, kp[0], kp[1], color) : false
}

export function getLegalMoves(rawBoard, fen, fx, fy) {
    const piece = rawBoard[fy][fx]
    if (!piece) return []
    const color = rawColor(piece)
    const moves = []
    for (let ty = 0; ty < 8; ty++)
        for (let tx = 0; tx < 8; tx++) {
            if (fx===tx && fy===ty) continue
            if (!canPieceMoveTo(rawBoard, fen, piece, fx, fy, tx, ty)) continue
            if (!moveLeavesCheck(rawBoard, fen, color, fx, fy, tx, ty)) moves.push([tx, ty])
        }
    return moves
}

// ─── Stockfish worker helper ──────────────────────────────────────────────────

export function createStockfish(onBestMove, onScore, path = '/engines/stockfish.js') {
    const worker = new Worker(path)
    let expectedFen = ''
    let pendingFen = ''

    worker.onmessage = (e) => {
        const msg = e.data
        if (typeof msg !== 'string') return

        if (msg.includes('score') && onScore) {
            const fenTurn = expectedFen.split(' ')[1] ?? 'w'
            const mateMatch = msg.match(/score mate (-?\d+)/)
            if (mateMatch) {
                const v = parseInt(mateMatch[1])
                onScore({ cp: v > 0 ? 10000 : -10000, mate: fenTurn === 'w' ? v : -v })
                return
            }
            const cpMatch = msg.match(/score cp (-?\d+)/)
            if (cpMatch) {
                const cp = parseInt(cpMatch[1])
                onScore({ cp: fenTurn === 'w' ? cp : -cp, mate: null })
            }
        }

        if (msg.startsWith('bestmove')) {
            if (!onBestMove) return
            const uci = msg.split(' ')[1]
            if (!uci || uci === '(none)') { onBestMove(null); return }
            if (pendingFen !== expectedFen) return
            const files = { a:0,b:1,c:2,d:3,e:4,f:5,g:6,h:7 }
            onBestMove({
                from: { x: files[uci[0]], y: 8 - parseInt(uci[1]) },
                to:   { x: files[uci[2]], y: 8 - parseInt(uci[3]) },
                uci,
            })
        }
    }

    worker.postMessage('uci')
    worker.postMessage('isready')

    return {
        analyse(fen, movetime = 800) {
            expectedFen = fen
            pendingFen  = fen
            worker.postMessage('stop')
            worker.postMessage(`position fen ${fen}`)
            worker.postMessage(`go movetime ${movetime}`)
        },
        terminate() { worker.terminate() },
    }
}
