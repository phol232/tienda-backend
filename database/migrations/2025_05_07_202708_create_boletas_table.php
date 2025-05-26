<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoletasTable extends Migration
{
    public function up(): void
    {
        Schema::create('boletas', function (Blueprint $table) {
            $table->string('boleta_id', 15)->primary();
            $table->string('boleta_numero', 20)->unique();
            $table->timestamp('boleta_fecha')->useCurrent();
            $table->decimal('boleta_subtotal', 10, 2);
            $table->decimal('boleta_impuestos', 10, 2)->default(0);
            $table->decimal('boleta_descuento', 10, 2)->default(0);
            $table->decimal('boleta_total', 10, 2);
            $table->string('boleta_estado', 20)->default('Pendiente');
            $table->text('boleta_notas')->nullable();
            $table->string('ped_id', 15);

            $table->foreign('ped_id')
                ->references('ped_id')->on('pedidos')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletas');
    }
}
