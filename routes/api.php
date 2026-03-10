<?php

use App\Http\Controllers\Api\SolicitacaoController;
use App\Http\Controllers\Api\SubSolicitacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('agent.auth')->group(function () {
    Route::post('/solicitacoes', [SolicitacaoController::class, 'store']);
    Route::get('/solicitacoes/{solicitacao}/sub-solicitacoes', [SolicitacaoController::class, 'subSolicitacoes']);
    Route::get('/sub-solicitacoes/{subSolicitacao}', [SubSolicitacaoController::class, 'show']);
    Route::patch('/sub-solicitacoes/{subSolicitacao}', [SubSolicitacaoController::class, 'update']);
});
