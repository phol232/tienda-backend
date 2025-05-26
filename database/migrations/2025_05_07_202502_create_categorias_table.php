<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriasTable extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->string('cat_id', 12)->primary();
            $table->string('cat_nombre', 50);
            $table->text('cat_descripcion')->nullable();
            $table->string('cat_imagen', 255)->nullable();
            $table->string('cat_color', 50)->nullable();
            $table->string('cat_estado', 20)->default('Activo');
            $table->integer('cat_orden')->default(0);
            $table->dateTime('cat_creado_en')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('cat_actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
}
