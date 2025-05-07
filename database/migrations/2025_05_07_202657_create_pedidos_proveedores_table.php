<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidosProveedoresTable extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_proveedores', function (Blueprint $table) {
            $table->string('pedprov_id', 15)->primary();
            $table->timestamp('pedprov_fecha')->useCurrent();
            $table->decimal('pedprov_total', 10, 2);
            $table->decimal('pedprov_subtotal', 10, 2);
            $table->decimal('pedprov_impuestos', 10, 2)->default(0);
            $table->decimal('pedprov_descuento', 10, 2)->default(0);
            $table->string('pedprov_estado', 20)->default('Pendiente');
            $table->text('pedprov_notas')->nullable();
            $table->string('prov_id', 12);
            $table->string('usr_id', 12);

            $table->foreign('prov_id')
                ->references('prov_id')->on('proveedores')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_proveedores');
    }
}
