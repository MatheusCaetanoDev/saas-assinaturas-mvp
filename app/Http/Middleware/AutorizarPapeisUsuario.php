<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutorizarPapeisUsuario
{
    public function handle(Request $request, Closure $next, string ...$papeisPermitidos): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            return $this->naoAutorizado('Usuario nao autenticado.');
        }

        if ($papeisPermitidos === []) {
            return $next($request);
        }

        if (! $usuario->possuiPapel(...$papeisPermitidos)) {
            return $this->proibido('Seu perfil nao tem permissao para esta operacao.');
        }

        return $next($request);
    }

    private function naoAutorizado(string $mensagem): JsonResponse
    {
        return response()->json([
            'mensagem' => $mensagem,
        ], 401);
    }

    private function proibido(string $mensagem): JsonResponse
    {
        return response()->json([
            'mensagem' => $mensagem,
        ], 403);
    }
}
