<?php

namespace App\Jobs;

use App\Models\Assinatura;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarNotificacaoAssinaturaVencendoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $assinaturaId) {}

    public function handle(): void
    {
        $assinatura = Assinatura::query()
            ->with(['empresa', 'plano'])
            ->find($this->assinaturaId);

        if ($assinatura === null || $assinatura->status !== 'ativa' || $assinatura->termina_em === null) {
            return;
        }

        if ($assinatura->termina_em->isPast()) {
            return;
        }

        Log::info('Assinatura proxima do vencimento.', [
            'assinatura_id' => $assinatura->id,
            'empresa_id' => $assinatura->empresa_id,
            'codigo_plano' => $assinatura->plano?->codigo,
            'termina_em' => $assinatura->termina_em?->toIso8601String(),
        ]);
    }
}
