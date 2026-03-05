<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacaoAssinaturaVencendoJob;
use App\Models\Assinatura;
use Illuminate\Console\Command;

class NotificarAssinaturasVencendoCommand extends Command
{
    protected $signature = 'assinaturas:notificar-vencendo {--dias=3 : Quantidade de dias a frente para notificar}';

    protected $description = 'Enfileira notificacoes de assinaturas ativas proximas do vencimento';

    public function handle(): int
    {
        $dias = max(1, (int) $this->option('dias'));
        $inicio = now();
        $fim = now()->addDays($dias);

        $totalEnfileirado = 0;

        Assinatura::query()
            ->ativa()
            ->whereNotNull('termina_em')
            ->whereBetween('termina_em', [$inicio, $fim])
            ->chunkById(100, function ($assinaturas) use (&$totalEnfileirado): void {
                foreach ($assinaturas as $assinatura) {
                    EnviarNotificacaoAssinaturaVencendoJob::dispatch($assinatura->id);
                    $totalEnfileirado++;
                }
            });

        $this->info("Foram enfileirados {$totalEnfileirado} jobs de notificacao de expiracao.");

        return self::SUCCESS;
    }
}
