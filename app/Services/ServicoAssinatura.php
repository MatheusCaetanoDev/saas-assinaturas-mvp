<?php

namespace App\Services;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Plano;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ServicoAssinatura
{
    public function obterAssinaturaAtiva(Empresa $empresa): ?Assinatura
    {
        return $empresa
            ->assinaturas()
            ->with('plano')
            ->ativa()
            ->orderByDesc('termina_em')
            ->first();
    }

    public function obterPlanoAplicavel(Empresa $empresa): Plano
    {
        $assinaturaAtiva = $this->obterAssinaturaAtiva($empresa);

        if ($assinaturaAtiva !== null) {
            return $assinaturaAtiva->plano;
        }

        $planoPadrao = Plano::query()
            ->where('codigo', 'gratis')
            ->where('ativo', true)
            ->first();

        if ($planoPadrao === null) {
            throw new RuntimeException('Plano gratis padrao nao foi encontrado.');
        }

        return $planoPadrao;
    }

    public function validarLimiteProjetos(Empresa $empresa): void
    {
        $plano = $this->obterPlanoAplicavel($empresa);

        if ($plano->limite_projetos === null) {
            return;
        }

        $quantidadeProjetos = $empresa->projetos()->count();

        if ($quantidadeProjetos >= $plano->limite_projetos) {
            throw ValidationException::withMessages([
                'limite' => sprintf(
                    'Limite de projetos atingido para o plano %s (%d projetos).',
                    $plano->codigo,
                    $plano->limite_projetos
                ),
            ]);
        }
    }

    public function obterProjetosRestantes(Empresa $empresa): ?int
    {
        $plano = $this->obterPlanoAplicavel($empresa);

        if ($plano->limite_projetos === null) {
            return null;
        }

        $restantes = $plano->limite_projetos - $empresa->projetos()->count();

        return max(0, $restantes);
    }
}
