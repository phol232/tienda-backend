<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClienteCategoriaTable extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_categoria', function (Blueprint $table) {
            $table->string('cli_cat_asoc_id', 15)->primary();
            $table->string('cli_id', 12);
            $table->string('cli_cat_id', 12);
            $table->dateTime('fecha_asociacion')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->foreign('cli_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('cli_cat_id')
                ->references('cli_cat_id')->on('categorias_clientes')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->unique(['cli_id','cli_cat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_categoria');
    }
}
