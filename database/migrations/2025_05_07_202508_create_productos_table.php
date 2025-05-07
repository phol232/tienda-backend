<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductosTable extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->string('pro_id', 12)->primary();
            $table->string('pro_nombre', 100);
            $table->decimal('pro_precio_venta', 10, 2);
            $table->integer('pro_stock')->default(0);
            $table->string('pro_estado', 20)->default('Activo');
            $table->string('cat_id', 12);

            $table->foreign('cat_id')
                ->references('cat_id')->on('categorias')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
}
