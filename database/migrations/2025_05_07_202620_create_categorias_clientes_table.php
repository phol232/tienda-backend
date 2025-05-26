<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriasClientesTable extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_clientes', function (Blueprint $table) {
            $table->string('cli_cat_id', 12)->primary();
            $table->string('cli_cat_nombre', 50);
            $table->text('cli_cat_descripcion')->nullable();
            $table->string('cli_cat_estado', 20)->default('Activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_clientes');
    }
}
