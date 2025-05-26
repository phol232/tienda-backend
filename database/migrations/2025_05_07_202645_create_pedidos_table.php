<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidosTable extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->string('ped_id', 15)->primary();
            $table->timestamp('ped_fecha')->useCurrent();
            $table->decimal('ped_total', 10, 2);
            $table->decimal('ped_subtotal', 10, 2);
            $table->decimal('ped_impuestos', 10, 2)->default(0);
            $table->decimal('ped_descuento', 10, 2)->default(0);
            $table->string('ped_estado', 20)->default('Pendiente');
            $table->text('ped_notas')->nullable();
            $table->string('ped_tipo', 20)->default('Venta');
            $table->string('ped_forma_entrega', 30)->nullable();
            $table->string('cli_id', 12)->nullable();
            $table->string('usr_id', 12);

            $table->foreign('cli_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('usr_id')
                ->references('usr_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
}
