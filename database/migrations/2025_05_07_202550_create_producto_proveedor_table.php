<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductoProveedorTable extends Migration
{
    public function up(): void
    {
        Schema::create('producto_proveedor', function (Blueprint $table) {
            $table->string('prod_prov_id', 15)->primary();
            $table->string('prod_id', 12);
            $table->string('prov_id', 12);
            $table->decimal('precio_proveedor', 10, 2)->nullable();
            $table->date('fecha_inicio_suministro')->nullable();
            $table->date('fecha_fin_suministro')->nullable();
            $table->text('notas')->nullable();
            $table->string('estado', 20)->default('Activo');

            $table->foreign('prod_id')
                ->references('pro_id')->on('productos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prov_id')
                ->references('prov_id')->on('proveedores')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->unique(['prod_id','prov_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_proveedor');
    }
}
