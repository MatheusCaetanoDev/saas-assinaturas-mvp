<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UsuarioFactory> */
    use HasFactory, Notifiable;

    public const PAPEL_OWNER = 'owner';

    public const PAPEL_ADMIN = 'admin';

    public const PAPEL_MEMBER = 'member';

    /**
     * @var list<string>
     */
    public const PAPEIS_VALIDOS = [
        self::PAPEL_OWNER,
        self::PAPEL_ADMIN,
        self::PAPEL_MEMBER,
    ];

    protected $table = 'usuarios';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'empresa_id',
        'papel',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'senha',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verificado_em' => 'datetime',
            'senha' => 'hashed',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tokensApi(): HasMany
    {
        return $this->hasMany(TokenApi::class);
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function possuiPapel(string ...$papeisPermitidos): bool
    {
        return in_array($this->papel, $papeisPermitidos, true);
    }
}
