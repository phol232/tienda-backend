<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificacionesAlertasTable extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_alertas', function (Blueprint $table) {
            $table->string('notif_id', 18)->primary();
            $table->timestamp('notif_fecha_envio')->useCurrent();
            $table->string('notif_canal', 20);
            $table->string('notif_estado', 20)->default('Enviada');
            $table->timestamp('notif_fecha_lectura')->nullable();
            $table->text('notif_mensaje')->nullable();
            $table->string('alerta_id', 15);
            $table->string('usr_id', 12);

            $table->foreign('alerta_id')
                ->references('alerta_id')->on('alertas_stock')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_alertas');
    }
}
