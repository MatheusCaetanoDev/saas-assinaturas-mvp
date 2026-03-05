<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assinatura extends Model
{
    use HasFactory;

    protected $table = 'assinaturas';

    protected $fillable = [
        'empresa_id',
        'plano_id',
        'criado_por_usuario_id',
        'status',
        'inicia_em',
        'termina_em',
        'cancelada_em',
    ];

    protected function casts(): array
    {
        return [
            'inicia_em' => 'datetime',
            'termina_em' => 'datetime',
            'cancelada_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function criadoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_usuario_id');
    }

    public function scopeAtiva(Builder $query): Builder
    {
        return $query
            ->where('status', 'ativa')
            ->where(function (Builder $interna): void {
                $interna
                    ->whereNull('termina_em')
                    ->orWhere('termina_em', '>', now());
            });
    }
}
