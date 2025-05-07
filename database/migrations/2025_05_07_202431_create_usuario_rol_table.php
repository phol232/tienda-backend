<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioRolTable extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->string('usr_rol_id', 15)->primary();
            $table->string('usr_id', 12);
            $table->string('rol_id', 10);
            $table->dateTime('fecha_asignacion')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('asignado_por', 12)->nullable();

            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('rol_id')
                ->references('rol_id')->on('roles')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->unique(['usr_id','rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
}
