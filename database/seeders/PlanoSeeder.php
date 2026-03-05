<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        Plano::query()->updateOrCreate(
            ['codigo' => 'gratis'],
            [
                'nome' => 'Gratis',
                'descricao' => 'Plano inicial para equipes pequenas.',
                'limite_projetos' => 3,
                'preco_mensal' => 0,
                'ativo' => true,
            ]
        );

        Plano::query()->updateOrCreate(
            ['codigo' => 'pro'],
            [
                'nome' => 'Pro',
                'descricao' => 'Limites maiores para empresas em crescimento.',
                'limite_projetos' => 50,
                'preco_mensal' => 99.90,
                'ativo' => true,
            ]
        );
    }
}
