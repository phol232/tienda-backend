<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfiguracionAlertasTable extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_alertas', function (Blueprint $table) {
            $table->string('config_id', 10)->primary();
            $table->string('config_nombre', 50)->unique();
            $table->string('config_tipo', 20);
            $table->integer('config_umbral')->nullable();
            $table->decimal('config_porcentaje', 5, 2)->nullable();
            $table->integer('config_periodo')->nullable();
            $table->string('config_estado', 20)->default('Activa');
            $table->text('config_destinatarios')->nullable();
            $table->dateTime('config_ultima_revision')->nullable();
            $table->string('usr_id', 12);

            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_alertas');
    }
}
