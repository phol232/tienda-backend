<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidosProveedoresDetallesTable extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_proveedores_detalles', function (Blueprint $table) {
            $table->string('ppdet_id', 15)->primary();
            $table->string('pedprov_id', 15);
            $table->string('pro_id', 12);
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->text('notas')->nullable();

            $table->foreign('pedprov_id')
                ->references('pedprov_id')->on('pedidos_proveedores')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('pro_id')
                ->references('pro_id')->on('productos')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_proveedores_detalles');
    }
}
