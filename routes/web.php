<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CorretorController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\SeguradoraController;
use App\Http\Controllers\Web\SolicitacaoController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('seguradoras', SeguradoraController::class);
    Route::resource('corretores', CorretorController::class);
    Route::resource('users', UserController::class)->except(['show']);

    Route::get('/solicitacoes', [SolicitacaoController::class, 'index'])->name('solicitacoes.index');
    Route::get('/solicitacoes/{solicitacao}', [SolicitacaoController::class, 'show'])->name('solicitacoes.show');
    Route::get('/solicitacoes/{solicitacao}/pdf/{sub}', [SolicitacaoController::class, 'downloadPdf'])->name('solicitacoes.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
