<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacturasTable extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->string('factura_id', 15)->primary();
            $table->string('factura_numero', 20)->unique();
            $table->timestamp('factura_fecha')->useCurrent();
            $table->decimal('factura_subtotal', 10, 2);
            $table->decimal('factura_impuestos', 10, 2)->default(0);
            $table->decimal('factura_descuento', 10, 2)->default(0);
            $table->decimal('factura_total', 10, 2);
            $table->string('factura_estado', 20)->default('Pendiente');
            $table->text('factura_notas')->nullable();
            $table->string('ped_id', 15);

            $table->foreign('ped_id')
                ->references('ped_id')->on('pedidos')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
}
