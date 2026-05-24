<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameReviewController;
use App\Http\Controllers\MoveController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/dashboard', [GameController::class, 'playerGames'])->name('dashboard');
    Route::get('/game', [GameController::class, 'index'])->name('game.index');
    Route::get('/game/create', [GameController::class, 'create'])->name('game.create');
    Route::post('/game', [GameController::class, 'store'])->name('game.store');
    Route::get('/game/{game}', [GameController::class, 'show'])->name('game.show');
    Route::get('/game/{game}/edit', [GameController::class, 'edit'])->name('game.edit');
    Route::patch('/game/{game}', [GameController::class, 'update'])->name('game.update');
    Route::delete('/game/{game}', [GameController::class, 'destroy'])->name('game.destroy');
    Route::post('/game/resign', [GameController::class, 'resign'])->name('game.resign');
    Route::get('/game/{game}/review', [GameReviewController::class, 'show'])->name('game.review');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::post('/moves', [MoveController::class, 'store'])->name('move.store');
    Route::post('/moves/undo', [MoveController::class, 'undo'])->name('move.undo');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','verified','role:admin'])->group(function (){
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin');
    Route::post('/admin/users', [AdminDashboardController::class, 'store'])->name('admin.users.store');
    Route::delete('/admin/users/{id}', [AdminDashboardController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/users/{id}/restore', [AdminDashboardController::class, 'restore'])->name('admin.users.restore');
    Route::post('/admin/users/{id}/toggle-admin', [AdminDashboardController::class, 'toggleAdmin'])->name('admin.users.toggle-admin');
});

require __DIR__.'/auth.php';
