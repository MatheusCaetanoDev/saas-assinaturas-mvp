<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\Usuario;
use App\Services\ServicoAssinatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssinaturaController extends Controller
{
    public function __construct(private readonly ServicoAssinatura $servicoAssinatura) {}

    public function atual(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $assinaturaAtiva = $this->servicoAssinatura->obterAssinaturaAtiva($usuario->empresa);
        $planoAtual = $this->servicoAssinatura->obterPlanoAplicavel($usuario->empresa);

        return response()->json([
            'dados' => [
                'assinatura' => $assinaturaAtiva,
                'plano' => $planoAtual,
                'projetos_restantes' => $this->servicoAssinatura->obterProjetosRestantes($usuario->empresa),
            ],
        ]);
    }

    public function criar(Request $request): JsonResponse
    {
        $dadosValidados = $request->validate([
            'codigo_plano' => ['required', 'string', 'exists:planos,codigo'],
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $plano = Plano::query()
            ->where('codigo', $dadosValidados['codigo_plano'])
            ->where('ativo', true)
            ->first();

        if ($plano === null) {
            return response()->json([
                'mensagem' => 'Plano selecionado indisponivel.',
            ], 422);
        }

        $assinaturaAtiva = $this->servicoAssinatura->obterAssinaturaAtiva($usuario->empresa);

        if ($assinaturaAtiva !== null && $assinaturaAtiva->plano_id === $plano->id) {
            return response()->json([
                'mensagem' => 'A empresa ja esta inscrita neste plano.',
                'dados' => [
                    'assinatura' => $assinaturaAtiva,
                ],
            ]);
        }

        if ($assinaturaAtiva !== null) {
            $assinaturaAtiva->update([
                'status' => 'expirada',
                'termina_em' => now(),
            ]);
        }

        $assinatura = Assinatura::query()->create([
            'empresa_id' => $usuario->empresa->id,
            'plano_id' => $plano->id,
            'criado_por_usuario_id' => $usuario->id,
            'status' => 'ativa',
            'inicia_em' => now(),
            'termina_em' => now()->addDays(30),
        ]);

        return response()->json([
            'mensagem' => 'Assinatura criada com sucesso.',
            'dados' => [
                'assinatura' => $assinatura->load('plano'),
            ],
        ], 201);
    }

    public function cancelar(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $assinatura = $this->servicoAssinatura->obterAssinaturaAtiva($usuario->empresa);

        if ($assinatura === null) {
            return response()->json([
                'mensagem' => 'Nenhuma assinatura ativa para cancelar.',
            ], 404);
        }

        $assinatura->update([
            'status' => 'cancelada',
            'cancelada_em' => now(),
        ]);

        return response()->json([
            'mensagem' => 'Assinatura cancelada com sucesso.',
            'dados' => [
                'assinatura' => $assinatura->refresh()->load('plano'),
            ],
        ]);
    }

    public function reativar(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->empresa === null) {
            return response()->json([
                'mensagem' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $ultimaAssinaturaCancelada = $usuario->empresa
            ->assinaturas()
            ->where('status', 'cancelada')
            ->latest('updated_at')
            ->first();

        if ($ultimaAssinaturaCancelada === null) {
            return response()->json([
                'mensagem' => 'Nenhuma assinatura cancelada disponivel para reativar.',
            ], 404);
        }

        $assinatura = Assinatura::query()->create([
            'empresa_id' => $usuario->empresa->id,
            'plano_id' => $ultimaAssinaturaCancelada->plano_id,
            'criado_por_usuario_id' => $usuario->id,
            'status' => 'ativa',
            'inicia_em' => now(),
            'termina_em' => now()->addDays(30),
        ]);

        return response()->json([
            'mensagem' => 'Assinatura reativada com sucesso.',
            'dados' => [
                'assinatura' => $assinatura->load('plano'),
            ],
        ]);
    }
}
