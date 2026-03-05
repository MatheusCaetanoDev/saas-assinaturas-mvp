<?php

namespace Tests\Feature;

use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AssinaturaMvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
    }

    public function test_usuario_consegue_cadastrar_entrar_e_buscar_perfil(): void
    {
        $respostaCadastro = $this->postJson('/api/v1/autenticacao/cadastrar', [
            'nome' => 'Joao da Silva',
            'email' => 'joao@example.com',
            'senha' => 'segredo123',
            'nome_empresa' => 'Acme Ltda',
        ]);

        $respostaCadastro
            ->assertCreated()
            ->assertJsonPath('dados.usuario.email', 'joao@example.com')
            ->assertJsonPath('dados.usuario.empresa.nome', 'Acme Ltda');

        $token = $respostaCadastro->json('meta.token');

        $this->getJson('/api/v1/autenticacao/eu', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('dados.usuario.email', 'joao@example.com');

        $this->postJson('/api/v1/autenticacao/entrar', [
            'email' => 'joao@example.com',
            'senha' => 'segredo123',
            'nome_dispositivo' => 'suite-teste',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'meta' => ['token'],
            ]);
    }

    public function test_limite_de_projetos_e_respeitado_no_plano_gratis_e_liberado_no_pro(): void
    {
        $token = $this->cadastrarEObterToken();

        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/projetos', [
                'nome' => "Projeto {$i}",
            ], [
                'Authorization' => "Bearer {$token}",
            ])->assertCreated();
        }

        $this->postJson('/api/v1/projetos', [
            'nome' => 'Projeto 4',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('limite');

        $this->postJson('/api/v1/assinaturas', [
            'codigo_plano' => 'pro',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();

        $this->postJson('/api/v1/projetos', [
            'nome' => 'Projeto apos upgrade',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();
    }

    public function test_pode_cancelar_e_reativar_assinatura(): void
    {
        $token = $this->cadastrarEObterToken();

        $this->postJson('/api/v1/assinaturas', [
            'codigo_plano' => 'pro',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();

        $this->postJson('/api/v1/assinaturas/cancelar', [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('dados.assinatura.status', 'cancelada');

        $this->postJson('/api/v1/assinaturas/reativar', [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('dados.assinatura.status', 'ativa');
    }

    private function cadastrarEObterToken(): string
    {
        $resposta = $this->postJson('/api/v1/autenticacao/cadastrar', [
            'nome' => 'Maria Oliveira',
            'email' => sprintf('maria-%s@example.com', uniqid()),
            'senha' => 'segredo123',
            'nome_empresa' => 'Empresa Teste',
        ]);

        $resposta->assertCreated();

        return $resposta->json('meta.token');
    }
}
