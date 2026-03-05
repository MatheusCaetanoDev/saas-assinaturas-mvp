<?php

namespace App\Http\Middleware;

use App\Models\TokenApi;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutenticarComTokenApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenBearer = $request->bearerToken();

        if ($tokenBearer === null || ! str_contains($tokenBearer, '|')) {
            return $this->naoAutorizado('Token ausente ou mal formatado.');
        }

        [$tokenId, $tokenTextoPuro] = explode('|', $tokenBearer, 2);

        if (! ctype_digit($tokenId) || $tokenTextoPuro === '') {
            return $this->naoAutorizado('Formato de token invalido.');
        }

        $tokenApi = TokenApi::query()
            ->with('usuario.empresa')
            ->find((int) $tokenId);

        if ($tokenApi === null) {
            return $this->naoAutorizado('Token nao encontrado.');
        }

        $hashInformado = hash('sha256', $tokenTextoPuro);

        if (! hash_equals($tokenApi->hash_token, $hashInformado)) {
            return $this->naoAutorizado('Token invalido.');
        }

        if ($tokenApi->expira_em !== null && $tokenApi->expira_em->isPast()) {
            $tokenApi->delete();

            return $this->naoAutorizado('Token expirado.');
        }

        $tokenApi->forceFill(['ultimo_uso_em' => now()])->save();

        Auth::setUser($tokenApi->usuario);
        $request->setUserResolver(static fn () => $tokenApi->usuario);
        $request->attributes->set('token_api', $tokenApi);

        return $next($request);
    }

    private function naoAutorizado(string $mensagem): JsonResponse
    {
        return response()->json([
            'mensagem' => $mensagem,
        ], 401);
    }
}
