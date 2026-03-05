<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->foreignId('criado_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('status', 20)->default('ativa');
            $table->timestamp('inicia_em');
            $table->timestamp('termina_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status', 'termina_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assinaturas');
    }
};
