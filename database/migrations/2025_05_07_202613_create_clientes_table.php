<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->string('cli_id', 12)->primary();
            $table->string('cli_nombre', 50);
            $table->string('cli_apellido', 50);
            $table->string('cli_email', 100)->nullable();
            $table->string('cli_telefono', 20)->nullable();
            $table->text('cli_direccion')->nullable();
            $table->string('cli_genero', 10)->nullable();
            $table->date('cli_fecha_nacimiento')->nullable();
            $table->string('cli_tipo', 20)->default('Regular');
            $table->string('cli_estado', 20)->default('Activo');
            $table->timestamp('cli_fecha_registro')->useCurrent();
            $table->string('cli_rfc', 20)->nullable();
            $table->text('cli_notas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
}
