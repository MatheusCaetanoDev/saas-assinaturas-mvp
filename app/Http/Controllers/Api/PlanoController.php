<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\JsonResponse;

class PlanoController extends Controller
{
    public function listar(): JsonResponse
    {
        $planos = Plano::query()
            ->where('ativo', true)
            ->orderBy('preco_mensal')
            ->get();

        return response()->json([
            'dados' => [
                'planos' => $planos,
            ],
        ]);
    }
}
