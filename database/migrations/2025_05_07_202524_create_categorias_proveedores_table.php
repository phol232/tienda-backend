<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriasProveedoresTable extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_proveedores', function (Blueprint $table) {
            $table->string('prov_cat_id', 12)->primary();
            $table->string('prov_cat_nombre', 50);
            $table->text('prov_cat_descripcion')->nullable();
            $table->string('prov_cat_estado', 20)->default('Activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_proveedores');
    }
}
