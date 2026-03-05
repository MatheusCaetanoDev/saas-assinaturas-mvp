<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto;
use App\Models\Usuario;
use App\Services\ServicoAssinatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    public function __construct(private readonly ServicoAssinatura $servicoAssinatura) {}

    public function listar(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $projetos = $usuario->empresa
            ->projetos()
            ->latest('id')
            ->get();

        return response()->json([
            'dados' => [
                'projetos' => $projetos,
                'projetos_restantes' => $this->servicoAssinatura->obterProjetosRestantes($usuario->empresa),
            ],
        ]);
    }

    public function criar(Request $request): JsonResponse
    {
        $dadosValidados = $request->validate([
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'status' => ['nullable', 'in:rascunho,ativo,arquivado'],
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $this->servicoAssinatura->validarLimiteProjetos($usuario->empresa);

        $projeto = $usuario->empresa->projetos()->create([
            'nome' => $dadosValidados['nome'],
            'descricao' => $dadosValidados['descricao'] ?? null,
            'status' => $dadosValidados['status'] ?? 'rascunho',
        ]);

        return response()->json([
            'mensagem' => 'Projeto criado com sucesso.',
            'dados' => [
                'projeto' => $projeto,
            ],
        ], 201);
    }

    public function mostrar(Request $request, int $projetoId): JsonResponse
    {
        $projeto = $this->resolverProjetoDaEmpresaAtual($request, $projetoId);

        if ($projeto === null) {
            return response()->json([
                'mensagem' => 'Projeto nao encontrado.',
            ], 404);
        }

        return response()->json([
            'dados' => [
                'projeto' => $projeto,
            ],
        ]);
    }

    public function atualizar(Request $request, int $projetoId): JsonResponse
    {
        $dadosValidados = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'descricao' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:rascunho,ativo,arquivado'],
        ]);

        $projeto = $this->resolverProjetoDaEmpresaAtual($request, $projetoId);

        if ($projeto === null) {
            return response()->json([
                'mensagem' => 'Projeto nao encontrado.',
            ], 404);
        }

        $projeto->update($dadosValidados);

        return response()->json([
            'mensagem' => 'Projeto atualizado com sucesso.',
            'dados' => [
                'projeto' => $projeto->refresh(),
            ],
        ]);
    }

    public function remover(Request $request, int $projetoId): JsonResponse
    {
        $projeto = $this->resolverProjetoDaEmpresaAtual($request, $projetoId);

        if ($projeto === null) {
            return response()->json([
                'mensagem' => 'Projeto nao encontrado.',
            ], 404);
        }

        $projeto->delete();

        return response()->json([], 204);
    }

    private function resolverProjetoDaEmpresaAtual(Request $request, int $projetoId): ?Projeto
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return null;
        }

        return $usuario->empresa
            ->projetos()
            ->where('id', $projetoId)
            ->first();
    }
}
