<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposMovimientoTable extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_movimiento', function (Blueprint $table) {
            $table->string('tipmov_id', 10)->primary();
            $table->string('tipmov_nombre', 50);
            $table->text('tipmov_descripcion')->nullable();
            $table->string('tipmov_estado', 20)->default('Activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_movimiento');
    }
}
