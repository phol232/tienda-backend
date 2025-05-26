<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosTable extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->string('pag_id', 15)->primary();
            $table->decimal('pag_monto', 10, 2);
            $table->timestamp('pag_fecha')->useCurrent();
            $table->string('pag_referencia', 100)->nullable();
            $table->text('pag_notas')->nullable();
            $table->string('pag_estado', 20)->default('Completado');
            $table->string('boleta_id', 15)->nullable();
            $table->string('factura_id', 15)->nullable();
            $table->string('met_id', 10);
            $table->string('usr_id', 12);

            $table->foreign('boleta_id')
                ->references('boleta_id')->on('boletas')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('factura_id')
                ->references('factura_id')->on('facturas')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('met_id')
                ->references('met_id')->on('metodos_pago')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->check('boleta_id IS NOT NULL OR factura_id IS NOT NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
}
