<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->string('rol_id', 10)->primary();
            $table->string('rol_nombre', 50);
            $table->text('rol_descripcion')->nullable();
            $table->integer('rol_nivel')->default(1);
            $table->dateTime('rol_creado_en')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('rol_actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
}
