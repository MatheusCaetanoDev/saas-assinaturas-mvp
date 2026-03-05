<?php

use App\Http\Controllers\Api\AssinaturaController;
use App\Http\Controllers\Api\AutenticacaoController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\ProjetoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/saude', static fn () => response()->json(['status' => 'ok']));

    Route::post('/autenticacao/cadastrar', [AutenticacaoController::class, 'cadastrar']);
    Route::post('/autenticacao/entrar', [AutenticacaoController::class, 'entrar']);

    Route::middleware('autenticar.token_api')->group(function (): void {
        Route::get('/autenticacao/eu', [AutenticacaoController::class, 'eu']);
        Route::post('/autenticacao/sair', [AutenticacaoController::class, 'sair']);

        Route::get('/empresa', [EmpresaController::class, 'mostrar']);
        Route::put('/empresa', [EmpresaController::class, 'atualizar']);

        Route::get('/planos', [PlanoController::class, 'listar']);

        Route::get('/assinaturas/atual', [AssinaturaController::class, 'atual']);
        Route::post('/assinaturas', [AssinaturaController::class, 'criar']);
        Route::post('/assinaturas/cancelar', [AssinaturaController::class, 'cancelar']);
        Route::post('/assinaturas/reativar', [AssinaturaController::class, 'reativar']);

        Route::get('/projetos', [ProjetoController::class, 'listar']);
        Route::post('/projetos', [ProjetoController::class, 'criar']);
        Route::get('/projetos/{projetoId}', [ProjetoController::class, 'mostrar']);
        Route::put('/projetos/{projetoId}', [ProjetoController::class, 'atualizar']);
        Route::delete('/projetos/{projetoId}', [ProjetoController::class, 'remover']);
    });
});
