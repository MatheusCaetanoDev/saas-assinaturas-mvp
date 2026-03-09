<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->string('papel', 20)->default('member')->after('empresa_id');
        });

        $idsEmpresas = DB::table('usuarios')
            ->whereNotNull('empresa_id')
            ->distinct()
            ->pluck('empresa_id');

        foreach ($idsEmpresas as $empresaId) {
            $idPrimeiroUsuario = DB::table('usuarios')
                ->where('empresa_id', $empresaId)
                ->min('id');

            if ($idPrimeiroUsuario !== null) {
                DB::table('usuarios')
                    ->where('id', $idPrimeiroUsuario)
                    ->update(['papel' => 'owner']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropColumn('papel');
        });
    }
};
