<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidosDetallesTable extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_detalles', function (Blueprint $table) {
            $table->string('det_id', 18)->primary();
            $table->integer('det_cantidad');
            $table->decimal('det_precio_unitario', 10, 2);
            $table->decimal('det_subtotal', 10, 2);
            $table->decimal('det_impuesto', 10, 2)->default(0);
            $table->decimal('det_descuento', 10, 2)->default(0);
            $table->text('det_nota')->nullable();
            $table->string('ped_id', 15);
            $table->string('prod_id', 12);

            $table->foreign('ped_id')
                ->references('ped_id')->on('pedidos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prod_id')
                ->references('pro_id')->on('productos')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_detalles');
    }
}
