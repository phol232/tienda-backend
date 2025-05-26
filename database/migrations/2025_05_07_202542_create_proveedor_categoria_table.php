<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProveedorCategoriaTable extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_categoria', function (Blueprint $table) {
            $table->string('prov_cat_map_id', 15)->primary();
            $table->string('prov_id', 12);
            $table->string('prov_cat_id', 12);
            $table->dateTime('fecha_asociacion')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->foreign('prov_id')
                ->references('prov_id')->on('proveedores')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prov_cat_id')
                ->references('prov_cat_id')->on('categorias_proveedores')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->unique(['prov_id','prov_cat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_categoria');
    }
}
