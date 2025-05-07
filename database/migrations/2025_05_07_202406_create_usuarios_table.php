<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosTable extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('usr_id', 12)->primary();
            $table->string('usr_email', 100)->unique();
            $table->string('usr_user', 30)->unique();
            $table->string('usr_password', 255);
            $table->string('usr_estado', 20)->default('Activo');
            $table->timestamp('usr_fecha_registro')->useCurrent();
            $table->timestamp('usr_ultimo_login')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
}
