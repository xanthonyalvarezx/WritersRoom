<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PieceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/create', [PieceController::class, 'create'])->name('pieces.create');
    Route::get('/create/{kind}', [PieceController::class, 'write'])->name('pieces.write');
    Route::get('/my-writings', [PieceController::class, 'index'])->name('pieces.index');
    Route::redirect('/pieces', '/my-writings');
    Route::get('/pieces/upload', [PieceController::class, 'uploadForm'])->name('pieces.upload');
    Route::post('/pieces/upload', [PieceController::class, 'upload'])->name('pieces.upload.store');
    Route::post('/pieces', [PieceController::class, 'store'])->name('pieces.store');
    Route::get('/pieces/{piece}/edit', [PieceController::class, 'edit'])->name('pieces.edit');
    Route::get('/pieces/{piece}/review', [PieceController::class, 'review'])->name('pieces.review');
    Route::post('/pieces/{piece}/submit', [PieceController::class, 'submit'])->name('pieces.submit');
    Route::post('/pieces/{piece}/continue', [PieceController::class, 'continueWriting'])->name('pieces.continue');
    Route::patch('/pieces/{piece}', [PieceController::class, 'update'])->name('pieces.update');
    Route::get('/pieces/{piece}', [PieceController::class, 'show'])->name('pieces.show');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
