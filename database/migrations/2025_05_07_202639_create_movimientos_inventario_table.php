<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovimientosInventarioTable extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->string('mov_id', 15)->primary();
            $table->string('tipmov_id', 10);
            $table->integer('mov_cantidad');
            $table->timestamp('mov_fecha')->useCurrent();
            $table->string('mov_referencia', 50)->nullable();
            $table->text('mov_notas')->nullable();
            $table->decimal('mov_costo_unitario', 10, 2)->nullable();
            $table->integer('mov_saldo_inicial')->nullable();
            $table->integer('mov_saldo_final')->nullable();
            $table->string('prod_id', 12);
            $table->string('usr_id', 12);
            $table->string('prov_id', 12)->nullable();

            $table->foreign('tipmov_id')
                ->references('tipmov_id')->on('tipos_movimiento')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('prod_id')
                ->references('pro_id')->on('productos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prov_id')
                ->references('prov_id')->on('proveedores')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
}
