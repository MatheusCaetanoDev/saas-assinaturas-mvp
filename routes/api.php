<?php

use App\Http\Controllers\Api\AssinaturaController;
use App\Http\Controllers\Api\AutenticacaoController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\ProjetoController;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/saude', static fn () => response()->json(['status' => 'ok']));

    Route::post('/autenticacao/cadastrar', [AutenticacaoController::class, 'cadastrar']);
    Route::post('/autenticacao/entrar', [AutenticacaoController::class, 'entrar']);

    Route::middleware('autenticar.token_api')->group(function (): void {
        Route::get('/autenticacao/eu', [AutenticacaoController::class, 'eu']);
        Route::post('/autenticacao/sair', [AutenticacaoController::class, 'sair']);

        Route::get('/empresa', [EmpresaController::class, 'mostrar']);
        Route::put('/empresa', [EmpresaController::class, 'atualizar'])
            ->middleware('autorizar.papeis:'.Usuario::PAPEL_OWNER);

        Route::get('/planos', [PlanoController::class, 'listar']);

        Route::get('/assinaturas/atual', [AssinaturaController::class, 'atual']);
        Route::middleware('autorizar.papeis:'.Usuario::PAPEL_OWNER.','.Usuario::PAPEL_ADMIN)->group(function (): void {
            Route::post('/assinaturas', [AssinaturaController::class, 'criar']);
            Route::post('/assinaturas/cancelar', [AssinaturaController::class, 'cancelar']);
            Route::post('/assinaturas/reativar', [AssinaturaController::class, 'reativar']);
        });

        Route::get('/projetos', [ProjetoController::class, 'listar']);
        Route::post('/projetos', [ProjetoController::class, 'criar']);
        Route::get('/projetos/{projetoId}', [ProjetoController::class, 'mostrar']);
        Route::put('/projetos/{projetoId}', [ProjetoController::class, 'atualizar'])
            ->middleware('autorizar.papeis:'.Usuario::PAPEL_OWNER.','.Usuario::PAPEL_ADMIN);
        Route::delete('/projetos/{projetoId}', [ProjetoController::class, 'remover'])
            ->middleware('autorizar.papeis:'.Usuario::PAPEL_OWNER.','.Usuario::PAPEL_ADMIN);
    });
});
