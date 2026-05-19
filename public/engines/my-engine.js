/**
 * MyEngine — Chess engine Web Worker (single file, no imports needed)
 *
 * Structure:
 *   POSITION  — FEN parsing, move application
 *   MOVEGEN   — move generation, check detection
 *   EVAL      — position evaluation
 *   SEARCH    — move selection (replace findBestMove for stronger play)
 *   UCI       — protocol handler
 */

'use strict';

// ═══════════════════════════════════════════════════════════
// POSITION
// ═══════════════════════════════════════════════════════════

const FILES = ['a','b','c','d','e','f','g','h'];

function parseFen(fen) {
    const parts = fen.split(' ');
    return {
        board:    parsePieces(parts[0]),
        turn:     parts[1] ?? 'w',
        castling: parts[2] ?? '-',
        ep:       parts[3] ?? '-',
    };
}

function parsePieces(position) {
    const board = Array.from({ length: 8 }, () => Array(8).fill(null));
    let y = 0;
    for (const row of position.split('/')) {
        let x = 0;
        for (const ch of row) {
            if (!isNaN(ch)) x += Number(ch);
            else { board[y][x] = ch; x++; }
        }
        y++;
    }
    return board;
}

function boardToFen(board, turn, castling, ep) {
    const rows = board.map(row => {
        let s = '', empty = 0;
        for (const cell of row) {
            if (!cell) { empty++; }
            else { if (empty) { s += empty; empty = 0; } s += cell; }
        }
        if (empty) s += empty;
        return s;
    });
    return `${rows.join('/')} ${turn} ${castling || '-'} ${ep || '-'} 0 1`;
}

function pieceColor(p) { return p === p.toUpperCase() ? 'w' : 'b'; }

function pathClear(board, fx, fy, tx, ty) {
    const sx = Math.sign(tx - fx), sy = Math.sign(ty - fy);
    let x = fx + sx, y = fy + sy;
    while (x !== tx || y !== ty) {
        if (board[y][x]) return false;
        x += sx; y += sy;
    }
    return true;
}

function applyUci(fen, uci) {
    const { board, turn, castling, ep } = parseFen(fen);
    const fx = FILES.indexOf(uci[0]), fy = 8 - parseInt(uci[1]);
    const tx = FILES.indexOf(uci[2]), ty = 8 - parseInt(uci[3]);
    const promo = uci[4] ?? null;
    const piece = board[fy][fx];
    const nb    = board.map(r => [...r]);

    if (piece.toLowerCase() === 'p' && tx !== fx && !board[ty][tx]) nb[fy][tx] = null;

    nb[ty][tx] = promo ? (turn === 'w' ? promo.toUpperCase() : promo.toLowerCase()) : piece;
    nb[fy][fx] = null;

    if (piece.toLowerCase() === 'k' && Math.abs(tx - fx) === 2) {
        const kingside = tx > fx;
        nb[fy][kingside ? 5 : 3] = nb[fy][kingside ? 7 : 0];
        nb[fy][kingside ? 7 : 0] = null;
    }

    let nc = castling;
    if (piece === 'K') nc = nc.replace(/[KQ]/g, '');
    if (piece === 'k') nc = nc.replace(/[kq]/g, '');
    if (piece === 'R' && fx === 7 && fy === 7) nc = nc.replace('K', '');
    if (piece === 'R' && fx === 0 && fy === 7) nc = nc.replace('Q', '');
    if (piece === 'r' && fx === 7 && fy === 0) nc = nc.replace('k', '');
    if (piece === 'r' && fx === 0 && fy === 0) nc = nc.replace('q', '');
    if (tx === 7 && ty === 7) nc = nc.replace('K', '');
    if (tx === 0 && ty === 7) nc = nc.replace('Q', '');
    if (tx === 7 && ty === 0) nc = nc.replace('k', '');
    if (tx === 0 && ty === 0) nc = nc.replace('q', '');
    if (!nc) nc = '-';

    let newEp = '-';
    if (piece.toLowerCase() === 'p' && Math.abs(ty - fy) === 2)
        newEp = FILES[fx] + (8 - Math.round((fy + ty) / 2));

    return boardToFen(nb, turn === 'w' ? 'b' : 'w', nc, newEp);
}

function applyMoves(fen, moves) {
    let cur = fen;
    for (const uci of moves) cur = applyUci(cur, uci);
    return cur;
}

// ═══════════════════════════════════════════════════════════
// MOVEGEN
// ═══════════════════════════════════════════════════════════

function toUci(fx, fy, tx, ty, promo) {
    return FILES[fx] + (8 - fy) + FILES[tx] + (8 - ty) + (promo ?? '');
}

function generateLegalMoves(fen) {
    const { board, turn, castling, ep } = parseFen(fen);
    return generatePseudoMoves(board, turn, castling, ep).filter(uci => {
        const { board: nb } = parseFen(applyUci(fen, uci));
        return !isInCheck(nb, turn);
    });
}

function generatePseudoMoves(board, turn, castling, ep) {
    const moves = [];
    for (let fy = 0; fy < 8; fy++) {
        for (let fx = 0; fx < 8; fx++) {
            const piece = board[fy][fx];
            if (!piece || pieceColor(piece) !== turn) continue;
            const t = piece.toLowerCase();
            if (t === 'p') addPawnMoves(moves, board, piece, fx, fy, ep, turn);
            else if (t === 'n') addKnightMoves(moves, board, fx, fy, turn);
            else if (t === 'b') addSliding(moves, board, fx, fy, turn, [[-1,-1],[-1,1],[1,-1],[1,1]]);
            else if (t === 'r') addSliding(moves, board, fx, fy, turn, [[-1,0],[1,0],[0,-1],[0,1]]);
            else if (t === 'q') addSliding(moves, board, fx, fy, turn, [[-1,-1],[-1,1],[1,-1],[1,1],[-1,0],[1,0],[0,-1],[0,1]]);
            else if (t === 'k') addKingMoves(moves, board, fx, fy, turn, castling);
        }
    }
    return moves;
}

function addPawnMoves(moves, board, piece, fx, fy, ep, turn) {
    const dir = turn === 'w' ? -1 : 1, startRow = turn === 'w' ? 6 : 1, promoRow = turn === 'w' ? 0 : 7;
    const push = (tx, ty) => {
        if (ty === promoRow) ['q','r','b','n'].forEach(p => moves.push(toUci(fx, fy, tx, ty, p)));
        else moves.push(toUci(fx, fy, tx, ty));
    };
    if (!board[fy + dir]?.[fx]) {
        push(fx, fy + dir);
        if (fy === startRow && !board[fy + 2 * dir]?.[fx]) push(fx, fy + 2 * dir);
    }
    for (const dx of [-1, 1]) {
        const tx = fx + dx, ty = fy + dir;
        if (tx < 0 || tx > 7) continue;
        const target = board[ty]?.[tx];
        if (target && pieceColor(target) !== turn) push(tx, ty);
        if (!target && ep !== '-' && tx === FILES.indexOf(ep[0]) && ty === 8 - parseInt(ep[1])) push(tx, ty);
    }
}

function addKnightMoves(moves, board, fx, fy, turn) {
    for (const [dx, dy] of [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]]) {
        const tx = fx + dx, ty = fy + dy;
        if (tx < 0 || tx > 7 || ty < 0 || ty > 7) continue;
        const t = board[ty][tx];
        if (!t || pieceColor(t) !== turn) moves.push(toUci(fx, fy, tx, ty));
    }
}

function addSliding(moves, board, fx, fy, turn, dirs) {
    for (const [dx, dy] of dirs) {
        let tx = fx + dx, ty = fy + dy;
        while (tx >= 0 && tx < 8 && ty >= 0 && ty < 8) {
            const t = board[ty][tx];
            if (t) { if (pieceColor(t) !== turn) moves.push(toUci(fx, fy, tx, ty)); break; }
            moves.push(toUci(fx, fy, tx, ty));
            tx += dx; ty += dy;
        }
    }
}

function addKingMoves(moves, board, fx, fy, turn, castling) {
    for (const [dx, dy] of [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]]) {
        const tx = fx + dx, ty = fy + dy;
        if (tx < 0 || tx > 7 || ty < 0 || ty > 7) continue;
        const t = board[ty][tx];
        if (!t || pieceColor(t) !== turn) moves.push(toUci(fx, fy, tx, ty));
    }
    if (turn === 'w' && fy === 7 && fx === 4) {
        if (castling.includes('K') && !board[7][5] && !board[7][6]) moves.push('e1g1');
        if (castling.includes('Q') && !board[7][3] && !board[7][2] && !board[7][1]) moves.push('e1c1');
    }
    if (turn === 'b' && fy === 0 && fx === 4) {
        if (castling.includes('k') && !board[0][5] && !board[0][6]) moves.push('e8g8');
        if (castling.includes('q') && !board[0][3] && !board[0][2] && !board[0][1]) moves.push('e8c8');
    }
}

function isInCheck(board, color) {
    const king = color === 'w' ? 'K' : 'k';
    for (let y = 0; y < 8; y++)
        for (let x = 0; x < 8; x++)
            if (board[y][x] === king) return isAttacked(board, x, y, color);
    return false;
}

function isAttacked(board, x, y, color) {
    const enemy = color === 'w' ? 'b' : 'w';
    for (let ey = 0; ey < 8; ey++) {
        for (let ex = 0; ex < 8; ex++) {
            const p = board[ey][ex];
            if (!p || pieceColor(p) !== enemy) continue;
            const dx = x - ex, dy = y - ey, t = p.toLowerCase();
            if (t==='p') { const d=enemy==='w'?-1:1; if(Math.abs(dx)===1&&dy===d) return true; }
            else if (t==='n') { if((Math.abs(dx)===1&&Math.abs(dy)===2)||(Math.abs(dx)===2&&Math.abs(dy)===1)) return true; }
            else if (t==='b') { if(Math.abs(dx)===Math.abs(dy)&&dx!==0&&pathClear(board,ex,ey,x,y)) return true; }
            else if (t==='r') { if((dx===0||dy===0)&&!(dx===0&&dy===0)&&pathClear(board,ex,ey,x,y)) return true; }
            else if (t==='q') { const s=(dx===0||dy===0)&&!(dx===0&&dy===0),d=Math.abs(dx)===Math.abs(dy)&&dx!==0; if((s||d)&&pathClear(board,ex,ey,x,y)) return true; }
            else if (t==='k') { if(Math.abs(dx)<=1&&Math.abs(dy)<=1&&!(dx===0&&dy===0)) return true; }
        }
    }
    return false;
}

// ═══════════════════════════════════════════════════════════
// EVAL
// ═══════════════════════════════════════════════════════════

const PIECE_VALUES = { p:100, n:320, b:330, r:500, q:900, k:0 };

function evaluate(board) {
    let score = 0;
    for (let y = 0; y < 8; y++)
        for (let x = 0; x < 8; x++) {
            const p = board[y][x];
            if (!p) continue;
            const v = PIECE_VALUES[p.toLowerCase()] ?? 0;
            score += p === p.toUpperCase() ? v : -v;
        }
    return score;
}

// ═══════════════════════════════════════════════════════════
// SEARCH
// ═══════════════════════════════════════════════════════════

/**
 * Replace this function to implement a stronger engine.
 * Currently selects a random legal move.
 */
function findBestMove(fen) {
    const moves = generateLegalMoves(fen);
    if (!moves.length) return null;
    return moves[Math.floor(Math.random() * moves.length)];
}

// ═══════════════════════════════════════════════════════════
// UCI
// ═══════════════════════════════════════════════════════════

let currentFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

function send(msg) { self.postMessage(msg); }

self.onmessage = function(e) {
    const cmd = (e.data ?? '').trim();

    if (cmd === 'uci') {
        send('id name MyEngine');
        send('id author You');
        send('uciok');
        return;
    }
    if (cmd === 'isready') { send('readyok'); return; }
    if (cmd === 'stop' || cmd === 'quit') return;

    if (cmd.startsWith('position fen ')) {
        const rest     = cmd.slice('position fen '.length);
        const movesIdx = rest.indexOf(' moves ');
        const fen      = movesIdx === -1 ? rest : rest.slice(0, movesIdx);
        const moves    = movesIdx === -1 ? [] : rest.slice(movesIdx + 7).split(' ').filter(Boolean);
        currentFen     = applyMoves(fen, moves);
        return;
    }

    if (cmd.startsWith('go')) {
        const best = findBestMove(currentFen);
        send('info depth 1 score cp 0');
        send(`bestmove ${best ?? '(none)'}`);
        return;
    }
};
