<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosPerfilTable extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_perfil', function (Blueprint $table) {
            $table->string('usrp_id', 12)->primary();
            $table->string('usrp_nombre', 50);
            $table->string('usrp_apellido', 50);
            $table->string('usrp_telefono', 20)->nullable();
            $table->text('usrp_direccion')->nullable();
            $table->string('usrp_genero', 10)->nullable();
            $table->date('usrp_fecha_nacimiento')->nullable();
            $table->string('usrp_imagen', 255)->nullable();

            $table->foreign('usrp_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_perfil');
    }
}
