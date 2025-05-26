<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlertasStockTable extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->string('alerta_id', 15)->primary();
            $table->string('alerta_tipo', 20);
            $table->string('alerta_nivel', 20);
            $table->text('alerta_mensaje');
            $table->timestamp('alerta_fecha')->useCurrent();
            $table->string('alerta_estado', 20)->default('Activa');
            $table->timestamp('alerta_fecha_resolucion')->nullable();
            $table->text('alerta_comentario')->nullable();
            $table->string('prod_id', 12);
            $table->string('usr_id_creador', 12);
            $table->string('usr_id_resolucion', 12)->nullable();

            $table->foreign('prod_id')
                ->references('pro_id')->on('productos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('usr_id_creador')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('usr_id_resolucion')
                ->references('usr_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_stock');
    }
}
