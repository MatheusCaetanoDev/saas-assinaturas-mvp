<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenApi extends Model
{
    use HasFactory;

    protected $table = 'tokens_api';

    protected $fillable = [
        'usuario_id',
        'nome',
        'hash_token',
        'ultimo_uso_em',
        'expira_em',
    ];

    protected $hidden = [
        'hash_token',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_uso_em' => 'datetime',
            'expira_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
