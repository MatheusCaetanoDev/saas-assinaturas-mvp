<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
            ->assertJsonPath('dados.usuario.empresa.nome', 'Acme Ltda')
            ->assertJsonPath('dados.usuario.papel', Usuario::PAPEL_OWNER);

        $token = $respostaCadastro->json('meta.token');

        $this->getJson('/api/v1/autenticacao/eu', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('dados.usuario.email', 'joao@example.com')
            ->assertJsonPath('dados.usuario.papel', Usuario::PAPEL_OWNER);

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
        $dadosOwner = $this->cadastrarUsuarioEObterDados();
        $token = $dadosOwner['token'];

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
        $dadosOwner = $this->cadastrarUsuarioEObterDados();
        $token = $dadosOwner['token'];

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

    public function test_member_nao_pode_criar_ou_gerenciar_assinatura(): void
    {
        $dadosOwner = $this->cadastrarUsuarioEObterDados();
        $membro = $this->criarUsuarioNaEmpresa($dadosOwner['empresa_id'], Usuario::PAPEL_MEMBER);
        $tokenMembro = $this->entrarEObterToken($membro->email);

        $this->postJson('/api/v1/assinaturas', [
            'codigo_plano' => 'pro',
        ], [
            'Authorization' => "Bearer {$tokenMembro}",
        ])
            ->assertForbidden()
            ->assertJsonPath('mensagem', 'Seu perfil nao tem permissao para esta operacao.');
    }

    public function test_member_nao_pode_atualizar_empresa(): void
    {
        $dadosOwner = $this->cadastrarUsuarioEObterDados();
        $membro = $this->criarUsuarioNaEmpresa($dadosOwner['empresa_id'], Usuario::PAPEL_MEMBER);
        $tokenMembro = $this->entrarEObterToken($membro->email);

        $this->putJson('/api/v1/empresa', [
            'nome' => 'Nova Razao Social',
        ], [
            'Authorization' => "Bearer {$tokenMembro}",
        ])
            ->assertForbidden()
            ->assertJsonPath('mensagem', 'Seu perfil nao tem permissao para esta operacao.');
    }

    public function test_admin_pode_atualizar_e_remover_projeto_da_empresa(): void
    {
        $dadosOwner = $this->cadastrarUsuarioEObterDados();
        $tokenOwner = $dadosOwner['token'];

        $respostaProjeto = $this->postJson('/api/v1/projetos', [
            'nome' => 'Projeto inicial',
        ], [
            'Authorization' => "Bearer {$tokenOwner}",
        ]);

        $respostaProjeto
            ->assertCreated()
            ->assertJsonPath('dados.projeto.nome', 'Projeto inicial');

        $projetoId = $respostaProjeto->json('dados.projeto.id');

        $admin = $this->criarUsuarioNaEmpresa($dadosOwner['empresa_id'], Usuario::PAPEL_ADMIN);
        $tokenAdmin = $this->entrarEObterToken($admin->email);

        $this->putJson("/api/v1/projetos/{$projetoId}", [
            'nome' => 'Projeto atualizado por admin',
            'status' => 'ativo',
        ], [
            'Authorization' => "Bearer {$tokenAdmin}",
        ])
            ->assertOk()
            ->assertJsonPath('dados.projeto.nome', 'Projeto atualizado por admin')
            ->assertJsonPath('dados.projeto.status', 'ativo');

        $this->deleteJson("/api/v1/projetos/{$projetoId}", [], [
            'Authorization' => "Bearer {$tokenAdmin}",
        ])->assertNoContent();
    }

    /**
     * @return array{token: string, empresa_id: int}
     */
    private function cadastrarUsuarioEObterDados(): array
    {
        $email = sprintf('owner-%s@example.com', uniqid());

        $resposta = $this->postJson('/api/v1/autenticacao/cadastrar', [
            'nome' => 'Maria Oliveira',
            'email' => $email,
            'senha' => 'segredo123',
            'nome_empresa' => 'Empresa Teste',
        ]);

        $resposta->assertCreated();

        return [
            'token' => $resposta->json('meta.token'),
            'empresa_id' => (int) $resposta->json('dados.usuario.empresa_id'),
        ];
    }

    private function criarUsuarioNaEmpresa(int $empresaId, string $papel): Usuario
    {
        return Usuario::query()->create([
            'nome' => sprintf('Usuario %s', $papel),
            'email' => sprintf('%s-%s@example.com', $papel, uniqid()),
            'senha' => Hash::make('segredo123'),
            'empresa_id' => $empresaId,
            'papel' => $papel,
        ]);
    }

    private function entrarEObterToken(string $email): string
    {
        $respostaLogin = $this->postJson('/api/v1/autenticacao/entrar', [
            'email' => $email,
            'senha' => 'segredo123',
            'nome_dispositivo' => 'suite-rbac',
        ]);

        $respostaLogin->assertOk();

        return $respostaLogin->json('meta.token');
    }
}
