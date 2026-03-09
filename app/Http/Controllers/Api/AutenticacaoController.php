<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AutenticacaoController extends Controller
{
    public function cadastrar(Request $request): JsonResponse
    {
        $dadosValidados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:8'],
            'nome_empresa' => ['required', 'string', 'max:255'],
        ]);

        $usuario = DB::transaction(function () use ($dadosValidados): Usuario {
            $empresa = Empresa::query()->create([
                'nome' => $dadosValidados['nome_empresa'],
                'slug' => $this->resolverSlugEmpresaUnico($dadosValidados['nome_empresa']),
            ]);

            return Usuario::query()->create([
                'nome' => $dadosValidados['nome'],
                'email' => $dadosValidados['email'],
                'senha' => Hash::make($dadosValidados['senha']),
                'empresa_id' => $empresa->id,
                'papel' => Usuario::PAPEL_OWNER,
            ]);
        });

        $token = $this->emitirToken($usuario, 'cadastro');

        return response()->json([
            'mensagem' => 'Usuario cadastrado com sucesso.',
            'dados' => [
                'usuario' => $usuario->load('empresa'),
            ],
            'meta' => [
                'token' => $token,
                'tipo_token' => 'Bearer',
            ],
        ], 201);
    }

    public function entrar(Request $request): JsonResponse
    {
        $dadosValidados = $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'nome_dispositivo' => ['nullable', 'string', 'max:100'],
        ]);

        $usuario = Usuario::query()->where('email', $dadosValidados['email'])->first();

        if ($usuario === null || ! Hash::check($dadosValidados['senha'], $usuario->senha)) {
            return response()->json([
                'mensagem' => 'Credenciais invalidas.',
            ], 422);
        }

        $token = $this->emitirToken($usuario, $dadosValidados['nome_dispositivo'] ?? 'login');

        return response()->json([
            'mensagem' => 'Login realizado com sucesso.',
            'dados' => [
                'usuario' => $usuario->load('empresa'),
            ],
            'meta' => [
                'token' => $token,
                'tipo_token' => 'Bearer',
            ],
        ]);
    }

    public function eu(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json([
            'dados' => [
                'usuario' => $usuario->load('empresa'),
            ],
        ]);
    }

    public function sair(Request $request): JsonResponse
    {
        $tokenApi = $request->attributes->get('token_api');

        if ($tokenApi !== null) {
            $tokenApi->delete();
        }

        return response()->json([], 204);
    }

    private function emitirToken(Usuario $usuario, string $nome): string
    {
        $tokenTextoPuro = Str::random(64);

        $token = $usuario->tokensApi()->create([
            'nome' => $nome,
            'hash_token' => hash('sha256', $tokenTextoPuro),
            'expira_em' => now()->addDays(30),
        ]);

        return $token->id.'|'.$tokenTextoPuro;
    }

    private function resolverSlugEmpresaUnico(string $nome): string
    {
        $slugBase = Str::slug($nome);
        $slugBase = $slugBase === '' ? 'empresa' : $slugBase;

        $slug = $slugBase;
        $contador = 1;

        while (Empresa::query()->where('slug', $slug)->exists()) {
            $slug = sprintf('%s-%d', $slugBase, $contador);
            $contador++;
        }

        return $slug;
    }
}
