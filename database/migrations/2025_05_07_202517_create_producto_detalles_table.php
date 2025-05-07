<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductoDetallesTable extends Migration
{
    public function up(): void
    {
        Schema::create('producto_detalles', function (Blueprint $table) {
            $table->string('prod_id', 12)->primary();
            $table->text('prod_descripcion')->nullable();
            $table->decimal('prod_precio_compra', 10, 2)->nullable();
            $table->integer('prod_stock_minimo')->default(5);
            $table->integer('prod_stock_maximo')->nullable();
            $table->string('prod_imagen', 255)->nullable();
            $table->date('prod_fecha_caducidad')->nullable();
            $table->timestamp('prod_fecha_creacion')->useCurrent();
            $table->string('prod_creado_por', 12)->nullable();
            $table->dateTime('prod_actualizado_en')->nullable();
            $table->string('prod_actualizado_por', 12)->nullable();

            $table->foreign('prod_id')
                ->references('pro_id')->on('productos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prod_creado_por')
                ->references('usr_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('prod_actualizado_por')
                ->references('usr_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_detalles');
    }
}
