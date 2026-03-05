<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmpresaController extends Controller
{
    public function mostrar(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        return response()->json([
            'dados' => [
                'empresa' => $usuario->empresa,
            ],
        ]);
    }

    public function atualizar(Request $request): JsonResponse
    {
        $dadosValidados = $request->validate([
            'nome' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();
        $empresa = $usuario->empresa;

        if ($empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $slug = $empresa->slug;

        if ($empresa->nome !== $dadosValidados['nome']) {
            $slug = $this->resolverSlugEmpresaUnico($dadosValidados['nome'], $empresa->id);
        }

        $empresa->update([
            'nome' => $dadosValidados['nome'],
            'slug' => $slug,
        ]);

        return response()->json([
            'mensagem' => 'Empresa atualizada com sucesso.',
            'dados' => [
                'empresa' => $empresa->refresh(),
            ],
        ]);
    }

    private function resolverSlugEmpresaUnico(string $nome, int $ignorarEmpresaId): string
    {
        $slugBase = Str::slug($nome);
        $slugBase = $slugBase === '' ? 'empresa' : $slugBase;

        $slug = $slugBase;
        $contador = 1;

        while (Empresa::query()
            ->where('slug', $slug)
            ->where('id', '!=', $ignorarEmpresaId)
            ->exists()) {
            $slug = sprintf('%s-%d', $slugBase, $contador);
            $contador++;
        }

        return $slug;
    }
}
