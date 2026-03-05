<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    use HasFactory;

    protected $table = 'planos';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'limite_projetos',
        'preco_mensal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'limite_projetos' => 'integer',
            'preco_mensal' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }
}
