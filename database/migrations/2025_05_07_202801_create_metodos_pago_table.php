<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetodosPagoTable extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->string('met_id', 10)->primary();
            $table->string('met_nombre', 50)->unique();
            $table->text('met_descripcion')->nullable();
            $table->string('met_estado', 20)->default('Activo');
            $table->string('met_tipo', 20)->nullable();
            $table->string('met_banco', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
}
